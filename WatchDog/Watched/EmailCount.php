<?php

namespace WatchDog\Watched;

class EmailCount extends \WatchDog\Core\LoggableWatchedFile {

    // Constructors/Destructors.
    public function __construct($cpanelPathName, array $logRoutes = array()){

        parent::__construct('email_accounts_count', $cpanelPathName, $logRoutes);

    }
    // ILoggable implementation.
    public function getLogData(\WatchDog\Core\Route $route, $data){

        $result = array(
            'data' => sprintf("%s (New Email Count Detected) --> %s", \WatchDog\Utils\Date::formatCurrentDateTime(), $data),
        );

        if ($route instanceof \WatchDog\Routes\FileRoute){
            $result['data'] .= "\r\n";
        } elseif ($route instanceof \WatchDog\Routes\MailRoute){
            $result['subject'] = "Watchdog Alert from {$route->getDomain()} (New Email Count): $data";
        }

        return $result;

    }

}