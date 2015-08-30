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
    $query = Pop::where('precincts', 'regexp', DB::raw('"[[:<:]]'.$precinct.'[[:>:]]"'));
    return $query->get(array('cluster', 'precincts'));
});

$app->post('webhook', function (Request $request) {
    if ($request->input('secret') === '87186188739312') {
        if ($request->input('event') == 'incoming_message') {
            header("Content-Type: application/json");
            return json_encode(array(
                'messages' => array(
                    array('content' => "Thanks for your message!")
                )
            ));
        }
    }
   //return 'The quick brown fox jumps over the lazy dog.';
});