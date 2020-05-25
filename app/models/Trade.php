<?php

namespace MyApp\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;

class Trade extends Model
{
    private $utilsModel;

    public function initialize()
    {
        $this->setConnectionService('dbTrade');
        $this->setSource("transactions");
        $this->utilsModel = new Utils();
    }

    public function getCount($data)
    {
        $result = $this->getWhere($data);

        $sql = "SELECT count(1) as count FROM transactions AS t LEFT JOIN trans_more AS tm ON t.transaction = tm.trans_id WHERE 1=1 " . $result['where'];

        if (!empty(DI::getDefault()->get('session')->get('app'))) {
            $sql .= " AND t.app_id=:app_id";
            $result['bind']['app_id'] = DI::getDefault()->get('session')->get('app');
        }
        $sql .= " ORDER BY t.id DESC";
        $query = DI::getDefault()->get('dbTrade')->query($sql, $result['bind']);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetch();

        return $data['count'];


    }

    public function getList($data, $page, $pagesize)
    {
        global $config;
        $result = $this->getWhere($data);

        $sql = "SELECT t.id, t.transaction, t.user_id, t.currency, t.amount, t.status, t.gateway, t.product_id, t.custom, t.create_time, tm.trans_id, tm.trade_no FROM transactions AS t LEFT JOIN trans_more AS tm ON t.transaction = tm.trans_id WHERE 1=1 " . $result['where'];

        if (!empty(DI::getDefault()->get('session')->get('app'))) {
            $sql .= " AND t.app_id=:app_id";
            $result['bind']['app_id'] = DI::getDefault()->get('session')->get('app');
        }
        $sql .= " ORDER BY t.id DESC";
        $sql .= " LIMIT " . ($page - 1) * $pagesize . ",$pagesize";
        $query = DI::getDefault()->get('dbTrade')->query($sql, $result['bind']);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetchAll();
        $newData = [];
        foreach ($data as $item) {
            $item['create_time'] = $this->utilsModel->toTimeZone($item['create_time'],
                $this->utilsModel->getTimeZone(),$config->setting->timezone);
            $newData[] = $item;
        }
        return $newData;
    }

    public function getTradeById($id)
    {
        global $config;
        $sql = "SELECT t.id, t.transaction, t.user_id, t.currency, t.amount, t.status, t.amount_usd, t.gateway, t.product_id, t.custom, t.device, t.channel, t.create_time,t.ip, t.uuid, t.complete_time, tm.trans_id, tm.trade_no FROM transactions AS t LEFT JOIN trans_more AS tm ON t.transaction = tm.trans_id WHERE 1=1 ";

        $sql .= " AND t.id=:id";
        $bind['id'] = $id;

        if (!empty(DI::getDefault()->get('session')->get('app'))) {
            $sql .= " AND t.app_id=:app_id";
            $bind['app_id'] = DI::getDefault()->get('session')->get('app');
        }
        $query = DI::getDefault()->get('dbTrade')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetch();
        $data['create_time'] = $this->utilsModel->toTimeZone($data['create_time'], $this->utilsModel->getTimeZone(),$config->setting->timezone, 'Y-m-d H:i:s O');
        $data['complete_time'] = $this->utilsModel->toTimeZone($data['complete_time'],
            $this->utilsModel->getTimeZone(),$config->setting->timezone, 'Y-m-d H:i:s O');
        return $data;
    }

    private function getWhere($data)
    {
        $where = '';
        $bind = [];
        if (!empty($data['transaction'])) {
            $where .= " AND t.transaction=:transaction";
            $bind['transaction'] = $data['transaction'];
        }
        if (!empty($data['user_id'])) {
            $where .= " AND t.user_id=:user_id";
            $bind['user_id'] = $data['user_id'];
        }
        if (!empty($data['status'])) {
            $where .= " AND t.status=:status";
            $bind['status'] = $data['status'];
        }
        if (!empty($data['gateway'])) {
            $where .= " AND t.gateway=:gateway";
            $bind['gateway'] = $data['gateway'];
        }
        if (!empty($data['product_id'])) {
            $where .= " AND t.product_id=:product_id";
            $bind['product_id'] = $data['product_id'];
        }
        if (!empty($data['custom'])) {
            $where .= " AND t.custom LIKE '%:custom%'";
            $bind['custom'] = $data['custom'];
        }
        if (!empty($data['start_time'])) {
            $where .= " AND t.create_time>=:start_time";
            $bind['start_time'] = $data['start_time'];
        }
        if (!empty($data['end_time'])) {
            $where .= " AND t.create_time<=:end_time";
            $bind['end_time'] = $data['end_time'];
        }
        if (!empty($data['trade_no'])) {
            $where .= " AND tm.trade_no=:trade_no";
            $bind['trade_no'] = $data['trade_no'];
        }
        return array('where' => $where, 'bind' => $bind);
    }


}
