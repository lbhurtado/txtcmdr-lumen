<?php
// app/Classes/MobileAddress.php

namespace App\Classes;

use App\Classes\CustomException;

class MobileAddressException extends CustomException {}

abstract class MobileAddress {
	//const REGEX_MOBILE = "/((?<country>0|63|\+63)(?<telco>9\d{2})(?<number>\d{7}))|((^s?)(?<operator>\d{3,4}$))/";
    protected $matches;

    public function __construct($matches) {
        $this->matches = $matches;
    }

    public static function getInstance($mobile) {
        if (preg_match("/(^s?)(?<operator>\d{3,4}$)/", $mobile, $matches)) 
            return new OperatorAddress($matches);
        if (preg_match("/^(?<country>0|63|\+63)(?<telco>9\d{2})(?<number>\d{7})$/", $mobile, $matches)) 
            return new InternationalAddress($matches);
        return new NullAddress($matches);
    }

    abstract function getServiceNumber();
}

class InternationalAddress extends MobileAddress {
    const DEFAULT_COUNTRY_CODE = "63";

    public function getServiceNumber() {
        return self::DEFAULT_COUNTRY_CODE . $this->matches['telco'] . $this->matches['number'];
    } 
}

class OperatorAddress extends MobileAddress {
    const OPERATOR_PREFIX = "s";
    
    public function getServiceNumber() {
        return self::OPERATOR_PREFIX . $this->matches['operator'];
    } 
}

class NullAddress extends MobileAddress {
    public function getServiceNumber() {
        return null;
    }
}