<?php

namespace WatchDog\Managers;

use \WatchDog\Utils\Date as DateUtil;

class ServerLogManager {

    const CACHE_FILE = 'LastMonth.txt';

    private $m_cachePath;
    private $m_logTasks;

    // Constructors/Destructors.
    public function __construct($cachePath){
        $this->m_cachePath = $cachePath;
    }
    // Accessors/Mutators.
    public function getLogTasks(){
        return $this->m_logTasks;
    }
    // Methods.
    public function addLogTask(\WatchDog\Core\MailableLog $task){
        $this->m_logTasks[] = $task;
    }
    public function run(){

        $result = false;
        $file = $this->m_cachePath . __DS__ . self::CACHE_FILE;

        if (!file_exists($file)){

            // First run, send all the logs for last month.
            $result = $this->runTasks($file);

        } else {

            $data = file_get_contents($file);

            if (empty($data)){

                // This should never happen, but if it does, just update the cacheFile.
                $result = $this->runTasks($file, true);

            } else {

                // Is this the first day of the month? Send last month's logs, and
                // update the cache file if we haven't already done so.
                if (DateUtil::isFirstDayOfMonth() && $data !== DateUtil::getCurrentMonth())
                    $result = $this->runTasks($file);

            }
        }

        return $result;

    }
    private function runTasks($cacheFile, $updateCacheOnly = false){

        $result = false;

        file_put_contents($cacheFile, DateUtil::getCurrentMonth());

        if (!$updateCacheOnly){
            foreach ($this->m_logTasks as $task){
                foreach ($task->getRoutes() as $route)
                    $result &= $route->run($task->getLogData($route, array('month' => DateUtil::getLastMonth())));

            }
        }

        return $result;

    }

}