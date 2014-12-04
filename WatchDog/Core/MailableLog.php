<?php

namespace WatchDog\Core;

abstract class MailableLog implements ILoggable {

    protected $m_files;
    protected $m_routes;

    protected function __construct(array $routes = array()){

        $this->m_routes = $routes;
        $this->m_files = array();

    }
    public function getFiles(){
        return $this->m_files;
    }
    public function getRoutes(){
        return $this->m_routes;
    }
    public function addRoute(Route $route){
        $this->m_routes[] = $route;
    }
    public function addFile($file){
        $this->m_files[] = $file;
    }
    public function addFiles(array $files){
        $this->m_files = array_merge($this->m_files, $files);
    }

    public abstract function getLogData(Route $route, $data);

}