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
    // 获取黑名单列表
    public function getBlacklist()
    {
        $sql = "SELECT
	* 
FROM
	blacklist 
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
        $sql = "SELECT * FROM `blacklist` WHERE id={$id}";
        $query = DI::getDefault()->get('dbAccount')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        return $query->fetch();
    }

    // 通过id删除数据
    public function deleteDataById($id)
    {
        $sql = "DELETE FROM `blacklist` WHERE  id={$id}";
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
    public function findAccountByUserId($user_id)
    {
        $sql = "SELECT COUNT(1) `count` FROM `blacklist` WHERE user_id={$user_id}";
        $query = DI::getDefault()->get('dbAccount')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        return $query->fetch();
    }
}