<?php

namespace MyApp\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;

class Products extends Model
{
    private $utilsModel;

    public function initialize()
    {
        $this->setConnectionService('dbTrade');
        $this->setSource("products");
        $this->utilsModel = new Utils();
    }

    public function getProductList($gateway)
    {
        global $config;
        $sql = "SELECT id,app_id,package,name,product_id,gateway,price,currency,coin,status,remark,create_time,update_time FROM products WHERE 1=1 AND gateway=:gateway";
        $bind = array('gateway' => $gateway);
        if (!empty(DI::getDefault()->get('session')->get('app'))) {
            $sql .= " AND app_id=:app_id";
            $bind['app_id'] = DI::getDefault()->get('session')->get('app');
        }
        $sql .= " ORDER BY product_id DESC, sort DESC";

        $query = DI::getDefault()->get('dbTrade')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetchAll();
        $newData = [];
        foreach ($data as $item) {
            $item['create_time'] = $this->utilsModel->toTimeZone($item['create_time'],
                $this->utilsModel->getTimeZone(),$config->setting->timezone);
            $item['update_time'] = $this->utilsModel->toTimeZone($item['update_time'],
                $this->utilsModel->getTimeZone(),$config->setting->timezone);
            $newData[] = $item;
        }

        return $newData;
    }

    public function createProduct($data)
    {
        $result = $this->utilsModel->yarRequest('Product', 'create', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function editProduct($data){
        $result = $this->utilsModel->yarRequest('Product', 'modify', $data);

        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function removeProduct($data)
    {
        $result = $this->utilsModel->yarRequest('Product', 'remove', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function item($data){
        global $config;
        $result = $this->utilsModel->yarRequest('Product', 'item', $data);
        if($result['code'] == 1){
            return false;
        }

        if($result['data']['more']){
            foreach($result['data']['more'] as $key=>$item)
                $result['data']['more'][$key]['start_time'] = $this->utilsModel->toTimeZone($item['start_time'],
                    $this->utilsModel->getTimeZone(),$config->setting->timezone);
            $result['data']['more'][$key]['end_time'] = $this->utilsModel->toTimeZone($item['end_time'],
                $this->utilsModel->getTimeZone(),$config->setting->timezone);
        }
        return $result;
    }

    public function createOption($data)
    {
        $result = $this->utilsModel->yarRequest('Product', 'create_option', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function editOption($data)
    {
        $result = $this->utilsModel->yarRequest('Product', 'modify_option', $data);
        if ($result['code'] == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    public function removeOption($data){
        $result = $this->utilsModel->yarRequest('Product', 'remove_option', $data);
        if($result['code'] == 0){
            return true;
        }else{
            return false;
        }
    }
}
