<?php


namespace MyApp\Controllers;


use Phalcon\Mvc\Controller;
use MyApp\Models\Utils;

class PublicController extends Controller
{

    public function initialize()
    {
        $this->view->common = ['user_id' => '', 'name' => '', 'username' => '', 'avatar' => ''];
    }


    public function indexAction()
    {
    }


    public function loginAction()
    {
        $ticket = $this->request->get('ticket', 'string');

        // BASE URL
        if ($this->config->setting->security_plugin == 1) {
            $base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/api/sso';
        }
        else {
            $base_url = $this->config->sso->base_url;
        }

        if (!$ticket) {
            // TODO :: https 协议
            $callback = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $login_url = $base_url . '?redirect=' . urlencode($callback);
            header('Location:' . $login_url);
            exit();
        }

        // 验证ticket
        $verify_url = $base_url . '/verify/' . $ticket;
        $result = file_get_contents($verify_url);
        $result = json_decode($result, true);

        if ($result['code'] != 0) {
            Utils::tips('warning', 'Login Failed');
        }


        // TODO::拿Ticket换取资源 增加APPKEY
        $resource_url = $base_url . '/resources?app=' . $this->config->setting->app_id . '&ticket=' . $ticket;
        $resources = json_decode(file_get_contents($resource_url), true);
        if ($resources['code'] != 0) {
            Utils::tips('warning', 'Error When Get Resources');
        }
        else {
            unset($resources['code'], $resources['msg']);
            $this->session->set('resources', $resources);
        }


        // 设置SESSION
        $this->session->set('user_id', $result['user_id']);
        $this->session->set('username', $result['username']);
        $this->session->set('name', $result['name']);
        $this->session->set('avatar', $result['avatar']);
        $this->session->set('role_id', $result['role_id']);

        $this->sendSessionLogAction();
        header('Location:/');
        exit();
    }

    /**
     * 通过接口发送登录的session信息
     */
    protected function sendSessionLogAction()
    {
        $data = [
            'user_id' => $this->session->get('user_id'),
            'website' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'],
            'session_id' => $this->session->getID()
        ];
        ksort($data);
        $query = http_build_query($data);
        $sign = md5($query . $this->config->setting->secret_key);
        $logs = array_merge($data, ['sign' => $sign]);
        $options['http'] = [
            'timeout' => 10,
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => http_build_query($logs)
        ];
        $url = $this->config->sso->sessionlog_url;
        $context = stream_context_create($options);
        $result = json_decode(file_get_contents($url, false, $context), true);
        if ($result['code'] !== 0) {
            exit($result['msg']);
        }
    }


    public function logoutAction()
    {
        $this->persistent->destroy();
        $this->session->destroy();
        setcookie('serverLists', '', -86400, '/');
        setcookie('attach', '', -86400, '/');
        $callback = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        $logoutUrl = $this->config->sso->base_url . 'logout?redirect=' . urlencode($callback);
        header('Location:' . $logoutUrl);
        exit;
    }


    public function logoutOthersAction()
    {
        $this->persistent->destroy();
        $this->session->destroy();
    }

    public function tipsAction()
    {
        $flashData = json_decode(trim($this->cookies->get('flash')->getValue()), true);
        $this->view->tips = $flashData;
        if (isset($_SERVER['HTTP_X_PJAX'])) {
            $this->view->setMainView('');
        }
        $this->view->pick("public/tipsPjax");
    }


    public function show401Action()
    {
        $this->view->message = 'Error 401, No Permission';
        $this->view->pick("public/errors");
    }


    public function show404Action()
    {
        $this->view->message = 'Error 404, Not Found';
        $this->view->pick("public/errors");
    }


    public function exceptionAction()
    {
        $this->view->message = 'Error 400, Exception Occurs';
        $this->view->pick("public/errors");
    }

}