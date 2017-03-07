<?php
namespace Library\Base;
use Library\Db\MemcachedDb;
use Library\Db\RedisDb;
use Library\Db\Mysql;

class Application
{
    private $_cfg;
    private $_memcached;
    private $_socialNetwork;

    function __construct()
    {
        require(CONFIG_PATH.'/Config.php');

        if(isset($GLOBALS["_CLI"]))
        {
            foreach($GLOBALS["cfgs"] as $host => $cfg)
            {
                if ($host == $GLOBALS['_HOST'])
                {
                    $serverName = $host;
                    $GLOBALS["_CLI_SERVER"] = $host;
                    break;
                }
            }
        }
        else
        {
            $serverName = $_SERVER["SERVER_NAME"];
        }

        $this->_cfg = isset($GLOBALS["cfgs"][$serverName]) ? $GLOBALS["cfgs"][$serverName] : die("Wrong configuration  \n");
    }

    /**
     * @return mixed
     */
    final public function getDbCfg()
    {
        return $this->_cfg["db"];
    }

    /**
     * @return mixed
     */
    final public function getRedisCfg()
    {
        return $this->_cfg["redis"];
    }

    /**
     * @return null
     */
    final public function getLocalDbCfg($lang = null)
    {
        $local_db = !empty($lang) ? $lang : "local_db";
        return isset($this->_cfg[$local_db]) ? $this->_cfg[$local_db] : (isset($this->_cfg["local_db"]) ? $this->_cfg["local_db"] : NULL);
    }
    /**
     * @return mixed
     */
    final public function getSnCfg()
    {
        return $this->_cfg["sn"];
    }

    /**
     * @return mixed
     */
    final public function getSnLocalPath()
    {
        return $this->_cfg["sn"]["local_path"];
    }

    /**
     * @return mixed
     */
    final public function getDbName()
    {
        return $this->_cfg["db"]["database"];
    }

    /**
     * @return mixed
     */
    final public function getIpAddress()
    {
        return $this->_cfg["address"];
    }

    /**
     * @return mixed
     */
    final public function getEmail()
    {
        return $this->_cfg["support_email"];
    }

    /**
     * @return mixed
     */
    final public function getProject()
    {
        return $this->_cfg["project"];
    }

    /**
     *
     * @return MemcachedDb
     */
    final public function getMemcached()
    {
        if ($this->_memcached == null) {
            $memcachedConfig = $this->_cfg["memcached"];
            $prefix = (isset($memcachedConfig["prefix"])) ? $memcachedConfig["prefix"] : '';
            $this->_memcached = new MemcachedDb($memcachedConfig['servers'], $prefix);
        }

        return $this->_memcached;
    }

    /**
     * @return null
     */
    final public function getGearmanCfg()
    {
        if(isset($this->_cfg["gearman"]))
        {
            return $this->_cfg["gearman"];
        }

        return NULL;
    }

    final public function getSocialNetwork()
    {
        if ($this->_socialNetwork == NULL)
        {
            $SN = 'Library\\SN\\' . $this->_cfg["sn"]["socialNetworkClass"];
            $this->_socialNetwork = new $SN ($this->_cfg["sn"]);
        }
        return $this->_socialNetwork;
    }

    /**
     * @return Mysql
     */
    final public function getMainDb()
    {
        $dbCfg = $this->getDbCfg();
        return new Mysql($dbCfg["host"], $dbCfg["user"], $dbCfg["pass"], $dbCfg["database"]);
    }

    /**
     * @param $uid
     * @return Mysql|null
     */
    final public function getShardDb($uid)
    {
        $memcached = $this->getMemcached();
        $shardKey = "shard_".$uid;
        $dbCfgShard = $memcached->get($shardKey);
        if (empty($dbCfgShard))
        {
            $mainDb = $this->getMainDb();
            $res = $mainDb->query("SELECT shard_id, host, user, password as pass, db_name AS `database` FROM game_shard WHERE first_user_id <='".$uid."' AND last_user_id >='".$uid."'");
            $dbCfgShard = $res->fetch();
            $time_out = 5 * 60;
            $memcached->set($shardKey, $dbCfgShard, $time_out);
        }

        if (!empty($dbCfgShard))
        {
            return new Mysql($dbCfgShard["host"], $dbCfgShard["user"], $dbCfgShard["pass"], $dbCfgShard["database"]);
        }

        return NULL;
    }

    /**
     * @return Mysql|null
     */

    final public function getLocalDb($uid)
    {
        $memcached = $this->getMemcached();
        $lang = $memcached->get('lang_' . $uid);

        $dbLocalCfg = $this->getLocalDbCfg($lang);
        $res = NULL;
        if ($dbLocalCfg != NULL)
        {
            $res = new Mysql($dbLocalCfg["host"], $dbLocalCfg["user"], $dbLocalCfg["pass"], $dbLocalCfg["database"]);
        }
        else
        {
            $dbCfg = $this->getDbCfg();
            $localPath = $this->getSnLocalPath();
            $res = new Mysql($dbCfg["host"], $dbCfg["user"], $dbCfg["pass"], $dbCfg["database"]."_".$localPath);
        }
        return $res;
    }

    final public function getLocalDbByPrefix($locale = null)
    {
        $dbLocalCfg = $this->getLocalDbCfg($locale);
        $result = null;
        if ($dbLocalCfg != null) {
            $result = new Mysql($dbLocalCfg["host"], $dbLocalCfg["user"], $dbLocalCfg["pass"], $dbLocalCfg["database"]);
        } else {
            $dbCfg = $this->getDbCfg();
            $localPath = $this->getSnLocalPath();
            $result = new Mysql($dbCfg["host"], $dbCfg["user"], $dbCfg["pass"], $dbCfg["database"]."_".$localPath);
        }
        return $result;
    }

    /**
     * @return RedisDB|null
     */
    final public function getRedis()
    {
        $redisCfg = $this->getRedisCfg();
        if(!empty($redisCfg))
        {
            return new RedisDb($redisCfg["host"], $redisCfg["port"], $redisCfg["prefix"], $redisCfg["db"]);
        }
        else
        {
            return NULL;
        }
    }
}