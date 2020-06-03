<?php

namespace MyApp\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;

class Server extends Model
{
    private $utilsModel;

    public function initialize()
    {
        $this->utilsModel = new Utils();
    }

    public function createServer($data)
    {
        $result = $this->utilsModel->yarRequest('Zone', 'create', $data);
        if ($result['code'] == 0) {
            $this->getServerLists();
            return true;
        }
        else {
            return false;
        }
    }

    public function getLists()
    {
        if (!isset($_COOKIE['serverLists'])) {
            $this->getServerLists();
        }
        return json_decode($_COOKIE['serverLists'], true);
    }

    public function findServer($data)
    {
        return $this->utilsModel->yarRequest('Zone', 'item', $data);
    }

    public function editServer($data)
    {
        $result = $this->utilsModel->yarRequest('Zone', 'modify', $data);
        if ($result['code'] == 0) {
            $this->getServerLists();
            return true;
        }
        else {
            return false;
        }
    }

    public function removeServer($data)
    {
        $result = $this->utilsModel->yarRequest('Zone', 'remove', $data);
        if ($result['code'] == 0) {
            $this->getServerLists();
            return true;
        }
        else {
            return false;
        }
    }

    public function getServerLists()
    {
        $result = $this->utilsModel->yarRequest('Zone', 'lists', array());
        if (!$result['data']) {
            return array();
        }
        else {
            $_COOKIE['serverLists'] = json_encode($result['data']);
            //setcookie('serverLists', json_encode($result['data']), time() + 7200, '/');    //线上不生效，线下没事
        }
    }
}