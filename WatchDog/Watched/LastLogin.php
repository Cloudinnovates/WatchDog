<?php

namespace WatchDog\Watched;

class LastLogin extends \WatchDog\Core\LoggableWatchedFile {

    // Constructors/Destructors.
    public function __construct($pathName, array $logRoutes = array()){

        parent::__construct('.lastlogin', $pathName, $logRoutes);

    }
    // ILoggable implementation.
    public function getLogData(\WatchDog\Core\Route $route, $data){

        $host = @gethostbyaddr($data);
        if (is_null($host))
            $host = '';

        $result = array(
            'data' => sprintf("%s (New IP Detected) --> %s %s", \WatchDog\Utils\Date::formatCurrentDateTime(), $data, $host),
        );

        if ($route instanceof \WatchDog\Routes\FileRoute){
            $result['data'] .= "\r\n";
        } elseif ($route instanceof \WatchDog\Routes\MailRoute){
            $result['subject'] = "Watchdog Alert from {$route->getDomain()} (New IP): $data";
        }

        return $result;

    }

}