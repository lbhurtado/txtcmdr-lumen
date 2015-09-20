<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/20/15
 * Time: 14:33
 */

namespace App\Classes\Telerivet\Keywords;

use \App\Classes\Telerivet\Maven;
use App\Classes\Telerivet\Webhook;


class AutoRecruit extends Maven
{
    protected $description = "Help";

    protected $defaultReply = "You are now in auto-recruit mode. Please enter mobile number of your recruit:";

    protected $defaultNextState = "recruit";

    protected $addtoGroups = "recruiter";

    public function getResponse()
    {
        return Webhook::getInstance()
            ->addtoGroups($this->getAddtoGroups())
            ->getResponse();
    }
}