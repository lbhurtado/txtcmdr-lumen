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

define('SECRET', env('PARSE_OTP_PREFIX'));
define('DEFAULT_INTERNATIONAL_PREFIX', '63');
define('VALID_MOBILE_PATTERN', "/^(" . "?P<country>0" . "|" . "63" . ")(?P<mobile>\d{10})$/");
define('RANDOM_FLOOR', 1000);
define('RANDOM_CEILING', 9999);
define('NO_STATE', '');
define('PARSE_USE_MASTERKEY', true);
define('PARSE_USERNAME', 'username');

class ParseController extends Controller
{
    public function initialize()
    {
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

    public function index()
    {
        $query = ParseUser::query();
        $results = $query->find(PARSE_USE_MASTERKEY);
        echo "Successfully retrieved " . count($results) . " users.\n";
        for ($i = 0; $i < count($results); $i++) {
            $object = $results[$i];
            echo $object->getObjectId() . ' - ' . $object->get('username') . "\n";
        }
    }


    public function webhook(Request $request)
    {
        if (Telehook::isAuthorized($request)) {
            switch (Telehook::$state) {
                case NO_STATE:
                    switch (strtoupper(Telehook::$word1)) {
                        case 'RECRUIT':
                            if (preg_match(VALID_MOBILE_PATTERN, Telehook::$remainder1, $matches)) {
                                $mobile = DEFAULT_INTERNATIONAL_PREFIX . $matches['mobile'];

                                return $this->recruit($request, $mobile);
                            } else {
                                Telehook::getInstance()
                                    ->setReply('You are now in recruiting mode. Please enter mobile number of your recruit:')
                                    ->setVariable('state.id|recruiting');
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

        return Telehook::getInstance()->getResponse();
    }

    public function login(Request $request)
    {
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

    public function recruit(Request $request, $mobile = null)
    {
        if (!$mobile)
            $mobile = Telehook::$content;
        if (preg_match(VALID_MOBILE_PATTERN, $mobile, $matches)) {
            $mobile = DEFAULT_INTERNATIONAL_PREFIX . $matches['mobile'];
            $num = mt_rand(RANDOM_FLOOR, RANDOM_CEILING);
            $user = ParseUser::query()->equalTo(PARSE_USERNAME, $mobile)->first(PARSE_USE_MASTERKEY);
            if (!$user) {
                $user = new ParseUser();
                $user->setUsername($mobile);
                $user->setPassword(SECRET . $num);
                $user->setACL(new ParseACL());
                try {
                    $user->signUp(PARSE_USE_MASTERKEY);
                } catch (ParseException $ex) {
                }
            } else {
                $user->set('password', SECRET . $num);
                $user->save(PARSE_USE_MASTERKEY);
            }
            Telehook::getInstance()
                ->setReply("The OTP was already sent to $mobile.")
                ->setForward("$mobile|Your OTP is $num")
                ->setVariable("state.id|verifying")
                ->addVariable("contact.vars.recruit|$mobile");
        } else {
            Telehook::getInstance()
                ->setReply(Telehook::$content . " is not a valid mobile number!")
                ->setVariable("state.id|recruiting");
        }
        return Telehook::getInstance()->getResponse();
    }

    public function verify(Request $request)
    {
        $mobile = Telehook::getProperty($request, 'contact.vars.recruit');
        if (preg_match(VALID_MOBILE_PATTERN, $mobile, $matches)) {
            $mobile = DEFAULT_INTERNATIONAL_PREFIX . $matches['mobile'];
            $num = trim($request->input('content'));
            try {
                $user = ParseUser::logIn($mobile, SECRET . $num);
                $user->set('phone', $mobile);
                Telehook::getInstance()
                    ->setReply("OTP is valid.")
                    ->setForward("$mobile|Your OTP is valid. Congratulations!")
                    ->setVariable("state.id|recruiting")
                    ->addVariable("contact.vars.recruit|");
            } catch (ParseException $ex) {
                Telehook::getInstance()
                    ->setReply("OTP is not valid! Please try again.")
                    ->setVariable("state.id|verifying")
                    ->addVariable("contact.vars.recruit|$mobile");
            }
        }
        return Telehook::getInstance()->getResponse();
    }
}
