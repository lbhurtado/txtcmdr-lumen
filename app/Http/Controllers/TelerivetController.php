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
use App\Classes\TextCommand;
use App\Classes\Maven;

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
        $this->request = $request;
    }

    public function webhook()
    {
        $command = new TextCommand($this->request);
        //$url = route($command->getKeyword(), $command->getParameters(), 307);


        return Maven::getInstance($this->request)->getResponse();

        return $this->autoRecruit();
        //return redirect()->route($command, $arguments);

    }

    private
    function autoRecruit()
    {
        if (Telehook::isAuthorized($this->request)) {
            Telehook::getInstance()
                ->setReply('You are now in recruiting mode. Please enter mobile number of your recruit:')
                ->setVariable('state.id|recruit');
        }
        return Telehook::getInstance()->getResponse();
    }
}
