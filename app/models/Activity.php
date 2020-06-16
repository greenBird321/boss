<?php

namespace MyApp\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;

class Activity extends Model
{
    private $utilsModel;

    public function initialize()
    {
        $this->utilsModel = new Utils();
    }

    public function getLists($data)
    {
        global $config;
        $result = $this->utilsModel->yarRequest('Activity', 'lists', $data);
        $newData = [];
        if (isset($result['count']) && $result['count'] > 0) {
            foreach ($result['data'] as $item) {
                $item['start_time'] = $this->utilsModel->toTimeZone($item['start_time'],
                    $this->utilsModel->getTimeZone(),$config->setting->timezone);
                $item['end_time'] = $this->utilsModel->toTimeZone($item['end_time'], $this->utilsModel->getTimeZone(),$config->setting->timezone);
                $newData[] = $item;
            }
            unset($result['data']);
        }
        $result['data'] = $newData;
        return $result;
    }

    /**
     * todo MLActivity
     * @param $data
     */
    public function getMLlists($data)
    {
        $result = $this->utilsModel->yarRequest('Activity', 'ml_lists', $data);
        if ($result['code'] != 0) {
            return false;
        }

        return [
            'count' => $result['count'],
            'data' => $result['data']
        ];
    }


    public function createActivity($data)
    {
        $result = $this->utilsModel->yarRequest('Activity', 'create', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function findActivity($data)
    {
        global $config;
        $result = $this->utilsModel->yarRequest('Activity', 'item', $data);
        $result['data']['start_time'] = $this->utilsModel->toTimeZone($result['data']['start_time'],
            $this->utilsModel->getTimeZone(),$config->setting->timezone);
        $result['data']['end_time'] = $this->utilsModel->toTimeZone($result['data']['end_time'],
            $this->utilsModel->getTimeZone(),$config->setting->timezone);
        return $result;
    }

    public function editActivity($data)
    {
        $result = $this->utilsModel->yarRequest('Activity', 'modify', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function removeActivity($data)
    {
        $result = $this->utilsModel->yarRequest('Activity', 'remove', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function listActivityCfg($data)
    {
        $result = $this->utilsModel->yarRequest('Activity', 'lists_cfg', $data);
        return $result;
    }

    public function createActivityCfg($data)
    {
        $result = $this->utilsModel->yarRequest('Activity', 'create_cfg', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function editActivityCfg($data)
    {
        $result = $this->utilsModel->yarRequest('Activity', 'modify_cfg', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function removeActivityCfg($data)
    {
        $result = $this->utilsModel->yarRequest('Activity', 'remove_cfg', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function logs($data){
        $result = $this->utilsModel->yarRequest('Activity', 'logs', $data);
        return $result;
    }

    public function importActivity($data){
        $result = $this->utilsModel->yarRequest('Activity', 'import', $data);
        if ($result['code'] == 0) {
            return true;
        }
        return false;
    }
}
