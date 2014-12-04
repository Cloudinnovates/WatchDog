<?php

namespace WatchDog\Utils;

class Zip {

    public static function createFile($file, array $files, $deleteFiles = false, $filenameReplaceCallback = null){

        $zip = new \ZipArchive();
        $success = $zip->open($file, \ZipArchive::CREATE);
        $deleteQueue = array();

        if ($success){
            foreach ($files as $file){

                $f = is_array($file) && array_key_exists('file', $file) ? $file['file'] : $file;

                $success &= $zip->addFile($f,
                    !is_null($filenameReplaceCallback) && is_callable($filenameReplaceCallback) ?
                        $filenameReplaceCallback($f) :
                        FileSystem::getFileName($f)
                );

                // Add the files that need to be deleted to a queue, since we cannot
                // delete them until the zip is closed. Delete them after.
                if ($success && $deleteFiles)
                    $deleteQueue[] = $f;

            }
            $success &= $zip->close();
        }

        // Now it is safe to delete the files.
        if ($success){
            foreach ($deleteQueue as $item)
                unlink($item);
        }

        return $success;

    }

}