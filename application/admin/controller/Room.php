<?php

namespace app\admin\controller;

use app\common\Api;
use socket\ChangeData;
use socket\Comm;
use socket\sendQuery;
class Room extends Main
{

    public function getScoket()
    {
        $comm = new Comm();
        $socket = $comm->getSocketInstance('DC');
        return $socket;
    }
    /**
     * 房间总览
     */
    public function index()
    {

        $res = Api::getInstance()->sendRequest([], 'room', 'list');
        $this->assign('roomlist', $res['data']);
        return $this->fetch();
    }

    /**
     * 捕鱼
     */
    public function buyu()
    {
        $comm = new Comm();
        $socket = $comm->getSocketInstance('DC');

        $sendQuery = new sendQuery();
//        $sendQuery->SendMDQueryRoom($socket, 69);
//        $out_data = $socket->response();
        $change = new ChangeData();
//        $out_array =  $change->ProcessDMQueryRoomRate($out_data);

//
        $sendQuery->SendMDCtrolRoom($socket,69,0,0,0,'');
        $out_data = $socket->response();
        $out_array =  $change->ProcessDMSetRoomRate($out_data);
        var_dump($out_array);
        die;
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 抢庄牛牛
     */
    public function qzniuniu()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 经典牛牛
     */
    public function jdniuniu()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 二人麻将
     */
    public function majiang()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 斗地主
     */
    public function doudizhu()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 龙虎斗
     */
    public function longhudou()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 百家乐
     */
    public function bjl()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 奔驰宝马
     */
    public function bcbm()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 飞禽走兽
     */
    public function fqzs()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 红黑大战
     */
    public function hhdz()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 百人牛牛
     */
    public function brnn()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }
}
