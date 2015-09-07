<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseQuery;
use Parse\ParseCloud;
use Parse\ParseACL;

define('SECRET','87186188739312');
define('DEFAULT_INTERNATIONAL_PREFIX', '63');
define('VALID_MOBILE_PATTERN', "/^(" . "?P<country>0" . "|" . "63" . ")(?P<mobile>\d{10})$/");
define('RANDOM_FLOOR', 1000);
define('RANDOM_CEILING', 9999);

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

                $content_array = explode(' ', trim($content));
                $word1 = array_shift($content_array);
                $remainder1 = implode(' ', $content_array);
                $mobile = $request->input('from_number');
                $status = $request->input('state.id');


                $args = $this->parse_args($content);
                $reply = $args['mobile'];
                /*
                switch (true) {
                    case $args['REQUEST_OTP']:
                        if (preg_match(VALID_MOBILE_PATTERN, $args['mobile'], $matches)) {
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
                                $reply = "The OTP was already sent to $mobile.";
                            }
                        }
                        break;
                    case $args['CONSUME_OTP']:
                        break;
                    default:
                        echo 'default';
                }
                */

                /*
                $query = ParseUser::query();
                $query->equalTo("phone", $request->input('contact_phone_number'));
                $results = $query->find();
                if (count($results) == 0) {
                    $user = new ParseUser();
                    $user->set("username", $request->input('contact_name'));
                    $user->set("password", "password");
                    $user->set("phone", $request->input('contact_phone_number'));
                    try {
                        $user->signUp();
                        // Hooray! Let them use the app now.
                    } catch (ParseException $ex) {
                        // Show the error message somewhere and let the user try again.
                        echo "Error: " . $ex->getCode() . " " . $ex->getMessage();
                    }
                }
                else {
                    try {
                        $user = ParseUser::logIn($request->input('contact_name'), "password");

                        // Do stuff after successful login.
                    } catch (ParseException $error) {
                        // The login failed. Check error to see why.
                    }

                    header("Content-Type: application/json");
                    return json_encode(array(
                        'messages' => array(
                            array('content' => $request->input('contact_name'))
                        )
                    ));
                }
                */

                $reply2 = $mobile . "=>" . $request->input('to_number') . "\n" .
                                $request->input('contact.name') . "\n" .
                                $content . "\n" .
                                $word1 . "\n" .
                                $remainder1. "\n" .
                                $status;

                header("Content-Type: application/json");
                return json_encode(array(
                    'messages' => array(
                        array(
                            'content' => $reply
                        )
                    ),
                    'variables' => array(
                        'contact.name' => "Lester Hurtado",
                        'contact.vars.otp' => '0421',
                        'state.id' => 'step3',
                        '$foo' => 1987,
                    )
                ));

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

    public function enterPIN (Request $request) {
        $mobile = $request->input('mobile');
        $code = $request->input('code');
        if (preg_match(VALID_MOBILE_PATTERN, $mobile, $matches)) {
            $mobile =  DEFAULT_INTERNATIONAL_PREFIX . $matches['mobile'];
            $num = $request->input('code');
            try {
                $user = ParseUser::logIn($mobile, SECRET . $num);
            } catch (ParseException $error) {
                echo "Error: " . $ex->getCode() . " " . $ex->getMessage();
            }
            return $user->username;
        }
        return 'Failed!';
    }

    public function args(Request $request) {
        $str = $request->input('text');
        dd($this->parse_args($str));
    }
}
