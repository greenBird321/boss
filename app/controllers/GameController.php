<?php


/**
 * 游戏信息
 */
namespace MyApp\Controllers;

use MyApp\Models\Page;
use MyApp\Models\Trade;
use Myapp\Models\Utils;
use MyApp\Models\Server;
use Phalcon\Mvc\Dispatcher;

class GameController extends ControllerBase
{
    private $tradeModel;
    private $serverModel;
    private $pageModel;
    private $send_type;
    private $allow_role;

    public function initialize()
    {
        parent::initialize();
        $this->tradeModel = new Trade();
        $this->serverModel = new Server();
        $this->pageModel = new Page();
        $this->send_type = [
            'coin'   => '金币',
            'exp'    => '经验',
            'mail'   => '邮件',
            'attach' => '道具',
        ];
        $this->allow_role = [
            100,    // 系统管理员
            103     // 运营主管
        ];
    }

    public function indexAction()
    {

    }


    /**
     * 玩家信息
     */
    public function playerAction()
    {
        $data['zone'] = $this->request->get('server', ['string', 'trim']);
        $show = $server = 0;
        $users = [];
        if ($data['zone']) {
            $data['user_id'] = $this->request->get('user_id', ['string', 'trim']);
            $data['name'] = $this->request->get('name', ['string', 'trim']);
            $data['account_id'] = $this->request->get('account_id', ['string', 'trim']);

            if (empty($data['zone']) || (empty($data['user_id']) && empty($data['name']) && empty($data['account_id']))) {
                Utils::tips('error', '数据不完整', '/game/player');
                exit;
            }

            $result = $this->gameModel->profile($data);
            if (empty($result)) {
                Utils::tips('error', '服务器ID错误', '/game/player');
                exit;
            }

            if ($result['code'] == 1) {
                Utils::tips('error', '没有该用户', '/game/player');
                exit;
            }
            if ($result['count'] == 1) {
                $where['user_id'] = $result['data']['account_id'];
                $count = $this->tradeModel->getCount($where);
                $this->view->trade = $this->tradeModel->getList($where, 1, $count);

                $this->view->user = $result['data'];
                $this->view->pick("game/playerone");
            }
            else {
                $show = 1;
                $users = $result['data'];
                $server = $data['zone'];
                $this->view->pick("game/player");
            }
        }

        $result = $this->serverModel->getLists();
        $this->view->users = $users;
        $this->view->show = $show;
        $this->view->server = $server;
        $this->view->lists = $result;
    }


    /**
     * 信息完善奖励
     */
    public function completeAction()
    {
    }


    /**
     * 充值排行
     */
    public function topAction()
    {
    }

    public function attachListAction()
    {
        $currentPage = $this->request->get('page', 'int') ? $this->request->get('page', 'int') : 1;
        $pagesize = 10;

        $logList = $this->gameModel->getAttachLogList($currentPage, $pagesize);
        foreach ($logList as $key => $log) {
            $logList[$key]['type'] = $this->send_type[$log['type']];
        }

        $this->view->logs = $this->view->page = '';
        $count = $this->gameModel->getAttachLogCount();
        $this->view->logs = $logList;
        $this->view->page = $this->pageModel->getPage($count, $pagesize, $currentPage);
    }

    /**
     * 补发管理
     */
    public function attachAction()
    {
        if ($_POST) {
            $type            = $this->request->get('action', ['string', 'trim']);
            $data['zone']    = $this->request->get('server', ['string', 'trim']);
            $data['user_id'] = $this->request->get('user_id', ['string', 'trim']);
            $data['amount']  = $this->request->get('amount', ['string', 'trim']);
            $data['title']   = $this->request->get('title', ['string', 'trim']);
            $data['msg']     = $this->request->get('msg', ['string', 'trim']);

            // 组装log数据
            $logData['type']         = $type;
            $logData['app_id']       = $this->session->get('app');
            $logData['operation_id'] = $this->session->get('user_id');
            $role_id                 = $this->session->get('role_id');
            $logData['title']        = $data['title'];
            $logData['msg']          = $data['msg'];
            $logData['send_prop']    = $data['amount'];
            $logData['send_user']    = $data['zone'] . '-' . $data['user_id'];

            if (empty($type) || empty($data['zone']) || empty($data['user_id'])) {
                echo json_encode(array('error' => 1, 'data' => '数据不完整'));
                exit;
            }

            if ($type == 'attach' && empty($data['amount'])) {
                echo json_encode(array('error' => 1, 'data' => '数据不完整'));
                exit;
            }

            if (($type == 'coin' || $type == 'exp') && empty($data['amount'])) {
                echo json_encode(array('error' => 1, 'data' => '数据不完整'));
                exit;
            }

            if ($type == 'mail' && empty($data['msg'])) {
                echo json_encode(array('error' => 1, 'data' => '数据不完整'));
                exit;
            }

            if (stristr($type, 'attach')) {
                $data['attach'] = $data['amount'];
                unset($data['amount']);
            }

            // 判断当前role等级，如果是这两个role的话，不用批复直接发送
            $isAdmin = false;
            foreach ($role_id as $role) {
                if (in_array($role, $this->allow_role)) {
                    $isAdmin = true;
                    break;
                }
            }

            if ($isAdmin) {
                $result = $this->gameModel->setProp($type, $data);
                if (!empty($result)) {
                    echo json_encode(array('error' => 0, 'data' => '补发成功'));
                    $logData['status'] = 1;
                    $this->gameModel->setAttachLog($logData);
                    exit;
                } else {
                    echo json_encode(array('error' => 1, 'data' => '补发失败'));
                    $logData['status'] = 2;
                    $this->gameModel->setAttachLog($logData);
                    exit;
                }
            }
            // 记录log
            $this->gameModel->setAttachLog($logData);
            echo json_encode(['error' => 0, 'data' => '创建成功等待审核']);
            exit;
        }

        $result = $this->gameModel->getAttribute();
        $server = $this->serverModel->getLists();

        $this->view->server = $server;
        $this->view->lists = $result['data'];
    }

    /**
     * log详情页
     */
    public function viewAction()
    {
        if ($_POST) {
            $id = $this->request->get('id');
            $status = $this->request->get('status');
            $agree_id = $this->_user_id;
            if (!$this->gameModel->updateAttachLog($id, $agree_id, $status)) {
                echo json_encode(['error' => 1, 'data' => '审核失败']);
                exit;
            }

            $attach_log = $this->gameModel->getAttachLog($id);
            list($zone, $send_user_id) = explode('-', $attach_log['send_user']);
            $data = [
                'title' => $attach_log['title'],
                'msg' => $attach_log['content'],
                'zone' => $zone,
                'user_id' => $send_user_id,
                'amount' => $attach_log['send_prop'],
            ];

            $result = $this->gameModel->setProp($attach_log['type'], $data);

            if (!empty($result)) {
                echo json_encode(array('error' => 0, 'data' => '补发成功'));
                $logData['status'] = 1;
                exit;
            } else {
                echo json_encode(array('error' => 1, 'data' => '补发失败'));
                $logData['status'] = 2;
                exit;
            }
        }
        $id = $this->request->get('id') ? $this->request->get('id') : 1;
        $log_info = $this->gameModel->getAttachLog($id);
        $log_info['type'] = $this->send_type[$log_info['type']];
        $isAdmin = false;
        $roles = $this->session->get('role_id');
        foreach ($roles as $role) {
            if (in_array($role, $this->allow_role)) {
                $isAdmin = true;
                break;
            }
        }

        $this->view->log = $log_info;
        $this->view->isAdmin = $isAdmin;
        $this->view->id = $id;
    }

    /**
     * 补发记录
     */
    public function logsAction()
    {
    }

}