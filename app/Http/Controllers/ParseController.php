<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseQuery;
use Parse\ParseCloud;

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

    public function webhook(Request $request) {
        if ($request->input('secret') === '87186188739312') {
            if ($request->input('event') == 'incoming_message') {
                $mobile = $request->input('contact_phone_number');


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
                header("Content-Type: application/json");
                return json_encode(array(
                    'messages' => array(
                        array('content' => $request)
                    )
                ));
            }
        }
        return 'nan';
    }

    public function sendCode (Request $request) {
        $results = ParseCloud::run("sendCode", array("phoneNumber" => "3104992907"), true);
        if  (varIsNull($results))
            return 'OTP sent';
        //return $request;
    }

    public function login (Request $request) {
        $results = ParseCloud::run("logIn", array("codeEntry" => "8432", "phoneNumber" => "3104992907"), true);

        try {
            $user = ParseUser::become($results);
            echo $user->getObjectId();
            // The current user is now set to user.
        } catch (ParseException $ex) {
            dd($ex);
        }

        return json_encode($request);

    }
}
