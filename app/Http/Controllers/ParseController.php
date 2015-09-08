<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseQuery;
use Parse\ParseCloud;
use Parse\ParseACL;
use Parse\ParseException;
use App\Classes\Telehook;

define('SECRET','87186188739312');
define('DEFAULT_INTERNATIONAL_PREFIX', '63');
define('VALID_MOBILE_PATTERN', "/^(" . "?P<country>0" . "|" . "63" . ")(?P<mobile>\d{10})$/");
define('RANDOM_FLOOR', 1000);
define('RANDOM_CEILING', 9999);
define('NO_STATE', '');

class WebhookHelper {
    public static $reply;

    public static $forwards = array();

    public static $variables = array();

    public static $data = array();

    public static function addReply($reply){
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

    public static function getData(){
        $ar = array();
        if (self::$reply)
            $ar['reply'] = self::$reply;
        if (self::$forwards)
            $ar['forwards'] = self::$forwards;
        if (self::$variables)
            $ar['variables'] = self::$variables;

        return $ar;
    }
}

class ParseController extends Controller
{
    public function initialize() {
        /*
        $pops = new ParseObject('pops');
        $pops->cluster = 1;
        $pops->precincts = '0001A,0001B,0001C';
        try {
            $pops->save();
            echo 'New object created with objectId: ' . $pops->getObjectId() . "\n";
        } catch (ParseException $ex) {
            // Execute any logic that should take place if the save fails.
            // error is a ParseException object with an error code and message.
            echo 'Failed to create new object, with error message: ' . $ex->getMessage();
        }
        $pops = new ParseObject('pops');
        $pops->cluster = 3;
        $pops->precincts = '0003A,0003B,0003C';
        try {
            $pops->save();
            echo 'New object created with objectId: ' . $pops->getObjectId() . "\n";
        } catch (ParseException $ex) {
            // Execute any logic that should take place if the save fails.
            // error is a ParseException object with an error code and message.
            echo 'Failed to create new object, with error message: ' . $ex->getMessage();
        }
        */


        $query = new ParseQuery('regions');
        $query->equalTo("name", 'Region I');
        if (!$query->find()) {
            $regions = new ParseObject('regions');
            $regions->name = 'Region I';
            $regions->description = 'Ilocos Region';
            try {
                $regions->save();
                echo 'Regions object created with objectId: ' . $regions->getObjectId() . "\n";
            } catch (ParseException $ex) {
                // Execute any logic that should take place if the save fails.
                // error is a ParseException object with an error code and message.
                echo 'Failed to create new object, with error message: ' . $ex->getMessage();
            }
        }

        $provinces = new ParseObject('provinces');
        $provinces->name = 'Ilocos Norte';
        $provinces->region = $regions;
        try {
            $provinces->save();
            echo 'Provinces object created with objectId: ' . $provinces->getObjectId() . "\n";
        } catch (ParseException $ex) {
            // Execute any logic that should take place if the save fails.
            // error is a ParseException object with an error code and message.
            echo 'Failed to create new object, with error message: ' . $ex->getMessage();
        }

    }

    public function index() {
        $query = new ParseQuery("pops");
        $results = $query->find();
        echo "Successfully retrieved " . count($results) . " pops.";
        for ($i = 0; $i < count($results); $i++) {
            $object = $results[$i];
            echo $object->getObjectId() . ' - ' . $object->get('precincts') . "\n";
        }
    }

    function parse_args($args) {
        $out = array();
        $last_arg = null;
        if(is_string($args)){
            $args = str_replace(array('=', "\'", '\"'), array('= ', '&#39;', '&#34;'), $args);
            $args = str_getcsv($args, ' ', '"');
            $tmp = array();
            foreach($args as $arg){
                if(!empty($arg) && $arg != "&#39;" && $arg != "=" && $arg != " "){
                    $tmp[] = str_replace(array('= ', '&#39;', '&#34;'), array('=', "'", '"'), trim($arg));
                }
            }
            $args = $tmp;
        }
        for($i = 0, $il = sizeof($args); $i < $il; $i++){
            if( (bool)preg_match("/^--(.+)/", $args[$i], $match) ){
                $parts = explode("=", $match[1]);
                $key = preg_replace("/[^a-zA-Z0-9-]+/", "", $parts[0]);
                if(isset($args[$i+1]) && substr($args[$i],0,2) == '--'){
                    $out[$key] = $args[$i+1];
                    $i++;
                }else if(isset($parts[1])){
                    $out[$key] = $parts[1];
                }else{
                    $out[$key] = true;
                }
                $last_arg = $key;
            }else if( (bool)preg_match("/^-([a-zA-Z0-9]+)/", $args[$i], $match) ){
                $len = strlen($match[1]);
                for( $j = 0, $jl = $len; $j < $jl; $j++ ){
                    $key = $match[1]{$j};
                    $val = ($args[$i+1]) ? $args[$i+1]: true;
                    $out[$key] = ($match[0]{$len} == $match[1]{$j}) ? $val : true;
                }
                $last_arg = $key;
            }else if((bool) preg_match("/^([a-zA-Z0-9-]+)$/", $args[$i], $match) ){
                $key = $match[0];
                $out[$key] = true;
                $last_arg = $key;
            }else if($last_arg !== null) {
                $out[$last_arg] = $args[$i];
            }
        }
        return $out;
        $str = 'yankee -D "oo\"d l e\'s" -went "2 town 2 buy him-self" -a pony --calledit=" \"macaroonis\' "';
    }

    public function webhook(Request $request) {
        if ($request->input('secret') === '87186188739312') {
            if ($request->input('event') == 'incoming_message') {
                $content = $request->input('content');
                $args = $this->parse_args($content);
                $content_array = explode(' ', trim($content));
                $word1 = array_shift($content_array);
                $remainder1 = implode(' ', $content_array);
                $mobile = $request->input('from_number');
                $state = $request->input('state.id');

                switch ($state) {
                    case NO_STATE:
                        switch (strtoupper($word1)) {
                            case 'RECRUIT':
                                $mobile = $remainder1;
                                if (preg_match(VALID_MOBILE_PATTERN, $mobile, $matches)) {
                                    $mobile =  DEFAULT_INTERNATIONAL_PREFIX . $matches['mobile'];
                                    $user = ParseUser::query()->equalTo("username", $mobile)->first(true);
                                    if (!$user) {
                                        $user = new ParseUser();
                                        $user->setUsername($mobile);
                                        $num = mt_rand(RANDOM_FLOOR, RANDOM_CEILING);
                                        $user->setPassword(SECRET . $num);
                                        $user->setACL(new ParseACL());
                                        $user->set("phone", $mobile);
                                        try {
                                            $user->signUp(true);
                                        } catch (ParseException $ex) {
                                            echo "Error: " . $ex->getCode() . " " . $ex->getMessage();
                                        }
                                        $data = array(
                                            'reply' => "The OTP was already sent to $mobile.",
                                            'forwards' => array(
                                                $mobile => "The your OTP is $num.",
                                            ),
                                            'variables' => array(
                                                'state.id' => "recruiting",
                                            ),
                                        );
                                        return response(view('webhook', $data), 200, ['Content-Type' => "application/json"]);
                                    }
                                }
                                else {

                                    //Telehook::addReply('You are now in recruiting mode. Please enter mobile number of your recruit:');
                                    //Telehook::addVariable('state.id|recruiting');

                                    $data = Telehook::getInstance()
                                        ->setReply('You are now in recruiting mode. Please enter mobile number of your recruit:')
                                        //->setForward('09189362349|Hello World')
                                        ->addVariable('state.id|recruiting')
                                        ->getData();

                                    return response(view('webhook', $data), 200, ['Content-Type' => "application/json"]);

                                }
                                break;
                        }
                        break;
                    case 'recruiting':
                        return $this->recruit($request);
                        break;
                    case 'verifying':
                        return $this->verify($request);
                        break;
                }
            }
        }
        return 'nan';
    }

    public function sendCode (Request $request) {
        $mobile = $request->input('mobile');
        $results = ParseCloud::run("sendCode", array("phoneNumber" => $mobile), true);
        //if  (varIsNull($results))
            return get_class($results);
        //return $request;
    }

    public function login (Request $request) {
        $mobile = $request->input('mobile');
        $code = $request->input('code');
        $results = ParseCloud::run("logIn", array("codeEntry" => $code, "phoneNumber" => $mobile), true);

        try {
            $user = ParseUser::become($results);
            echo $user->getEmail();

            // The current user is now set to user.
        } catch (ParseException $ex) {
            dd($ex);
        }

        return json_encode($request);

    }

    public function sendOTP (Request $request) {
        $mobile = $request->input('mobile');
        if (preg_match(VALID_MOBILE_PATTERN, $mobile, $matches)) {
            $mobile =  DEFAULT_INTERNATIONAL_PREFIX . $matches['mobile'];
            $num = mt_rand(RANDOM_FLOOR,RANDOM_CEILING);
            $user = ParseUser::query()->equalTo("username", $mobile)->first(true);
            if (!$user) {
                $user = new ParseUser();
                $user->setUsername($mobile);
                $user->setEmail($mobile."@mhandle.net");
                $user->setPassword(SECRET . $num);
                $user->setACL(new ParseACL());
                $user->set("phone", $mobile);
                try {
                    $user->signUp(true);
                } catch (ParseException $ex) {
                    echo "Error: " . $ex->getCode() . " " . $ex->getMessage();
                }
            }
            else {
                $user->set("password", SECRET . $num);
                $user->save(true);
            }
        }

        return $num;
    }

    public function recruit (Request $request) {
        $mobile = $request->input('content');
        if (preg_match(VALID_MOBILE_PATTERN, $mobile, $matches)) {
            $mobile =  DEFAULT_INTERNATIONAL_PREFIX . $matches['mobile'];
            $user = ParseUser::query()->equalTo("username", $mobile)->first(true);
            $num = mt_rand(RANDOM_FLOOR, RANDOM_CEILING);
            if (!$user) {
                $user = new ParseUser();
                $user->setUsername($mobile);
                $user->setPassword(SECRET . $num);
                $user->setACL(new ParseACL());
                $user->set("phone", $mobile);
                try {
                    $user->signUp(true);
                } catch (ParseException $ex) {
                    //echo "Error: " . $ex->getCode() . " " . $ex->getMessage();
                }
            }
            else {
                $user->set("password", SECRET . $num);
                $user->save(true);
            }
            $data = array(
                'reply' => "The OTP was already sent to $mobile",
                'forwards' => array(
                    $mobile => "The your OTP is $num.",
                ),
                'variables' => array(
                    'state.id' => "verifying",
                    'contact.vars.recruit' => $mobile,
                ),
            );
        }
        else {
            $data = array(
                'reply' => "$mobile is not a valid mobile number.",
                'variables' => array(
                    'state.id' => "recruiting",
                    'contact.vars.recruit' => null,
                ),
            );
        }
        return response(view('webhook', $data), 200, ['Content-Type' => "application/json"]);
    }

    public function enterPIN (Request $request) {
        $mobile = $request->input('mobile');
        $code = $request->input('code');
        if (preg_match(VALID_MOBILE_PATTERN, $mobile, $matches)) {
            $mobile =  DEFAULT_INTERNATIONAL_PREFIX . $matches['mobile'];
            $num = $request->input('code');
            try {
                $user = ParseUser::logIn($mobile, SECRET . $num);
            } catch (ParseException $ex) {
                echo "Error: " . $ex->getCode() . " " . $ex->getMessage();
            }
            return $user->username;
        }
        return 'Failed!';
    }

    public function verify (Request $request) {
        $mobile = $request->input('contact.vars.recruit');
        if (preg_match(VALID_MOBILE_PATTERN, $mobile, $matches)) {
            $mobile =  DEFAULT_INTERNATIONAL_PREFIX . $matches['mobile'];
            $num = trim($request->input('content'));
            try {
                $user = ParseUser::logIn($mobile, SECRET . $num);
                $data = array(
                    'reply' => "OTP is valid.",
                    'forwards' => array(
                        $mobile => "Your OTP is valid.  Congratulations!",
                    ),
                    'variables' => array(
                        'state.id' => "recruiting",
                        'contact.vars.recruit' => null,
                    ),
                );
                return response(view('webhook', $data), 200, ['Content-Type' => "application/json"]);
            } catch (ParseException $error) {
                //echo "Error: " . $ex->getCode() . " " . $ex->getMessage();
                $data = array(
                    'reply' => "OTP is not valid! Please try again.",
                    'variables' => array(
                        'state.id' => "verifying",
                        'contact.vars.recruit' => $mobile,
                    ),
                );
                return response(view('webhook', $data), 200, ['Content-Type' => "application/json"]);
            }

        }
        return false;
    }

    public function args(Request $request) {
        //$str = $request->input('text');
        //dd($this->parse_args($str));
        /*
        $telehook = new Telehook();
        $telehook->AddReply('this is a reply')
            ->AddForward('09189362340|Yes yes yo.')
            ->AddForward('09189362339|"Kring kring"')
            ->AddVariable('state.id|recruiting');
        */
        //$telehook->getData());
        return Telehook::staticMethod();

    }
}
