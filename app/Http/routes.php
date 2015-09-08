<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Http\Request;
use Parse\ParseClient;
use Parse\ParseObject;
use Illuminate\Support\Facades\View;

class Pop extends Illuminate\Database\Eloquent\Model {

}

$app->get('/', function () use ($app) {

    $pop = new Pop;
    $pop->cluster = 1;
    $pop->precincts = '0001A,0001B,0001C';
    $pop->save();

    $pop = new Pop;
    $pop->cluster = 3;
    $pop->precincts = '0003A,0003B,0003C';
    $pop->save();


    return 'TxtCmdr';
    //return $app->welcome('TxtCmdr');
});

$app->get('pop/{id}', function ($id) {
    return Pop::findOrFail($id);
});

$app->get('cluster/{cluster}', function ($cluster) {
    $query = Pop::where('cluster', '=', $cluster);
    return $query->get();
});

$app->get('precinct/{precinct}', function ($precinct) {
    $query = Pop::where('precincts', 'regexp', DB::raw('"[[:<:]](0*'.$precinct.')[[:>:]]"'));
    return $query->get(array('cluster', 'precincts'));
});

$app->post('webhook', function (Request $request) {
//    $app->post('webhook', function () {

        if ($request->input('secret') === '87186188739312') {
            if ($request->input('event') == 'incoming_message') {
                header("Content-Type: application/json");
                return json_encode(array(
                    'messages' => array(
                        array('content' => $request->input('contact.name') . ", thanks for your message!")
                    )
                ));
            }
        }

        //return 'The quick brown fox jumps over the lazy dog.';
});

ParseClient::initialize('U6CaTTyJ2AGXWLdF3bfl89eWYR2BbMWrEE73Ynsd', 'sz7rz1fuCIo4wRjNlM2lVrfuInsHbCRjr270tK8E', 'vfUXDTVhAxvjteuuNq2in1fYrG7KKtdSMvchj1Qg');

$app->get('parse/object', function(){

    $object = ParseObject::create("TestObject");
    $objectId = $object->getObjectId();
    $php = $object->get("elephant");

// Set values:
    $object->set("elephant", "php");
    $object->set("today", new DateTime());
    $object->setArray("mylist", [1, 2, 3]);
    $object->setAssociativeArray(
        "languageTypes", array("php" => "awesome", "ruby" => "wtf")
    );

// Save:
    $object->save();
    return 'object saved.';
});

$app->group(['prefix' => 'parse'], function ($app) {
    $app->get(  '/',            'App\Http\Controllers\ParseController@index');
    $app->post( 'initialize',   'App\Http\Controllers\ParseController@initialize');
    $app->post( 'webhook',      'App\Http\Controllers\ParseController@webhook');
    $app->post( 'login',        'App\Http\Controllers\ParseController@login');
    $app->post( 'args',         'App\Http\Controllers\ParseController@args');
});
