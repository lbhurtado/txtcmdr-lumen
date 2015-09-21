<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/18/15
 * Time: 07:55
 */

namespace app\Classes\Parse;

use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseQuery;
use Parse\ParseCloud;
use Parse\ParseACL;
use Parse\ParseException;
use App\Classes\Random;
use App\Classes\MobileAddress;
use App\Classes\CustomException;
use App\Classes\MobileAddressException;

define('SECRET', env('PARSE_OTP_PREFIX'));

class AnyphoneException extends CustomException
{

}

class Anyphone
{
    const RANDOM_FLOOR = 1000;

    const RANDOM_CEILING = 9999;

    const PARSE_USERNAME = 'username';

    const PARSE_USE_MASTERKEY = true;

    private static $_instance = null;

    private $user = null;

    private $mobile;

    public static function getInstance()
    {
        if (static::$_instance === null) {
            static::$_instance = new self();
        }

        return static::$_instance;
    }

    public function setMobile($somenumber) {
        $mobile = MobileAddress::getInstance($somenumber)->getServiceNumber();

        if (!$mobile)
            throw new MobileAddressException();
        $this->mobile = $mobile;

        return $this;
    }

    public function getMobile() {

        return $this->mobile;
    }

    public function setUser($somenumber){
        $this->user = null;
        if ($this->setMobile($somenumber)) {
            $user = ParseUser::query()
                ->equalTo(Anyphone::PARSE_USERNAME, $this->getMobile())
                ->first(Anyphone::PARSE_USE_MASTERKEY);
            if (!$user)
                return false;
            $this->user = $user;
        }

        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function signupParseUserWithRandomCode($testcode = null)
    {
        $randomCode = Random::num(Anyphone::RANDOM_FLOOR, Anyphone::RANDOM_CEILING);

        $this->user = new ParseUser();
        $this->user->setUsername($this->mobile);
        $this->user->setPassword(SECRET . $randomCode);
        $this->user->setACL(new ParseACL());
        try {
            $this->user->signUp(self::PARSE_USE_MASTERKEY);
        } catch (ParseException $ex) {

        }
        if ($testcode)
            return $testcode;

        return $randomCode;
    }

    public function updateParseUserWithRandomCode($testcode = null)
    {
        $randomCode = Random::num(Anyphone::RANDOM_FLOOR, Anyphone::RANDOM_CEILING);

        try {
            $this->getUser()->set('password', SECRET . $randomCode);
            $this->getUser()->save(Anyphone::PARSE_USE_MASTERKEY);
        } catch (ParseException $ex) {
            echo $ex.code();
        }

        if ($testcode)
            return $testcode;

        return $randomCode;
    }

    protected function getSessionToken($somenumber, $allegedOTP)
    {
        //$user = ParseUser::logIn($mobile, SECRET . $extracted_allegedotp);  //use PARSE_USE_MASTERKEY
        $mobile = MobileAddress::getInstance($somenumber)->getServiceNumber();
        if (!$mobile)
            throw new MobileAddressException();
        try {
            return ParseCloud::run(
                'logIn',
                array(
                    'codeEntry' => $allegedOTP,
                    'phoneNumber' => $mobile
                )
            );
        }
        catch (\Exception $ex) {
           return null;
        }
    }

    public function validateUser($somenumber, $allegedOTP)
    {
        $this->user = null;
        if ($this->setMobile($somenumber)) {

            try {
                $sessionToken = $this->getSessionToken($this->getMobile(), $allegedOTP);
                $user = ParseUser::become($sessionToken);
                $this->user = $user;
            } catch (\Exception $ex) {
                return false;
            }
        }

        return $this;
    }

    public function scrambleParseUserPassword() {
        $this->updateParseUserWithRandomCode();

        return $this;
    }
}