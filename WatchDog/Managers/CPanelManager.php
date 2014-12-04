<?php

namespace WatchDog\Managers;

class CPanelManager {

    private $m_cPanel;

    private function __construct($host, $user, $password){

        $this->m_cPanel = \Cpanel_PublicAPI::factory('WHM');
        $this->m_cPanel->setUser($user)
                       ->setHost($host);

        // Auto detect if a hash is being used.
        if (strlen($password) > 500)
            $this->m_cPanel->setHash($password);
        else
            $this->m_cPanel->setPassword($password);

    }
    private function query($module, $method, array $params = array(), array $extra = array(), $type = 'json'){

        $url = "/{$type}-api/cpanel?cpanel_{$type}api_module={$module}&cpanel_{$type}api_func={$method}";
        $response = $this->m_cPanel->directURLQuery($url, $params, $extra)->getRawResponse();
        $parsed = $type === 'json' ? json_decode($response) : new \SimpleXMLElement($response);

        if (!isset($parsed->cpanelresult)){
            return array(array('status' => 'Failed', 'data' => 'cPanel Error: Unexpected data received from server.'));
        } elseif (isset($parsed->cpanelresult->error) && !empty($parsed->cpanelresult->error)
            && isset($parsed->cpanelresult->data->reason) && !empty($parsed->cpanelresult->data->reason)){
            return array(array('status' => 'Failed', 'data' => 'cPanel Error: ' . $parsed->cpanelresult->data->reason));
        }

        return $parsed->cpanelresult;

    }
    public function getDiskInfo(array $data = array()){

        $result = array();
        $response = $this->query('Fileman', 'getdiskinfo', array('user' => $data['user']));

        if ($response instanceof \stdClass === false &&
            $response instanceof \SimpleXMLElement === false)
            return $response;

        // TODO: get proper results
        foreach ($response->data as $dataset) {
            foreach ($dataset as $key => $value) {
                switch ($key){
                    case 'status':
                        $result['status'] = $value ? 'Success' : 'Failed';
                        break;
                    case 'statusmsg':
                        $result['data'] = $value . " ({$data['command']})";
                        break;
                }
            }
        }

        return array($result);

    }
    public function createCron($data){

        $result = array();
        $response = $this->query('Cron', 'add_line', $data);

        if ($response instanceof \stdClass === false &&
            $response instanceof \SimpleXMLElement === false)
            return $response;

        foreach ($response->data as $dataset) {
            foreach ($dataset as $key => $value) {
                switch ($key){
                    case 'status':
                        $result['status'] = $value ? 'Success' : 'Failed';
                        break;
                    case 'statusmsg':
                        $result['data'] = $value . " ({$data['command']})";
                        break;
                }
            }
        }

        return array($result);

    }
    public function deleteCron($data){

        $result = array();
        $cron = array();
        $response = $this->query('Cron', 'listcron', array('user' => $data['user']));

        if ($response instanceof \stdClass === false &&
            $response instanceof \SimpleXMLElement === false)
            return $response;

        foreach ($response->data as $dataset) {

            if (isset($dataset->command) && $dataset->command === $data['command']){
                $cron['command'] = $dataset->command;
                $cron['linekey'] = isset($dataset->linekey) ? $dataset->linekey : '';
                $cron['count'] = isset($dataset->count) ? $dataset->count : '';
                break;
            }

        }

        if (empty($cron) || strlen($cron['linekey']) === 0 || strlen($cron['count']) === 0)
            return array(array('status' => 'Failed', 'data' => 'Error retrieving cron id. It does not appear to exist.'));

        $response = $this->query('Cron', 'remove_line', array('user' => $data['user'], 'line' => $cron['count']));

        if ($response instanceof \stdClass === false &&
            $response instanceof \SimpleXMLElement === false)
            return $response;

        foreach ($response->data as $dataset) {
            foreach ($dataset as $key => $value) {
                switch ($key){
                    case 'status':
                        $result['status'] = $value ? 'Success' : 'Failed';
                        break;
                    case 'statusmsg':
                        $result['data'] = str_replace('installed', 'removed', $value) . " ({$data['command']})";
                        break;
                }
            }
        }

        return array($result);

    }
    public static function getInstance(array $config = array()){

        static $instance = null;

        if (is_null($instance) && count($config) > 0)
            $instance = new CPanelManager($config['host'], $config['user'], $config['password']);

        return $instance;

    }
}
