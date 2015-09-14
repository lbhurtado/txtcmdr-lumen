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
use Symfony\Component\Yaml\Exception\ParseException;

define('TELEHOOK_NO_STATE', '');

class TelerivetController extends Controller
{
    private $request;

    private $commands = [
        'recruit' => [
            'pattern' => "/^(?<somenumber>(0|63)(\d{10}))(?<text>.*)$/",
        ],
        'verify' => [
            'pattern' => "/^(?<allegedotp>\d{4})$/",
            'parameters' => [
                //"somenumber" => "/^contact(.|_)vars(.|_)recruit$/",
                "somenumber" => "/^contact.vars.recruit$/",
            ],
        ],
    ];

    public function __construct(Request $request)
    {
        if (Telehook::isAuthorized($request))
            $this->request = $request;
    }

    public function webhook()
    {
        try {
            switch (Telehook::$state) {
                case TELEHOOK_NO_STATE:
                    $command = Telehook::$keyword;
                    $arguments = Telehook::$arguments;
                    break;
                default:
                    $command = Telehook::$state;
                    $pattern = array_get($this->commands, "$command.pattern");
                    $arguments = preg_match($pattern, Telehook::$content, $matches)
                        ? array_where($matches, function ($key) {
                            return preg_match("/^[a-zA-Z]*$/", $key);
                        })
                        : array();

                    $parameters = array_get($this->commands, "$command.parameters");
                    if (is_array($parameters)) {
                        foreach (array_get($this->commands, "$command.parameters") as $parameter => $regex) {
                            $found_record = array_where(Telehook::$inputs, function ($key) use ($regex) {
                                return preg_match($regex, $key);
                            });
                            if ($found_record)
                                $arguments[$parameter] = current($found_record);
                        }
                    }
                    break;
            }
            //$url = route($command, $arguments, 307);
            //return $url;
            //return $arguments;
        }
        catch (ParseException $ex) {
            return Telehook::getErrorResponse();
        }
        return redirect()->route($command, $arguments, 307);
    }
}