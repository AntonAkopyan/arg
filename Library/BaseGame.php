<?php
namespace Library;

use Library\Base\Application;

define('GAME_BEGIN_TIME', microtime(true));
defined('SYSTEM_PATH') or define('SYSTEM_PATH', __DIR__);
defined('GAME_PATH') OR define('GAME_PATH', realpath(__DIR__ . '/..'));
defined('CONFIG_PATH') OR define('CONFIG_PATH', realpath(__DIR__.'/../Config'));
defined('LIB_PATH') or define('LIB_PATH', realpath(__DIR__ . '/../Library'));
defined('TILEMAP_PATH') OR define('TILEMAP_PATH', '/data/asset');
defined('ASSET_PATH') OR define('ASSET_PATH', realpath(__DIR__ . '/../data/asset'));
defined('ASSET_PATH_IOS') OR define('ASSET_PATH_IOS', '/data/asset/ios');
defined('ASSET_PATH_ANDROID') OR define('ASSET_PATH_ANDROID', '/data/asset/android');
defined('ENVIRONMENT')
|| define('ENVIRONMENT', 1);

require_once CONFIG_PATH . '/const.php';

class BaseGame
{
    public static $classMap = [];

    public static $prefixDirsPsr4 = [];

    public static $prefixLengthsPsr4 = [];

    /**
     * @var Application
     */
    protected static $app;

    public static $container;

    public static function loadPsr4($class)
    {
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';
        $first = $class[0];
        if (isset(static::$prefixLengthsPsr4[$first])) {
            foreach (static::$prefixLengthsPsr4[$first] as $prefix => $lng) {
                if (0 === strpos($class, $prefix)) {
                    foreach (static::$prefixDirsPsr4[$prefix] as $dir) {
                        $file = $dir . DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $lng);
                        if (is_file($file)) {
                            require $file;
                        }
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return '0.0.4-dev';
    }

    public static function isDevelopment() {
        return (ENVIRONMENT == 1);
    }

    /**
     * @param $class
     * @param array $params
     * @return mixed
     */
    public static function createObject($class, array $params = array())
    {
        return static::$container->get($class, $params, false);
    }

    /**
     * @param $class
     * @return mixed
     */
    public static function getInstance($class)
    {
        return static::$container->get($class, array(), true);
    }

    /**
     * @param $class
     * @return mixed
     */
    public static function getComponent($class)
    {
//        if (empty(static::$container)) {
//            static::$container = new Container();
//        }
        return Game::getContainer()->get('Game\\Components\\' . $class, array(), true);
//        return static::$container->get('Game\\Components\\' . $class, array(), true);
    }
}