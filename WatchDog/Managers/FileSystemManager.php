<?php

namespace WatchDog\Managers;

class FileSystemManager {

    private $m_watchedFiles;
    private $m_cachePathName;

    // Constructors/Destructors.
    public function __construct($cachePathName){

        $this->m_cachePathName = $cachePathName;
        $this->m_watchedFiles = array();

    }
    // Accessors/Mutators.
    public function getWatchedFiles(){
        return $this->m_watchedFiles;
    }
    // Methods.
    public function addWatchedFile(\WatchDog\Core\WatchedFile $watchedFile){

        if (!array_key_exists($watchedFile->getFullFileName(), $this->m_watchedFiles))
            $this->m_watchedFiles[$watchedFile->getFullFileName()] = $watchedFile;

    }
    public function run(){

        $result = false;

        foreach ($this->m_watchedFiles as $key => $watchedFile){

            $snapshot = $watchedFile->getSnapshot($this->m_cachePathName);

            if (!is_null($snapshot) &&
                $watchedFile instanceof \WatchDog\Core\ILoggable &&
                count($watchedFile->getLogRoutes()) > 0){

                foreach ($watchedFile->getLogRoutes() as $route)
                    $result &= $route->run($watchedFile->getLogData($route, $snapshot));

            }

        }

        return $result;

    }

}