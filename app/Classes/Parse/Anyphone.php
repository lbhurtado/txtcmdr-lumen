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

    private static $randomCode = 1991;

    protected function __construct(){
        static::$randomCode = Random::num(Anyphone::RANDOM_FLOOR, Anyphone::RANDOM_CEILING);
    }

    public static function getRandomCode(){
        return static::$randomCode;
    }

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

    public function signupParseUserWithRandomCode()
    {
        $this->user = new ParseUser();
        $this->user->setUsername($this->mobile);
        $this->user->setPassword(SECRET . static::getRandomCode());
        $this->user->setACL(new ParseACL());
        try {
            $this->user->signUp(self::PARSE_USE_MASTERKEY);
        } catch (ParseException $ex) {
            return false;
        }

        return static::getRandomCode();
    }

    public function updateParseUserWithRandomCode()
    {
        try {
            $this->getUser()->set('password', SECRET . static::getRandomCode());
            $this->getUser()->save(Anyphone::PARSE_USE_MASTERKEY);
        } catch (ParseException $ex) {
            return false;
        }

        return static::getRandomCode();
    }

    /**
     * @param $somenumber
     * @param $allegedOTP
     * @return mixed|null
     * @throws MobileAddressException
     *
     * Validate the Parse user using the phone number and sms OTP.
     * Refactored as a separate function to accomodate different
     * authentication methods in the future.
     */
    protected function getSessionToken($somenumber, $allegedOTP)
    {
        /*
         * Alternative validation of user
         * $user = ParseUser::logIn($somenumber, SECRET . $allegedOTP);
         */
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
                $this->user = ParseUser::become($sessionToken);
            } catch (\Exception $ex) {
                return false;
            }
        }

        return $this;
    }

    public function scrambleParseUserPassword() {
        static::$randomCode = Random::num(Anyphone::RANDOM_FLOOR, Anyphone::RANDOM_CEILING);
        $this->updateParseUserWithRandomCode();

        return $this;
    }
}