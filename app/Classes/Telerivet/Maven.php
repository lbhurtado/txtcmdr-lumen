<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/14/15
 * Time: 14:13
 */

namespace App\Classes\Telerivet;

use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseQuery;
use Parse\ParseCloud;
use Parse\ParseACL;
use Parse\ParseException;
use Illuminate\Http\Request;
use App\Classes\Telerivet\Webhook;
use App\Classes\Telerivet\TextCommand;
use App\Classes\MobileAddress;
use \Exception;
use App\Classes\MobileAddressException;
use App\Classes\Parse\Anyphone;
use App\Classes\Random;

//define('SECRET', env('PARSE_OTP_PREFIX'));

abstract class Maven
{
    const RANDOM_FLOOR = 1000;

    const RANDOM_CEILING = 9999;

    const PARSE_USERNAME = 'username';

    const PARSE_USE_MASTERKEY = true;

    private $_data;

    private $command;

    protected $description;

    protected $defaultReply;

    protected $defaultNextState;

    protected $addtoGroups;

    protected function __construct(TextCommand $command)
    {
        $this->command = $command;

        $this->_data = $this->getCommand()->getParameters();

        Webhook::getInstance()
            ->setReply($this->getDefaultReply())
            ->setState($this->getDefaultNextState());
    }

    public function __set($property, $value)
    {
        return $this->_data[$property] = $value;
    }

    public function __get($property)
    {
        return array_key_exists($property, $this->_data)
            ? $this->_data[$property]
            : null;
    }

    public static function getInstance(Request $request)
    {
        try {
            $command = new TextCommand($request);
            switch ($command->getKeyword()) {
                case 'autorecruit':
                    return new AutoRecruit($command);
                case 'recruit':
                    return new Recruit($command);
                case 'verify':
                    return new Verify($command);
                default:
                    return new Nuisance($command);
            }
        } catch (WebhookException $ex) {
            return new Unauthorized();
        } catch (TextCommandException $ex) {
            return new Nuisance();
        }

    }

    abstract function getResponse();

    protected function getCommand()
    {
        return $this->command;
    }

    /*
    protected function updateParseUserWithRandomCode(ParseUser $user)
    {
        //$randomCode = $this->getRandomCode();
        $randomCode = Random::num(1000, 9999);
        try {
            $user->set('password', SECRET . $randomCode);
            $user->save(self::PARSE_USE_MASTERKEY);
        } catch (ParseException $ex) {
            Webhook::getInstance()
                ->setReply("Something is wrong! Error code " . $ex->getMessage());
        }

        return $randomCode;
    }
    */

    /*
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
            Webhook::getInstance()
                ->setReply("Something is wrong! Error code " . $ex->getMessage());
        }

        return $randomCode;
    }
    */

    /*
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
    */

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
            return Webhook::$state;
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


class Unauthorized extends Maven
{
    protected function __construct()
    {
    }

    public function getResponse()
    {
        return Webhook::getInstance()
            ->setHandled(true)
            ->getResponse();
    }
}

class Nuisance extends Maven
{
    protected function __construct()
    {
    }

    public function getResponse()
    {
        return Webhook::getInstance()
            ->setReply("Invalid command!")
            ->setHandled(true)
            ->getResponse();
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
        return Webhook::getInstance()
            ->addtoGroups($this->getAddtoGroups())
            ->getResponse();
    }
}

class Recruit extends Maven
{
    public function getResponse()
    {
        try {
            if ($somenumber = $this->somenumber) {
                $randomCode =
                    (!Anyphone::getInstance()->setUser($somenumber))
                        ? Anyphone::getInstance()->signupParseUserWithRandomCode()
                        : Anyphone::getInstance()->updateParseUserWithRandomCode();

                $mobile = Anyphone::getInstance()->getMobile();

                Webhook::getInstance()
                    ->setReply("The OTP was already sent to $mobile.")
                    ->setForward($mobile, "Your OTP is $randomCode")
                    ->setState("verify")
                    ->addVariable("contact.vars.recruit|$mobile")
                    ->addMobileToGroups($mobile, "pending");
            }
            else
                throw new \Exception();
        } catch (MobileAddressException $ex) {
            Webhook::getInstance()
                ->setReply("Mobile phone is not valid! Please try again.")
                ->setState(Webhook::RECRUITING)
                ->addVariable("contact.vars.recruit|");
        } catch (Exception $ex) {
            return Webhook::getInstance()->getDebugResponse("Error! No somenumber parameter in http.");
        }

        return Webhook::getInstance()
            ->addtoGroups("recruiter")
            ->getResponse();
    }
}

class Verify extends Maven
{
    public function getResponse()
    {
        $somenumber = $this->somenumber; //from magic method __set
        $allegedotp = $this->allegedotp; //from magic method __set

        if (!$allegedotp) {
            return Webhook::getInstance()->getDebugResponse("Error! No allegedotp parameter in http.");
        }

        if (!$somenumber) {
            return Webhook::getInstance()->getDebugResponse("Error! No somenumber parameter in http.");
        }

        try {
            if (Anyphone::getInstance()->validateUser($somenumber, $allegedotp)) {
                Anyphone::getInstance()->scrambleParseUserPassword();
                $mobile = Anyphone::getInstance()->getMobile();
                Webhook::getInstance()
                    ->setReply("OTP is valid.")
                    ->setForward($mobile, "Your OTP is valid. Congratulations!")
                    ->setState(Webhook::RECRUITING)
                    ->addVariable("contact.vars.recruit|")
                    ->removeMobileFromGroups($mobile, 'pending')
                    ->addMobileToGroups($mobile, "recruit");
            } else {
                throw new ParseException();
            }

        } catch (MobileAddressException $ex) {
            return Webhook::getInstance()->getDebugResponse("Error! Mobile number is not valid.");
        } catch (ParseException $ex) {
            Webhook::getInstance()
                ->setReply("OTP is not valid! Please try again.")
                ->setState(Webhook::VERIFYING)
                ->addVariable("contact.vars.recruit|$mobile");
        }

        return Webhook::getInstance()->getResponse();

    }
}