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
    protected $defaultReply = "The OTP was already sent to mobile device.";

    protected $defaultNextState = Webhook::VERIFYING;

    protected $addtoGroups = "recruiter";

    public function getResponse()
    {
        try {
            $randomCode =
                /*
                 * Check if $somenumber (taken from TextCommand magic setter)
                 * is already a user in Parse.
                 */
                (!Anyphone::getInstance()->setUser($this->getCommand()->somenumber))
                /*
                 * If not, automatically sign up the user with OTP.
                 * Otherwise, generate a new OTP for the user.
                 */
                    ? Anyphone::getInstance()->signupParseUserWithRandomCode()
                    : Anyphone::getInstance()->updateParseUserWithRandomCode();

            $mobile = Anyphone::getInstance()->getMobile();

            Webhook::getInstance()
                ->setForward($mobile, "Your OTP is $randomCode")
                ->addVariable("contact.vars.recruit|$mobile")
                ->addMobileToGroups($mobile, "pending");
        } catch (MobileAddressException $ex) {
            Webhook::getInstance()
                ->setReply("Mobile phone is not valid! Please try again.")
                ->setState(Webhook::RECRUITING)
                ->addVariable("contact.vars.recruit|");
        }

        return Webhook::getInstance()->getResponse();
    }
}