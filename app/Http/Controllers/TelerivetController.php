<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/11/15
 * Time: 06:53
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\Telerivet\Maven;
use App\Classes\Random;
use App\Classes\Parse\Anyphone;

class TelerivetController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function webhook()
    {
        Random::seed(537);

        return Maven::getInstance($this->request)->getResponse();

        // set seed


        /*
// echo 10 numbers between 1 and 100
        for ($i = 0; $i < 10; $i++) {
            echo Random::num(1000, 9999) . '<br />';
        }
        */
        return Anyphone::getInstance()->signupParseUserWithRandomCode('09189362340');
    }
}
