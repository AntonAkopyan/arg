<?php
/**
 * Created by IntelliJ IDEA.
 * User: volodymyr.ivchyk
 * Date: 8/25/16
 * Time: 1:00 PM
 */

namespace Library\Base;
use Library\Game;

class BaseService
{
    protected $userId;
    protected $_socialNetwork;
    protected $socialId;

    public static function getInstance()
    {
        static $instance;
        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Класс отвечает за инициализацию юзера!
     *
     * @param $uid
     * @param $class
     * @param $method
     * @param $param
     * @param null $clientData
     * @return array
     * @throws \Exception
     */
    public function execute($uid, $class, $method, $param, $clientData = null)
    {
        if (empty($uid)) {
            throw new \Exception("Method called without uid parameter", 1);

        }

        $this->socialId = $uid;
        $memcached = Game::getApp()->getMemcached();

//        if ($this->is_banned()) {
//            throw new Exception("User is banned", 666);
//        }

        $this->userId = Game::init($uid);

        if (empty($this->userId)) {
            throw new \Exception("Missed user_id", 2);

        }

        $sessionKeyCached = $memcached->get('session_key_' . $this->userId);
//        if (!isset($clientData['session_key'] ) || $clientData['session_key'] != $sessionKeyCached) {
//            throw new \Exception("Wrong session key", 999);
//        }

//        $this->user = Application::getInstance()->getUser($user_id);

//        $attack = false;
//        foreach ($obj as $ob) {
//            if (is_array($ob)) {
//                foreach ($ob as $o) {
//                    if ($attack = $this->sqlHardFilter($o)) break 2;
//                }
//            } else {
//                if ($attack = $this->sqlHardFilter($ob)) break;
//            }
//        }

        $class_name = 'Game\\Services\\'.$class;

        $function = new \ReflectionMethod($class_name, $method);
        $need_params = $function->getParameters();

        $set_args = [];
        foreach ($need_params as $item) {
            $set_args[] = (isset($param[$item->getName()])) ? $param[$item->getName()] : null;
        }

        $result = $function->invokeArgs(new $class_name(), $set_args);

        $data = array();
        if (is_array($result) || is_object($result)) {
            $data = $result;
            $result = 0;
        }

//        if (!empty($result)) {
//            $this->save_error($userId, $result, $uid);
//        }

        $gameVersion = $memcached->get('version');

        return [
            'data' => $data,
            'error' => $result,
            'version' => $gameVersion,
            'server_time' => time()
        ];
    }
}

