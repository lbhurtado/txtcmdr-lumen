<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/14/15
 * Time: 14:22
 */

namespace app\Classes;

use Illuminate\Http\Request;
use App\Classes\Telehook;

class TextCommand
{
    const TELEHOOK_NO_STATE = '';

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
        if (Telehook::isAuthorized($request)) {
            $this->conjure();
        }
    }

    protected function conjure()
    {
        switch (Telehook::$state) {
            case TextCommand::TELEHOOK_NO_STATE:
                $this->keyword = Telehook::$keyword;
                $this->parameters = Telehook::$arguments;

                break;

            default:
                $this->keyword = Telehook::$state;
                $pattern = array_get($this->keywords, "$this->keyword.text_pattern");
                $this->parameters = preg_match($pattern, Telehook::$content, $matches)
                    ? array_where($matches, function ($key) {
                        return preg_match("/^[a-zA-Z]*$/", $key);
                    })
                    : array();
                $parameters = array_get($this->keywords, "$this->keyword.http_parameters");
                if (is_array($parameters)) {
                    foreach (array_get($this->keywords, "$this->keyword.http_parameters") as $parameter => $array_shortcut) {
                        $value = array_get(Telehook::$inputs, $array_shortcut);
                        $this->parameters[$parameter] = $value;
                    }
                }

                break;
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