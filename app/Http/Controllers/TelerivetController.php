<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/11/15
 * Time: 06:53
 */

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\Telehook;
use App\Classes\MobileAddress;

define('TELEHOOK_NO_STATE', '');

class TelerivetController extends Controller
{
    public function webhook(Request $request) {
        $command = null;
        $arguments = array();
        if (Telehook::isAuthorized($request)) {
            switch (Telehook::$state) {
                case TELEHOOK_NO_STATE:
                    $command = Telehook::$keyword;
                    $arguments = Telehook::$arguments;
                    break;
                default:
                    $command = Telehook::$state;
                    $contents = parse_args(Telehook::$content);
                    $paramvalues = array_keys(array_where($contents, function ($key, $value) {
                        return ($value === true) and (MobileAddress::getInstance($key)->getServiceNumber());
                    }));
                    $paramkeys = array('somenumber');
                    $arguments = array();
                    for ($i = 0; ($i < count($paramkeys)) and ($i < count($paramvalues)); ++$i) {
                        $arguments[$paramkeys[$i]] = $paramvalues[$i];
                    }
            }
        }
        $url = route($command, $arguments, 307);
        //return $arguments;

        return redirect()->route($command, $arguments, 307);
    }
}