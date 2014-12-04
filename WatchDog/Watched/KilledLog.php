<?php

namespace WatchDog\Watched;

class KilledLog extends \WatchDog\Core\MailableLog {

    private $m_zbBlockPath;

    // Constructors/Destructors.
    public function __construct($zbBlockPath, array $routes){

        parent::__construct($routes);
        $this->m_zbBlockPath = $zbBlockPath;

    }
    // Accessors/Mutators.
    public function getLogPath(){
        return $this->m_zbBlockPath;
    }
    // ILoggable implementation.
    public function getLogData(\WatchDog\Core\Route $route, $data){

        $result = array();
        $file = $this->m_zbBlockPath . __DS__. 'killed_log.txt';

        if (file_exists($file) && $route instanceof \WatchDog\Routes\MailRoute){

            // Zip the log up to shrink it. This zip file will
            // be deleted in $route's __deconstruct
            $zipFile = str_replace('.txt', '.zip', $file);
            $success = \WatchDog\Utils\Zip::createFile($zipFile, array($file), true);
            if ($success)
                $file = $zipFile;

            $result['data'] = "Hi, this is your website killed log for last month ({$data['month']}). Please inspect it. Yours faithfully, WatchDog.";
            $result['subject'] = "WatchDog Monthly Web Server Killed Log for {$data['month']}";
            $result['attachments'] = array($file);

        }

        return $result;

    }

}