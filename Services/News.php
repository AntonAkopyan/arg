<?php

/**
 * Created by IntelliJ IDEA.
 * User: volodymyr.ivchyk
 * Date: 10/24/16
 * Time: 5:11 PM
 */
namespace Game\Services;

use Game\JWT\JWT;
use Library\Game;

class News
{
    public function get($lang)
    {
        $result = [
            'data' => [],
            'error' => 0
        ];

        $mainDb = Game::getApp()->getMainDb();
        $newsQuery = $mainDb->paramQuery("SELECT * FROM news");

        while($row = $newsQuery->fetch()) {
            $row["title"] = $row["title"] . '_' . $lang;
            $result["data"][] = $row;
        }

        return $result;
    }
}