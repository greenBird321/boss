<?php
/**
 * Created by PhpStorm.
 * User: lihe
 * Date: 2020/6/23
 * Time: 6:08 PM
 */

namespace MyApp\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;

class Account extends Model
{
    public $utilsModel;

    public function initialize()
    {
        $this->utilsModel = new Utils();
    }

    // 获取黑名单列表
    public function getBlacklist($app_id)
    {
        $sql   = "SELECT
	`id`, `role_id`, `start_time`, `end_time`
FROM
	blacklist 
WHERE
    `app_id`={$app_id}
ORDER BY
	id DESC";
        $query = DI::getDefault()->get('dbAccount')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        return $query->fetchAll();
    }

    // 保存数据
    public function saveData($data)
    {
        $sql = "INSERT INTO `blacklist` ( zone, user_id, start_time, end_time )
VALUES
	(
		{$data['zone']},
		{$data['user_id']},
	{$data['start_time']},
	{$data['end_time']})";
        return DI::getDefault()->get('dbAccount')->execute($sql);
    }

    // 通过id获取数据详情
    public function getDataById($id)
    {
        $sql   = "SELECT * FROM `blacklist` WHERE id={$id}";
        $query = DI::getDefault()->get('dbAccount')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        return $query->fetch();
    }

    // 通过id删除数据
    public function deleteDataById($id)
    {
        $sql = "DELETE FROM `blacklist` WHERE id={$id}";
        return DI::getDefault()->get('dbAccount')->execute($sql);
    }

    // 通过userId和app_id删除数据
    public function deleteDataByUserId($data)
    {
        $sql = "DELETE FROM `blacklist` WHERE `user_id`={$data['account_id']} AND `app_id`={$data['app_id']}";
        return DI::getDefault()->get('dbAccount')->execute($sql);
    }

    // 通过id更新数据
    public function updateDataById($data)
    {
        $sql = "UPDATE `blacklist` 
SET `start_time`={$data['start_time']},
`end_time`={$data['end_time']} 
WHERE
	id={$data['id']}";
        return DI::getDefault()->get('dbAccount')->execute($sql);
    }

    // 通过id更新account的status 0: 失效， 1: 正常
    public function updateAccountById($id, $status = 1)
    {
        $sql = "UPDATE `accounts` 
SET `status` = {$status}
WHERE
    id={$id}";
        return DI::getDefault()->get('dbAccount')->execute($sql);
    }

    // 通过userId查询数据
    public function findAccountByUserId($user_id, $app_id)
    {
        $sql   = "SELECT COUNT(1) `count` FROM `blacklist` WHERE user_id={$user_id} AND app_id={$app_id}";
        $query = DI::getDefault()->get('dbAccount')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        return $query->fetch();
    }

    public function setBlackListData($data)
    {
        $sql = "INSERT INTO `blacklist` (`user_id`, `app_id`, `zone`, `start_time`, `end_time`) VALUES ({$data['user_id']}, {$data['app_id']}, {$data['zone']}, {$data['start_time']}, {$data['end_time']})";
        return DI::getDefault()->get('dbAccount')->execute($sql);
    }

    public function getRoleId($data)
    {
        $response = $this->utilsModel->yarRequest('User', 'getRoleId', $data);
        if ($response['code'] == 0) {
            return $response['data']['RoleID'];
        }
        return false;
    }

    public function playerOffline($roleInfo)
    {
        $response = $this->utilsModel->yarRequest('User', 'playerOffline', $roleInfo);
        if ($response['code'] == 0) {
            return true;
        }
        return false;
    }

    public function getRoleIdByName($paramer) 
    {
        $response = $this->utilsModel->yarRequest('User', 'getRoleIdByName', $paramer);
        if ($response['code'] == 0) {
            return $response['data'];
        }
        return false;
    }

    public function findRoleByRoleId($paramer)
    {
        $sql = "SELECT
        COUNT( 1 )  as count
    FROM
        `blacklist` 
    WHERE
        `role_id` = {$paramer['roleId']} 
    AND `app_id` = {$paramer['appId']}";
    }

    public function cancelPlayerBan($paramer)
    {
        $response = $this->utilsModel->yarRequest('User', 'cancelPlayerBan', ['role_id' => $paramer['role_id'], 'zone' => $paramer['zone']]);
        if ($response['code'] == 0) {
            return true;
        }
        return false;
    }
}