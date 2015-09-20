<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/20/15
 * Time: 16:01
 */

namespace app\Classes\Telerivet\Keywords;

use \App\Classes\Telerivet\Maven;
use App\Classes\Telerivet\Webhook;

class Unauthorized extends Maven
{
    public function __construct()
    {
    }

    public function getResponse()
    {
        return Webhook::getInstance()
            ->setHandled(true)
            ->getResponse();
    }
}