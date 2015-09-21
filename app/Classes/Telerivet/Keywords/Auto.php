<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/21/15
 * Time: 19:05
 */

namespace app\Classes\Telerivet\Keywords;


use \App\Classes\Telerivet\Maven;
use App\Classes\Telerivet\Webhook;


abstract class Auto extends Maven
{
    protected $description = "Help";

    protected $defaultReply = "autoreply";

    protected $defaultNextState = "autostate";

    protected $addtoGroups = "autogroup";

    protected $name;

    public function __construct(){
        $reflect = new \ReflectionClass($this);
        $this->name = $reflect->getShortName();
    }

    public function getResponse()
    {
        return Webhook::getInstance()
            ->addtoGroups($this->getAddtoGroups())
            ->getResponse();
    }
}