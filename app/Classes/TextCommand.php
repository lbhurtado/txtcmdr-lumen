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

    private $keyword = '';

    private $parameters = [];

    protected $keywords = [
        'recruit' => [
            'pattern' => "/^(?<somenumber>(0|63)(\d{10}))(?<text>.*)$/",
        ],
        'verify' => [
            'pattern' => "/^(?<allegedotp>\d{4})$/",
            'parameters' => [
                //"somenumber" => "/^contact(.|_)vars(.|_)recruit$/",
                "somenumber" => "contact.vars.recruit",
                //"somenumber" => "/^contact_vars_recruit$/",
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
                $this->keyword = Telehook::$keyword;
                $this->parameters = Telehook::$arguments;
                break;
            default:
                $this->keyword = Telehook::$state;
                $pattern = array_get($this->keywords, "$this->keyword.pattern");
                $this->parameters = preg_match($pattern, Telehook::$content, $matches)
                    ? array_where($matches, function ($key) {
                        return preg_match("/^[a-zA-Z]*$/", $key);
                    })
                    : array();

                $parameters = array_get($this->keywords, "$this->keyword.parameters");
                if (is_array($parameters)) {
                    foreach (array_get($this->keywords, "$this->keyword.parameters") as $parameter => $regex) {
                        /*
                        $found_record = array_where(Telehook::$inputs, function ($key) use ($regex) {
                            return preg_match($regex, $key);
                        });
                        if ($found_record)

                            $this->parameters[$parameter] = current($found_record);
                        */
                        $this->parameters[$parameter] = $regex;
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