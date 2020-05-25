<?php

namespace MyApp\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;

class Card extends Model
{
    private $utilsModel;

    public function initialize()
    {
        $this->utilsModel = new Utils();
    }

    public function createCard($data)
    {
        $result = $this->utilsModel->yarRequest('Card', 'create', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function getLists($data)
    {
        global $config;
        $result = $this->utilsModel->yarRequest('Card', 'lists', $data);
        $newData = [];
        if (isset($result['count']) && $result['count'] > 0) {
            foreach ($result['data'] as $item) {
                $item['expired_in'] = $this->utilsModel->toTimeZone($item['expired_in'],
                    $this->utilsModel->getTimeZone(),$config->setting->timezone);
                $newData[] = $item;
            }
            unset($result['data']);
        }
        $result['data'] = $newData;
        return $result;
    }

    public function findCard($data)
    {
        global $config;
        $result = $this->utilsModel->yarRequest('Card', 'item', $data);
        $result['data']['expired_in'] = $this->utilsModel->toTimeZone($result['data']['expired_in'],
            $this->utilsModel->getTimeZone(),$config->setting->timezone);
        return $result;
    }

    public function editCard($data)
    {
        $result = $this->utilsModel->yarRequest('Card', 'modify', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function removeCard($data)
    {
        $result = $this->utilsModel->yarRequest('Card', 'remove', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }
}