<?php
/**
 * Created by IntelliJ IDEA.
 * User: volodymyr.ivchyk
 * Date: 11/12/16
 * Time: 6:24 PM
 */

// Allow from any origin
//if (isset($_SERVER['HTTP_ORIGIN'])) {
//    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
//    // you want to allow, and if so:
//    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
//    header('Access-Control-Allow-Credentials: true');
//    header('Access-Control-Max-Age: 86400');    // cache for 1 day
//}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        header("Access-Control-Allow-Origin: http://localhost:8080");
        header("Access-Control-Allow-Credentials: true");
        echo 'POST,GET,PATCH';
        exit;
}

$time_start = microtime(true);

function errorDisplay() {

    if (!$e = error_get_last()) {
        return;
    }
    if (!is_array($e)
        || !in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
        return;
    }
    while (ob_get_level()) {
        ob_end_clean();
    }
    require_once './Library/error.php';
}

register_shutdown_function('errorDisplay');

require_once './Library/Game.php';

$request = explode('/', trim($_GET["request"],'/'));

$requestString = file_get_contents("php://input");
$requestArray = (array)json_decode($requestString);

$response = [];

$function = new ReflectionMethod('\\Game\\Services\\'.ucfirst($request[0]), $request[1]);
$need_params = $function->getParameters();

$set_args = [];

foreach ($need_params as $item) {
    $set_args[] = (isset($requestArray[$item->getName()])) ? $requestArray[$item->getName()] : null;
}

$class_name = 'Game\\Services\\'.$request[0];
$response = $function->invokeArgs(new $class_name(), $set_args);

echo json_encode($response);
