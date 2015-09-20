<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/20/15
 * Time: 15:58
 */

namespace app\Classes\Telerivet\Keywords;

use \App\Classes\Telerivet\Maven;
use App\Classes\Telerivet\Webhook;

class Nuisance extends Maven
{
    public function __construct()
    {
    }

    public function getResponse()
    {
        return Webhook::getInstance()
            ->setReply("Invalid command!")
            ->setHandled(true)
            ->getResponse();
    }
}