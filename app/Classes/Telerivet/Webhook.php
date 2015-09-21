<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/18/15
 * Time: 07:00
 */

namespace App\Classes\Telerivet;

use Illuminate\Http\Request;
use App\Classes\CustomException;


/**
 * Class Telehook
 * @package App\Classes
 */
class UnauthorizedException extends CustomException
{
}

/**
 * Interface WebhookResponse
 * @package App\Classes\Telerivet
 *
 * JSON formatted response as required by
 * Telerivet webhook for proper handing of
 * replies, forwards and variables.
 */
interface WebhookResponse
{
    function getResponse();
}

class Webhook implements WebhookResponse
{
    /*
     * Webhook variables
     */
    const WHV_ADD_TO_GROUPS = "\$addtogroups";
    const WHV_REMOVE_FROM_GROUPS = "\$removefromgroups";

    const RECRUITING = "recruit";
    const VERIFYING = "verify";

    private static $_instance = null;

    private static $request = null;
    private static $content = null;

    private $handled = 0;

    private static $reply;
    private static $forwards = array();
    private static $variables = array();

    private static $word1 = null;
    private static $remainder1 = null;
    private static $state = null;
    private static $inputs = array();

    public static $keyword;
    public static $arguments;
    private static $content_array = array();


    public static function getInstance()
    {
        if (static::$_instance === null) {
            static::$_instance = new self();
        }

        return static::$_instance;
    }

    public static function isAuthorized(Request $request)
    {
        if ($request->input('secret') === env('TELERIVET_WEBHOOK_SECRET')) {
            if ($request->input('event') == 'incoming_message') {
                static::$request = $request;
                /*
                 * get the text message coming from sms of sender
                 * without the white spaces
                 */
                static::$content = trim($request->input('content'));
                static::$content_array = explode(' ', static::$content);
                /*
                 * get the word1 variable ala Telerivet 'word1' javascript variable
                 */
                static::$word1 = array_shift(static::$content_array);
                /*
                 * get the remainder1 variable ala Telerivet 'remainder1' javascript variable
                 */
                static::$remainder1 = implode(' ', static::$content_array);
                // get the state variable ala Telerivet 'state.id' javascript variable
                static::$state = static::getVariable('state.id');
                // get all http parameters and their corresponding values
                static::$inputs = $request->all();
                static::$arguments = parse_args(static::$content);
                /*
                 * get key word from array of arguments
                 * by getting the first pair of associative array element
                 * and deleting it
                 */
                reset(static::$arguments);
                static::$keyword = key(static::$arguments);
                unset(static::$arguments[static::$keyword]);

                return true;
            }
        } else
            throw new UnauthorizedException();

        return false;
    }

    public static function getContent()
    {
        return static::$content;
    }

    public static function getInputs()
    {
        return static::$inputs;
    }

    public static function getState()
    {
        return static::$state;
    }

    /**
     * @param $variable
     * @return mixed
     *
     * Get the value of the http variable coming from the
     * webhook message of Telerivet.  Sometimes, the system
     * cannot parse variables with '.' so it is necessary
     * to use underscore because somehow the system
     * changes the variable name e.g. state.id becomes state_id.
     */
    public static function getVariable($variable)
    {
        $result = static::$request->input($variable);
        if (!$result)
            $result = static::$request->input(str_replace('.', '_', $variable));
        if (!$result)
            $result = static::$request->input(str_replace('_', '.', $variable));

        return $result;
    }

    /**
     * @return \Laravel\Lumen\Http\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        /* If 0, the script after the Telerivet webhook link will be executed,
         * otherwise it will stop there.
         */
        $this->addVariable("return_value|" . $this->getHandled());

        /* Use the php template 'webhook' view populating it with getData().
         * The required format by Telerivet is json.
         */
        return response(view('webhook', static::getData()), 200, ['Content-Type' => "application/json"]);
    }

    /**
     * @return array
     *
     * Dynamically prepare the data organized by
     * reply, forwards and variable setters as required
     * by Telerivet webhook.  Start with an empty array
     * and then populate it with associative arrays coming
     * from the class variables.
     */
    public static function getData()
    {
        $ar = array();
        if (self::$reply)
            $ar['reply'] = self::$reply;
        if (self::$forwards)
            $ar['forwards'] = self::$forwards;
        if (self::$variables)
            $ar['variables'] = self::$variables;

        return $ar;
    }

    public function setReply($reply)
    {
        static::$reply = $reply;

        return static::getInstance();
    }

    public function setForward($mobile, $missive)
    {
        /*
         * Erase all the previous entries and create
         * a new forward missive.
         */
        static::$forwards = array();
        static::$forwards[$mobile] = $missive;

        return static::getInstance();
    }

    public function addForward($mobile, $missive)
    {
        static::$forwards[$mobile] = $missive;

        return static::getInstance();
    }

    public function setState($state)
    {
        /*
         * Erase all the previous entries and create
         * a new state.id variable.
         */
        return $this->setVariable("state.id|$state");
    }

    /**
     * @param $pipe_delimited_text
     * @return null
     *
     * Generic variable setter
     */
    public function addVariable($pipe_delimited_text)
    {
        $var = explode('|', trim($pipe_delimited_text));
        //static::$variables[$var[0]] = $var[1];
        if (count($var))
            static::$variables[$var[0]] = $var[1] ?: null;

        return static::getInstance();
    }

    /**
     * @param $pipe_delimited_text
     * @return array
     *
     * Generic variable setter but reset the static::$variable
     * to null array
     */
    public function setVariable($pipe_delimited_text)
    {
        if (trim($pipe_delimited_text)) {
            static::$variables = array();
            return static::addVariable($pipe_delimited_text);
        }

        return static::getInstance();
    }

    public function addtoGroups($comma_delimited_text)
    {
        if (is_array($comma_delimited_text))
            $comma_delimited_text = implode(',', $comma_delimited_text);

        return $this->addVariable(static::WHV_ADD_TO_GROUPS . "|$comma_delimited_text");
    }

    public function removefromGroups($comma_delimited_text)
    {
        if (is_array($comma_delimited_text))
            $comma_delimited_text = implode(',', $comma_delimited_text);

        return $this->addVariable("\$removefromgroups|$comma_delimited_text");
    }

    public function addMobileToGroups($mobile, $comma_delimited_text)
    {
        if (is_array($comma_delimited_text))
            $comma_delimited_text = implode(',', $comma_delimited_text);

        return $this->addVariable("\$addmobiletogroups|$mobile:$comma_delimited_text");
    }

    public function transferMobile($mobile, $fromGroup, $toGroup)
    {
        return $this->removeMobileFromGroups($mobile, $fromGroup)->addMobileToGroups($mobile, $toGroup);
    }

    public function removeMobileFromGroups($mobile, $comma_delimited_text)
    {
        if (is_array($comma_delimited_text))
            $comma_delimited_text = implode(',', $comma_delimited_text);

        return $this->addVariable("\$removemobilefromgroups|$mobile:$comma_delimited_text");
    }

    public function loadMobile($mobile)
    {
        return $this->addVariable("\$loadmobile|$mobile");
    }

    public function getHandled()
    {
        return $this->handled;
    }

    public function setHandled($handled)
    {
        $this->handled = (int)(bool)($handled);

        return $this;
    }
}