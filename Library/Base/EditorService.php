<?php
/**
 * Created by IntelliJ IDEA.
 * User: volodymyr.ivchyk
 * Date: 8/25/16
 * Time: 1:00 PM
 */

namespace Library\Base;
use Library\Game;

class EditorService
{
    public function execute($pass, $class, $method, $param)
    {
        Game::initEditor($pass);

        $memcached = Game::getApp()->getMemcached();

        $function = new \ReflectionMethod('\\Game\\Services\\'.$class, $method);
        $need_params = $function->getParameters();

        $set_args = [];
        foreach ($need_params as $item) {
            $set_args[] = $param[$item->getName()];
        }

        $result = call_user_func_array(array('self', $method), $set_args);

        $data = array();
        if (is_array($result) || is_object($result)) {
            $data = $result;
            $result = 0;
        }

        $gameVersion = $memcached->get('version');

        return [
            'data' => $data,
            'error' => $result,
            'version' => $gameVersion,
            'server_time' => time()
        ];
    }
}

