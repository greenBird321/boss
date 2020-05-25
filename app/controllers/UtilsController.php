<?php


/**
 * 公共类
 */

namespace MyApp\Controllers;


use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;
use MyApp\Models\Game;
use MyApp\Models\Utils;
use Phalcon\DI;

class UtilsController extends Controller
{
    private $gameModel;

    public function initialize()
    {
        $this->view->common = [
            'user_id'   => $this->session->get('user_id'),
            'name'      => $this->session->get('name'),
            'username'  => $this->session->get('username'),
            'avatar'    => $this->session->get('avatar'),
            'menu_true' => $this->session->get('resources')['menu_tree'],
        ];
        $this->gameModel = new Game();
    }

    public function switch_appAction()
    {
        $allow_game = DI::getDefault()->get('session')->get('resources')['allow_game'];

        if (empty($allow_game)) {
            Utils::tips('error', '没有权限', '', 999999);
        }

        foreach ($allow_game as $item) {
            $class_id = substr($item, 0, 3);
            $info = $this->gameModel->getGame($class_id);
            $version = $this->gameModel->getVersionList($info['id']);
            $gameList[$info['class_id']]['info'] = $info;
            $gameList[$info['class_id']]['data'] = $version;
        }

        $this->view->apps = empty($gameList) ? array() : $gameList;
    }

    /**
     * 切换游戏版本
     */
    public function setGameVersionAction()
    {
        if ($_GET) {
            $id = $this->request->get('id', 'int');

            if (!$id) {
                Utils::tips('error', '参数错误', '', 1);
            }

            $game = $this->gameModel->findFirst($id)->toArray();
            if (!$game) {
                Utils::tips('error', '没有此版本', '', 999999);
            }
//            if (!in_array($game->game_id, DI::getDefault()->get('session')->get('resources')['allow_game'])) {
//                Utils::tips('error', '没有权限', '', 999999);
//            }

            DI::getDefault()->get('session')->set('app', $game['game_id']);
            DI::getDefault()->get('session')->set('group', $game['class_id']);
            DI::getDefault()->get('session')->set('lang', $game['version']);
            DI::getDefault()->get('session')->set('game_name', $game['name']);
            DI::getDefault()->get('session')->set('default_app', $game);

            if (isset($_COOKIE['attach'])) {
                $_COOKIE['attach'] = '';
                setcookie('attach', '', -86400, '/');
            }

            if (isset($_COOKIE['serverLists'])) {
                $_COOKIE['serverLists'] = '';
                setcookie('serverLists', '', -86400, '/');
            }
            header('Location: /');
        }
    }


}