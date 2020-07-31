<?php


/**
 * 账号管理
 */
namespace MyApp\Controllers;


use MyApp\Models\Account;
use MyApp\Models\Utils;
use MyApp\Models\Server;
use Phalcon\Mvc\Dispatcher;

class AccountController extends ControllerBase
{
    private $accountModel;
    private $utilsModel;
    private $serverModel;

    public function initialize()
    {
        parent::initialize();
        $this->accountModel = new Account();
        $this->utilsModel = new Utils();
        $this->serverModel = new Server();
    }

    public function indexAction()
    {
    }


    /**
     * 黑名单
     */
    public function blacklistAction()
    {
        $app_id = $this->session->get('app');
        $blacklists = $this->accountModel->getBlacklist($app_id);
        foreach ($blacklists as $key => $value) {
            $blacklists[$key]['start_time'] = date("Y-m-d H:i:s", $value['start_time']);
            $blacklists[$key]['end_time']   = date("Y-m-d H:i:s", $value['end_time']);
        }

        // 发送给Rpc获取玩家角色名称
        //$result = $this->utilsModel->yarRequest('User', 'userinfo', $blacklists);
        $this->view->blacklists = $blacklists;
        $this->view->pick("account/blacklist");
    }

    /**
     * 黑名单详情
     */
    public function infoAction()
    {
        if ($_POST) {
            $data['id']         = $this->request->get('id');
            $data['start_time'] = strtotime($this->request->get('start'));
            $data['end_time']   = strtotime($this->request->get('end'));

            if ($data['start_time'] >= $data['end_time']) {
                Utils::tips('error', '结束时间不能小于开始时间', '/account/blacklist');
                exit;
            }

            // 更新黑名单表
            $result = $this->accountModel->updateDataById($data);
            // 更新account表的状态
            $r      = $this->accountModel->updateAccountById($data['id'], 0);

            if (!$result || !$r) {
                Utils::tips('error', '更新错误', '/account/blacklist');
                exit;
            }

            Utils::tips('success', '更新成功', '/account/blacklist');
            exit;
        }

        $id                 = $this->request->get('id');
        $info               = $this->accountModel->getDataById($id);
        $info['start_time'] = date('Y-m-d H:i:s', $info['start_time']);
        $info['end_time']   = date('Y-m-d H:i:s', $info['end_time']);
        $server_lists       = $this->serverModel->getLists();

        foreach ($server_lists as $server) {
            if ($info['zone'] == $server['server_id']) {
                $server_name = $server['name'];
            }
        }

        $this->view->data       = $info;
        $this->view->servername = $server_name;
        $this->view->pick('account/info');
    }

    /**
     * 删除黑名单记录
     */
    public function deleteAction()
    {
        $id      = $this->request->get('id');
        $account = $this->accountModel->getDataById($id);
        // 通知服务端解除玩家封禁
        $result = $this->accountModel->cancelPlayerBan($account);
        if ($result['code'] != 0) {
            Utils::tips('error', '游戏服务器移除失败', '/account/blacklist');
            exit;
        }

        $result  = $this->accountModel->deleteDataById($id);

        if (!$result) {
            Utils::tips('error', '删除失败', '/account/blacklist');
            exit;
        }

        Utils::tips('success', '删除成功', '/account/blacklist');
        exit;
    }

    /**
     * 创建黑名单
     */
    public function createAction()
    {
        if ($_POST) {
            $zone       = $this->request->get('server', 'trim');
            $end        = $this->request->get('end', 'trim');
            $role_id    = $this->request->get('role_id', 'trim');
            $name       = $this->request->get('name', 'trim');

            if (strtotime($end) <= time()) {
                Utils::tips('error', '结束时间不能小于开始时间', '/account/blacklist');
                exit;
            }

            // 通过用户名换取用户id
            if (!empty($name)) {
                $role_id = $this->accountModel->getRoleIdByName(['zone' => $zone, 'name' => $name]);
                if (!$role_id) {
                    Utils::tips('error', '获取角色id错误', '/account/blacklist');
                    exit;
                }
            }

            // 查询之前有没有加过，如果有则不能添加
            $app_id  = $this->session->get('app');
            $account = $this->accountModel->findRoleByRoleId(['roleId' => $role_id, 'appId' => $app_id]);

            if (!empty($account['count'])) {
                Utils::tips('error', '该用户已经被拉黑', '/account/blacklist');
                exit;
            }

            //todo 此处通知cp，让用户立即下线并封禁该角色
            $response = $this->accountModel->playerOffline(['zone' => $zone, 'role_id' => $role_id, 'end' => strtotime($end)]);

            if ($response['code'] != 0) {
                Utils::tips('error', '创建失败', '/account/blacklist');
                exit;
            }

            // 当所有操作都通过，才进行记录
            $this->accountModel->setBlackListData([
                'user_id' => $role_id,
                'app_id' => $app_id,
                'zone' => $zone,
                'start_time' => strtotime('Y-m-d H:i:s', time()),
                'end_time' => strtotime($end)
            ]);

            Utils::tips('success', '创建成功', '/account/blacklist');
            exit;
        }

        $this->view->lists = $this->serverModel->getLists();
        $this->view->pick('account/create');
    }

    /**
     * 信息完善 - 配置预览
     */
    public function fillAction()
    {
    }


    /**
     * 信息完善 - 日志
     */
    public function fill_logsAction()
    {
    }


    /**
     * 信息完善 - 配置管理
     */
    public function fill_manageAction()
    {
        $do = $this->request->get('do', ['string', 'trim']);

        switch ($do) {
            case 'create':
                // do something
                break;
            case 'edit':
                // do something
                break;
            case 'remove':
                // do something
                break;
        }
    }

}