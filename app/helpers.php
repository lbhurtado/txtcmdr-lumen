<?php // app/helpers.php
if (!function_exists('route_parameter')) {
    /**
     * Get a given parameter from the route.
     *
     * @param $name
     * @param null $default
     * @return mixed
     */
    function route_parameter($name, $default = null)
    {
        $routeInfo = app('request')->route();

        return array_get($routeInfo[2], $name, $default);
    }
}

if (!function_exists('parse_args')) {
    function parse_args($args)
    {
        $out = array();
        $last_arg = null;
        if (is_string($args)) {
            $args = str_replace(array('=', "\'", '\"'), array('= ', '&#39;', '&#34;'), $args);
            $args = str_getcsv($args, ' ', '"');
            $tmp = array();
            foreach ($args as $arg) {
                if (!empty($arg) && $arg != "&#39;" && $arg != "=" && $arg != " ") {
                    $tmp[] = str_replace(array('= ', '&#39;', '&#34;'), array('=', "'", '"'), trim($arg));
                }
            }
            $args = $tmp;
        }
        for ($i = 0, $il = sizeof($args); $i < $il; $i++) {
            if ((bool)preg_match("/^--(.+)/", $args[$i], $match)) {
                $parts = explode("=", $match[1]);
                $key = preg_replace("/[^a-zA-Z0-9-]+/", "", $parts[0]);
                if (isset($args[$i + 1]) && substr($args[$i], 0, 2) == '--') {
                    $out[$key] = $args[$i + 1];
                    $i++;
                } else if (isset($parts[1])) {
                    $out[$key] = $parts[1];
                } else {
                    $out[$key] = true;
                }
                $last_arg = $key;
            } else if ((bool)preg_match("/^-([a-zA-Z0-9]+)/", $args[$i], $match)) {
                $len = strlen($match[1]);
                for ($j = 0, $jl = $len; $j < $jl; $j++) {
                    $key = $match[1]{$j};
                    $val = ($args[$i + 1]) ? $args[$i + 1] : true;
                    $out[$key] = ($match[0]{$len} == $match[1]{$j}) ? $val : true;
                }
                $last_arg = $key;
            } else if ((bool)preg_match("/^([a-zA-Z0-9-]+)$/", $args[$i], $match)) {
                $key = $match[0];
                $out[$key] = true;
                $last_arg = $key;
            } else if ($last_arg !== null) {
                $out[$last_arg] = $args[$i];
            }
        }

        return $out;
        //$str = 'yankee -D "oo\"d l e\'s" -went "2 town 2 buy him-self" -a pony --calledit=" \"macaroonis\' "';
    }
}
