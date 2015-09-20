<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/14/15
 * Time: 14:13
 */

namespace App\Classes\Telerivet;

use Illuminate\Http\Request;
use App\Classes\Telerivet\Webhook;
use App\Classes\Telerivet\TextCommand;

interface WebhookResponse
{
    function getResponse();
}

abstract class Maven implements WebhookResponse
{
    private $_data;

    private $command;

    protected $description;

    protected $defaultReply;

    protected $defaultNextState;

    protected $addtoGroups;

    public function __construct(TextCommand $command)
    {
        $this->command = $command;
        $this->_data = $this->getCommand()->getParameters();
        Webhook::getInstance()
            ->setReply($this->getDefaultReply())
            ->setState($this->getDefaultNextState());
    }

    public function __set($property, $value)
    {
        return $this->_data[$property] = $value;
    }

    public function __get($property)
    {
        return array_key_exists($property, $this->_data)
            ? $this->_data[$property]
            : null;
    }

    protected function getCommand()
    {
        return $this->command;
    }

    protected function getDescription()
    {
        return $this->description;
    }

    protected function getDefaultReply()
    {
        if (array_get($this->getCommand()->getParameters(), 'help'))
            return $this->getDescription();
        else
            return $this->defaultReply;
    }

    protected function getDefaultNextState()
    {
        if (array_get($this->getCommand()->getParameters(), 'help'))
            return Webhook::$state;
        else
            return $this->defaultNextState;
    }

    protected function getAddtoGroups()
    {
        if (array_get($this->getCommand()->getParameters(), 'help'))
            return '';
        else
            return $this->addtoGroups;
    }
}










