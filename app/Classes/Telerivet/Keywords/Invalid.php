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
use app\Classes\Parse\Anyphone;

class Invalid extends Maven
{
    public function __construct()
    {
    }

    public function getResponse()
    {
        $mobile = Anyphone::getInstance()->getMobile();
        return Webhook::getInstance()
            ->setReply("Your OTP is not valid! Please try again.")
            ->setState(Webhook::VERIFYING)
            ->addVariable("contact.vars.recruit|$mobile")
            ->setHandled(true)
            ->getResponse();
    }
}