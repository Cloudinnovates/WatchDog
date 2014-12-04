<?php

namespace WatchDog\Core;

abstract class LoggableWatchedFile
    extends \WatchDog\Core\WatchedFile
    implements \WatchDog\Core\ILoggable {

    protected $m_logRoutes;

    // Constructors/Destructors.
    protected function __construct($fileName, $pathName, array $logRoutes = array()){

        parent::__construct($fileName, $pathName);
        $this->m_logRoutes = $logRoutes;

    }
    // Accessors/Mutators.
    public function getLogRoutes(){
        return $this->m_logRoutes;
    }
    // Methods.
    public function addLogRoute(Route $logRoute){
        $this->m_logRoutes[] = $logRoute;
    }
    // ILoggable implementation (pass the buck to subclass).
    public abstract function getLogData(Route $route, $data);

}