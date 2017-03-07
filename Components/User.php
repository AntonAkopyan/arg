<?php
/**
 * Created by IntelliJ IDEA.
 * User: volodymyr.vlasyuk
 * Date: 10/1/14
 * Time: 7:30 PM
 */

namespace Game\Components;

use Library\Game;
use Library\Base\Component;

class User extends Component
{
    private $_user_data = array();
    private static $_is_tester;

    const RESOURCE_COIN         = 1;
    const RESOURCE_BB           = 2;
    const RESOURCE_FERTILIZER   = 3;
    const RESOURCE_XP           = 4;
    const RESOURCE_PEOPLE       = 9;
    const RESOURCE_PEOPLE_LIMIT = 10;

    /**
     * @return array of user main data
     */
    public function getData()
    {
        if(empty($this->_user_data[static::$user_id])) {
            $result = $this->main_db->query("SELECT *
                                             FROM game_user_config
                                             WHERE user_id = '" . static::$user_id . "'");

            if($user = $result->fetch()) {
                $user['user_xp']           = $this->getXp();
                $user['social_id']         = static::$social_id;
                $user['level']             = Level::dataLevel($user['user_xp']);
                $user['xp_level_start']    = ($user['level'] == 1) ? 0 : Level::xpLevelStart($user['level']);
                $user['xp_level_end']      = Level::xpLevelStart($user['level'] + 1);
                $user['xp_next_level_end'] = Level::xpLevelStart($user['level'] + 2);
            } else {
                return NULL;
            }

            $this->_user_data[static::$user_id] = $user;
        }

        return $this->_user_data[static::$user_id];
    }

    /**
     * @param null $resourceId
     * @return array|bool
     */
    public function getResources($resourceId = null)
    {
        $resources = array();
        $result = $this->shard_db->query("SELECT *
                                          FROM game_user_resource
                                          WHERE user_id = " . static::$user_id);

        while($row = $result->fetch())
        {
            $resources[$row['resource_id']] = array(
                'resource_count' => $row['resource_count'],
                'resource_id' => $row['resource_id']
            );
        }

        if ($resourceId !== null) {
            if (!isset($resources[$resourceId])) {
                return false;
            }
            return $resources[$resourceId]['resource_count'];
        }

        return $resources;
    }

    /**
     * @param $resourceID
     * @param $count
     * @return string
     */
    public function increaseResource($resourceID, $count)
    {
        $result = $this->shard_db->query("INSERT INTO game_user_resource (user_id, resource_id, resource_count)
                                          VALUES ('" . static::$user_id . "', '" . $resourceID . "', '".$count."')
			                              ON DUPLICATE KEY UPDATE resource_count = resource_count + ".$count);
        if ($result->getResult()) {
            return TRUE;
        }

        return FALSE;
    }

    public function openNews($newsId)
    {
        $result = $this->shard_db->query("INSERT INTO game_user_news (user_id, news_id)
                                          VALUES ('" . static::$user_id . "', '" . $newsId . "')
			                              ON DUPLICATE KEY UPDATE news_id = news_id + ".$newsId);
        if ($result->getResult()) {
            return TRUE;
        }

        return FALSE;
    }

    public function deleteUser()
    {
        $this->main_db->query("DELETE FROM game_user WHERE user_id = " . static::$user_id);
        $this->main_db->query("DELETE FROM game_user_config WHERE user_id = " . static::$user_id);
        $this->shard_db->query("DELETE FROM game_user_object WHERE user_id = " . static::$user_id);
        $this->shard_db->query("DELETE FROM game_user_object_inventory WHERE user_id = " . static::$user_id);
        $this->shard_db->query("DELETE FROM game_user_quest WHERE user_id = " . static::$user_id);
        $this->shard_db->query("DELETE FROM game_user_quest_task WHERE user_id = " . static::$user_id);
        $this->shard_db->query("DELETE FROM game_user_resource WHERE user_id = " . static::$user_id);
        $this->shard_db->query("DELETE FROM game_user_storage WHERE user_id = " . static::$user_id);
        $this->shard_db->query("DELETE FROM game_user_wild_object WHERE user_id = " . static::$user_id);
        $this->shard_db->query("DELETE FROM game_user_xp WHERE user_id = " . static::$user_id);
        $this->shard_db->query("DELETE FROM game_user_zone WHERE user_id = " . static::$user_id);
        return TRUE;
    }

    public function resetQuest()
    {
        $this->shard_db->query("DELETE FROM game_user_quest WHERE user_id = " . static::$user_id);
        $this->shard_db->query("DELETE FROM game_user_quest_task WHERE user_id = " . static::$user_id);
        return TRUE;
    }

    /**
     * @param $resourceID
     * @param $count
     * @return string
     */
    public function decreaseResource($resourceID, $count)
    {
        $result = $this->shard_db->query("UPDATE game_user_resource
                                          SET resource_count = resource_count - ".$count."
                                          WHERE user_id = '" . static::$user_id . "'
                                          AND resource_id='" . $resourceID . "'
                                          AND resource_count >= " .$count);
        if ($result->getResult()) {
            return true;
        }

        static::triggerError('!decreaseResource');
        return false;
    }

    /**
     * @param $count
     * @return string
     */
    public function increasePeople($count)
    {
        return $this->increaseResource(self::RESOURCE_PEOPLE, $count);
    }

    /**
     * @param $count
     * @return string
     */
    public function decreasePeople($count)
    {
        return self::decreaseResource(self::RESOURCE_PEOPLE, $count);
    }

    /**
     * @param $count
     * @return string
     */
    public function increasePeopleLimit($count)
    {
        return $this->increaseResource(self::RESOURCE_PEOPLE_LIMIT, $count);
    }

    /**
     * @param $count
     * @return string
     */
    public function decreasePeopleLimit($count)
    {
        return $this->decreaseResource(self::RESOURCE_PEOPLE_LIMIT, $count);
    }

    /**
     * @param $count
     * @return bool
     */
    public function increaseFertilizer($count)
    {
        return $this->increaseResource(self::RESOURCE_FERTILIZER, $count);
    }

    /**
     * @param $objectUpgradeID
     * @param $amount
     * @return bool|string
     */
    public function increaseObject($objectUpgradeID, $amount)
    {
        $result = $this->shard_db->query("INSERT INTO game_user_object_inventory (user_id, object_upgrade_id, amount)
                                          VALUES ('" . static::$user_id . "', '" . $objectUpgradeID . "', '".$amount."')
                                          ON DUPLICATE KEY UPDATE amount = amount + ".$amount."");
        if ($result->getResult())
        {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param $objectUpgradeID
     * @param $amount
     * @return bool|string
     */
    public function decreaseResourceObject($objectUpgradeID, $amount)
    {
        $result = $this->shard_db->query("UPDATE game_user_object_inventory
                                          SET amount = amount - ".$amount."
                                          WHERE user_id = '" . static::$user_id . "' AND object_upgrade_id = '" . $objectUpgradeID . "' AND amount >= " . $amount);
        if ($result->getResult())
        {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @return mixed
     */
    public function getXp()
    {
        $result = $this->_get('game_user_xp');
        return $result['user_xp'];
    }

    /**
     * @return int|string
     */
    public function getLevel()
    {
        return Level::dataLevel($this->getXp());
    }

    /**
     * @param $count
     * @return int
     */
    public function increaseXp($count)
    {
        $xp = $this->_get('game_user_xp');

        $xp['user_xp'] += $count;
        $xp['user_xp'] = (int) $xp['user_xp'];

        $this->_set('game_user_xp', $xp);

        return $xp['user_xp'];
    }

    /**
     * @param $step
     * @return mixed
     */
    public function setTutorialStep($step)
    {
        $result = $this->main_db->query("UPDATE game_user_config
                                         SET tutorial = '" . $step . "'
                                         WHERE user_id = '" . static::$user_id . "';");

        if($result->getResult())
        {
            return TRUE;
        }

        return TRUE;
    }

    /**
     * @param $step
     * @return bool
     */
    public function setZone($zoneID)
    {

        $result = $this->shard_db->query("INSERT INTO game_user_zone (user_id, zone_id)
                                          VALUES ('" . static::$user_id . "', '" . $zoneID . "')
			                              ON DUPLICATE KEY UPDATE zone_id = zone_id");

        if($result->getResult())
        {
            return TRUE;
        }

        return TRUE;
    }

    /**
     * @return int
     */
    public function isTester()
    {
        if(self::$_is_tester !== NULL)
        {
            return self::$_is_tester;
        }

        $testers = self::getTesters();

        return self::$_is_tester = (isset($testers[static::$social_id])) ? 1 : 0;
    }

    /**
     * @return array
     */
    public static function getTesters()
    {
        $memcache = Game::getApp()->getMemcached();
        $return = $memcache->get('testers');

        if(empty($return))
        {
            $return = array();

            $main_db = Game::getApp()->getMainDb();
            $result = $main_db->query('SELECT *
                                       FROM game_testers');
            while($row = $result->fetch())
            {
                $return[$row['uid']] = $row['show_panel'];
            }
            $memcache->set('testers', $return, 300);
        }

        return $return;
    }

    /**
     * @return mixed
     */
    public function getID()
    {
        return static::$user_id;
    }

    /**
     * @return mixed
     */
    public function getSocialID()
    {
        return static::$social_id;
    }

    /**
     * Check if user has enough resource
     *
     * @param $resourceId
     * @param $resourceCount
     * @return bool
     */
    public function checkResource($resourceId, $resourceCount)
    {
        $resourceId = filter_var($resourceId);
        $resourceHas = $this->getResources($resourceId);
        if ($resourceHas && $resourceHas >= $resourceCount) {
            return true;
        }
        return false;

    }
}