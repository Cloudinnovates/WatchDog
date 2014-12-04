<?php

namespace WatchDog\Utils;

class OperatingSystem {

    public static function isWindows(){

        return strtolower(substr(PHP_OS, 0, 1)) === 'w';

    }

}