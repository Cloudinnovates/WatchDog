<?php

namespace WatchDog;

use \WatchDog\Utils\FileSystem as FileSystemUtils;

// Singleton implementation.
class WatchDog {

    private $m_basePath;
    private $m_cachePath;
    private $m_logPath;
    private $m_cpanelPath;
    private $m_etcPath;
    private $m_taskManager;
    private $m_domain;
    private $m_directoryUsername;

    // Constructors/Destructors.
    private function __construct(){

        $this->m_basePath = dirname(WATCHDOG_PATH);
        $this->m_cachePath = WATCHDOG_PATH . DIRECTORY_SEPARATOR . 'cache';
        $this->m_logPath = $this->m_cachePath . DIRECTORY_SEPARATOR . 'logs';
        $this->m_cpanelPath = $this->m_basePath . DIRECTORY_SEPARATOR . '.cpanel';
        $this->m_etcPath = $this->m_basePath . DIRECTORY_SEPARATOR . 'etc';
        $this->m_domain = FileSystemUtils::loadStringFromDirectory($this->m_etcPath);
        $this->m_directoryUsername = FileSystemUtils::getDirectoryUsername();
        $this->m_taskManager = new \WatchDog\Managers\TaskManager();

        $fileSystemTask = new \WatchDog\Tasks\FileSystemTask();
        $cronTask = new \WatchDog\Tasks\CronTask();

        $this->m_taskManager->addTask($fileSystemTask);
        $this->m_taskManager->addTask($cronTask);

    }
    // Accessors/Mutators.
    public function getBasePath(){
        return $this->m_basePath;
    }
    public function getCachePath(){
        return $this->m_cachePath;
    }
    public function getLogPath(){
        return $this->m_logPath;
    }
    public function getCpanelPath(){
        return $this->m_cpanelPath;
    }
    public function getEtcPath(){
        return $this->m_etcPath;
    }
    public function getTaskManager(){
        return $this->m_taskManager;
    }
    public function getDomain(){
        return $this->m_domain;
    }
    public function getDirectoryUsername(){
        return $this->m_directoryUsername;
    }
    // For testing purposes only.
    public function setDomain($newValue){
        $this->m_domain = $newValue;
    }
    // For testing purposes only.
    public function setDirectoryUsername($newValue){
        $this->m_directoryUsername = $newValue;
    }
    // Methods.
    public function install($user, $pw){

        return $this->m_taskManager->install(array(
            'cachePathName' => $this->m_cachePath,
            'logPathName' => $this->m_logPath,
            'domain' => $this->m_domain,
            'user' => $user,
            'pw' => $pw,
            'directoryUsername' => $this->m_directoryUsername,
        ));

    }
    public function uninstall($user, $pw){

        return $this->m_taskManager->uninstall(array(
            'cachePathName' => $this->m_cachePath,
            'logPathName' => $this->m_logPath,
            'domain' => $this->m_domain,
            'user' => $user,
            'pw' => $pw,
            'directoryUsername' => $this->m_directoryUsername,
        ));

    }
    public function run(){

        // Set up mail information.
        $domain = FileSystemUtils::loadStringFromDirectory($this->m_etcPath);
        $sender = FileSystemUtils::loadStringFromFile(null, $this->m_etcPath . __DS__ . $domain . __DS__ . '@pwcache') . chr(0x40) . $domain;
        $receiver = FileSystemUtils::loadStringFromFile('.contactemail', $this->m_basePath, $sender);

        // Create the managers.
        $fileSystemManager = new \WatchDog\Managers\FileSystemManager($this->m_cachePath);
        $serverLogManager = new \WatchDog\Managers\ServerLogManager($this->m_cachePath);

        // Setup a mail route. This will be used as a common route.
        $mailRoute = new \WatchDog\Routes\MailRoute($receiver, $sender, $this->m_cachePath);

        // Setup the watching of last login. Also setup the file route.
        $lastLogin = new \WatchDog\Watched\LastLogin($this->m_basePath, array(
            $mailRoute,
            new \WatchDog\Routes\FileRoute($this->m_logPath . __DS__ . 'LastLogin.log'),
        ));
        // Setup the watching of email accounts. Also setup the file route.
        $emailCount = new \WatchDog\Watched\EmailCount($this->m_cpanelPath, array(
            $mailRoute,
            new \WatchDog\Routes\FileRoute($this->m_logPath . __DS__ . 'EmailCount.log'),
        ));

        // Setup monthly access log mail. Also use the common mail route.
        $accessLogs = new \WatchDog\Watched\AccessLogs($this->m_basePath . __DS__ . 'logs', array($mailRoute));

        // Setup monthly error log mail. Create a new mail route because all the error logs will
        // be collected and placed in a zip file. After it is sent, this zip will be deleted.
        // Be careful going anymore than 3 maxDepth. It can take a long time with many directories.
        $errorLogs = new \WatchDog\Watched\ErrorLogs($this->m_basePath, array(
            new \WatchDog\Routes\MailRoute($receiver, $sender, $this->m_cachePath, array(), true),
        ), 3 /* maxDepth */);

        // Setup monthly killed log mail. Create a new MailRoute because
        // we want to delete the log after it is sent as it gets quite large
        // and just keeps appending values to the same file.
        $zbBlockPath = FileSystemUtils::findZbBlockPath($this->m_basePath);
        $killedLog = null;

        // Is ZbBlock installed? Only watch the killed log if it is.
        if (!is_null($zbBlockPath)){
            $killedLog = new \WatchDog\Watched\KilledLog($zbBlockPath, array(
                new \WatchDog\Routes\MailRoute($receiver, $sender, $this->m_cachePath, array(), true),
            ));
        }

        // Add watched files to FileSystemManager
        $fileSystemManager->addWatchedFile($lastLogin);
        $fileSystemManager->addWatchedFile($emailCount);

        // Add watched server logs to ServerLogManager
        $serverLogManager->addLogTask($accessLogs);
        $serverLogManager->addLogTask($errorLogs);
        if (!is_null($killedLog))
            $serverLogManager->addLogTask($killedLog);

        $success = $serverLogManager->run();

        return $fileSystemManager->run() && $success;

    }
    // Static Methods.
    public static function getInstance(){

        static $instance = null;

        if (is_null($instance))
            $instance = new WatchDog();

        return $instance;

    }



}