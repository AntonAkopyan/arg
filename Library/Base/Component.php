<?php
/**
 * Created by IntelliJ IDEA.
 * User: volodymyr.vlasyuk
 * Date: 10/1/14
 * Time: 7:43 PM
 */

namespace Library\Base;

use Library\Game;
use Library\Log\Logger;
use League\Fractal\Resource\Collection;
use League\Fractal\Manager;

class Component
{
    public static $user_id;
    public static $social_id;

    public static $now;

    protected static $_initialized = FALSE;
    protected static $_neighbour = FALSE;

    private static $DB = array();
    private static $userSwitch = array();

    private static $fractal;

    /**
     *
     */
    public function __construct()
    {
        if(!static::$_initialized)
        {
            die('Error: Game is not initialized.');
        }
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        static $instance;
        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * @param $social_id
     * @return null
     */
    public static function initialize($social_id)
    {
        if  (static::$_initialized) return FALSE;

        $memcache = Game::getApp()->getMemcached();

        $keyUID = "UID" . $social_id . "_userId";
        static::$user_id = $memcache->get($keyUID);

//        if (empty(static::$user_id)) {
            $main_db = Game::getApp()->getMainDb();
            $result = $main_db->query('SELECT user_id
                                       FROM game_user
                                       WHERE social_id = "' . $social_id . '"');

            if ($row = $result->fetch()) {
                static::$user_id = $row['user_id'];
                static::$social_id = $social_id;
                static::$now = time();
                static::$_initialized = TRUE;

                $memcache->set($keyUID, static::$user_id, 3600);
            } else {
                static::$user_id = NULL;
            }
//        } else {
//            static::$social_id = $social_id;
//            static::$now = time();
//            static::$_initialized = TRUE;
//        }

        return static::$user_id;
    }

    public static function initializeEditor($pass)
    {
        if  (static::$_initialized) return FALSE;

        if ($pass == '1234') {
            static::$user_id = 0;
            static::$now = time();
            static::$_initialized = TRUE;
        }

        return static::$_initialized;
    }

    /**
     * @param $hash
     * @return bool
     */
    public static function initializeAdminTool($hash)
    {
        if  (static::$_initialized) return FALSE;

        if ($hash == 'crazydespot') {
            static::$user_id = 0;
            static::$now = time();
            static::$_initialized = TRUE;
        }

        return static::$_initialized;
    }

    /**
     * @param $user_id
     * @return bool
     */
    public static function setNeighbour($user_id)
    {
        if(static::$_initialized)
        {
            self::$userSwitch['user_id'] = static::$user_id;
            self::$userSwitch['social_id'] = static::$social_id;

            static::$user_id = $user_id;
            static::$_neighbour = TRUE;

            return TRUE;
        }

        return FALSE;
    }

    /**
     * @return bool
     */
    public static function unsetNeighbour()
    {
        if(!empty(self::$userSwitch)) {
            static::$user_id = self::$userSwitch['user_id'];
            static::$social_id = self::$userSwitch['social_id'];
            static::$_neighbour = FALSE;

            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param $table
     * @return null
     */
    protected function _get($table)
    {
         if ($this->redis !== NULL && $this->redis->select($table)) {
            $data = $this->redis->get($table.'_'.static::$user_id);

            if(is_array($data) && !empty($data)) {
                return $data;
            } else {
                $result = $this->shard_db->query("SELECT *
                                                  FROM `".$table."`
                                                  WHERE `user_id` = '" . static::$user_id . "'");
                if($row = $result->fetch())
                {
                    unset($row['user_id']);

                    return $row;
                }
                else
                {
                    return NULL;
                }
            }
        } else {
            $result = $this->shard_db->query("SELECT *
                                              FROM `".$table."`
                                              WHERE `user_id` = '" . static::$user_id . "'");
            if($row = $result->fetch()) {
                unset($row['user_id']);

                return $row;
            } else {
                return NULL;
            }
        }
    }

    /**
     * @param $table
     * @param $data
     */
    protected function _set($table, array $data = array())
    {
        if($this->redis !== NULL && $this->redis->select($table)) {

            if(rand(1, 100) == 1) {
                $this->_reservedShardSave($table, $data);
            }

            return $this->redis->set($table.'_'.static::$user_id, $data);
        } else {
            $set = '';
            foreach($data as $k => $v) {
                if($k == 'user_id') continue;

                $set .= ('`' . $k . '`' . '=' . "'". $v ."',");
            }

            $set = substr($set, 0, -1);

            $result = $this->shard_db->query("UPDATE `".$table."`
                                              SET ".$set."
                                              WHERE `user_id` = '" . static::$user_id . "'");

            return $result->getResult();
        }
    }

    /**
     * @param $table
     * @param array $data
     */
    private function _reservedShardSave($table, array $data = array())
    {
        $set = '';

        foreach($data as $k => $v) {
            if($k == 'user_id') continue;

            $set .= ('`' . $k . '`' . '=' . "'". $v ."',");
        }

        $set = substr($set, 0, -1);
        $this->shard_db->query("UPDATE `".$table."`
                                SET ".$set."
                                WHERE `user_id` = '" . static::$user_id . "'");
    }

    /**
     * @param $property
     * @return mixed
     */
    function __get($property)
    {
        $key = ($property == 'shard_db') ? $property.static::$user_id : $property;

        if(array_key_exists($key, self::$DB)) {
            return self::$DB[$key];
        } else {
            switch($property)
            {
                case 'local':
                    self::$DB[$key] = Game::getApp()->getLocalDb(static::$user_id);
                    break;
                case 'main_db':
                    self::$DB[$key] = Game::getApp()->getMainDb();
                    break;
                case 'shard_db':
                    self::$DB[$key] = Game::getApp()->getShardDb(static::$user_id);
                    break;
                case 'memcache':
                    self::$DB[$key] = Game::getApp()->getMemcached();
                    break;
                case 'redis':
                    self::$DB[$key] = Game::getApp()->getRedis();
                    break;
            }
            return self::$DB[$key];
        }
    }

    public function numeric(&$row){
        foreach ($row as &$val) {
            if (is_numeric($val))
                $val = $val + 0;
        }
    }

    public function renameColl(&$row, $old_name, $new_name){
        $row[$new_name] = $row[$old_name];
        unset($row[$old_name]);
    }

    /**
     * @param $message
     */
    public function triggerError($message)
    {
        Logger::log($message, 'COMPONENT');
    }

    /**
     * @param $collection
     * @param $callback
     * @return mixed
     */
    protected function respondWithCollection($collection, $callback)
    {
        $fractal = $this->getFractalManager();
        $resource = new Collection($collection, $callback);

        $rootScope = $fractal->createData($resource);

        return $this->respondWithArray($rootScope->toArray());
    }

    /**
     * @param array $array
     * @param array $headers
     * @return mixed
     */
    protected function respondWithArray(array $array, array $headers = [])
    {
        return $array['data'];
    }

    /**
     * @return Manager
     */
    public function getFractalManager()
    {
        if (!self::$fractal) {
            self::$fractal = new Manager();
        }
        return self::$fractal;
    }

    protected function increaseCounter($userId, $count = 1)
    {
        $social = Game::getApp()->getSocialNetwork();
        if($social->setCounters(null))
        {
            if(!is_array($userId)) {
                $userId = [$userId];
            }

            $usersAndCounters = array();
            $mainDb = Game::getApp()->getMainDb();
            $result = $mainDb->query("SELECT social_id FROM game_user WHERE user_id IN (".implode(',', $userId).")");
            //
            while($row = $result->fetch())
            {
                $userCounter = $this->memcache->get('vk_menu_'.$row['id_social']);

                $savedCount = (!empty($userCounter)) ? $userCounter['count'] : 0;
                $savedCount = ($count == 0) ? 0 : $savedCount + $count;

                $usersAndCounters[] = array(
                    'id' => $row['id_social'],
                    'counter' => $savedCount
                );
                $this->memcache->set('vk_menu_'.$row['id_social'], array('count' => $savedCount), 84600);

            }
            $social->setCounters($usersAndCounters);
        }
    }
}