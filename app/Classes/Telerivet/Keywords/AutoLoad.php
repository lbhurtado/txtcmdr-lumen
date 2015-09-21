<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/20/15
 * Time: 15:40
 */

namespace app\Classes\Telerivet\Keywords;

use \App\Classes\Telerivet\Keywords\Auto;
use App\Classes\Telerivet\Webhook;

class AutoLoad extends Auto
{
    public function getResponse()
    {
        dd($this->name);
        if (
            ($mobile = $this->getCommand()->mobile) and
            ($amount = $this->getCommand()->amount) and
            ($reply = $this->getCommand()->reply) and
            ($message = $this->getCommand()->message)
        ) {
            return Webhook::getInstance()
                ->setReply($reply)
                ->setForward($mobile, $message)
                ->loadMobile("$mobile:$amount")
                ->getResponse();
        }

        return Webhook::getInstance();
    }
}