<?php


/**
 * 公告
 */
namespace MyApp\Controllers;


use MyApp\Models\Notice;
use MyApp\Models\Page;
use MyApp\Models\Utils;
use Phalcon\Mvc\Dispatcher;

class NoticeController extends ControllerBase
{
    private $noticeModel;
    private $utilsModel;
    private $pageModel;

    public function initialize()
    {
        parent::initialize();
        $this->noticeModel = new Notice();
        $this->utilsModel = new Utils();
        $this->pageModel = new Page();

    }

    /**
     * 公告列表
     */
    public function indexAction()
    {
        $currentPage = $this->request->get('page', 'int') ? $this->request->get('page', 'int') : 1;
        $pagesize = 10;

        $data['title'] = $this->request->get('title', ['string', 'trim']);
        $start_time = $this->request->get('start_time', ['string', 'trim']);
        $end_time = $this->request->get('end_time', ['string', 'trim']);
        $data['page'] = $currentPage;
        $data['size'] = $pagesize;

        $data['start_time'] = !empty($start_time) ? $this->utilsModel->toTimeZone($start_time, $this->config->setting->timezone,
            $this->utilsModel->getTimeZone()) : '';
        $data['end_time'] = !empty($end_time) ? $this->utilsModel->toTimeZone($end_time, $this->config->setting->timezone,
            $this->utilsModel->getTimeZone()) : '';

        $result = $this->noticeModel->getLists($data);

        $this->view->page = '';
        if (isset($result['count']) && $result['count'] > 0) {
            $this->view->page = $this->pageModel->getPage($result['count'], $pagesize, $currentPage);
        }
        $this->view->lists = $result['data'];

        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        $this->view->query = $data;
    }

    /**
     * 发布公告
     */
    public function createAction()
    {
        if ($_POST) {
            $data['title'] = $this->request->get('title', ['string', 'trim']);
            $data['start_time'] = $this->request->get('start_time', ['string', 'trim']);
            $data['end_time'] = $this->request->get('end_time', ['string', 'trim']);
            $data['zone'] = $this->request->get('zone', ['string', 'trim']);
            $data['channel'] = $this->request->get('channel', ['string', 'trim']);
            $data['img'] = $this->request->get('img', ['string', 'trim']);
            $data['status'] = $this->request->get('status', 'int');
            $data['sort'] = $this->request->get('sort', 'int');
            $data['content'] = $this->request->get('formcontent');


            if (!$data['title'] || !$data['start_time'] || !$data['end_time'] || !$data['content']) {
                Utils::tips('error', '数据不完整', '/notice/index');
            }

            $data['start_time'] = $this->utilsModel->toTimeZone($data['start_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());
            $data['end_time'] = $this->utilsModel->toTimeZone($data['end_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());

            $result = $this->noticeModel->createNotice($data);

            if ($result) {
                Utils::tips('success', '添加成功', '/notice/index');
            }
            else {
                Utils::tips('error', '添加失败', '/notice/index');
            }
        }
    }

    /**
     * 修改公告
     */
    public function editAction()
    {
        $data['id'] = $this->request->get('id', 'int');
        if (!$data['id']) {
            Utils::tips('error', '数据不完整', '/notice/index');
        }

        $notice = $this->noticeModel->findNotice($data);
        if ($notice == 1) {
            Utils::tips('error', '没有此数据', '/notice/index');
        }

        if ($_POST) {
            $data['id'] = $this->request->get('id', 'int');
            $data['title'] = $this->request->get('title', ['string', 'trim']);
            $data['start_time'] = $this->request->get('start_time', ['string', 'trim']);
            $data['end_time'] = $this->request->get('end_time', ['string', 'trim']);
            $data['zone'] = $this->request->get('zone', ['string', 'trim']);
            $data['channel'] = $this->request->get('channel', ['string', 'trim']);
            $data['img'] = $this->request->get('img', ['string', 'trim']);
            $data['status'] = $this->request->get('status', 'int');
            $data['sort'] = $this->request->get('sort', 'int');
            $data['content'] = $this->request->get('formcontent');

            if (!$data['title'] || !$data['start_time'] || !$data['end_time'] || !$data['content']) {
                Utils::tips('error', '数据不完整', '/notice/index');
            }

            $data['start_time'] = $this->utilsModel->toTimeZone($data['start_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());
            $data['end_time'] = $this->utilsModel->toTimeZone($data['end_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());

            $result = $this->noticeModel->editNotice($data);

            if ($result) {
                Utils::tips('success', '修改成功', '/notice/index');
            }
            else {
                Utils::tips('error', '修改失败', '/notice/index');
            }
        }

        $this->view->notice = $notice['data'];
    }

    /**
     * 删除公告
     */
    public function removeAction()
    {
        $data['id'] = $this->request->get('id', 'int');
        if (!$data['id']) {
            Utils::tips('error', '数据不完整', '/notice/index');
        }

        $notice = $this->noticeModel->findNotice($data);
        if ($notice == 1) {
            Utils::tips('error', '没有此数据', '/notice/index');
        }

        $result = $this->noticeModel->removeNotice($data);

        if ($result) {
            Utils::tips('success', '删除成功', '/notice/index');
        }
        else {
            Utils::tips('error', '删除失败', '/notice/index');
        }
    }

    /**
     * 导入公告
     */
    public function importAction()
    {

    }
}