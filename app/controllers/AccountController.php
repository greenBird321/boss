<?php


/**
 * 账号管理
 */
namespace MyApp\Controllers;


use Phalcon\Mvc\Dispatcher;

class AccountController extends ControllerBase
{

    public function indexAction()
    {
    }


    /**
     * 黑名单
     */
    public function blacklistAction()
    {
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