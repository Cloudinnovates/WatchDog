<?php

namespace WatchDog\Core;

abstract class WatchedFile {

    protected $m_fileName;
    protected $m_pathName;

    // Constructors/Destructors.
    protected function __construct($fileName, $pathName){

        $this->m_fileName = $fileName;
        $this->m_pathName = $pathName;

    }
    // Accessors/Mutators.
    public function getFileName(){
        return $this->m_fileName;
    }
    public function getPathName(){
        return $this->m_pathName;
    }
    public function getFullFileName(){
        return $this->m_pathName . DIRECTORY_SEPARATOR . $this->m_fileName;
    }
    // Methods.
    public function getSnapshot($cachePathName){

        $result = null;
        $filename = $this->getFullFileName();
        $snapshot = $cachePathName . DIRECTORY_SEPARATOR . $this->m_fileName;

        if (!file_exists($filename))
            return $result;

        if (!file_exists($snapshot)){

            copy($filename, $snapshot);

        } else {

            $new = file_get_contents($filename);
            $old = file_get_contents($snapshot);

            if ($new !== $old){

                unlink($snapshot);
                copy($filename, $snapshot);
                $result = $new;

            }
        }

        return $result;

    }

}