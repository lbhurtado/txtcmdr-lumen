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

    protected static $request;

    protected function __construct(TextCommand $command)
    {
        $this->command = $command;
    }

    public static function getInstance(Request $request)
    {
        static::$request = $request;
        $command = new TextCommand(static::$request);
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
        $mobile = MobileAddress::getInstance($somenumber)->getServiceNumber();

        return ParseCloud::run(
            'logIn',
            array(
                'codeEntry' => $allegedOTP,
                'phoneNumber' => $mobile
            )
        );
    }
}

class AutoRecruit extends Maven
{
    public function getResponse()
    {
        return Telehook::getInstance()
            ->setReply('You are now in recruiting mode. Please enter mobile number of your recruit:')
            ->setVariable('state.id|recruit')
            ->getResponse();
    }
}

class Recruit extends Maven
{
    public function getResponse()
    {
        //$somenumber = array_get($this->getCommand()->getParameters(), 'somenumber');
        extract($this->getCommand()->getParameters(), EXTR_PREFIX_ALL, 'hook');
        $mobile = MobileAddress::getInstance($hook_somenumber)->getServiceNumber();
        if ($mobile) {
            $randomCode =
                ($user = ParseUser::query()->equalTo(Maven::PARSE_USERNAME, $mobile)->first(Maven::PARSE_USE_MASTERKEY))
                    ? $this->updateParseUserWithRandomCode($user)
                    : $this->signupParseUserWithRandomCode($mobile);
            Telehook::getInstance()
                ->setReply("The OTP was already sent to $mobile.")
                ->setForward("$mobile|Your OTP is $randomCode")
                ->setVariable("state.id|verify")
                ->addVariable("contact.vars.recruit|$mobile");
        } else {
            $msg = is_int($hook_somenumber)
                ? "somenumber is not a valid mobile number. "
                : "You are now in recruiting mode. Please enter mobile number of your recruit:";
            Telehook::getInstance()
                ->setReply($msg)
                ->setVariable("state.id|recruit");
        }

        return Telehook::getInstance()->getResponse();
    }
}

class Verify extends Maven
{
    public function getResponse()
    {
        Telehook::isAuthorized(static::$request);
        //extract($this->getCommand()->getParameters(), EXTR_PREFIX_ALL, 'extracted');
        $somenumber = array_get($this->getCommand()->getParameters(), 'somenumber');
        $mobile = MobileAddress::getInstance($somenumber)->getServiceNumber();

        //$text = implode(' ', array_keys(Telehook::$inputs['contact']['vars']));

        //$text = implode(' ', $this->getCommand()->getParameters());
        //$text = serialize(parent::$request->input['contact']);
        //$text = implode(' ', static::$request);
        $text = array_get(Telehook::$inputs,'contact.vars.recruit');


        if ($mobile) {
            try {
                //$user = ParseUser::logIn($mobile, SECRET . $extracted_allegedotp);  //use PARSE_USE_MASTERKEY
                $sessionToken = $this->getSessionToken($mobile, $extracted_allegedotp);
                $user = ParseUser::become($sessionToken);
                $user->set('phone', $mobile);
                //$user->setPassword(SECRET . $this->getRandomCode());
                $user->save();
                Telehook::getInstance()
                    ->setReply("OTP is valid.")
                    ->setForward("$mobile|Your OTP is valid. Congratulations!")
                    ->setVariable("state.id|recruit")
                    ->addVariable("contact.vars.recruit|");
            } catch (ParseException $ex) {
                Telehook::getInstance()
                    ->setReply("OTP is not valid! Please try again.")
                    ->setVariable("state.id|verify")
                    ->addVariable("contact.vars.recruit|$mobile");
            } finally {
                return Telehook::getInstance()->getResponse();
            }
        } else

            return Telehook::getInstance()->getDebugResponse($text ?: "no text");
    }
}