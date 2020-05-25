<?php


/**
 * 邀请
 */
namespace MyApp\Controllers;


use Phalcon\Mvc\Dispatcher;

class InviteController extends ControllerBase
{

    /**
     * 邀请 - 预览
     */
    public function indexAction()
    {
    }


    /**
     * 邀请 - 日志
     */
    public function logsAction()
    {
    }


    /**
     * 邀请 - 配置管理
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
            case 'remove':
                $this->remove();
                break;
        }
    }


    private function create()
    {
        if ($_POST) {
        }
    }


    private function edit()
    {
        if ($_POST) {
        }
    }


    private function remove()
    {
    }


}