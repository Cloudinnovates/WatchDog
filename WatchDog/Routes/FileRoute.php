<?php

namespace WatchDog\Routes;

class FileRoute extends \WatchDog\Core\Route {

    private $m_filePath;

    // Constructors/Destructors.
    public function __construct($filePath){
        $this->m_filePath = $filePath;
    }
    // Accessors/Mutators.
    public function getFilePath(){
        return $this->m_filePath;
    }
    // LogRoute implementation.
    public function run($data = array()){

        $handle = fopen($this->m_filePath, 'ab');
        if (!$handle)
            return false;

        $result = fwrite($handle, $data['data']);
        @fclose($handle);

        return $result === false ? false : true;

    }

}