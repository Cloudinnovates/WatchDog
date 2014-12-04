<?php

namespace WatchDog\Managers;

class TaskManager {

    private $m_tasks;

    // Constructors/Destructors.
    //public function __construct(){}
    // Accessors/Mutators.
    public function getTasks(){
        return $this->m_tasks;
    }
    // Methods.
    public function addTask(\WatchDog\Core\IInstallable $task){
        $this->m_tasks[] = $task;
    }
    public function install(array $data = array()){

        $result = array();

        foreach ($this->m_tasks as $task)
            $result = array_merge($result, $task->install($data));

        return $result;

    }
    public function uninstall(array $data = array()){

        $result = array();

        foreach ($this->m_tasks as $task)
            if ($task instanceof \WatchDog\Core\IUninstallable)
                $result = array_merge($result, $task->uninstall($data));

        return $result;

    }

}