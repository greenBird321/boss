<?php


namespace MyApp\Controllers;


use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Logger;
use MyApp\Models\Game;

class ControllerBase extends Controller
{

    public $gameModel;

    public $_user_id;

    public $_app;


    /**
     * @link http://php.net/manual/en/book.gettext.php
     * @link http://www.laruence.com/2009/07/19/1003.html
     * @param Dispatcher $dispatcher
     * ./lang/zh_CN/LC_MESSAGES/zh_CN.mo
     * ./lang/en_US/LC_MESSAGES/en_US.mo
     * lang=zh_CN,en_US
     */
    public function beforeExecuteRoute(Dispatcher $dispatcher)
    {
        $lang = $this->request->get('lang');
        if ($lang) {
            setlocale(LC_ALL, $lang);
            $domain = $lang;
            bind_textdomain_codeset($domain, 'UTF-8');
            bindtextdomain($domain, APP_DIR . '/lang');
            textdomain($domain);
        }
    }


    public function initialize()
    {
        // set userId
        $this->_user_id = $this->session->get('user_id');


        // set appId
        $this->_app = $this->session->get('app');


        // set timezone
        ini_set("date.timezone", $this->config->setting->timezone);


        // record request
        if ($this->config->setting->request_log) {
            if (isset($_REQUEST['_url'])) {
                $_url = $_REQUEST['_url'];
                unset($_REQUEST['_url']);
            }
            else {
                $_url = '/';
            }
            $log = empty($_REQUEST) ? $_url : ($_url . '?' . urldecode(http_build_query($_REQUEST)));
            $logger = new FileLogger(APP_DIR . '/logs/' . date("Ym") . '.log');
            $logger->log($log, Logger::INFO);
        }


        // check auth
        if ($this->config->setting->security_plugin) {
            if (!$this->_user_id) {
                header('Location:/login');
                exit;
            }
        }


        // check app
//        if (!$this->_app) {
//            header('Location: /utils/switch_app');
//            exit;
//        }

        $this->getGamesMsg();

    }


    public function afterExecuteRoute(Dispatcher $dispatcher)
    {
        if (!isset($_SERVER['HTTP_X_PJAX'])) {
            $this->view->common = [
                'user_id'   => $this->session->get('user_id'),
                'name'      => $this->session->get('name'),
                'username'  => $this->session->get('username'),
                'avatar'    => $this->session->get('avatar'),
                'menu_true' => $this->session->get('resources')['menu_tree'],
            ];
        }
        else {
            // 视图渲染控制
            // https://docs.phalconphp.com/zh/latest/api/Phalcon_Mvc_View.html
            // $this->view->setLayoutsDir();
            $this->view->setMainView('');
        }
    }

    /**
     * des: 获取游戏信息, 将其分类后存入 session
     * TODO :: 放在非Pjax请求里
     */
    private function getGamesMsg()
    {
        $this->gameModel = new Game();
        $apps = $this->gameModel->getGamesByGroup();
        $this->session->set('apps', $apps);

        $this->view->apps = $apps;

        // 默认App
        $defaultApp = $this->session->get('default_app');

        $this->session->set('app', $defaultApp['game_id']);
        $this->session->set('group', $defaultApp['class_id']);
        $this->session->set('lang', $defaultApp['version']);
        $this->session->set('game_name', $defaultApp['name']);
        if (!$defaultApp) {
            $defaultApp = $this->gameModel->setDefaultApp();
        }
        $this->view->app_name = $defaultApp["name"];
        $this->session->set('default_app', $defaultApp);
    }



}