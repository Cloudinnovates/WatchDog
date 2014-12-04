<?php

namespace WatchDog\Core;

abstract class InstallableTask implements \WatchDog\Core\IInstallable {

    public abstract function install(array $data = array());

}