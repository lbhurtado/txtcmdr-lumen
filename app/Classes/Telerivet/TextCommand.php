<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/14/15
 * Time: 14:22
 */

namespace App\Classes\Telerivet;

use Illuminate\Http\Request;
use App\Classes\Telerivet\Webhook;
use App\Classes\CustomException;

class TextCommandException extends CustomException
{
}

class TextCommand
{
    const WEBHOOK_CONTACT_NO_STATE = '';

    protected $keywords = [
        'recruit' => [
            'text_pattern' => "/^(?<somenumber>(0|63)(\d{10}))(?<text>.*)$/", //used for preg_match
        ],
        'verify' => [
            'text_pattern' => "/^(?<allegedotp>\d{4})$/",
            'http_parameters' => [
                "somenumber" => "contact.vars.recruit", //used for array_get contact[vars][recruit]
            ],
        ],
    ];

    private $keyword = '';

    private $parameters = [];

    public function __construct(Request $request)
    {
        if (Webhook::isAuthorized($request))
            $this->conjure();
        else
            throw new TextCommandException();
    }

    protected function conjure()
    {
        switch (Webhook::$state) {
            case TextCommand::WEBHOOK_CONTACT_NO_STATE:
                $this->keyword = Webhook::$keyword;
                $this->parameters = Webhook::$arguments;
                break;
            default:
                $this->keyword = Webhook::$state;
                $pattern = array_get($this->keywords, "$this->keyword.text_pattern");
                if ($pattern) {
                    $this->parameters = preg_match($pattern, Webhook::$content, $matches)
                        ? array_where($matches, function ($key) {
                            return preg_match("/^[a-zA-Z]*$/", $key);
                        })
                        : array();
                    $parameters = array_get($this->keywords, "$this->keyword.http_parameters");
                    if (is_array($parameters)) {
                        foreach (array_get($this->keywords, "$this->keyword.http_parameters")
                                 as $parameter => $array_shortcut) {
                            $value = array_get(Webhook::$inputs, $array_shortcut);
                            $this->parameters[$parameter] = $value;
                        }
                    }
                } else
                    throw new TextCommandException();
        }
    }

    public function getKeyword()
    {
        return $this->keyword;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}