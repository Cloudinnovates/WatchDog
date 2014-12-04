<?php

namespace WatchDog\Watched;

class ErrorLogs extends \WatchDog\Core\MailableLog {

    private $m_basePath;
    private $m_maxDepth;

    // Constructors/Destructors.
    public function __construct($basePath, array $routes, $maxDepth = -1){

        parent::__construct($routes);
        $this->m_basePath = $basePath;
        $this->m_maxDepth = $maxDepth;

    }
    // Accessors/Mutators.
    public function getBasePath(){
        return $this->m_basePath;
    }
    public function getMaxDepth(){
        return $this->m_maxDepth;
    }
    public function setMaxDepth($newValue){
        $this->m_maxDepth = $newValue;
    }
    // ILoggable implementation.
    public function getLogData(\WatchDog\Core\Route $route, $data){

        set_time_limit(0);

        $result = array();
        $directories = \WatchDog\Utils\FileSystem::getDirectoriesAndFiles($this->m_basePath, array('error_log'), $this->m_maxDepth);

        // Check WATCHDOG_PATH for errors too.
        $we = WATCHDOG_PATH . __DS__ . 'error_log';
        if (file_exists($we)){
            $size = filesize($we);
            $directories[WATCHDOG_PATH] = array('files' => array(array(
                'file' => 'error_log',
                'size' => $size,
                'permissions' => decoct(fileperms($we) & 0777),
            )), 'totalSize' => $size, 'fileCount' => 1);
        }

        // Only send the email if we have at least 1 log to send.
        if (count($directories) > 0 && $route instanceof \WatchDog\Routes\MailRoute){

            $files = array();

            // Since all the files are called error_log, this will be confusing
            // when opening them up. So put the path in the filename too.
            foreach ($directories as $path => $directory){
                foreach ($directory['files'] as $file){
                    $files[] = $path . __DS__ . $file['file'];
                    // Provide a fallback, in case the zip creation fails.
                    $result['attachments'][] = $files[count($files) - 1];
                }
            }

            // Zip the logs up into 1 zip file. This zip file will
            // be deleted in $route's __deconstruct
            $zipFile = $this->m_basePath . __DS__ . 'error_logs.zip';
            $success = \WatchDog\Utils\Zip::createFile($zipFile, $files, false, function($filename){
                return str_replace(__DS__, '-', str_replace(':', '', $filename));
            });

            $result['data'] = "Hi, this is a collection of your website PHP error logs for last month ({$data['month']}). Please inspect them for any suspicious behaviour or any problems. Yours faithfully, WatchDog.";
            $result['subject'] = "WatchDog Monthly Web Server Error Logs for {$data['month']}";
            // Overwrite the fallback.
            if ($success)
                $result['attachments'] = array($zipFile);

        }

        return $result;

    }

}