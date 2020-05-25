<?php

namespace MyApp\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;

class Setting extends Model
{

    public function initialize()
    {
        $this->setConnectionService('dbTrade');
        $this->setSource("gateways");
    }

    public function getList()
    {
        $sql = "SELECT id,app_id,gateway,sub,type,visible,name,remark,currency,parent,sort,sandbox,tips FROM gateways WHERE 1=1 ";
        $bind = [];
        if (!empty(DI::getDefault()->get('session')->get('app'))) {
            $sql .= " AND app_id=:app_id";
            $bind['app_id'] = DI::getDefault()->get('session')->get('app');
        }
        $sql .= " ORDER BY sort DESC, id DESC";
        $query = DI::getDefault()->get('dbTrade')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetchAll();
        if (!$data) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if ($value['type'] == 'wallet') {
                $data[$key]['typename'] = '钱包';
            }
            else {
                if ($value['type'] == 'card') {
                    $data[$key]['typename'] = '预付卡';
                }
                else {
                    if ($value['type'] == 'telecom') {
                        $data[$key]['typename'] = '运营商';
                    }
                }
            }
        }
        return $data;
    }

    public function getGatewaysList()
    {
        $sql = "SELECT id,gateway,name FROM gateways WHERE 1=1 AND parent = 0 ";
        $bind = [];
        if (!empty(DI::getDefault()->get('session')->get('app'))) {
            $sql .= " AND app_id=:app_id";
            $bind['app_id'] = DI::getDefault()->get('session')->get('app');
        }
        $sql .= " GROUP BY gateway ORDER BY sort DESC, id DESC";
        $query = DI::getDefault()->get('dbTrade')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetchAll();

        return $data;
    }

    public function getParent()
    {
        $sql = "SELECT id,gateway,name, COUNT(DISTINCT gateway) FROM gateways WHERE 1=1  AND parent = 0 ";
        $bind = [];
        if (!empty(DI::getDefault()->get('session')->get('app'))) {
            $sql .= " AND app_id=:app_id";
            $bind['app_id'] = DI::getDefault()->get('session')->get('app');
        }

        $sql .= " GROUP BY gateway ORDER BY sort DESC, id DESC";
        $query = DI::getDefault()->get('dbTrade')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetchAll();

        return $data;
    }

    public function findApp()
    {
        $sql = "SELECT id,name,app_id,secret_key,notify_url,trade_tip FROM apps WHERE 1=1 ";
        $bind = [];
        if (!empty(DI::getDefault()->get('session')->get('app'))) {
            $sql .= " AND app_id=:app_id";
            $bind['app_id'] = DI::getDefault()->get('session')->get('app');
        }

        $query = DI::getDefault()->get('dbTrade')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetch();
        return $data;
    }

    public function saveApp($app)
    {
        if ($this->findApp()) {
            $sql = "UPDATE `apps` SET `secret_key` = ? , notify_url = ?, trade_tip = ? WHERE `app_id` = ?";
            DI::getDefault()->get('dbTrade')->execute($sql, array(
                $app['secret_key'],
                $app['notify_url'],
                $app['trade_tip'],
                DI::getDefault()->get('session')->get('app')
            ));
        }
        else {
            $sql = "INSERT INTO `apps`(`app_id`, `secret_key`,`notify_url`,`trade_tip`) VALUES (?, ?, ?, ?)";
            DI::getDefault()->get('dbTrade')->execute($sql, array(
                DI::getDefault()->get('session')->get('app'),
                $app['secret_key'],
                $app['notify_url'],
                $app['trade_tip']
            ));
        }

        return true;
    }

    public function findOne()
    {
        $sql = "SELECT id,app_id,user_id,timezone,create_time FROM setting_timezone WHERE 1=1 ";

        $sql .= " AND user_id=:user_id";
        $bind['user_id'] = DI::getDefault()->get('session')->get('user_id');

        if (!empty(DI::getDefault()->get('session')->get('app'))) {
            $sql .= " AND app_id=:app_id";
            $bind['app_id'] = DI::getDefault()->get('session')->get('app');
        }

        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetch();
        return $data;
    }

    public function saveTimeZone($timezone)
    {
        if ($this->findOne()) {
            $sql = "UPDATE `setting_timezone` SET `timezone` = ? WHERE `app_id` = ? AND user_id = ? ";
            DI::getDefault()->get('dbData')->execute($sql, array(
                $timezone['timezone'],
                DI::getDefault()->get('session')->get('app'),
                DI::getDefault()->get('session')->get('user_id')
            ));
        }
        else {
            $sql = "INSERT INTO `setting_timezone`(`app_id`, `user_id`,`timezone`) VALUES (?, ?, ?)";
            DI::getDefault()->get('dbData')->execute($sql, array(
                DI::getDefault()->get('session')->get('app'),
                DI::getDefault()->get('session')->get('user_id'),
                $timezone['timezone']
            ));
        }

        return true;
    }
}
