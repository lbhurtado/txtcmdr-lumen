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
use App\Classes\MobileAddress;

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
    private $request;

    public function __construct(Request $request){
        if (Telehook::isAuthorized($request))
            $this->request = $request;
    }

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

    public function test()
    {
        //return redirect()->route('parse::home');
        //return redirect()->route('parse/');
        //return redirect('cluster/3');
        //return redirect()->route('recruit::');
        return redirect()->route('recruit', ['mobile' => 639189362340]);
    }

    public
    function webhook(Request $request)
    {
        //return redirect()->route('cluster',['cluster'=>3]);

        //return redirect('cluster/3');

        if (Telehook::isAuthorized($request)) {
            switch (Telehook::$state) {
                case NO_STATE:
                    switch (strtoupper(Telehook::$word1)) {
                        case 'RECRUIT':
                            ($mobile = MobileAddress::getInstance(Telehook::$remainder1)->getServiceNumber())
                                ? $this->recruit($request, $mobile)
                                : Telehook::getInstance()
                                ->setReply('You are now in recruiting mode. Please enter mobile number of your recruit:')
                                ->setVariable('state.id|recruiting');
                            break;
                    }
                    break;
                case 'recruit':
                    return $this->recruit($request);
                    break;
                case 'verify':
                    return $this->verify($request);
                    break;
            }
        }

        return Telehook::getInstance()->getResponse();
    }

    public
    function recruit($somenumber = null)
    {
        try {
            if (Telehook::isAuthorized($this->request)) {
                $mobile = MobileAddress::getInstance($somenumber ?: Telehook::$word1)->getServiceNumber();
                if ($mobile) {
                    $randomCode =
                        ($user = ParseUser::query()->equalTo(PARSE_USERNAME, $mobile)->first(PARSE_USE_MASTERKEY))
                            ? $this->updateParseUserWithRandomCode($user)
                            : $this->signupParseUserWithRandomCode($mobile);
                    Telehook::getInstance()
                        ->setReply("The OTP was already sent to $mobile.")
                        ->setForward("$mobile|Your OTP is $randomCode")
                        ->setVariable("state.id|verifying")
                        ->addVariable("contact.vars.recruit|$mobile");
                } else {
                    $msg = is_int($somenumber)
                        ? "$somenumber is not a valid mobile number. "
                        : "You are now in recruiting mode. Please enter mobile number of your recruit:";
                    Telehook::getInstance()
                        ->setReply($msg)
                        ->setVariable("state.id|recruit");
                }
            }
            else {
                return Telehook::getInstance()->getDebugResponse('not authorized');
            }
        }
        catch (ParseException $ex) {
            Telehook::getDebugResponse('recruit');
        }

        return Telehook::getInstance()->getResponse();
    }

    public
    function autoRecruit()
    {
        if (Telehook::isAuthorized($this->request)) {
            Telehook::getInstance()
                ->setReply('You are now in recruiting mode. Please enter mobile number of your recruit:')
                ->setVariable('state.id|recruit');
        }
        return Telehook::getInstance()->getResponse();
    }

    private
    function updateParseUserWithRandomCode(ParseUser $user)
    {
        $randomCode = $this->getRandomCode();
        try {
            $user->set('password', SECRET . $randomCode);
            $user->save(PARSE_USE_MASTERKEY);
        } catch (ParseException $ex) {
            Telehook::getInstance()
                ->setReply("Something is wrong! Error code " . $ex->getMessage());
        }

        return $randomCode;
    }

    private
    function getRandomCode()
    {
        list($usec, $sec) = explode(' ', microtime());
        $seed = (float)$sec + ((float)$usec * 100000);
        mt_srand($seed);
        return mt_rand(RANDOM_FLOOR, RANDOM_CEILING);
    }

    private
    function signupParseUserWithRandomCode($mobile)
    {
        $randomCode = $this->getRandomCode();
        $user = new ParseUser();
        $user->setUsername($mobile);
        $user->setPassword(SECRET . $randomCode);
        $user->setACL(new ParseACL());
        try {
            $user->signUp(PARSE_USE_MASTERKEY);
        } catch (ParseException $ex) {
            Telehook::getInstance()
                ->setReply("Something is wrong! Error code " . $ex->getMessage());
        }

        return $randomCode;
    }

    public
    function verify($somenumber, $allegedotp)
    {
        //$somenumber = Telehook::getVariable('contact.vars.recruit');
        $mobile = MobileAddress::getInstance($somenumber)->getServiceNumber();

        if ($mobile) {
            //$allegedOTP = Telehook::$content;
            try {
                //$user = ParseUser::logIn($mobile, SECRET . $allegedOTP);  //use PARSE_USE_MASTERKEY
                $sessionToken = $this->getSessionToken($mobile, $allegedotp);
                $user = ParseUser::become($sessionToken);
                $user->set('phone', $mobile);
                $user->setPassword(SECRET . $this->getRandomCode());
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
        }

        return false;
    }

    private
    function getSessionToken($somenumber, $allegedOTP)
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
