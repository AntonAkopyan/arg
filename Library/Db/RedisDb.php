<?php
/**
 * Created by IntelliJ IDEA.
 * User: volodymyr.vlasyuk
 * Date: 10/1/14
 * Time: 10:53 PM
 */

namespace Library\Db;

class RedisDb
{
    private $_key_prefix;
    private $_linkIdentifier;
    private $_databases;

    static private $_connections = array();

    /**
     * @param $host
     * @param $port
     * @param $prefix
     * @param $databases
     */
    function __construct($host, $port, $prefix, $databases)
    {
        $key = $host.$port;
        $this->_key_prefix = $prefix.'_';
        $this->_databases = $databases;

        if(!empty(self::$_connections[$key]))
        {
            $this->_linkIdentifier = self::$_connections[$key];
        }
        else
        {
            @self::$_connections[$key] = $this->_linkIdentifier = new \Redis();
            if(!$this->_linkIdentifier->connect($host, $port))
            {
                die ("Could not connect to host <b>\"".$host."\"</b>");
            }
            $this->_linkIdentifier->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        }
    }

    /**
     * @param $db
     * @return bool
     */
    public function select($db)
    {
        if(!isset($this->_databases[$db]))
        {
            return FALSE;
        }

        return $this->_linkIdentifier->select($this->_databases[$db]);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value)
    {
        return $this->_linkIdentifier->set($this->_key_prefix.$key, $value);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->_linkIdentifier->get($this->_key_prefix.$key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function delete($key)
    {
        return $this->_linkIdentifier->delete($this->_key_prefix.$key);
    }
}