<?php


/**
 * 投票
 */
namespace MyApp\Controllers;


use MyApp\Models\Page;
use MyApp\Models\Utils;
use MyApp\Models\Vote;
use Phalcon\Mvc\Dispatcher;

class VoteController extends ControllerBase
{
    private $voteModel;
    private $pageModel;
    private $utilsModel;

    public function initialize()
    {
        parent::initialize();
        $this->voteModel = new Vote();
        $this->pageModel = new Page();
        $this->utilsModel = new Utils();

    }

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

        $result = $this->voteModel->getLists($data);

        $this->view->page = '';
        $this->view->page = $this->pageModel->getPage($result['count'], $pagesize, $currentPage);
        $this->view->lists = $result['data'];
        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        $this->view->query = $data;
    }

    /**
     * 发布投票信息
     */
    public function createAction()
    {
        if ($_POST) {
            $data['title'] = $this->request->get('title', ['string', 'trim']);
            $data['start_time'] = $this->request->get('start_time', ['string', 'trim']);
            $data['end_time'] = $this->request->get('end_time', ['string', 'trim']);
            $data['img'] = $this->request->get('img', ['string', 'trim']);
            $data['status'] = $this->request->get('status', 'int');
            $data['intro'] = $this->request->get('intro', ['string', 'trim']);

            if (!$data['title'] || !$data['start_time'] || !$data['end_time'] || !$data['intro']) {
                Utils::tips('error', '数据不完整', '/vote/index');
            }

            $data['start_time'] = $this->utilsModel->toTimeZone($data['start_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());
            $data['end_time'] = $this->utilsModel->toTimeZone($data['end_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());

            $result = $this->voteModel->createVote($data);

            if ($result) {
                Utils::tips('success', '添加成功', '/vote/index');
            }
            else {
                Utils::tips('error', '添加失败', '/vote/index');
            }
        }
    }

    public function editAction()
    {
        $data['id'] = $this->request->get('id', 'int');
        if (!$data['id']) {
            Utils::tips('error', '数据不完整', '/vote/index');
        }

        $vote = $this->voteModel->findVote($data);
        if ($vote == 1) {
            Utils::tips('error', '没有此数据', '/vote/index');
        }

        if ($_POST) {
            $data['id'] = $this->request->get('id', 'int');
            $data['title'] = $this->request->get('title', ['string', 'trim']);
            $data['start_time'] = $this->request->get('start_time', ['string', 'trim']);
            $data['end_time'] = $this->request->get('end_time', ['string', 'trim']);
            $data['img'] = $this->request->get('img', ['string', 'trim']);
            $data['status'] = $this->request->get('status', 'int');
            $data['intro'] = $this->request->get('intro', ['string', 'trim']);

            if (!$data['title'] || !$data['start_time'] || !$data['end_time'] || !$data['intro']) {
                Utils::tips('error', '数据不完整', '/vote/index');
            }

            $data['start_time'] = $this->utilsModel->toTimeZone($data['start_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());
            $data['end_time'] = $this->utilsModel->toTimeZone($data['end_time'], $this->config->setting->timezone,
                $this->utilsModel->getTimeZone());

            $result = $this->voteModel->editVote($data);

            if ($result) {
                Utils::tips('success', '修改成功', '/vote/index');
            }
            else {
                Utils::tips('error', '修改失败', '/vote/index');
            }
        }

        $this->view->vote = $vote['data'];
    }

    public function removeAction()
    {
        $data['id'] = $this->request->get('id', 'int');
        if (!$data['id']) {
            Utils::tips('error', '数据不完整', '/vote/index');
        }

        $vote = $this->voteModel->findVote($data);
        if ($vote == 1) {
            Utils::tips('error', '没有此数据', '/vote/index');
        }

        $result = $this->voteModel->removeVote($data);

        if ($result) {
            Utils::tips('success', '删除成功', '/vote/index');
        }
        else {
            Utils::tips('error', '删除失败', '/vote/index');
        }
    }

    public function viewAction()
    {
        $data['group_id'] = $this->request->get('id', ['string', 'trim']);
        if (!$data['group_id']) {
            Utils::tips('error', '数据不完整', '/vote/index');
        }

        $voteOption = $this->voteModel->listVoteOption($data);

        $this->view->voteOption = $voteOption['data'];
        $this->view->voteid = $data['group_id'];
    }

    public function createoptionAction()
    {
        $data['id'] = $this->request->get('id', 'int');
        if (!$data['id']) {
            Utils::tips('error', '数据不完整', '/vote/index');
        }

        $vote = $this->voteModel->findVote($data);
        if ($vote == 1) {
            Utils::tips('error', '没有此数据', '/vote/index');
        }

        if ($_POST) {
            $data['group_id'] = $data['id'];
            $data['subject'] = $this->request->get('subject', ['string', 'trim']);
            $data['answer'] = $this->request->get('answer', 'int');
            $data['option_1'] = $this->request->get('option_1', ['string', 'trim']);
            $data['option_2'] = $this->request->get('option_2', ['string', 'trim']);
            $data['option_3'] = $this->request->get('option_3', ['string', 'trim']);
            $data['option_4'] = $this->request->get('option_4', ['string', 'trim']);

            if (!$data['subject'] || !$data['option_1']) {
                Utils::tips('error', '数据不完整', '/vote/view?id=' . $data['group_id']);
            }
            unset($data['id']);

            $result = $this->voteModel->createOption($data);

            if ($result) {
                Utils::tips('success', '添加成功', '/vote/view?id=' . $data['group_id']);
            }
            else {
                Utils::tips('error', '添加失败', '/vote/view?id=' . $data['group_id']);
            }
        }

        $this->view->voteid = $data['id'];
    }

    public function editoptionAction()
    {
        $data['id'] = $this->request->get('id', 'int');
        if (!$data['id']) {
            Utils::tips('error', '数据不完整', '/vote/index');
        }

        $vote = $this->voteModel->findOption($data);
        if ($vote == 1) {
            Utils::tips('error', '没有此数据', '/vote/index');
        }

        if ($_POST) {
            $data['id'] = $this->request->get('id', 'int');
            $data['group_id'] = $this->request->get('group_id', 'int');
            $data['subject'] = $this->request->get('subject', ['string', 'trim']);
            $data['answer'] = $this->request->get('answer', 'int');
            $data['option_1'] = $this->request->get('option_1', ['string', 'trim']);
            $data['option_2'] = $this->request->get('option_2', ['string', 'trim']);
            $data['option_3'] = $this->request->get('option_3', ['string', 'trim']);
            $data['option_4'] = $this->request->get('option_4', ['string', 'trim']);

            if (!$data['subject'] || !$data['option_1']) {
                Utils::tips('error', '数据不完整', '/vote/view?id=' . $data['group_id']);
            }

            $result = $this->voteModel->editOption($data);

            if ($result) {
                Utils::tips('success', '修改成功', '/vote/view?id=' . $data['group_id']);
            }
            else {
                Utils::tips('error', '修改失败', '/vote/view?id=' . $data['group_id']);
            }
        }

        $this->view->vote = $vote['data'];
    }

    public function removeoptionAction()
    {
        $data['id'] = $this->request->get('id', 'int');
        if (!$data['id']) {
            Utils::tips('error', '数据不完整', '/vote/index');
        }

        $vote = $this->voteModel->findOption($data);
        if ($vote == 1) {
            Utils::tips('error', '没有此数据', '/vote/index');
        }

        $result = $this->voteModel->removeOption($data);

        if ($result) {
            Utils::tips('success', '删除成功', '/vote/view?id=' . $vote['data']['group_id']);
        }
        else {
            Utils::tips('error', '删除失败', '/vote/view?id=' . $vote['data']['group_id']);
        }
    }
}