<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/11/15
 * Time: 06:53
 */

namespace App\Http\Controllers;

use App\Classes\Telerivet\MavenFactory;
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
        list($usec, $sec) = explode(' ', microtime());
        $seed = (float)$sec + ((float)$usec * 100000);
        Random::seed($seed);

        return MavenFactory::getMaven($this->request)->getResponse();

        // echo 10 numbers between 1 and 100
        for ($i = 0; $i < 100; $i++) {
            echo Random::num(1000, 9999) . "\n";
        }

    }
}
