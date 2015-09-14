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

    private static $_instance = null;

    private $request;

    private $command = '';

    private $arguments = [];

    private $commands = [
        'recruit' => [
            'pattern' => "/^(?<somenumber>(0|63)(\d{10}))(?<text>.*)$/",
        ],
        'verify' => [
            'pattern' => "/^(?<allegedotp>\d{4})$/",
            'parameters' => [
                //"somenumber" => "/^contact(.|_)vars(.|_)recruit$/",
                "somenumber" => "/^contact.vars.recruit$/",
            ],
        ],
    ];

    public function __construct(Request $request)
    {
        if (Telehook::isAuthorized($request)) {
            $this->request = $request;
            $this->organize();
        }
    }

    protected function organize()
    {
        switch (Telehook::$state) {
            case TELEHOOK_NO_STATE:
                $this->command = Telehook::$keyword;
                $this->arguments = Telehook::$arguments;
                break;
            default:
                $this->command = Telehook::$state;
                $pattern = array_get($this->commands, "$this->command.pattern");
                $this->arguments = preg_match($pattern, Telehook::$content, $matches)
                    ? array_where($matches, function ($key) {
                        return preg_match("/^[a-zA-Z]*$/", $key);
                    })
                    : array();

                $parameters = array_get($this->commands, "$this->command.parameters");
                if (is_array($parameters)) {
                    foreach (array_get($this->commands, "$this->command.parameters") as $parameter => $regex) {
                        $found_record = array_where(Telehook::$inputs, function ($key) use ($regex) {
                            return preg_match($regex, $key);
                        });
                        if ($found_record)
                            $this->arguments[$parameter] = current($found_record);
                    }
                }
                break;
        }
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getArguments()
    {
        return $this->arguments;
    }
}