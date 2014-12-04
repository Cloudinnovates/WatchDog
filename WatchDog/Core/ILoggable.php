<?php

namespace WatchDog\Core;

interface ILoggable {

    public function getLogData(\WatchDog\Core\Route $route, $data);

}