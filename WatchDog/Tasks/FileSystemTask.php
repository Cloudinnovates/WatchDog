<?php

namespace WatchDog\Tasks;

use \WatchDog\Utils\FileSystem as FileSystemManager;

class FileSystemTask extends \WatchDog\Core\UninstallableTask {

    // Methods.
    private function createDirectoryMessage($result, $data, $install = true){
        $s = $install ? 'create' : 'delete';
        return array(
            'status' => $result ? 'Success' : 'Failed',
            'data' => $result ? "Successfully {$s}d {$data} directory." : "Failed to $s {$data} directory.",
        );
    }
    private function createFileMessage($result, $data, $install = true){
        $s = $install ? 'create' : 'delete';
        return array(
            'status' => $result ? 'Success' : 'Failed',
            'data' => ($result ? "Successfully {$s}d {$data}" : "Failed to $s {$data}") . ' htaccess file.',
        );
    }

    // IInstallableTask implementation.
    public function install(array $data = array()){

        return array(
            $this->createDirectoryMessage(FileSystemManager::createDirectory($data['cachePathName']), $data['cachePathName']),
            $this->createDirectoryMessage(FileSystemManager::createDirectory($data['logPathName']), $data['logPathName']),
            //$this->createFileMessage(FileSystemManager::createHtAccessFile($data['cachePathName']), $data['cachePathName']),
            //$this->createFileMessage(FileSystemManager::createHtAccessFile($data['logPathName']), $data['logPathName']),
        );

    }
    // IUninstallableTask implementation.
    public function uninstall(array $data = array()){

        return array(
            //$this->createFileMessage(FileSystemManager::deleteHtAccessFile($data['cachePathName']), $data['cachePathName'], false),
            //$this->createFileMessage(FileSystemManager::deleteHtAccessFile($data['logPathName']), $data['logPathName'], false),
            $this->createDirectoryMessage(FileSystemManager::deleteDirectory($data['logPathName']), $data['logPathName'], false),
            $this->createDirectoryMessage(FileSystemManager::deleteDirectory($data['cachePathName']), $data['cachePathName'], false),
        );

    }

}