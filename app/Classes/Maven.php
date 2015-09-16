<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/14/15
 * Time: 14:13
 */

namespace app\Classes;

use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseQuery;
use Parse\ParseCloud;
use Parse\ParseACL;
use Parse\ParseException;
use Illuminate\Http\Request;
use App\Classes\Telehook;
use App\Classes\TextCommand;
use App\Classes\MobileAddress;

define('SECRET', env('PARSE_OTP_PREFIX'));

abstract class Maven
{
    const RANDOM_FLOOR = 1000;

    const RANDOM_CEILING = 9999;

    const PARSE_USERNAME = 'username';

    const PARSE_USE_MASTERKEY = true;

    private $command;

    protected $description;

    protected $defaultReply;

    protected $defaultNextState;

    protected $addtoGroups;

    protected function __construct(TextCommand $command)
    {
        $this->command = $command;
        Telehook::getInstance()
            ->setReply($this->getDefaultReply())
            ->setState($this->getDefaultNextState());
    }

    public static function getInstance(Request $request)
    {
        $command = new TextCommand($request);
        switch ($command->getKeyword()) {
            case 'autorecruit':
                return new AutoRecruit($command);
            case 'recruit':
                return new Recruit($command);
            case 'verify':
                return new Verify($command);
        }
    }

    abstract function getResponse();

    protected function getCommand()
    {
        return $this->command;
    }

    protected function updateParseUserWithRandomCode(ParseUser $user)
    {
        $randomCode = $this->getRandomCode();
        try {
            $user->set('password', SECRET . $randomCode);
            $user->save(self::PARSE_USE_MASTERKEY);
        } catch (ParseException $ex) {
            Telehook::getInstance()
                ->setReply("Something is wrong! Error code " . $ex->getMessage());
        }

        return $randomCode;
    }

    protected function getRandomCode()
    {
        list($usec, $sec) = explode(' ', microtime());
        $seed = (float)$sec + ((float)$usec * 100000);
        mt_srand($seed);
        return mt_rand(self::RANDOM_FLOOR, self::RANDOM_CEILING);
    }

    protected function signupParseUserWithRandomCode($mobile)
    {
        $randomCode = $this->getRandomCode();
        $user = new ParseUser();
        $user->setUsername($mobile);
        $user->setPassword(SECRET . $randomCode);
        $user->setACL(new ParseACL());
        try {
            $user->signUp(self::PARSE_USE_MASTERKEY);
        } catch (ParseException $ex) {
            Telehook::getInstance()
                ->setReply("Something is wrong! Error code " . $ex->getMessage());
        }

        return $randomCode;
    }

    protected function getSessionToken($somenumber, $allegedOTP)
    {
        //$user = ParseUser::logIn($mobile, SECRET . $extracted_allegedotp);  //use PARSE_USE_MASTERKEY
        $mobile = MobileAddress::getInstance($somenumber)->getServiceNumber();

        return ParseCloud::run(
            'logIn',
            array(
                'codeEntry' => $allegedOTP,
                'phoneNumber' => $mobile
            )
        );
    }

    protected function getDescription()
    {
        return $this->description;
    }

    protected function getDefaultReply()
    {
        if (array_get($this->getCommand()->getParameters(), 'help'))
            return $this->getDescription();
        else
            return $this->defaultReply;
    }

    protected function getDefaultNextState()
    {
        if (array_get($this->getCommand()->getParameters(), 'help'))
            return Telehook::$state;
        else
            return $this->defaultNextState;
    }

    protected function getAddtoGroups()
    {
        if (array_get($this->getCommand()->getParameters(), 'help'))
            return '';
        else
            return $this->addtoGroups;
    }
}

class AutoRecruit extends Maven
{

    protected $description = "Help";

    protected $defaultReply = "You are now in auto-recruit mode. Please enter mobile number of your recruit:";

    protected $defaultNextState = "recruit";

    protected $addtoGroups = "recruiter";

    public function getResponse()
    {
        return Telehook::getInstance()
            ->addtoGroups($this->getAddtoGroups())
            ->getResponse();
    }
}

class Recruit extends Maven
{
    public function getResponse()
    {
        $somenumber = null;

        extract($this->getCommand()->getParameters());

        if (!$somenumber) {
            return Telehook::getInstance()->getDebugResponse("Error! No somenumber parameter in http.");
        }

        $mobile = MobileAddress::getInstance($somenumber)->getServiceNumber();

        if (!$mobile) {
            $msg = is_int($somenumber)
                ? "somenumber is not a valid mobile number. "
                : "You are now in recruiting mode. Please enter mobile number of your recruit:";
            Telehook::getInstance()
                ->setReply($msg)
                ->setState("recruit");
        }

        $randomCode =
            ($user = ParseUser::query()->equalTo(Maven::PARSE_USERNAME, $mobile)->first(Maven::PARSE_USE_MASTERKEY))
                ? $this->updateParseUserWithRandomCode($user)
                : $this->signupParseUserWithRandomCode($mobile);

        Telehook::getInstance()
            ->setReply("The OTP was already sent to $mobile.")
            ->setForward($mobile, "Your OTP is $randomCode")
            ->setState("verify")
            ->addVariable("contact.vars.recruit|$mobile:pending,recruit");

        return Telehook::getInstance()
            ->addtoGroups("recruiter")
            ->getResponse();
    }
}

class Verify extends Maven
{
    public function getResponse()
    {
        $somenumber = null;
        $allegedotp = null;

        extract($this->getCommand()->getParameters());

        if (!$allegedotp) {
            return Telehook::getInstance()->getDebugResponse("Error! No allegedotp parameter in http.");
        }

        if (!$somenumber) {
            return Telehook::getInstance()->getDebugResponse("Error! No somenumber parameter in http.");
        }

        $mobile = MobileAddress::getInstance($somenumber)->getServiceNumber();

        if (!$mobile) {
            return Telehook::getInstance()->getDebugResponse("Error! Mobile number is not valid.");
        }

        try {
            $sessionToken = $this->getSessionToken($mobile, $allegedotp);
            $user = ParseUser::become($sessionToken);
            $user->set('phone', $mobile);
            $user->setPassword(SECRET . $this->getRandomCode());
            $user->save();
            Telehook::getInstance()
                ->setReply("OTP is valid.")
                ->setForward($mobile, "Your OTP is valid. Congratulations!")
                ->setVariable("state.id|recruit")
                ->addVariable("contact.vars.recruit|");
        } catch (ParseException $ex) {
            Telehook::getInstance()
                ->setReply("OTP is not valid! Please try again.")
                ->setVariable("state.id|verify")
                ->addVariable("contact.vars.recruit|$mobile");
        }

        return Telehook::getInstance()->getResponse();

    }
}