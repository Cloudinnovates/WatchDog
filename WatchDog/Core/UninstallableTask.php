<?php

namespace WatchDog\Core;

abstract class UninstallableTask
    extends \WatchDog\Core\InstallableTask
    implements \WatchDog\Core\IUninstallable {

    public abstract function uninstall(array $data = array());

}