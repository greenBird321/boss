<?php


/**
 * 服务器管理
 */
namespace MyApp\Controllers;


use MyApp\Models\Server;
use Phalcon\Mvc\Dispatcher;
use MyApp\Models\Utils;

class ServerController extends ControllerBase
{
    private $serverModel;

    private $server_translate = [
            0  => '普通',
            1  => '爆满',
            2  => '繁荣',
            3  => '流畅',
            4  => '维护',
    ];

    private $flag = [
        0 => '推荐',
        1 => '默认',
        3 => '强烈推荐',
    ];

    private $new_server = [
        0 => '否',
        1 => '是',
    ];

    public function initialize()
    {
        parent::initialize();
        $this->serverModel = new Server();

    }

    public function indexAction()
    {
        $result = $this->serverModel->getLists();
        foreach ($result as $key => $value) {
            $result[$key]['open_mode'] = $this->server_translate[$value['open_mode']];
        }
        $this->view->lists = $result;
    }

    /**
     * 创建服务器
     */
    public function createAction()
    {
        if ($_POST) {
            $data['name'] = $this->request->get('name', ['string','trim']);
            $data['host'] = $this->request->get('host', ['string','trim']);
            $data['port'] = $this->request->get('port', 'int');
            $data['open_mode'] = $this->request->get('open_mode', 'int');
            $data['flag'] = $this->request->get('server_flag', 'int');

            if (!$data['name'] || !$data['host']) {
                Utils::tips('error', '数据不完整', '/server/index');
            }

            $result = $this->serverModel->createServer($data);

            if($result){
                Utils::tips('success', '添加成功', '/server/index');
            }else{
                Utils::tips('error', '添加失败', '/server/index');
            }
        }

        $this->view->status = $this->server_translate;
        $this->view->flag = $this->flag;
        $this->view->is_new = $this->new_server;
    }

    /**
     * 修改服务器
     */
    public function editAction()
    {
        $data['id'] = $this->request->get('id', 'int');
        if ($data['id'] == null) {
            Utils::tips('error', '数据不完整', '/server/index');
        }

        $server = $this->serverModel->findServer($data);
        if ($server == 1) {
            Utils::tips('error', '没有此数据', '/server/index');
        }

        if ($_POST) {
            $data['id']        = $this->request->get('id', 'int');
            $data['name']      = $this->request->get('name', ['string', 'trim']);
            $data['host']      = $this->request->get('host', ['string', 'trim']);
            $data['port']      = $this->request->get('port', 'int');
            $data['open_mode'] = $this->request->get('open_mode', ['string', 'trim']);
            $data['flag']      = $this->request->get('server_flag', 'int');
            $data['is_new']    = $this->request->get('is_new', 'int');

            if ($data['id'] == null || !$data['name'] || !$data['host'] ) {
                Utils::tips('error', '数据不完整', '/server/index');
            }
            $result = $this->serverModel->editServer($data);

            if($result){
                Utils::tips('success', '修改成功', '/server/index');
            }else{
                Utils::tips('error', '修改失败', '/server/index');
            }
        }

        $this->view->status = $this->server_translate;
        $this->view->flag = $this->flag;
        $this->view->is_new = $this->new_server;
        $this->view->server = $server['data'];
    }

    /**
     * 删除服务器
     */
    public function removeAction(){
        $data['id'] = $this->request->get('id', 'int');
        if (!$data['id']) {
            Utils::tips('error', '数据不完整', '/server/index');
        }

        $activity = $this->serverModel->findServer($data);
        if ($activity == 1) {
            Utils::tips('error', '没有此数据', '/server/index');
        }

        $result = $this->serverModel->removeServer($data);

        if($result){
            Utils::tips('success', '删除成功', '/server/index');
        }else{
            Utils::tips('error', '删除失败', '/server/index');
        }
    }

    /**
     * 软件包
     */
    public function packageAction()
    {

    }

    /**
     * 创建软件包
     */
    public function createpackageAction()
    {

    }

}