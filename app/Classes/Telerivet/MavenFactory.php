<?php
/**
 * Created by PhpStorm.
 * User: lbhurtado
 * Date: 9/20/15
 * Time: 14:28
 */

namespace App\Classes\Telerivet;

use Illuminate\Http\Request;
use App\Classes\Telerivet\TextCommand;

class MavenFactory
{
    const PREFIX = __NAMESPACE__ . "\\Keywords\\";

    public static function getMaven(Request $request)
    {
        $params = array();
        try {
            $command = new TextCommand($request);
            $params = array($command);
            $class = static::PREFIX . $command->getKeyword();
            $reflection_class = new \ReflectionClass($class);
        }
        catch (UnauthorizedException $ex) {
            $class = static::PREFIX . "Unauthorized";
            $reflection_class = new \ReflectionClass($class);
        }
        catch (\Exception $ex) {
            $class = static::PREFIX . "Nuisance";
            $reflection_class = new \ReflectionClass($class);
        }

        return $reflection_class->newInstanceArgs($params);
    }
}