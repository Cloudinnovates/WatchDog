<?php

namespace WatchDog\Watched;

class AccessLogs extends \WatchDog\Core\MailableLog {

    private $m_logPath;

    // Constructors/Destructors.
    public function __construct($logPath, array $routes){

        parent::__construct($routes);
        $this->m_logPath = $logPath;

    }
    // Accessors/Mutators.
    public function getLogPath(){
        return $this->m_logPath;
    }
    // ILoggable implementation.
    public function getLogData(\WatchDog\Core\Route $route, $data){

        $result = array();
        $logs = \WatchDog\Utils\FileSystem::getFiles($this->m_logPath);

        if (count($logs) > 0 && $route instanceof \WatchDog\Routes\MailRoute){

            $result['data'] = "Hi, this is a collection of your website access logs for last month ({$data['month']}). Please inspect them for any suspicious behaviour. Yours faithfully, WatchDog.";
            $result['subject'] = "WatchDog Monthly Web Server Access Logs for {$data['month']}";
            $result['attachments'] = array();

            foreach ($logs as $log)
                if (strrpos($log['file'], ucfirst($data['month']) . '-' . date('Y') . '.gz') !== false)
                    $result['attachments'][] = $this->m_logPath . __DS__ . $log['file'];

        }

        return $result;

    }

}