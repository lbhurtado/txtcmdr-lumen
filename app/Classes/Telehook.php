<?php

namespace App\Classes;

use Illuminate\Http\Request;


/**
 * Class Telehook
 * @package App\Classes
 */
class Telehook
{
    public static $test = 'Test';
    public static $reply;
    public static $forwards = array();
    public static $variables = array();
    private static $_instance = null;
    public static $request = null;
    public static $content = null;
    private static $content_array = array();
    public static $word1 = null;
    public static $remainder1 = null;
    public static $state = null;
    public static $inputs = array();

    public static $keyword;
    public static $arguments;

    private function __construct()
    {
    }

    public function setReply($reply)
    {
        static::$reply = $reply;
        return static::getInstance();
    }

    public function setForward($mobile, $missive)
    {
        static::$forwards = array();
        static::$forwards[$mobile] = $missive;

        return static::getInstance();
    }

    public function addForward($mobile, $missive)
    {
        static::$forwards[$mobile] = $missive;
        return static::getInstance();
    }

    public function setVariable($pipe_delimited_text)
    {
        static::$variables = array();
        $var = explode('|', $pipe_delimited_text);
        static::$variables[$var[0]] = $var[1] ?: null;

        return static::getInstance();
    }

    public function setState($state){
        return $this->setVariable("state.id|$state") ;
    }

    public function addVariable($pipe_delimited_text)
    {
        $var = explode('|', $pipe_delimited_text);
        static::$variables[$var[0]] = $var[1];

        return static::getInstance();
    }

    public function addtoGroups($comma_delimited_text){
        if (is_array($comma_delimited_text))
            $comma_delimited_text = implode(',', $comma_delimited_text);
        return $this->addVariable("\$addtogroups|$comma_delimited_text");
    }

    public static function getVariable($variable)
    {
        $result = static::$request->input($variable);
        if (!$result)
            $result = static::$request->input(str_replace('.', '_', $variable));
        if (!$result)
            $result = static::$request->input(str_replace('_', '.', $variable));

        return $result;
    }

    public static function getInstance()
    {
        if (static::$_instance === null) {
            static::$_instance = new self();
        }

        return static::$_instance;
    }

    public static function isAuthorized(Request $request)
    {
        if ($request->input('secret') === env('TELERIVET_WEBHOOK_SECRET')) {
            if ($request->input('event') == 'incoming_message') {
                static::$request = $request;
                static::$content = trim($request->input('content'));
                static::$content_array = explode(' ', static::$content);
                static::$word1 = array_shift(static::$content_array);
                static::$remainder1 = implode(' ', static::$content_array);
                static::$state = static::getVariable('state_id');
                static::$inputs = $request->all();

                static::$arguments = parse_args(static::$content);
                $value = reset(static::$arguments);
                static::$keyword = key(static::$arguments);
                unset(static::$arguments[static::$keyword]);

                return true;
            }
        }
        return false;
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

    public static function getError()
    {
        return [
            'messages' =>
                ['content' => 'error here.',]
        ];
    }

    public function getResponse()
    {
        $this->addVariable("return_value|0");
        return response(view('webhook', static::getData()), 200, ['Content-Type' => "application/json"]);
    }

    public static function getDebugResponse($msg = 'debug')
    {
        self::$reply = $msg;
        return response(view('webhook', static::getData()), 200, ['Content-Type' => "application/json"]);
    }
}