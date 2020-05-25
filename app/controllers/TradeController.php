<?php


/**
 * 订单Trade
 */
namespace MyApp\Controllers;


use MyApp\Models\Page;
use MyApp\Models\Trade;
use MyApp\Models\Utils;
use Phalcon\Mvc\Dispatcher;

class TradeController extends ControllerBase
{
    private $tradeModel;
    private $pageModel;

    public function initialize()
    {
        parent::initialize();
        $this->tradeModel = new Trade();
        $this->pageModel = new Page();
        $this->utilsModel = new Utils();
    }

    /**
     * 订单列表
     */
    public function indexAction()
    {
        $currentPage = $this->request->get('page', 'int') ? $this->request->get('page', 'int') : 1;
        $pagesize = 10;
        $data['transaction'] = $this->request->get('transaction', ['string', 'trim']);
        $data['user_id'] = $this->request->get('user_id', ['string', 'trim']);
        $data['status'] = $this->request->get('status', ['string', 'trim']);
        $data['gateway'] = $this->request->get('gateway', ['string', 'trim']);
        $data['product_id'] = $this->request->get('product_id', ['string', 'trim']);
        $data['custom'] = $this->request->get('custom', ['string', 'trim']);
        $start_time = $this->request->get('start_time', ['string', 'trim']);
        $end_time = $this->request->get('end_time', ['string', 'trim']);
        $data['trade_no'] = $this->request->get('trade_no', ['string', 'trim']);

        $data['start_time'] = !empty($start_time) ? $this->utilsModel->toTimeZone($start_time, $this->config->setting->timezone,
            $this->utilsModel->getTimeZone() , 'Y-m-d') : '';
        $data['end_time'] = !empty($end_time) ? $this->utilsModel->toTimeZone($end_time, $this->config->setting->timezone,
            $this->utilsModel->getTimeZone() , 'Y-m-d') : '';

        $count = $this->tradeModel->getCount($data);

        $this->view->trade = $this->view->page = '';
        $this->view->trade = $this->tradeModel->getList($data, $currentPage, $pagesize);
        $this->view->page = $this->pageModel->getPage($count, $pagesize, $currentPage);
        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        $this->view->query = $data;
        $this->view->isquery = empty(array_filter($data)) ? 0 : 1;
    }


    /**
     * 订单详细
     */
    public function viewAction()
    {
        $id = $this->request->get('id', 'int');
        if (!$id) {
            Utils::tips('error', '数据不完整', '/trade/index');
        }

        $trade = $this->tradeModel->getTradeById($id);
        if (!$trade) {
            Utils::tips('error', '没有此数据', '/trade/index');
        }

        $this->view->trade = $trade;
    }


    /**
     * 追加补充订单
     */
    public function appendAction()
    {
    }

}