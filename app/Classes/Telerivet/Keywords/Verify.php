<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/20/15
 * Time: 15:50
 */

namespace app\Classes\Telerivet\Keywords;

use \App\Classes\Telerivet\Maven;
use App\Classes\Telerivet\Webhook;
use App\Classes\Parse\Anyphone;

class Verify extends Maven
{
    public function getResponse()
    {
        $somenumber = $this->somenumber; //from magic method __set
        $allegedotp = $this->allegedotp; //from magic method __set

        if (!$allegedotp) {
            return Webhook::getInstance()->getDebugResponse("Error! No allegedotp parameter in http.");
        }

        if (!$somenumber) {
            return Webhook::getInstance()->getDebugResponse("Error! No somenumber parameter in http.");
        }

        try {
            if (Anyphone::getInstance()->validateUser($somenumber, $allegedotp)) {
                Anyphone::getInstance()->scrambleParseUserPassword();
                $mobile = Anyphone::getInstance()->getMobile();
                Webhook::getInstance()
                    ->setReply("OTP is valid.")
                    ->setForward($mobile, "Your OTP is valid. Congratulations!")
                    ->setState(Webhook::RECRUITING)
                    ->addVariable("contact.vars.recruit|")
                    ->removeMobileFromGroups($mobile, 'pending')
                    ->addMobileToGroups($mobile, "recruit");
            } else {
                throw new ParseException();
            }

        } catch (MobileAddressException $ex) {
            return Webhook::getInstance()->getDebugResponse("Error! Mobile number is not valid.");
        } catch (ParseException $ex) {
            Webhook::getInstance()
                ->setReply("OTP is not valid! Please try again.")
                ->setState(Webhook::VERIFYING)
                ->addVariable("contact.vars.recruit|$mobile");
        }

        return Webhook::getInstance()->getResponse();

    }
}