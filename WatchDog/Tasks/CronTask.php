<?php

namespace WatchDog\Tasks;

class CronTask extends \WatchDog\Core\UninstallableTask {

    const FREQUENCY = '*/5'; // run task every 5 minutes.

    // Methods.
    private function createCronCommand($directoryUsername){

        return "php /home/{$directoryUsername}/watchdog/index.php >/dev/null 2>&1";

    }
    // InstallableTask implementation.
    public function install(array $data = array()){

        $cp = \WatchDog\Managers\CPanelManager::getInstance(array(
            'host' => $data['domain'],
            'user' => $data['user'],
            'password' => $data['pw'],
        ));

        return $cp->createCron(array(
            'command' => $this->createCronCommand($data['directoryUsername']),
            'day' => '*',
            'hour' => '*',
            'minute' => self::FREQUENCY,
            'month' => '*',
            'weekday' => '*',
            'user' => $data['directoryUsername'],
        ));

    }
    // UninstallableTask implementation
    public function uninstall(array $data = array()){

        $cp = \WatchDog\Managers\CPanelManager::getInstance(array(
            'host' => $data['domain'],
            'user' => $data['user'],
            'password' => $data['pw'],
        ));

        return $cp->deleteCron(array(
            'command' => $this->createCronCommand($data['directoryUsername']),
            'user' => $data['directoryUsername'],
        ));

    }
}