<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Parse\ParseObject;
use Parse\ParseQuery;

class ParseController extends Controller
{
    public function initialize() {
        $pops = new ParseObject('pops');
        $pops->cluster = 1;
        $pops->precincts = '0001A,0001B,0001C';
        try {
            $pops->save();
            echo 'New object created with objectId: ' . $pops->getObjectId();
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
            echo 'New object created with objectId: ' . $pops->getObjectId();
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

                /*
                $query = ParseUser::query();
                $query->equalTo("mobile", $request->input('contact.phone_number'));
                $results = $query->find();
                */

                $query = Pop::where('precincts', 'regexp', DB::raw('"[[:<:]](0*'.$precinct.')[[:>:]]"'));

                header("Content-Type: application/json");
                return json_encode(array(
                    'messages' => array(
                        array('content' => $request->input('contact.name') . ", " . $query->get(array('cluster', 'precincts')));
                    )
                ));
            }
        }
        return 'nan';
    }
}