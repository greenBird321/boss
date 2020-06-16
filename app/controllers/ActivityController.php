<?php


/**
 * 活动
 */
namespace MyApp\Controllers;


use MyApp\Models\Activity;
use MyApp\Models\Game;
use MyApp\Models\Page;
use MyApp\Models\Server;
use MyApp\Models\Utils;
use Phalcon\Mvc\Dispatcher;

class ActivityController extends ControllerBase
{

    private $activityModel;
    private $utilsModel;
    private $serverModel;
    private $pageModel;


    public function initialize()
    {
        parent::initialize();
        $this->activityModel = new Activity();
        $this->utilsModel = new Utils();
        $this->pageModel = new Page();
        $this->serverModel = new Server();
    }


    /**
     * 活动列表
     */
    public function indexAction()
    {
        $currentPage = $this->request->get('page', 'int') ? $this->request->get('page', 'int') : 1;
        $pagesize = 10;

        $data['page'] = $currentPage;
        $data['size'] = $pagesize;

        $result = $this->activityModel->getMLlists($data);

        $this->view->page = '';
        if (isset($result['count']) && $result['count'] > 0) {
            $this->view->page = $this->pageModel->getPage($result['count'], $pagesize, $currentPage);
        }
        $this->view->lists = $result['data'];
        $this->view->query = $data;
    }


    /**
     * 操作管理
     */
    public function manageAction()
    {
        $do = $this->request->get('do', ['string', 'trim']);

        switch ($do) {
            case 'create':
                $this->create();
                break;

            case 'edit':
                $this->edit();
                break;

            case 'cfg':
                $this->cfg();
                break;

            case 'remove':
                $this->remove();
                break;
        }
    }


    public function logsAction()
    {
        $currentPage = $this->request->get('page', 'int') ? $this->request->get('page', 'int') : 1;
        $pagesize = 30;

        $data['item_id'] = $this->request->get('item_id', ['string', 'trim']);
        $data['user_id'] = $this->request->get('user_id', ['string', 'trim']);
        $data['cfg_id'] = $this->request->get('cfg_id', ['string', 'trim']);
        $data['prop'] = $this->request->get('prop', ['string', 'trim']);
        $data['start_time'] = $this->request->get('start_time', ['string', 'trim']);
        $data['end_time'] = $this->request->get('end_time', ['string', 'trim']);
        $isquery = empty(array_filter($data)) ? 0 : 1;
        $data['page'] = $currentPage;
        $data['size'] = $pagesize;

        $result = $this->activityModel->logs($data);

        $this->view->page = $this->pageModel->getPage($result['count'], $pagesize, $currentPage);
        $this->view->lists = $result['data'];
        $this->view->query = $data;
        $this->view->isquery = $isquery;
    }


    /**
     * 导入活动
     */
    public function importAction()
    {
        if (!empty($_FILES)) {
            $server   = empty($this->request->get('server')) ? 0 : $this->request->get('server');
            $files    = $_FILES;
            $error    = $files['activity']['error'];
            $filename = $files['activity']['name'];
            list($name, $ext) = explode('.', $filename);
            $filepath = __DIR__ . '/../../public/files/' . date("Y-m-d H:i:s")."-$filename";
            if ($error > 0) {
                Utils::tips('error', '上传文件失败', '/activity/import');
                exit;
            }

            if (is_array($server)) {
                $server = implode(',', $server);
            }

            $allow_extension = [
                'bytes'
            ];

            if (!in_array($ext, $allow_extension)) {
                Utils::tips('error', '文件格式错误', '/activity/import');
                exit;
            }

            // 将文件转移到正式文件夹
            if (!move_uploaded_file($files['activity']['tmp_name'], $filepath)) {
                Utils::tips('error', '上传文件失败,请重试', '/activity/import');
                exit;
            }

            // 读取文件内容
            $content = file_get_contents($filepath);

            $data = [
                'zone' => $server,
                'title' => $name,
                'content' => $content,
            ];
            // 发送给rpc并且入库
            $result = $this->activityModel->importActivity($data);
            if ($result) {
                Utils::tips('success', '上传成功', '/activity/import');
            }

            Utils::tips('error', '上传失败', '/activity/import');
        }

        $server = $this->serverModel->getLists();
        $this->view->lists = $server;
        $this->view->pick("activity/import");
    }


    /**
     * 添加活动
     */
    private function create()
    {
        if ($_POST) {
            $data['title'] = $this->request->get('title', ['string', 'trim']);
            $data['type'] = $this->request->get('type', ['string', 'trim']);
            $data['start_time'] = $this->request->get('start_time', ['string', 'trim']);
            $data['end_time'] = $this->request->get('end_time', ['string', 'trim']);
            $data['zone'] = $this->request->get('zone', ['string', 'trim']);
            $data['channel'] = $this->request->get('channel', ['string', 'trim']);
            $data['url'] = $this->request->get('url', ['string', 'trim']);
            $data['img'] = $this->request->get('img', ['string', 'trim']);
            $data['img_small'] = $this->request->get('img_small', ['string', 'trim']);
            $data['custom'] = $this->request->get('custom', ['string', 'trim']);
            $data['content'] = $this->request->get('formcontent');
            $data['visible'] = empty($this->request->get('visible', 'int')) ? 0 : $this->request->get('visible', 'int');
            $data['status'] = empty($this->request->get('status', 'int')) ? 0 : $this->request->get('status', 'int');
            $data['sort'] = $this->request->get('sort', 'int');

            if (!$data['title'] || !$data['start_time'] || !$data['end_time']) {
                Utils::tips('error', '数据不完整', '/activity/index');
            }

            $data['start_time'] = $this->utilsModel->toTimeZone($data['start_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());
            $data['end_time'] = $this->utilsModel->toTimeZone($data['end_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());

            $result = $this->activityModel->createActivity($data);

            if ($result) {
                Utils::tips('success', '添加成功', '/activity/index');
            }
            else {
                Utils::tips('error', '添加失败', '/activity/index');
            }
        }
        $this->view->pick("activity/manage_create");
    }


    /**
     * 修改活动
     */
    private function edit()
    {
        $data['id'] = $this->request->get('id', 'int');
        if (!$data['id']) {
            Utils::tips('error', '数据不完整', '/activity/index');
        }

        $activity = $this->activityModel->findActivity($data);
        if ($activity == 1) {
            Utils::tips('error', '没有此数据', '/activity/index');
        }

        if ($_POST) {
            $data['id'] = $this->request->get('id', 'int');
            $data['title'] = $this->request->get('title', ['string', 'trim']);
            $data['type'] = $this->request->get('type', ['string', 'trim']);
            $data['start_time'] = $this->request->get('start_time', ['string', 'trim']);
            $data['end_time'] = $this->request->get('end_time', ['string', 'trim']);
            $data['zone'] = $this->request->get('zone', ['string', 'trim']);
            $data['channel'] = $this->request->get('channel', ['string', 'trim']);
            $data['url'] = $this->request->get('url', ['string', 'trim']);
            $data['img'] = $this->request->get('img', ['string', 'trim']);
            $data['img_small'] = $this->request->get('img_small', ['string', 'trim']);
            $data['custom'] = $this->request->get('custom', ['string', 'trim']);
            $data['content'] = $this->request->get('formcontent');
            $data['visible'] = empty($this->request->get('visible', 'int')) ? 0 : $this->request->get('visible', 'int');
            $data['status'] = empty($this->request->get('status', 'int')) ? 0 : $this->request->get('status', 'int');
            $data['sort'] = $this->request->get('sort', 'int');

            if (!$data['title'] || !$data['start_time'] || !$data['end_time']) {
                Utils::tips('error', '数据不完整', '/activity/index');
            }
            $data['start_time'] = $this->utilsModel->toTimeZone($data['start_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());
            $data['end_time'] = $this->utilsModel->toTimeZone($data['end_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());

            $result = $this->activityModel->editActivity($data);

            if ($result) {
                Utils::tips('success', '修改成功', '/activity/index');
            }
            else {
                Utils::tips('error', '修改失败', '/activity/index');
            }
        }

        $this->view->activity = $activity['data'];
        $this->view->pick("activity/manage_edit");
    }


    /**
     * 删除活动
     */
    private function remove()
    {
        $data['id'] = $this->request->get('id', ['string', 'trim']);
        if (!$data['id']) {
            Utils::tips('error', '数据不完整', '/activity/index');
        }

        $activity = $this->activityModel->findActivity($data);
        if ($activity == 1) {
            Utils::tips('error', '没有此数据', '/activity/index');
        }

        $result = $this->activityModel->removeActivity($data);

        if ($result) {
            Utils::tips('success', '删除成功', '/activity/index');
        }
        else {
            Utils::tips('error', '删除失败', '/activity/index');
        }
    }


    /**
     * 查看活动配置
     */
    private function cfg()
    {
        if ($_POST) {
            $dataNew['item_id'] = $data['item_id'] = $this->request->get('id', ['string', 'trim']);

            if (!$data['item_id']) {
                Utils::tips('error', '数据不完整', '/activity/index');
            }

            $cfgid = $this->request->get('cfgid', 'int');
            $step = $this->request->get('step', 'int');
            $prop = $this->request->get('prop', ['string', 'trim']);
            $sort = $this->request->get('sort', 'int');
            $title = $this->request->get('title', ['string', 'trim']);
            $content = $this->request->get('content', ['string', 'trim']);
            $remark = $this->request->get('remark', ['string', 'trim']);
            $stepNew = $this->request->get('stepNew', 'int');
            $propNew = $this->request->get('propNew', ['string', 'trim']);
            $sortNew = $this->request->get('sortNew', 'int');
            $titleNew = $this->request->get('titleNew', ['string', 'trim']);
            $contentNew = $this->request->get('contentNew', ['string', 'trim']);
            $remarkNew = $this->request->get('remarkNew', ['string', 'trim']);

            if ($cfgid) {
                foreach ($cfgid as $item) {
                    $data['id'] = $item;
                    $data['step'] = $step[$item];
                    $data['prop'] = $prop[$item];
                    $data['title'] = $title[$item];
                    $data['content'] = $content[$item];
                    $data['remark'] = $remark[$item];
                    $data['sort'] = $sort[$item];

                    if ($data['step']) {
                        $this->activityModel->editActivityCfg($data);
                    }
                    else {
                        $this->activityModel->removeActivityCfg(array('id' => $item));
                    }
                }
            }

            foreach ($stepNew as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                $dataNew['step'] = $value;
                $dataNew['prop'] = $propNew[$key];
                $dataNew['title'] = $titleNew[$key];
                $dataNew['content'] = $contentNew[$key];
                $dataNew['remark'] = $remarkNew[$key];
                $dataNew['sort'] = $sortNew[$key];

                $this->activityModel->createActivityCfg($dataNew);
            }

            Utils::tips('success', '配置活动成功', '/activity/index');
        }


        $data['item_id'] = $this->request->get('id', ['string', 'trim']);
        if (!$data['item_id']) {
            Utils::tips('error', '数据不完整', '/activity/index');
        }
        $activity_cfg = $this->activityModel->listActivityCfg($data);

        $this->view->activitycfg = $activity_cfg['data'];
        $this->view->actcount = count($activity_cfg['data']);
        $this->view->activityid = $data['item_id'];
        $this->view->pick("activity/manage_cfg");
    }

}