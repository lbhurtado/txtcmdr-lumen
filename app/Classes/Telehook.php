<?php

namespace App\Classes;

/**
 * Class Telehook
 * @package App\Classes
 */
class Telehook
{
    public static $reply;
    public static $forwards = array();
    public static $variables = array();
    private static $_instance = null;

    private function __construct()
    {
    }

    public static function addReply($reply)
    {
        self::$reply = $reply;
    }

    public static function addForward($pipe_delimited_text)
    {
        $forward = explode('|', $pipe_delimited_text);
        self::$forwards[$forward[0]] = $forward[1];
    }

    public static function addVariable($pipe_delimited_text)
    {
        $variable = explode('|', $pipe_delimited_text);
        self::$variables[$variable[0]] = $variable[1];
    }

    public static function getData()
    {
        $ar = array();
        if (self::$reply)
            $ar['reply'] = self::$reply;
        if (self::$forwards)
            $ar['forwards'] = self::$forwards;
        if (self::$variables)
            $ar['variables'] = self::$variables;

        return $ar;
    }

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    public function setReply($reply)
    {
        self::$reply = $reply;
        return $this;
    }

    public function setForward($pipe_delimited_text)
    {
        $forward = explode('|', $pipe_delimited_text);
        self::$forwards[$forward[0]] = $forward[1];

        return $this;
    }

    public function setVariable($pipe_delimited_text)
    {
        $variable = explode('|', $pipe_delimited_text);
        self::$variables[$variable[0]] = $variable[1];

        return $this;
    }
}