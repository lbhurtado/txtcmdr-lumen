<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/20/15
 * Time: 15:40
 */

namespace app\Classes\Telerivet\Keywords;

use \App\Classes\Telerivet\Maven;
use App\Classes\Telerivet\Webhook;

class LoadMobile extends Maven
{
    public function getResponse()
    {
        if (
            ($mobile = $this->mobile) and
            ($amount = $this->amount) and
            ($reply = $this->reply) and
            ($message = $this->message)
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