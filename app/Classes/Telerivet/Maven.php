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

abstract class Maven implements WebhookResponse
{
    private $command;

    protected $description;

    protected $defaultReply;

    protected $defaultNextState;

    protected $addtoGroups;

    public function __construct(TextCommand $command)
    {
        $this->command = $command;

        Webhook::getInstance()
            ->setReply($this->getDefaultReply())
            ->setState($this->getDefaultNextState())
            ->addtoGroups($this->addtoGroups);
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
            return Webhook::getState();
        else
            return $this->defaultNextState;
    }

    protected function getAddToGroups()
    {
        if (array_get($this->getCommand()->getParameters(), 'help'))
            return '';
        else
            return $this->addtoGroups;
    }
}










