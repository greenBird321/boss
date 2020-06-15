<?php
/**
 * Created by PhpStorm.
 * User: lkl
 * Date: 2017/5/8
 * Time: 10:34
 */

namespace MyApp\Models;


use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;

class Game extends Model
{
    private $utilsModel;

    public function initialize()
    {
        $this->setConnectionService('dbData');
        $this->setSource("games");
        $this->utilsModel = new Utils();
    }

    public function profile($data)
    {
        $result = $this->utilsModel->yarRequest('User', 'profile', $data);
        return $result;
    }

    /**
     * 保存补发log
     */
    public function setAttachLog($data)
    {
        $type         = $data['type'];
        $status       = isset($data['status']) ? $data['status'] : 0;
        $title        = $data['title'];
        $content      = $data['msg'];
        $operation_id = $data['operation_id'];
        // 发送道具的id 以及 发送的数量
        $send_prop    = $data['send_prop'];
        // 发送用户的 服务器id 以及 用户id
        $send_user    = $data['send_user'];
        $create_time  = date('Y-m-d H:i:s', time());
        $app_id       = $data['app_id'];

        $sql          = "INSERT INTO logs_attach (`app_id`, `status`, `title`, `content`, `type`, `operation_id`, `send_prop`, `send_user`, `create_time`) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        DI::getDefault()->get('dbData')->execute($sql, [
            $app_id,
            $status,
            $title,
            $content,
            $type,
            $operation_id,
            $send_prop,
            $send_user,
            $create_time
        ]);
    }

    /**
     * 获得logs_attach数据
     */
    public function getAttachLogList($currentPage, $pageSize)
    {
        $sql = "SELECT * FROM `logs_attach` WHERE 1=1";
        if (!empty(DI::getDefault()->get('session')->get('app'))) {
            $app_id = DI::getDefault()->get('session')->get('app');
            $sql .= " AND app_id={$app_id}";
        }
        $sql .= " ORDER BY id DESC";
        $sql .= " LIMIT " . ($currentPage - 1) * $pageSize . ",$pageSize";
        $query = DI::getDefault()->get('dbData')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetchAll();

        return $data;
    }

    /**
     * 获取attachLog的记录总数
     */
    public function getAttachLogCount()
    {
        $sql = "SELECT COUNT(1) as count FROM `logs_attach` WHERE 1=1";
        if (!empty(DI::getDefault()->get('session')->get('app'))) {
            $app_id = DI::getDefault()->get('session')->get('app');
            $sql .= " AND app_id={$app_id}";
        }
        $query = DI::getDefault()->get('dbData')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $result = $query->fetch();

        return $result['count'];
    }

    /**
     * 通过id查找单条log
     * @param $id
     */
    public function getAttachLog($id)
    {
        $bind = [
            'id' => $id
        ];
        $sql = "SELECT `status`, `title`, `content`, `type`, `operation_id`, `agree_id`, `send_prop`, `send_user`, `create_time`, `update_time` FROM `logs_attach` WHERE id=:id";
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetch();

        return $data;
    }

    /**
     * 更新log日志
     */
    public function updateAttachLog($id, $user_id, $status)
    {
        $update_time = date('Y-m-d H:i:s', time());
        $bind = [
            'status' => $status,
            'user_id' => $user_id,
            'id' => $id,
            'update_time' => $update_time
        ];
        $sql = "UPDATE `logs_attach` SET `status`=:status, `agree_id`=:user_id, `update_time`=:update_time WHERE id=:id";
        $result = DI::getDefault()->get('dbData')->execute($sql, $bind);

        return $result;
    }

    /*
     * 补发操作
     */
    public function setProp($type, $data)
    {
        $result = $this->utilsModel->yarRequest('prop', $type, $data);
        return $result;
    }

    /*
     * 获取补发项
     */
    public function getAttribute()
    {
        if (empty($_COOKIE['attach'])) {
            $result = $this->utilsModel->yarRequest('prop', 'attribute', array());
            $_COOKIE['attach'] = json_encode($result);
            setcookie('attach', json_encode($result), time() + 7200, '/');
        }
        return json_decode($_COOKIE['attach'], true);
    }

    //dbTop
    public function getGame($class_id)
    {
        $sql = "SELECT * FROM class WHERE 1=1 AND id = '$class_id' ORDER BY create_time DESC";
        $query = DI::getDefault()->get('dbData')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetch();
        return $data;
    }

    public function getVersionList($gameid)
    {
        $sql = "SELECT * FROM games WHERE 1=1 AND game_id LIKE '" . $gameid . "%' ORDER BY id DESC";
        $query = DI::getDefault()->get('dbData')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetchAll();
        return $data;
    }

    public function getGames($status = 1)
    {
        $sql = "SELECT id,class_id,game_id,version,name,icon FROM games WHERE status=:status";
        $bind = array('status' => $status);
        if (!empty($_SESSION['resources']['allow_game'])) {
            $allow_game = '"' . implode('","', $_SESSION['resources']['allow_game']) . '"';
            $sql .= " AND game_id IN($allow_game)";
        }

        $query = DI::getDefault()->get('dbData')->query($sql, $bind); //$query->numRows();
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetchAll();
        return $data;
    }

    public function setDefaultApp()
    {
        $games = $this->getGames(1);
        if (count($games) == 1) {
            return $games[0];
        }
        return $games[1];
    }

    public function getGroups()
    {
        $sql = "SELECT id,class_id,name,icon FROM class";
        $query = DI::getDefault()->get('dbData')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetchAll();
        return $data;
    }

    public function getGamesByGroup()
    {
        $games = $this->getGames(1);
        $groups = $this->getGroups();
        if (!$games) {
            return false;
        }

        $result = [];

        foreach ($groups as $group) {
            $result[$group['class_id']]['info'] = $group;
        }

        foreach ($games as $game) {
            if (!isset($result[$game['class_id']])) {
                continue;
            }
            $result[$game['class_id']]['data'][$game['game_id']] = $game;
        }

        // 过滤
        foreach ($result as $key => $value) {
            if (empty($value['data'])) {
                unset($result[$key]);
            }
        }
        return $result;
    }

    public function saveData($data)
    {
        $sql = 'TRUNCATE TABLE class;';
        DI::getDefault()->get('dbData')->execute($sql);

        $sql = 'TRUNCATE TABLE games;';
        DI::getDefault()->get('dbData')->execute($sql);

        foreach ($data['data'] as $item) {
            $this->saveClass($item);

            foreach ($item['game'] as $game) {
                $this->saveGame($game);
            }
        }
    }

    private function saveClass($data)
    {
        $sql = "INSERT INTO `class`(`id`, `class_id`,`name`,create_time) VALUES (?, ?, ?, ? )";
        DI::getDefault()->get('dbData')->execute($sql, array(
            $data['id'],
            $data['class_id'],
            $data['name'],
            date('Y-m-d H:i:s', time()),
        ));
    }

    private function saveGame($data)
    {
        $sql = "INSERT INTO `games`(`id`,`game_id`, `class_id`,`version`, `name`,`en_name`,`status`,`domain`,`icon`,create_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
        DI::getDefault()->get('dbData')->execute($sql, array(
            $data['id'],
            $data['game_id'],
            $data['class_id'],
            $data['version'],
            $data['name'],
            $data['en_name'],
            $data['status'],
            $data['domain'],
            $data['icon'],
            date('Y-m-d H:i:s', time()),
        ));
    }

    public function propinfo($data)
    {
        $result = $this->utilsModel->yarRequest('User', 'propInfo', $data);
        if ($result['code'] == 0) {
            return $result['data'];
        } else {
            return false;
        }
    }

    public function getGuild($data)
    {
        $result = $this->utilsModel->yarRequest('Guild', 'getGuild', $data);
        if ($result['code'] == 0) {
            return $result['data'];
        } else {
            return false;
        }
    }
}