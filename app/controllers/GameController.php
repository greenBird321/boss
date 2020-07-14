<?php


/**
 * 游戏信息
 */
namespace MyApp\Controllers;

use MyApp\Models\Page;
use MyApp\Models\Trade;
use Myapp\Models\Utils;
use MyApp\Models\Server;
use Phalcon\Mvc\Dispatcher;
use SimpleXLSX;

class GameController extends ControllerBase
{
    private $tradeModel;
    private $serverModel;
    private $pageModel;
    private $send_type;
    private $allow_role;
    private $allow_type;
    private $role_describe;
    private $shop;


    public function initialize()
    {
        parent::initialize();
        $this->tradeModel = new Trade();
        $this->serverModel = new Server();
        $this->pageModel = new Page();
        $this->send_type = [
            'coin'   => '金币',
            'exp'    => '经验',
            'mail'   => '邮件',
            'attach' => '道具',
            'attachServer' => '全服发奖',
        ];
        $this->allow_role = [
            101,    // 系统管理员
            103     // 运营主管
        ];
        $this->allow_type = [
            'xlsx'
        ];
        $this->role_describe = [
            'account_id' => '账号平台id',
            'user_id' => '角色id',
            'server'  => '角色所在服务器id',
            'name' => '角色名称',
            'level' => '角色等级',
            'exp'   => '经验值',
            'morale' => '士气',
            'ArenaPoint' => '竞技场积分',
            'ArenaWinTime' => '竞技场挑战时间',
            'RobValue' => '掠夺值',
            'GoodValue' => '善良值',
            'LogoutTime' => '下线时间',
            'MPower' => '角色战力',
            'WandLevel' => '鼓舞系统星级',
            'device_id' => '设备id',
            'create_time' => '角色创建时间',
            'active_day' => '活跃天数',
            'ban' => '账号封禁',
            'quick_afkNum' => '今日快速挂机次数',
            'high_arenaPoint' => '高阶竞技场积分',
            'GM_privilege' => 'gm权限',
        ];
        $this->shop = [
            '100001' => '精品商店',
            '100002' => '随机商店',
            '100005' => '公会商店',
            '100011' => '幻境商店',
            '100012' => '竞技商店'
        ];
    }

    public function indexAction()
    {

    }

    /**
     * 公会管理
     */
    public function guildAction()
    {
        if ($_POST) {
            $data['guild_id']   = $this->request->get('guild_id', ['string', 'trim']);
            $data['guild_name'] = $this->request->get('guild_name', ['string', 'trim']);
            $data['zone']       = $this->request->get('server', ['string']);

            if (empty($data['zone'])) {
                Utils::tips('error', '数据不完整', '/game/guild');
                exit;
            }

            $result = $this->gameModel->getGuild($data);

            if (!$result) {
                Utils::tips('error', '没有数据', '/game/guild');
                exit;
            }
            $guild  = $result['guildData'];
            $player = $result['playerData'];
            // 服务器通用数据
            $guild_example = [
                'server' => $guild[0]['ServerID'],     // 服务器id
                'guildName' => $guild[0]['GuildName'], // 公会名称
                'createrName' => $guild[0]['CreaterName'], // 创始玩家姓名
                'guildId' => $guild[0]['GuildID'], //公会id
                'create_time' => $guild[0]['CreateTime'],  // 公会创建时间
                'guildLv' => $guild[0]['GuildLevel'],  //公会等级
                'guildDl' => $guild[0]['GuildDeclaration'], //公会公告
                'guildLe' => $guild[0]['GuildLevelExp'], // 公会经验
            ];

            // 需要匹配公会详情,必须上传公会职位表
            $position_file = 'guildExcel';
            // 需要上传公会消息表 展示公会消息
            $news_name = 'guildNewsExcel';
            // 获取公会职位
            $guild_position = $this->getXlsx($position_file);
            // 获取消息模板
            $guild_news = $this->getXlsx($news_name, 3, [0, 2]);
            // 数据组装
            if (!empty($guild_position)) {
                //todo 需要遍历，但是没有表，现在只能空着, 没有表的话以int类型展示
            }
            // 消息模板数据
            $msg = [];
            if ($guild_news) {
                // 获取对应公会的消息记录
                $news = $this->gameModel->getGuildNews($data);
                foreach ($news as $value) {
                    $show_str = $guild_news[$value['NewsType']];
                    $r        = preg_replace_array("/(?:\{)(.(\d*))(?:\})/i", explode(';', $value['NewsParam']), $show_str);
                    $msg[]    = [
                        'news' => $r,
                        'send_time' => date("Y-m-d H:i:s", $value['Timestamp'])
                    ];
                }
            }

            $this->view->guildInfo = $guild_example;
            $this->view->player    = $player;
            $this->view->guildnews = $msg;
            return $this->view->pick("game/guildInfo");
        }

        $lists             = $this->serverModel->getLists();
        $this->view->lists = $lists;
        $this->view->pick("game/guild");
    }

    /**
     * 上传公会职位表格
     */
    public function importGuildExcelAction()
    {
        if (!empty($_POST)) {
            $file = empty($_FILES['guild']) ? false : $_FILES['guild'];
            if ($file['error'] > 0 || !$file) {
                echo json_encode(['error' => 1, 'data' => '上传文件错误']);
                exit;
            }

            list($file_name, $file_ext) = explode('.', $file['name']);
            $filepath = __DIR__ . '/../../public/files/' . 'guildExcel' . '.' . $file_ext;

            if (!in_array($file_ext, $this->allow_type)) {
                echo json_encode(['error' => 1, 'data' => '文件类型非法']);
                exit;
            }

            // 将文件转移到正式文件夹
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                echo json_encode(['error' => 1, 'data' => '上传文件失败,请重试']);
                exit;
            }

            echo json_encode(['error' => 0, 'data' => '文件上传成功']);
            exit;
        }

        $this->view->pick('game/importGuildExcel');
    }

    /**
     * 玩家信息
     */
    public function playerAction()
    {
        $data['zone'] = $this->request->get('server', ['string', 'trim']);
        $show         = $server = 0;
        $users        = [];
        if ($data['zone']) {
            $data['user_id']    = $this->request->get('user_id', ['string', 'trim']);
            $data['name']       = $this->request->get('name', ['string', 'trim']);
            $data['account_id'] = $this->request->get('account_id', ['string', 'trim']);

            if (empty($data['zone']) || (empty($data['user_id']) && empty($data['name']) && empty($data['account_id']))) {
                Utils::tips('error', '数据不完整', '/game/player');
                exit;
            }
            $result = $this->gameModel->profile($data);
            if (empty($result)) {
                Utils::tips('error', '服务器ID错误', '/game/player');
                exit;
            }

            if ($result['code'] == 1) {
                Utils::tips('error', '没有该用户', '/game/player');
                exit;
            }
            if ($result['count'] == 1) {
                $where['user_id']  = $result['data']['account_id'];
                $count             = $this->tradeModel->getCount($where);
                $this->view->trade = $this->tradeModel->getList($where, 1, $count);
                $tmp               = array_combine($result['data']['money_type'], $result['data']['money_num']);
                unset($result['data']['money_type'], $result['data']['money_num']);
                $proplist = $this->getXlsx('propExcel');
                $final = [];

                foreach ($tmp as $key => $value) {
                    if ($proplist) {
                        $final[$proplist[$key]] = $value;
                    } else {
                        $final[$key] = $value;
                    }
                    unset($result['data'][$key]);
                }

                foreach ($result['data'] as $k => $v) {
                    $final[$this->role_describe[$k]] = $v;
                }

                $result['data']['attribute'] = $final;
                $this->view->user            = $result['data'];
                $this->view->pick("game/playerone");
            } else {
                $show   = 1;
                $users  = $result['data'];
                $server = $data['zone'];
                $this->view->pick("game/player");
            }
        }

        $result = $this->serverModel->getLists();
        $this->view->users = $users;
        $this->view->show = $show;
        $this->view->server = $server;
        $this->view->lists = $result;
    }

    /**
     * 信息完善奖励
     */
    public function completeAction()
    {
    }

    /**
     * 消耗排行
     */
    public function consumeAction()
    {
        if ($_POST) {
            $data['serverId'] = $this->request->get('server', 'int');
            $data['start']    = strtotime($this->request->get('start'));
            $data['end']      = strtotime($this->request->get('end'));

            if (empty($data['start']) || empty($data['end'])) {
                Utils::tips('error', '数据不全', '/game/consume');
                exit;
            }

            $response = $this->gameModel->getConsumeList($data);
            // 获取行为表
            $actionEx = $this->getXlsx('actionExcel', 3, [0, 2]);
            // 将行为id翻译为中文
            foreach ($response as $key => $value) {
                if (isset($actionEx[$value['actionId']])) {
                    $response[$key]['action'] = $actionEx[$value['actionId']];
                } else {
                    $response[$key]['action'] = $value['actionId'];
                }
            }

            $this->view->datas = $response;
            return $this->view->pick("game/consumeList");
        }

        $this->view->lists = $this->serverModel->getLists();
    }

    /**
     *  发奖列表
     */
    public function attachListAction()
    {
        $currentPage = $this->request->get('page', 'int') ? $this->request->get('page', 'int') : 1;
        $pagesize = 10;

        $logList = $this->gameModel->getAttachLogList($currentPage, $pagesize);
        foreach ($logList as $key => $log) {
            $logList[$key]['type'] = $this->send_type[$log['type']];
        }

        $this->view->logs = $this->view->page = '';
        $count = $this->gameModel->getAttachLogCount();
        $this->view->logs = $logList;
        $this->view->page = $this->pageModel->getPage($count, $pagesize, $currentPage);
    }

    /**
     * 补发管理
     */
    public function attachAction()
    {
        if ($_POST) {
            $type            = $this->request->get('action', ['string', 'trim']);
            $data['zone']    = $this->request->get('server', ['string', 'trim']);
            $data['user_id'] = $this->request->get('user_id', ['string', 'trim']);
            $data['amount']  = $this->request->get('amount', ['string', 'trim']);
            $data['title']   = $this->request->get('title', ['string', 'trim']);
            $data['msg']     = $this->request->get('msg', ['string', 'trim']);

            // 组装log数据
            $logData['type']         = $type;
            $logData['app_id']       = $this->session->get('app');
            $logData['operation_id'] = $this->session->get('user_id');
            $role_id                 = $this->session->get('role_id');
            $logData['title']        = $data['title'];
            $logData['msg']          = $data['msg'];
            $logData['send_prop']    = $data['amount'];
            $logData['send_user']    = $data['zone'] . '-' . $data['user_id'];

            if (empty($type) || empty($data['zone']) || (empty($data['user_id']) && $type != 'attachServer')) {
                echo json_encode(array('error' => 1, 'data' => '数据不完整'));
                exit;
            }

            if ($type == 'attach' && empty($data['amount'])) {
                echo json_encode(array('error' => 1, 'data' => '数据不完整'));
                exit;
            }

            if (($type == 'coin' || $type == 'exp') && empty($data['amount'])) {
                echo json_encode(array('error' => 1, 'data' => '数据不完整'));
                exit;
            }

            if ($type == 'mail' && empty($data['msg'])) {
                echo json_encode(array('error' => 1, 'data' => '数据不完整'));
                exit;
            }

            if ($type == 'attachServer' && empty($data['amount'])) {
                echo json_encode(array('error' => 1, 'data' => '数据不完整'));
                exit;
            }

            if (stristr($type, 'attach')) {
                $data['attach'] = $data['amount'];
                unset($data['amount']);
            }

            // 判断当前role等级，如果是这两个role的话，不用批复直接发送
            $isAdmin = false;
            foreach ($role_id as $role) {
                if (in_array($role, $this->allow_role)) {
                    $isAdmin = true;
                    break;
                }
            }

            if ($isAdmin) {
                $result = $this->gameModel->setProp($type, $data);
                if (!empty($result)) {
                    echo json_encode(array('error' => 0, 'data' => '补发成功'));
                    $logData['status'] = 1;
                    $this->gameModel->setAttachLog($logData);
                    exit;
                } else {
                    echo json_encode(array('error' => 1, 'data' => '补发失败'));
                    $logData['status'] = 2;
                    $this->gameModel->setAttachLog($logData);
                    exit;
                }
            }
            // 记录log
            $this->gameModel->setAttachLog($logData);
            echo json_encode(['error' => 0, 'data' => '创建成功等待审核']);
            exit;
        }
        $result = $this->gameModel->getAttribute();
        $server = $this->serverModel->getLists();

        $this->view->server = $server;
        $this->view->lists = $result['data'];
    }

    /**
     * log详情页
     */
    public function viewAction()
    {
        if ($_POST) {
            $id = $this->request->get('id');
            $status = $this->request->get('status');
            $agree_id = $this->_user_id;
            if (!$this->gameModel->updateAttachLog($id, $agree_id, $status)) {
                echo json_encode(['error' => 1, 'data' => '审核失败']);
                exit;
            }

            $attach_log = $this->gameModel->getAttachLog($id);
            list($zone, $send_user_id) = explode('-', $attach_log['send_user']);
            $data = [
                'title' => $attach_log['title'],
                'msg' => $attach_log['content'],
                'zone' => $zone,
                'user_id' => $send_user_id,
                'amount' => $attach_log['send_prop'],
            ];

            $result = $this->gameModel->setProp($attach_log['type'], $data);

            if (!empty($result)) {
                echo json_encode(array('error' => 0, 'data' => '补发成功'));
                $logData['status'] = 1;
                exit;
            } else {
                echo json_encode(array('error' => 1, 'data' => '补发失败'));
                $logData['status'] = 2;
                exit;
            }
        }
        $id = $this->request->get('id') ? $this->request->get('id') : 1;
        $log_info = $this->gameModel->getAttachLog($id);
        $log_info['type'] = $this->send_type[$log_info['type']];
        $isAdmin = false;
        $roles = $this->session->get('role_id');
        foreach ($roles as $role) {
            if (in_array($role, $this->allow_role)) {
                $isAdmin = true;
                break;
            }
        }

        $this->view->log = $log_info;
        $this->view->isAdmin = $isAdmin;
        $this->view->id = $id;
    }

    /**
     * 上传道具表格
     */
    public function importExcelAction()
    {
        if (!empty($_POST)) {
            $file = isset($_FILES['excel'])? $_FILES['excel'] : false;
            if ($file['error'] > 0 || !$file) {
                echo json_encode(['error' => 1, 'data' => '文件上传失败']);
                exit;
            }

            $extension = explode('.', $file['name'])[1];
            $filepath = __DIR__ . '/../../public/files/' .'propExcel'.'.'.$extension;

            if (!in_array($extension, $this->allow_type)) {
                echo json_encode(['error' => 1, 'data' => '文件非法']);
                exit;
            }

            // 将文件转移到正式文件夹
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                echo json_encode(['error' => 1, 'data' => '上传文件失败,请重试']);
                exit;
            }

            echo json_encode(['error' => 0, 'data' => '上传文件成功']);
            exit;
        }

        $this->view->pick('game/importExcel');
    }

    /**
     * 上传公会消息模板
     */
    public function importGuildNewsAction()
    {
        if (!empty($_FILES)) {
            $file = isset($_FILES['guildnews'])? $_FILES['guildnews'] : false;
            if ($file['error'] > 0 || !$file) {
                echo json_encode(['error' => 1, 'data' => '文件上传失败']);
                exit;
            }

            $extension = explode('.', $file['name'])[1];
            $filepath = __DIR__ . '/../../public/files/' .'guildNewsExcel'.'.'.$extension;

            if (!in_array($extension, $this->allow_type)) {
                echo json_encode(['error' => 1, 'data' => '文件非法']);
                exit;
            }

            // 将文件转移到正式文件夹
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                echo json_encode(['error' => 1, 'data' => '上传文件失败,请重试']);
                exit;
            }

            echo json_encode(['error' => 0, 'data' => '上传文件成功']);
            exit;
        }

        $this->view->pick('game/importGuildNewsExcel');
    }

    /**
     * 获取xlsx的内容
     * @param $filename
     * @param $fieldnum: 需要读取的列数量
     */
    public function getXlsx($filename, $start = 3 ,$index = [0, 1])
    {
        $path = __DIR__ . '/../../public/files/' . $filename . '.xlsx';
        if ($xlsx = SimpleXLSX::parse($path)) {
            foreach ($xlsx->rows() as $key => $value) {
                // 从第三行，才是正式数据
                if ($key < $start) {
                    continue;
                }
                list($s, $e) = $index;
                if ($s > $e) {
                    return false;
                }
                // 把表的id和描述 拼接成一个数组
                $result[$value[$s]] = $value[$e];

            }

            return $result;
        } else {
            // todo 应该记录日志
            return false;
        }
    }

    /**
     * 查看道具列表
     */
    public function proplistAction()
    {
        $result = $this->getXlsx('propExcel');
        $this->view->list = $result;
    }

    /**
     * 道具查询操作
     */
    public function propAction()
    {
        if ($_POST) {
            $data['zone']      = $this->request->get('server', ['string', 'trim']);
            $data['user_id']   = $this->request->get('user_id', ['string', 'trim']);
            $data['action_id'] = $this->request->get('action_id', ['string', 'trim']);
            $data['status']    = $this->request->get('status', ['string', 'trim']);

            if (empty($data['zone'])) {
                Utils::tips('error', '数据不全', '/game/prop');
                exit;
            }

            if (empty($data['user_id'])) {
                Utils::tips('error', '数据不完整', '/game/prop');
            }

            // 获取游戏内的道具消耗信息
            $result = empty($this->gameModel->propinfo($data)) ? [] :$this->gameModel->propinfo($data);
            // 获取用户的详细信息
            $player_info = $this->gameModel->profile($data);

            if ($player_info['code'] == 1) {
                Utils::tips('error', '该用户不存在', '/game/prop');
                exit;
            }

            // 获取action描述
            $filepath = 'actionExcel';
            $action_list = empty($this->getXlsx($filepath)) ? [] : $this->getXlsx($filepath);
            $get      = false;
            $consume = false;
            $proplist = $this->getXlsx('propExcel');

            // 数据组装
            foreach ($result as $key => $value) {
                $value['ItemId']   = $proplist[$value['ItemId']];
                $value['Action'] = isset($action_list[$value['Action']]) ? $result[$value['Action']] : $value['Action'];
                if ($value['GetItem'] == 1) {
                    // 角色获得道具
                    $get[] = $value;
                } elseif ($value['GetItem'] == 0) {
                    // 角色消耗道具
                    $consume[] = $value;
                }
            }

            $this->view->user = $player_info['data'];
            $this->view->get  = $get;
            $this->view->consume = $consume;
            return $this->view->pick("game/playerprop");
        }

        $this->view->lists = $this->serverModel->getLists();
        $this->view->pick("game/prop");
    }

    /**
     * 补发记录
     */
    public function logsAction()
    {
    }

    /**
     * 上传行为表
     */
    public function importActionExcelAction(){
        if ($_FILES) {
            $file = empty($_FILES['action'])?false:$_FILES['action'];

            if ($file['error'] != 0 || !$file) {
                echo json_encode(['error' => 1, 'data' => '上传失败，重新上传']);
                exit;
            }

            list($filename, $extension) = explode('.', $file['name']);

            if (!in_array($extension, $this->allow_type)) {
                echo json_encode(['error' => 1, 'data' => '文件类型非法']);
                exit;
            }

            $filepath = __DIR__ . '/../../public/files/' .'actionExcel'.'.'.$extension;
            // 将文件转移到正式文件夹
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                echo json_encode(['error' => 1, 'data' => '上传文件失败,请重试']);
                exit;
            }

            echo json_encode(['error' => 0, 'data' => '上传文件成功']);
            exit;
        }
        $this->view->pick("game/importActionExcel");
    }

    /**
     * 查看行为表
     */
    public function actionlistAction(){
        $file_name = 'actionExcel';
        $list = $this->getXlsx($file_name, 3 , [0, 2]);
        $this->view->list = $list;
        $this->view->pick("game/actionlist");
    }

    /**
     * 商城销售 商城销售排行
     */
    public function shopAction() {
        if ($_POST) {
            $data['zone']  = $this->request->get('server');
            $data['start'] = $this->request->get('start');
            $data['end']   = $this->request->get('end');

            $response = $this->gameModel->getShopRanking($data);
            if (!$response) {
                echo json_encode(['error' => 1, 'data' => '数据获取失败']);
                exit;
            }

            // 获取道具表
            $propInfo = $this->getXlsx('propExcel');
            foreach ($response as $key => $value) {
                $shop[$value['Action']][] = [
                    'ActionTime' => date("Y-m-d H:i:s", $value['ActionTime']),
                    'Action' => $value['Action'],
                    'shopName' => isset($this->shop[$value['Action']]) ? $this->shop[$value['Action']] : $value['Action'],
                    'ItemId' => isset($propInfo[$value['ItemId']]) ? $propInfo[$value['ItemId']] : $value['ItemId'],
                    'buy_propNum' => $value['buy_propNum'],
                ];

                $tab[$value['Action']] = $this->shop[$value['Action']];
            }

            $this->view->tab  = $tab;
            $this->view->data = $shop;
            return $this->view->pick('game/shopInfo');
        }

        $this->view->lists = $this->serverModel->getLists();
        $this->view->pick('game/shop');
    }

    /**
     * 用户白名单列表，只支持ip地址
     */
    public function whiteListAction()
    {
       $lists = $this->gameModel->getWhiteList();
       $need = true;
       if (empty($lists)) {
           $need = false;
       }
       $this->view->lists = $lists;
       $this->view->need = $need;
       $this->view->pick('game/whitelist');
    }

    /**
     * 添加白名单
     */
    public function createWhiteListAction(){
        if ($_POST) {
            $data['ip'] = $this->request->get('ip', 'trim');
            $data['flag'] = 0;

            if (empty($data['ip'])) {
                Utils::tips('error', '请输入ip地址', '/game/whiteList');
                exit;
            }

            if (!$this->gameModel->addWhiteList($data)) {
                Utils::tips('error', '添加失败', '/game/whiteList');
                exit;
            }

            Utils::tips('success', '添加成功', '/game/whiteList');
            exit;
        }

        $this->view->pick('game/addWhitelist');
    }

    /**
     * 删除白名单
     */
    public function deleteWhiteListAction() {
        $data['id'] = $this->request->get('id', 'int');
        if (!$this->gameModel->deletWhiteList($data)) {
            Utils::tips('error', '删除失败', '/game/whiteList');
            exit;
        }

        Utils::tips('success', '删除成功', '/game/whiteList');
        exit;
    }

    /**
     * GM命令
     */
    public function gmCommandAction()
    {
        if ($_POST) {
            $data['server']  = $this->request->get('server');
            $data['gm']      = trim($this->request->get('gm'));
            if (empty($data['server']) || empty($data['gm'])) {
                Utils::tips('error', '数据不完整', '/game/gmCommand');
                exit;
            }

            if (!$this->gameModel->sendGmData($data)) {
                Utils::tips('error', 'Gm命令发送失败', '/game/gmCommand');
                exit;
            }

            Utils::tips('success', 'Gm命令发送成功', '/game/gmCommand');
            exit;
        }

        $this->view->lists = $this->serverModel->getLists();
        $this->view->pick('game/gmCommand');
    }

    /**
     * 发送请求
     * @param $url
     * @param $data
     */
    public function post($url, $data) {

        //初使化init方法
        $ch = curl_init();

        //指定URL
        curl_setopt($ch, CURLOPT_URL, $url);

        //设定请求后返回结果
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //声明使用POST方式来进行发送
        curl_setopt($ch, CURLOPT_POST, 1);

        //发送什么数据呢
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);


        //忽略证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //忽略header头信息
        curl_setopt($ch, CURLOPT_HEADER, 0);

        //设置超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        //发送请求
        $output = curl_exec($ch);

        //关闭curl
        curl_close($ch);

        //返回数据
        return $output;
    }
}