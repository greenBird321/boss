<?php


/**
 * 设置
 */
namespace MyApp\Controllers;


use MyApp\Models\Timezone;
use Phalcon\Mvc\Dispatcher;
use MyApp\Models\Setting;
use MyApp\Models\Utils;

class SettingController extends ControllerBase
{

    private $settingModel;


    public function initialize()
    {
        parent::initialize();
        $this->settingModel = new Setting();
    }


    public function indexAction()
    {
    }


    /**
     * 网关
     */
    public function paymentAction()
    {

        $do = $this->request->get('do', ['string', 'trim']);

        switch ($do) {

            case 'create':
                if ($_POST) {
                    $this->_gateways_create();
                }

                $this->view->pick("setting/gateways_create");
                $this->view->parent = $this->settingModel->getParent();
                break;

            case 'edit':
                $id = $this->request->get('id', 'int');
                if (!$id) {
                    Utils::tips('error', '数据不完整', '/setting/payment');
                }

                $gateways = $this->settingModel->findFirst($id);
                if (!$gateways) {
                    Utils::tips('error', '没有此数据', '/setting/payment');
                }

                if ($_POST) {
                    $this->_gateways_edit($gateways);
                }

                $this->view->parent = $this->settingModel->getParent();
                $this->view->gateways = $gateways->toArray();
                $this->view->pick("setting/gateways_edit");
                break;

            case 'remove':
                $this->_gateways_remove();
                break;

            case 'notify':
                $this->notify();
                break;

            default:
                $this->view->gateways = $this->settingModel->getList();
                $this->view->pick("setting/gateways");
        }
    }


    /**
     * 创建网关
     */
    private function _gateways_create()
    {
        $this->settingModel->app_id = $this->session->get('app');
        $this->settingModel->gateway = $this->request->get('gateway', ['string', 'trim']);
        $this->settingModel->name = $this->request->get('name', ['string', 'trim']);
        $this->settingModel->sub = $this->request->get('sub', ['string', 'trim']);
        $this->settingModel->type = $this->request->get('type', ['string', 'trim']);
        $this->settingModel->remark = $this->request->get('remark', ['string', 'trim']);
        $this->settingModel->currency = $this->request->get('currency', ['string', 'trim']);
        $this->settingModel->parent = $this->request->get('parent', 'int');
        $this->settingModel->sort = $this->request->get('sort', 'int');
        $this->settingModel->visible = $this->request->get('visible', 'int', 0);
        $this->settingModel->sandbox = $this->request->get('sandbox', 'int');
        $this->settingModel->tips = $this->request->get('tips', ['string', 'trim']);

        if (!$this->settingModel->name || !$this->settingModel->app_id || !$this->settingModel->gateway) {
            Utils::tips('error', '数据不完整', '/setting/payment?do=create');
        }

        $this->settingModel->save();
        Utils::tips('success', '添加成功', '/setting/payment');
    }


    /**
     * 修改网关
     */
    private function _gateways_edit($gateways)
    {
        $gateways->app_id = $this->session->get('app');
        $gateways->gateway = $this->request->get('gateway', ['string', 'trim']);
        $gateways->name = $this->request->get('name', ['string', 'trim']);
        $gateways->sub = $this->request->get('sub', ['string', 'trim']);
        $gateways->type = $this->request->get('type', ['string', 'trim']);
        $gateways->remark = $this->request->get('remark', ['string', 'trim']);
        $gateways->currency = $this->request->get('currency', ['string', 'trim']);
        $gateways->parent = $this->request->get('parent', 'int');
        $gateways->sort = $this->request->get('sort', 'int');
        $gateways->visible = $this->request->get('visible', 'int', 0);
        $gateways->sandbox = $this->request->get('sandbox', 'int');
        $gateways->tips = $this->request->get('tips', ['string', 'trim']);

        if (!$gateways->name || !$gateways->app_id || !$gateways->gateway) {
            Utils::tips('error', '数据不完整', '/setting/payment?do=edit&id=' . $gateways['id']);
        }

        $gateways->save();
        Utils::tips('success', '修改成功', '/setting/payment');
    }


    /**
     * 修改网关
     */
    private function _gateways_remove()
    {
        $id = $this->request->get('id', 'int');
        if (!$id) {
            Utils::tips('error', '数据不完整', '/setting/payment');
        }

        $gateways = $this->settingModel->findFirst($id);
        if (!$gateways) {
            Utils::tips('error', '没有此数据', '/setting/payment');
        }

        $gateways->delete();
        Utils::tips('success', '删除成功', '/setting/payment');
    }

    /**
     * 回调
     */

    private function notify()
    {
        if ($_POST) {
            $secret_key = $this->request->get('secret_key', ['string', 'trim']);
            $notify_url = $this->request->get('notify_url', ['string', 'trim']);
            $trade_tip = $this->request->get('trade_tip', ['string', 'trim']);

            if (!$notify_url) {
                Utils::tips('error', '数据不完整', '/setting/notify');
            }

            $app['secret_key'] = $secret_key;
            $app['notify_url'] = $notify_url;
            $app['trade_tip'] = $trade_tip;
            $this->settingModel->saveapp($app);

            Utils::tips('success', '保存成功', '/setting/payment');
        }

        $this->view->app = $this->settingModel->findApp();
        $this->view->pick("setting/notify");
    }

    public function synchroAction()
    {
        if ($this->request->get('is_form', ['string', 'trim'])) {

            $secret_key = $this->config->setting->secret_key;
            $time       = time();

            $base_url = $this->config->sso->api_url . '?time=' . $time . '&key=' . md5($secret_key . $time);

            $gameList = json_decode(file_get_contents($base_url), true);

            if($gameList['code'] == 1){
                Utils::tips('error', '同步失败', '/');
            }

            $this->gameModel->saveData($gameList);

            Utils::tips('success', '同步成功', '/');
            exit;

        }
    }

    /**
     * 设置时区
     */
    public function timezoneAction()
    {
        if ($_POST) {
            $timeZone = $this->request->get('timeZone', ['string', 'trim']);
            $zonetext = $this->request->get('zonetext', ['string', 'trim']);

            if (!$timeZone) {
                echo json_encode(array('error' => 0, 'data' => '参数错误'));
                exit;
            }

            $timezone['timezone'] = $timeZone;
            $this->settingModel->saveTimeZone($timezone);


            $this->session->set('timezone', $timeZone);
            $this->session->set('zone_text', $zonetext);
            echo json_encode(array('error' => 0, 'data' => '时区切换成功'));
            exit;
        }

        $userTimeZone = $this->settingModel->findOne();
        if (!empty($userTimeZone)) {
            $this->session->set('zone_text', $this->timeText($userTimeZone['timezone']));
        }

    }

    private function timeText($key = 'Asia/Shanghai')
    {
        $timeArray = array(
            'Asia/Shanghai'       => '上海',
            'Asia/Taipei'         => '台北',
            'Asia/Seoul'          => '韩国',
            'Asia/Tokyo'          => '日本',
            'Asia/Bangkok'        => '泰国',
            'Asia/Hanoi'          => '越南',
            'Asia/Singapore'      => '马来西亚',
            'Europe/London'       => '伦敦',
            'Europe/Paris'        => '巴黎',
            'Europe/Rome'         => '罗马',
            'Europe/Berlin'       => '柏林',
            'America/Los_Angeles' => '洛杉矶',
            'America/Chicago'     => '芝加哥',
            'America/Vancouver'   => '温哥华',
        );

        return $timeArray[$key];
    }

}