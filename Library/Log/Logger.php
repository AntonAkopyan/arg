<?php
/**
 * Created by IntelliJ IDEA.
 * User: volodymyr.ivchyk
 * Date: 10/3/16
 * Time: 2:45 PM
 */

namespace Library\Log;

use Library\Game;

class Logger
{
    const SERVER_ERROR_URL    = 'http://mom.joyrocks.com/ws-error/';

    public static function log($message)
    {
        $app = Game::getApp();
        $project = $app->getProject();

        if ($project) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, self::SERVER_ERROR_URL . $project . '-set-error');
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, array(
                'error_object' => json_encode(array(
                        'message' => $message,
                        'time' => self::getElapsedTime()
                    )
                )));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_exec($curl);
            curl_close($curl);
        }

    }

    private static function getElapsedTime()
    {
        return microtime(true) - GAME_BEGIN_TIME;
    }
}