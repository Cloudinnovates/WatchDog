<?php

/*
 * This script will automatically create the cache and log directories,
 * create htaccess files inside those directories with "deny from all".
 * It will also create a cron in cPanel to run WatchDog.
 *
 * You will need either a WHM username and WHM access hash or
 * a cPanel username with a cPanel password.
 *
 * Usage:
 *
 * First create the watchdog directory underneath public_html (not inside!).
 * eg:
 * /home/example/watchdog and NOT /home/example/public_html/watchdog
 *
 * Then upload the htaccess and index files, along with the app folder in there.
 * Then upload this script to public_html and execute it
 *
 * To Install: run http://example.com/watchdog-install.php
 * To Uninstall: run http://example.com/watchdog-install.php?uninstall=1
 *
 * After installed, delete watchdog-install.php and upload it again to uninstall.
 *
 */

define('WATCHDOG_PATH', str_replace('public_html', 'watchdog', __DIR__));
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
$watchdog = \WatchDog\WatchDog::getInstance();

// Uncomment below for testing.
/////////////////////////////////////////////////
//define('WATCHDOG_TESTING', 1);
//$user = 'xxxxxx';
//$pw = 'xxxxx';
//$watchdog->setDomain('xxxx');
//$watchdog->setDirectoryUsername('xxxxx');
/////////////////////////////////////////////////

$install = isset($_GET['uninstall']) ? false : true;
$user = $watchdog->getDirectoryUsername();
$pw = \WatchDog\Utils\FileSystem::getAccessHash();
$domain = $watchdog->getDomain();
$messages = array();

// Check we are in public_html.
$currentPath = __DIR__;
if (substr($currentPath, strlen($currentPath) - 1, 1) === __DS__)
    $currentPath = substr($currentPath, strlen($currentPath) - 1);
$ph = 'public_html';

if (!\WatchDog\Utils\OperatingSystem::isWindows() && substr($currentPath, strlen($currentPath) - strlen($ph), strlen($ph)) !== $ph)
    $messages[] = array('status' => 'Fatal Error', 'data' => "You must install this from your $ph directory");

// Did we find a domain?
if (count($messages) === 0 && is_null($domain) || strlen($domain) === 0)
    $messages[] = array('status' => 'Fatal Error', 'data' => 'Unable to enumerate domain name. Please check that all your cPanel files are in correct order.');

if (count($messages) === 0 && isset($_POST) && count($_POST) > 0){
    if (!defined('WATCHDOG_TESTING'))
        extract($_POST);
    if (strlen($user) > 0 && strlen($pw) > 0){
        if ($install)
            $messages = $watchdog->install($user, $pw);
       else
            $messages = $watchdog->uninstall($user, $pw);
    } else
        $messages[] = array('status' => 'Error', 'data' => 'You must enter all credentials.');
}

$installString = $install ? 'I' : 'Uni';

function loadWatchDog($className){

    set_include_path(WATCHDOG_PATH . PATH_SEPARATOR . get_include_path());
    require_once str_replace('\\', __DS__, $className) . '.php';

}
?>
<!DOCTYPE html>
<html>
<head>
    <title>WatchDog - <?php echo $installString;?>nstallation</title>
    <script type="text/javascript">
        function onSubmit(){

            var f = document.forms[0];

            if (f.user.length === 0){
                alert('You must enter a Username.\nI<?php echo $installString;?>nstallation aborted.');
                return false;
            } else if (f.pw.length === 0){
                alert('You must enter a Password.\n<?php echo $installString;?>nstallation aborted.');
                return false;
            }

            return true;
        }
    </script>
</head>
<body>

<h1><?php echo $installString;?>nstall WatchDog</h1>

<div id="messages">
    <?php if (count($messages) > 0):?>
        <table>
            <?php foreach($messages as $message):?>
                <tr><td><?php echo $message['status'];?>: </td><td><?php echo $message['data'];?></td></tr>
            <?php endforeach;?>
        </table>
    <?php endif;?>
</div>

<h5>Please fill out the following credentials:</h5>
<form action="<?php echo $_SERVER['PHP_SELF'] . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''); ?>" method="post" onsubmit="return onSubmit();">
    <table>
        <tr><td><label for="user">WHM or cPanel Username:</label></td><td><input type="text" id="user" name="user" required="true" value="<?php echo $user; ?>"></td></tr>
        <tr><td><label for="pw">WHM Access Hash or cPanel Password:</label></td><td><input type="password" id="pw" name="pw" required="true" value="<?php echo $pw; ?>"></td></tr>
        <tr><td>Detected Domain:</td><td><?php echo $domain;?></td></tr>
        <tr><td colspan="2"><input type="submit" value="<?php echo $installString;?>nstall WatchDog"></td></tr>
    </table>
</form>

</body>
</html>