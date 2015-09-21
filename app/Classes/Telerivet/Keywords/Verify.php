<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/20/15
 * Time: 15:50
 */

namespace app\Classes\Telerivet\Keywords;

use App\Classes\CustomException;
use App\Classes\Telerivet\Maven;
use App\Classes\Telerivet\Webhook;
use App\Classes\Parse\Anyphone;
use App\Classes\Telerivet\TextCommand;

class VerifyException extends CustomException
{

}

class Verify extends Maven
{
    protected $defaultReply = "OTP is valid.";

    protected $defaultNextState = Webhook::RECRUITING;

    /**
     * @param TextCommand $command
     * @throws VerifyException
     *
     * Override __construct to include verification of OTP.
     * Will be using parameters from magic method __set in
     * TextCommand class. It raises an exception to allow
     * the MavenFactory class to instantiate the Invalid
     * class.
     */
    public function __construct(TextCommand $command)
    {
        parent::__construct($command);

        if (!Anyphone::getInstance()->validateUser(
            $this->getCommand()->somenumber, // from magic method __set
            $this->getCommand()->allegedotp) // from magic method __set
        ) throw new VerifyException("Huy, may mali dito sa __construct ng Maven!");

        /*
         * Scramble the password once the previous entry succeeds.
         * Otherwise, the OTP should be untouched for retries.
         */
        Anyphone::getInstance()->scrambleParseUserPassword();
    }

    public function getResponse()
    {
        /*
         * If Anyphone::getInstance()->validateUser succeeds,
         * $somenumber will become accessible as getMobile()
         */
        $mobile = Anyphone::getInstance()->getMobile();
        Webhook::getInstance()
            ->setForward($mobile, "Your OTP is valid. Congratulations!")
            ->removeMobileFromGroups($mobile, 'pending')
            ->addMobileToGroups($mobile, "recruit");

        return Webhook::getInstance()
            ->addVariable("contact.vars.recruit|")
            ->getResponse();
    }
}