<?php

namespace MyApp\Models;


use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;
use GeoIp2\Database\Reader;
use Xxtime\Http\Curl;

class Utils extends Model
{

    /**
     * 闪存提示
     * @param string $type
     * @param string $message
     * @param string $redirect
     * @param int $seconds
     */
    static public function tips($type = 'info', $message = '', $redirect = '', $seconds = 0)
    {
        $flash = json_encode(
            array(
                'type'     => $type,
                'message'  => $message,
                'seconds'  => !empty($seconds) ? $seconds : 3,
                'redirect' => $redirect ? $redirect : 'javascript:history.back(-1)'
            )
        );
        DI::getDefault()->get('cookies')->set('flash', $flash, time() + 30);
        DI::getDefault()->get('cookies')->send();
        header('Location:/public/tips');
        exit();
    }


    /**
     * 输出
     * @param array $data
     */
    static public function outputJSON($data = [])
    {
        header("Content-type:application/json; charset=utf-8");
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }


    /**
     * 形象图片
     * @param string $username
     * @return string
     */
    static public function getAvatar($username = '')
    {
        return 'https://secure.gravatar.com/avatar/' . md5(strtolower(trim($username))) . '?s=80&d=identicon';
    }


    /**
     * 把数组转换为树状结构
     * @param array $data
     * @return array
     */
    public function list2tree($data = array())
    {
        if (!$data) {
            return [];
        }
        $result = array();
        foreach ($data as $value) {
            $parent[] = $value['parent'];
            $result[$value['id']] = $value;
        }
        unset($data);
        $parent = array_filter(array_unique($parent));
        $left_item_id = array();
        $left = array();
        foreach ($result as $id => $value) {
            if ($value['parent'] == 0) {
                continue;
            }
            if (!in_array($id, $parent)) {
                // 移动节点,只允许存在父级的节点移动
                if (isset($result[$value['parent']]['id'])) {
                    $result[$value['parent']]['sub'][$id] = $value;
                }
                unset($result[$id]);
            }
            else {
                $left_item_id[] = $id;
                $left[] = $value;
            }
        }
        $intersect = array_intersect($parent, $left_item_id);
        if ($intersect) {
            $result = $this->list2tree($result);
        }
        return $result;
    }


    /**
     * 位置信息
     * @param string $ipAddress
     * @return string|void
     */
    public function getLocation($ipAddress = '')
    {
        if (in_array($ipAddress, ['127.0.0.1'])) {
            return;
        }
        if (!file_exists(APP_DIR . '/config/GeoLite2-City.mmdb')) {
            return;
        }
        $reader = new Reader(APP_DIR . '/config/GeoLite2-City.mmdb');
        $record = $reader->city($ipAddress);
        $location = $record->country->names['zh-CN'] . ' ' . $record->mostSpecificSubdivision->names['zh-CN'] . ' ' . $record->city->names['zh-CN'];
        $location .= ' ' . $record->location->latitude . ' ' . $record->location->longitude;
        return $location;
    }


    /*
     * 时区转换
     */

    public function toTimeZone($src, $to_tz = '', $from_tz = 'UTC', $fm = 'Y-m-d H:i:s')
    {
        if($src == '0000-00-00 00:00:00'){
            return '暂无时间';
        }
        if ($to_tz) {

            $datetime = new \DateTime($src, new \DateTimeZone($from_tz));
            $datetime->setTimezone(new \DateTimeZone($to_tz));

            return $datetime->format($fm);
        }
        else {
            return $src;
        }
    }

    /**
     * 获取时区
     */

    public function getTimeZone()
    {
        global $config;
        if (empty(DI::getDefault()->get('session')->get('timezone'))) {
            $sql = "SELECT id,app_id,user_id,timezone,create_time FROM setting_timezone WHERE 1=1 ";

            $sql .= " AND user_id=:user_id";
            $bind['user_id'] = DI::getDefault()->get('session')->get('user_id');

            if (!empty(DI::getDefault()->get('session')->get('app'))) {
                $sql .= " AND app_id=:app_id";
                $bind['app_id'] = DI::getDefault()->get('session')->get('app');
            }

            $query = DI::getDefault()->get('dbData')->query($sql, $bind);
            $query->setFetchMode(Db::FETCH_ASSOC);
            $timezone = $query->fetch();

            $userTimeZone = empty($timezone) ? $config->setting->timezone : $timezone['timezone'];

            DI::getDefault()->get('session')->set('timezone', $userTimeZone);
        }
        return DI::getDefault()->get('session')->get('timezone');
    }


    public function yarRequest($class, $fun, $data)
    {
        global $config;
        $service = DI::getDefault()->get('session')->get('group');
        $lang = DI::getDefault()->get('session')->get('lang');
        $app_id = DI::getDefault()->get('session')->get('app');
        $host = str_replace('*', $app_id, $config->api->rpc_host);

        $protocol = $config->api->rpc_protocol;

        switch ($protocol) {

            case 'yar':
                $client = new \Yar_Client($host . "/$service/$lang/yar/$class");
                $rs =  $client->$fun($data);
                return $rs;
                break;

            case 'soap':
                $client = new \SoapClient(null, array(
                    'location' => $host . "/$service/$lang/soap/$class",
                    'uri'      => 'app',
                    'style'    => SOAP_RPC,
                    'use'      => SOAP_ENCODED,
                    'trace'    => true
                ));
                return $client->$fun($data);
                break;

            case 'http':
                $url1 = $host . "/$service/$lang/$class/$fun/";
                $curl = new Curl();
                $rs = $curl->get($url1,$data);
                return json_decode($rs,true);
                break;
        }
    }
}