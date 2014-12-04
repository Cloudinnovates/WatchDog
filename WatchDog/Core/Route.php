<?php

namespace WatchDog\Core;

abstract class Route {

    public abstract function run($data = array());

}