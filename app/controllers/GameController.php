<?php


/**
 * 游戏信息
 */
namespace MyApp\Controllers;

use MyApp\Models\Trade;
use Myapp\Models\Utils;
use MyApp\Models\Server;
use Phalcon\Mvc\Dispatcher;

class GameController extends ControllerBase
{
    private $tradeModel;
    private $serverModel;

    public function initialize()
    {
        parent::initialize();
        $this->tradeModel = new Trade();
        $this->serverModel = new Server();
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


    /**
     * 补发管理
     */
    public function attachAction()
    {
        if ($_POST) {
            $type = $this->request->get('action', ['string', 'trim']);
            $data['zone'] = $this->request->get('server', ['string', 'trim']);
            $data['user_id'] = $this->request->get('user_id', ['string', 'trim']);
            $data['amount'] = $this->request->get('amount', ['string', 'trim']);
            $data['title'] = $this->request->get('title', ['string', 'trim']);
            $data['msg'] = $this->request->get('msg', 'string');

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

            $result = $this->gameModel->setProp($type, $data);

            if (!empty($result)) {
                echo json_encode(array('error' => 0, 'data' => '补发成功'));
                exit;
            }
            else {
                echo json_encode(array('error' => 1, 'data' => '补发失败'));
                exit;
            }
        }

        $result = $this->gameModel->getAttribute();
        $server = $this->serverModel->getLists();

        $this->view->server = $server;
        $this->view->lists = $result['data'];
    }


    /**
     * 补发记录
     */
    public function logsAction()
    {
    }

}