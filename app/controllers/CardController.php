<?php


/**
 * 礼品卡
 */
namespace MyApp\Controllers;


use MyApp\Models\Card;
use MyApp\Models\Page;
use MyApp\Models\Server;
use Phalcon\Mvc\Dispatcher;
use MyApp\Models\Utils;

class CardController extends ControllerBase
{
    private $cardModel;
    private $pageModel;
    private $utilsModel;
    private $serverModel;
    private $allow_role;

    public function initialize()
    {
        parent::initialize();
        $this->cardModel = new Card();
        $this->pageModel = new Page();
        $this->utilsModel = new Utils();
        $this->serverModel = new Server();
        $this->allow_role = [
            100,    // 系统管理员
            103     // 运营主管
        ];
    }

    /**
     * 卡片 - 概况信息
     */
    public function indexAction()
    {
        $do = $this->request->get('do', ['string', 'trim']);

        switch ($do) {
            case 'view':
                $this->view();
                break;
            case 'logs':
                $this->logs();
                break;
        }

        $currentPage = $this->request->get('page', 'int') ? $this->request->get('page', 'int') : 1;
        $pagesize = 10;

        $data['title'] = $this->request->get('title', ['string', 'trim']);
        $data['type'] = $this->request->get('type', ['string', 'trim']);
        $data['page'] = $currentPage;
        $data['size'] = $pagesize;
        $role_id = $this->session->get('role_id');
        $result = $this->cardModel->getLists($data);
        $this->view->page = '';
        if (isset($result['count']) && $result['count'] > 0) {
            $this->view->page = $this->pageModel->getPage($result['count'], $pagesize, $currentPage);
        }
        foreach($result['data'] as $key=>$item){
            $tempex = strtotime($item['expired_in']);
            $tempstart = strtotime($item['start_time']);
            $temptime = time();
            if($temptime > $tempex){
                $result['data'][$key]['expire'] = 1;
            }elseif($temptime < $tempstart) {
                $result['data'][$key]['expire'] = 2;
            }else {
                $result['data'][$key]['expire'] = 0;
            }
            $result['data'][$key]['is_show'] = false;
            foreach ($role_id as $role) {
                if (in_array($role, $this->allow_role)) {
                    $result['data'][$key]['is_show'] = true;
                }
            }
        }

        $this->view->lists = $result['data'];
        //$this->view->servers = $servers;
        $this->view->query = $data;
    }


    /**
     * 卡片 - 管理
     */
    public function manageAction()
    {
        $do = $this->request->get('do', ['string', 'trim']);

        switch ($do) {
            case 'create':
                $this->create();
                break;
            case 'edit':
                $data['id'] = $this->request->get('id', 'int');
                if (!$data['id']) {
                    Utils::tips('error', '数据不完整', '/card/index');
                }

                $card = $this->cardModel->findCard($data);
                if (!$card) {
                    Utils::tips('error', '没有此数据', '/card/index');
                }

                if ($_POST) {
                    $this->edit();
                }

                $this->view->card = $card['data'];
                $this->view->pick("card/edit");
                break;
            case 'remove':
                $this->remove();
                break;
            case 'download':
                $this->download();
                break;
            case 'examine':
                if ($_POST) {
                    $this->examine();
                }
                $data['id'] = $this->request->get('id', 'int');
                if (!$data['id']) {
                    Utils::tips('error', '数据不完整', '/card/index');
                }
                $card = $this->cardModel->findCard($data);
                if (!$card) {
                    Utils::tips('error', '没有此数据', '/card/index');
                }

                $this->view->card = $card['data'];
                $this->view->pick('card/examine');
        }
    }


    // 卡片 - 日志
    private function logs()
    {
    }


    // 卡片 - 详细预览
    private function view()
    {
    }

    // 卡片 - 下载
    private function download()
    {
        $card_id = $this->request->get('id');
        if (!$card_id) {
            Utils::tips('error', '数据不完整', '/card/index');
        }

        if (!$this->cardModel->downloadCard($card_id, 'Card', 'download')) {
            Utils::tips('error', '下载失败', '/card/index');
        }
    }

    // 卡片 - 审核
    private function examine()
    {
        if ($_POST) {
            $data['id'] = $this->request->get('id', 'int');
            $data['title'] = $this->request->get('title', ['string', 'trim']);
            $data['type'] = $this->request->get('type', ['string', 'trim']);
            $data['expired_in'] = $this->request->get('expired_in', ['string', 'trim']);
            $data['start_time'] = $this->request->get('start_time', ['string', 'trim']);
            $data['limit_times'] = $this->request->get('limit_times', ['string', 'trim']);
            $data['data'] = $this->request->get('data', ['string', 'trim']);
            $data['intro'] = $this->request->get('formcontent');

            $result = $this->cardModel->examineCard($data);
            if ($result) {
                Utils::tips('success', '审核成功，礼包码已经可以使用', '/card/index');
            } else {
                Utils::tips('error', '审核失败', '/card/index');
            }
        }
    }

    // 卡片 - 创建
    private function create()
    {
        if ($_POST) {
            $data['title'] = $this->request->get('title', ['string', 'trim']);
            $data['type'] = $this->request->get('type', ['string', 'trim']);
            $data['expired_in'] = $this->request->get('expired_in', ['string', 'trim']);
            $data['start_time'] = $this->request->get('start_time', ['string', 'trim']);
            $data['data'] = $this->request->get('data', ['string', 'trim']);
            $data['count'] = $this->request->get('count', ['string', 'trim']);
            $data['limit_times'] = $this->request->get('limit_times', ['string', 'trim']);
            $data['code_limit_times'] = $this->request->get('code_limit_times', ['string', 'trim']);
            $data['intro'] = $this->request->get('formcontent');

            if (!$data['title'] || !$data['type'] || !$data['expired_in'] || !$data['data'] || !$data['count']) {
                Utils::tips('error', '数据不完整', '/card/manage?do=create');
            }

            if($data['count'] <= 0 ){
                Utils::tips('error', '数量必须大于0', '/card/manage?do=create');
            }

            $data['expired_in'] = $this->utilsModel->toTimeZone($data['expired_in'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());

            $result = $this->cardModel->createCard($data);
            if ($result) {
                Utils::tips('success', '添加成功', '/card/index');
            }
            else {
                Utils::tips('error', '添加失败', '/card/index');
            }
        }
        $this->view->pick("card/create");
    }


    // 卡片 - 编辑
    private function edit()
    {
        if ($_POST) {
            $data['id'] = $this->request->get('id', 'int');
            $data['title'] = $this->request->get('title', ['string', 'trim']);
            $data['type'] = $this->request->get('type', ['string', 'trim']);
            $data['expired_in'] = $this->request->get('expired_in', ['string', 'trim']);
            $data['start_time'] = $this->request->get('start_time', ['string', 'trim']);
            $data['limit_times'] = $this->request->get('limit_times', ['string', 'trim']);
            $data['data'] = $this->request->get('data', ['string', 'trim']);
            $data['intro'] = $this->request->get('formcontent');

            $result = $this->cardModel->editCard($data);

            $data['expired_in'] = $this->utilsModel->toTimeZone($data['expired_in'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());

            if ($result) {
                Utils::tips('success', '修改成功', '/card/index');
            }
            else {
                Utils::tips('error', '修改失败', '/card/index');
            }
        }
    }


    // 卡片 - 删除
    private function remove()
    {
        $data['id'] = $this->request->get('id', ['string', 'trim']);
        if (!$data['id']) {
            Utils::tips('error', '数据不完整', '/card/index');
        }

        $activity = $this->cardModel->findCard($data);
        if ($activity == 1) {
            Utils::tips('error', '没有此数据', '/card/index');
        }

        $result = $this->cardModel->removeCard($data);

        if ($result) {
            Utils::tips('success', '删除成功', '/card/index');
        }
        else {
            Utils::tips('error', '删除失败', '/card/index');
        }
    }

    /**
     * 激活码查询
     */
    public function searchAction()
    {
        $data['role_id']  = $this->request->get('role_id', ['string', 'trim']);
        $data['topic_id'] = $this->request->get('card_id', ['string', 'trim']);

        if (!$data['role_id'] && !$data['topic_id']) {
            Utils::tips('error', '数据不完整', '/card/index');
        }

        $result = $this->cardModel->searchCard($data);

        if (!$result) {
            Utils::tips('error', '查询失败');
            exit;
        }

        $this->view->cards = $result['data'];
        $this->view->pick('card/search');
    }

}