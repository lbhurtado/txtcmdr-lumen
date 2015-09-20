<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/20/15
 * Time: 15:48
 */

namespace app\Classes\Telerivet\Keywords;

use \App\Classes\Telerivet\Maven;
use App\Classes\Telerivet\Webhook;
use App\Classes\Parse\Anyphone;

class Recruit extends Maven
{
    public function getResponse()
    {
        $somenumber = $this->somenumber; //from magic method __set

        if (!$somenumber) {
            return Webhook::getInstance()->getDebugResponse("Error! No somenumber parameter in http.");
        }
        try {
            $randomCode =
                (!Anyphone::getInstance()->setUser($somenumber))
                    ? Anyphone::getInstance()->signupParseUserWithRandomCode()
                    : Anyphone::getInstance()->updateParseUserWithRandomCode();

            $mobile = Anyphone::getInstance()->getMobile();

            Webhook::getInstance()
                ->setReply("The OTP was already sent to $mobile.")
                ->setForward($mobile, "Your OTP is $randomCode")
                ->setState("verify")
                ->addVariable("contact.vars.recruit|$mobile")
                ->addMobileToGroups($mobile, "pending");
        } catch (MobileAddressException $ex) {
            Webhook::getInstance()
                ->setReply("Mobile phone is not valid! Please try again.")
                ->setState(Webhook::RECRUITING)
                ->addVariable("contact.vars.recruit|");
        }

        return Webhook::getInstance()
            ->addtoGroups("recruiter")
            ->getResponse();
    }
}