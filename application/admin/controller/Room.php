<?php

namespace app\admin\controller;

use app\admin\controller\traits\search;
use app\common\Api;
use socket\QuerySocket;
use app\admin\controller\traits\getSocketRoom;


class Room extends Main
{
    use getSocketRoom;
    use search;
    private $socket = null;

    public function __construct()
    {
        parent::__construct();
        $this->socket = new QuerySocket();
    }

    //获取房间库存概率信息
    public function getSocketRoomData()
    {
        $roomid    = input('roomid');
        $roomsData = $this->getSocketRoom($this->socket, $roomid);
        return $this->apiReturn(0, $roomsData, 'success');
    }


    //设置房间概率信息
    public function setSocketRoomRate()
    {
        $roomid    = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $init      = intval(input('init')) ? intval(input('init')) : 0;
        $current   = intval(input('current')) ? intval(input('current')) : 0;
        $roomsData = $this->getSocketRoom($this->socket, $roomid);

        $this->socket->setRoom($roomid, $roomsData['nCtrlRatio'], $init, $current, $roomsData['szStorageRatio']);
        ob_clean();
        return $this->apiReturn(0, [], '修改成功');
    }


    //设置房间库存信息
    public function setSocketRoomStorage()
    {
        if ($this->request->isAjax()) {
            $request = $this->request->request();
            $roomid  = $request['roomid'];
            $storage = json_decode($request['data'], true);
            ksort($storage);

            $storageStr = '';
            foreach ($storage as $k => $v) {
                $storageStr .= $k . '#' . $v . '#';
            }
            $storageStr = rtrim($storageStr, '#');
            $roomsData  = $this->getSocketRoom($this->socket, $roomid);
            $this->socket->setRoom($roomid, $roomsData['nCtrlRatio'], $roomsData['nInitStorage'], $roomsData['nCurrentStorage'], $storageStr);

            ob_clean();
            return $this->apiReturn(0, [], '修改成功');
        }

        $roomid                      = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $roomsData                   = $this->getSocketRoom($this->socket, $roomid);
        $roomsData['szStorageRatio'] = trim($roomsData['szStorageRatio']);

        $array = [];
        if ($roomsData['szStorageRatio']) {

            $storage = explode('#', $roomsData['szStorageRatio']);
            $info    = array_chunk($storage, 2);

            if ($info) {
                foreach ($info as $k => $v) {
                    $array[] = [
                        'rate'    => $v[1],
                        'storage' => $v[0]
                    ];
                }
            }
        }


        $this->assign('lists', $array);
        $this->assign('thisroomid', $roomid);
        return $this->fetch('setstorage');
    }

    /**
     * 房间总览
     */
    public function index()
    {
        $res = Api::getInstance()->sendRequest(['id' => 0], 'room', 'list');
        $this->assign('roomlist', $res['data']);
        return $this->fetch();
    }

    /**
     * 捕鱼
     */
    public function buyu()
    {
        $kindId = 2223;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch();
    }

    /**
     * 抢庄牛牛
     */
    public function qzniuniu()
    {
        $kindId = 1010;
        if ($this->request->isAjax()) {

        }

        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch();
    }

    /**
     * 经典牛牛
     */
    public function jdniuniu()
    {
        $kindId = 1140;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch();
    }

    /**
     * 二人麻将
     */
    public function majiang()
    {
        $kindId = 9006;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch();
    }

    /**
     * 斗地主
     */
    public function doudizhu()
    {
        $kindId = 1072;
        if ($this->request->isAjax()) {

        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch();
    }

    /**
     * 获取房间数据
     * @return mixed
     */
    public function roomData()
    {
        $roomid = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $res    = Api::getInstance()->sendRequest([
            'id' => $roomid
        ], 'room', 'list');

        return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
    }

    /**
     * 龙虎斗
     */
    public function longhudou()
    {
        $kindId = 1100;
        if ($this->request->isAjax()) {

        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch();
    }

    /**
     * 百家乐
     */
    public function bjl()
    {
        $kindId = 1150;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch();
    }

    /**
     * 奔驰宝马
     */
    public function bcbm()
    {
        $kindId = 500;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch();
    }

    /**
     * 飞禽走兽
     */
    public function fqzs()
    {
        $kindId = 1109;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch();
    }

    /**
     * 红黑大战
     */
    public function hhdz()
    {
        $kindId = 9005;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch();
    }

    /**
     * 百人牛牛
     */
    public function brnn()
    {
        $kindId = 9000;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch();
    }
}
