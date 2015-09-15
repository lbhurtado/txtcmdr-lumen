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
use App\Classes\TextCommand;
use App\Classes\Maven;

class TelerivetController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function webhook()
    {
        $command = new TextCommand($this->request);

        return Maven::getInstance($this->request)->getResponse();
    }
}
