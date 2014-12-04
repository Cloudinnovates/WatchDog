<?php

define('WATCHDOG_PATH', __DIR__);
define('__DS__', DIRECTORY_SEPARATOR);

// Had to add these because they don't exist on Windows.
if (!function_exists('posix_getpwuid')){
    function posix_getpwuid($uid){
        $name = getenv('USERNAME');
        return array(
            'name' => $name,
            'dir' => "home/$name",
        );
    }
}
if (!function_exists('posix_geteuid')){
    function posix_geteuid(){
        return getmyuid();
    }
}

// Autoload dependencies.
require(WATCHDOG_PATH . __DS__ . 'vendor' . __DS__ . 'autoload.php');
// Autoload app.
spl_autoload_register('loadWatchDog');

// Run app.
exit(WatchDog\WatchDog::getInstance()->run() ? 'yes' : 'no');

function loadWatchDog($className){

    set_include_path(WATCHDOG_PATH . PATH_SEPARATOR . get_include_path());
    require_once str_replace('\\', __DS__, $className) . '.php';

}