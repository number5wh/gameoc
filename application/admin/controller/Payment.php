<?php

namespace app\admin\controller;


use app\common\Api;
use app\common\GameLog;

/**
 * 支付通道
 */
class Payment extends Main
{
    /**
     * 线下转账
     */
    public function offline()
    {
        if ($this->request->isAjax()) {
            $res = Api::getInstance()->sendRequest([], 'payment', 'offline');
            return $this->apiReturn($res['code'], $res['data'], $res['message'], 2);
        }
        return $this->fetch();
    }

    /**
     * 线下转账修改
     */
    public function editOffline()
    {
        if ($this->request->isAjax()) {
            $request  = $this->request->request();
            $validate = validate('Offline');
            $validate->scene('editOffline');
            if (!$validate->check($request)) {
                return $this->apiReturn(1, [], $validate->getError());
            }
            $res = Api::getInstance()->sendRequest([
                'classid'   => $request['classid'],
                'classname' => $request['classname'],
                'bank'      => $request['bank'],
                'cardno'    => $request['cardno'],
                'cardname'  => $request['cardname'],
                'descript'  => $request['descript']
            ], 'payment', 'updateoff');

            //log记录
            GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);
            return $this->apiReturn($res['code'], $res['data'], $res['message']);
        }


        $this->assign('classid', input('classid'));
        $this->assign('classname', input('classname') ?? '');
        $this->assign('bank', input('bank') ?? '');
        $this->assign('cardno', input('cardno') ?? '');
        $this->assign('cardname', input('cardname') ?? '');
        $this->assign('descript', input('descript') ?? '');
        return $this->fetch();
    }

    /**
     * 支付通道
     */
    public function channel()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 新增支付通道
     */
    public function addChannel()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 支付金额
     */
    public function amount()
    {
        if ($this->request->isAjax()) {
            $res = Api::getInstance()->sendRequest([], 'payment', 'amount');
            return $this->apiReturn($res['code'], $res['data'], $res['message'], 10);
        }
        return $this->fetch();
    }

    /**
     * 新增金额
     */
    public function addAmount()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * Notes: 修改金额
     * @return mixed
     */
    public function editAmount()
    {
        if ($this->request->isAjax()) {
            $request  = $this->request->request();
            $validate = validate('Payment');
            $validate->scene('editAmount');
            if (!$validate->check($request)) {
                return $this->apiReturn(1, [], $validate->getError());
            }
            $res = Api::getInstance()->sendRequest([
                'id'   => $request['id'],
                'amount' => $request['amount']
            ], 'payment', 'updateamount');

            //log记录
            GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);
            return $this->apiReturn($res['code'], $res['data'], $res['message']);
        }


        $this->assign('id', input('id'));
        $this->assign('amount', input('amount') ?? '');
        return $this->fetch();
    }

    /**
     * 通道金额配置
     */
    public function payment()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * 新增通道金额
     */
    public function addPayment()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

}
