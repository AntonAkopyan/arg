<?php

namespace Library;

use Game\Components\NewPlayer;
use Library\Base\Application;
use Library\Base\Component;
use Library\Base\Container;
use Library\Log\Logger;

require(__DIR__ . '/BaseGame.php');

class Game extends BaseGame
{
    const CACHE_TIMEOUT = 300; //5 minutes.
    const TRACE_SLICE_OFFSET = 2;

    private static $loader;

    /**
     * @return Application
     */
    public static function getApp()
    {
        if (empty(static::$app)) {
            static::$app = new Application();
        }
        return static::$app;
    }

    /**
     * @return Container
     */
    public static function getContainer()
    {
        if (empty(static::$container)) {
            static::$container = new Container();
        }
        return static::$container;
    }

    /**
     * @param $socialId
     * @return null
     */
    final public static function init($socialId)
    {
        return Component::initialize($socialId);
    }

    final public static function initEditor($pass)
    {
        return Component::initializeEditor($pass);
    }

    /**
     * @param $hash
     * @return bool
     */
    final public static function initAdminTool($hash)
    {
        return Component::initializeAdminTool($hash);
    }

    /**
     * @param $social_id
     * @param $sex
     * @param $birthDate
     * @return bool|null
     */
    final public static function newPlayer($social_id, $sex, $birthDate, $lang)
    {
        return NewPlayer::addNewPlayer($social_id, $sex, $birthDate, $lang);
    }
}

set_exception_handler(function (\Exception $e) {
    $exceptionArray = [];
    $traceSlice = [];
    $exceptionArray['Error'] = $e->getCode();
    $exceptionArray['File'] = $e->getFile() . ": " .  $e->getLine();
    $exceptionArray['Message'] = $e->getMessage();
    $trace = $e->getTrace();

    if (is_array($trace)) {
        $sliceSize = count($trace) > 2 ? 2 : count($trace);
        $traceSlice = array_slice($trace, 0, $sliceSize);
    }
    $exceptionArray['Trace']  = $traceSlice;

    Logger::log($exceptionArray);
    if (Game::isDevelopment()) {
        require_once __DIR__ . '/error.php';
//        echo json_encode($exceptionArray);
    } else {
        echo json_encode([
            'Error' => $e->getCode(),
            'Message' => 'An error occurred. Please contact us explaining what has happened'
        ]);
    }

});

set_error_handler(function ($errorNo, $errorStr, $errorFile, $errorLine, $errorContext) {
    if (!(error_reporting() & $errorNo)) {
        return;
    }
    throw new \ErrorException($errorStr, $errorNo, 0, $errorFile, $errorLine);
});


spl_autoload_register(['Library\\Game', 'loadPsr4'], true, true);


$map = require __DIR__ . '/psr4.map.php';
foreach ($map as $namespace => $path) {
    Game::$prefixDirsPsr4[$namespace] = (array) $path;
    $length = strlen($namespace);
    if ('\\' !== $namespace[$length - 1]) {
        throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
    }
    Game::$prefixLengthsPsr4[$namespace[0]][$namespace] = $length;
}


