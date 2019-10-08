<?php

namespace app\admin\controller;


use app\common\Api;
use app\common\GameLog;
use redis\Redis;
use socket\QuerySocket;
use alipay\Pay as Alipay;


class Playertrans extends Main
{
    /**
     * 转出申请审核
     */
    public function apply()
    {

        if ($this->request->isAjax()) {


            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId   = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $start    = input('start') ? input('start') : '';
            $end      = input('end') ? input('end') : '';
            $payway   = intval(input('payway')) > 0 ? intval(input('payway')) : 0;
            $realname = input('realname') ? input('realname') : '';
            $data     = ['page' => $page, 'pagesize' => $limit, 'roleid' => $roleId, 'startdate' => $start, 'enddate' => $end, 'OperateVerifyType' => 0, 'payway' => $payway, 'realname' => $realname];
            $res      = Api::getInstance()->sendRequest($data, 'charge', 'outlist');
//            print_r($res);die;
            return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], $count = $res['total'],
                ['alltotal'    => isset($res['data']['totalin']) ? $res['data']['totalin'] : 0,
                 'alltotalout' => isset($res['data']['totalout']) ? $res['data']['totalout'] : 0,
                ]);

//            return $this->apiReturn($res['code'], isset($res['data']['list'])?$res['data']['list']:[], $res['message'], $count = $res['total'], ['alltotal'=>isset($res['data']['total'])?$res['data']['total']:0]);

        }
        return $this->fetch();
    }

    /**
     * 检查是否有新的申请记录
     */
    public function checknewapply()
    {
        $data  = ['page' => 1, 'pagesize' => 10, 'roleid' => 0, 'startdate' => '', 'enddate' => '', 'OperateVerifyType' => 0, 'payway' => 0, 'realname' => ''];
        $res   = Api::getInstance()->sendRequest($data, 'charge', 'outlist');
        $count = isset($res['data']['list']) ? count($res['data']['list']) : 0;
        return $this->apiReturn(0, ['count' => $count]);
    }


    /**
     * 财务审核
     */
    public function finance()
    {

        if ($this->request->isAjax()) {

            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId   = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $start    = input('start') ? input('start') : '';
            $end      = input('end') ? input('end') : '';
            $payway   = intval(input('payway')) > 0 ? intval(input('payway')) : 0;
            $realname = input('realname') ? input('realname') : '';
            $data     = ['page' => $page, 'pagesize' => $limit, 'roleid' => $roleId, 'startdate' => $start, 'enddate' => $end, 'OperateVerifyType' => 1, 'payway' => $payway, 'realname' => $realname];
            $res      = Api::getInstance()->sendRequest($data, 'charge', 'outlist');
            if (isset($res['data']['list'])) {
                foreach ($res['data']['list'] as &$v) {
                    $v['totaltax']       = config('site.tax')['tax'] * $v['totalmoney'] / 100;
                    $v['withdrawstatus'] = config('site.withdraw');//自动/手动提现配置
                }
                unset($v);
            }

            return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], $count = $res['total'], ['alltotal' => isset($res['data']['total']) ? $res['data']['total'] : 0]);

        }

        return $this->fetch();
    }

    /**
     * 转出记录
     */
    public function record()
    {
        if ($this->request->isAjax()) {

            $page              = intval(input('page')) ? intval(input('page')) : 1;
            $limit             = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId            = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roleName          = input('rolename') ? input('rolename') : '';
            $OperateVerifyType = intval(input('classid')) >= 0 ? intval(input('classid')) : -1;
            $start             = input('start') ? input('start') : '';
            $end               = input('end') ? input('end') : '';
            $payway            = intval(input('payway')) >= 0 ? intval(input('payway')) : 0;

            $realname = input('realname') ? input('realname') : '';
            $data     = ['page' => $page, 'pagesize' => $limit, 'roleid' => $roleId, 'rolename' => $roleName, 'startdate' => $start, 'enddate' => $end, 'OperateVerifyType' => $OperateVerifyType, 'payway' => $payway, 'realname' => $realname];
            $res      = Api::getInstance()->sendRequest($data, 'charge', 'outlist');
            if (isset($res['data']['list'])) {
                foreach ($res['data']['list'] as &$v) {
                    $v['totaltax'] = config('site.tax')['tax'] * $v['totalmoney'] / 100;
                }
                unset($v);
            }

            return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], $count = $res['total'], ['alltotal' => isset($res['data']['total']) ? $res['data']['total'] : 0]);
        }
        return $this->fetch();
    }

    /**
     * 同意
     */
    public function agree()
    {
        if ($this->request->isAjax()) {

            $request = $this->request->request();
            $status  = $request['status'];
            if ($status == 0) {//未审核->已审核
                $status = 1;
                $res    = Api::getInstance()->sendRequest([
                    'roleid'    => $request['roleid'],
                    'orderid'   => $request['orderid'],
                    'status'    => $status,
                    'checkuser' => $request['checkuser'],
                    'descript'  => $request['descript']
                ], 'charge', 'updatecheck');
                $request['return'] = '审核通过';
                //log记录
                GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);
            } else if ($status == 1) {//已审核->已结算
                //获取订单详情
                $orderInfo = Api::getInstance()->sendRequest(['orderno' => $request['orderid']], 'payment', 'cashorder');
                if (!$orderInfo['data']) {
                    return $this->apiReturn(1, [], '未找到订单数据');
                }
                if ((config('site.withdraw')['alipay'] == 0 && $orderInfo['data']['payway'] == 1) || (config('site.withdraw')['bank'] == 0 && $orderInfo['data']['payway'] == 2)) {
                    //判断自动打款配置
                    return $this->apiReturn(2, [], '自动提现设置未开');
                }


                $orderInfo['data']['totalmoney'] /= 1000;
                $status                          = 3;
                $res                             = Api::getInstance()->sendRequest([
                    'roleid'    => $request['roleid'],
                    'orderid'   => $request['orderid'],
                    'status'    => $status,
                    'checkuser' => $request['checkuser'],
                    'descript'  => $request['descript']
                ], 'charge', 'updatecheck');
                $request['return'] = '同意提现';
                //log记录
                GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);

                //支付宝&&自动提现
                if ($orderInfo['data']['payway'] == 1 && config('site.withdraw')['alipay'] == 1) {
                    $alipay = new Alipay();
                    $alipay->paysynch($orderInfo['data'], $request['orderid']);
                }

            } else {
                return $this->apiReturn(3, [], '操作失败');
            }

            return $this->apiReturn($res['code'], $res['data'], $res['message']);
        }

        $this->assign('roleid', input('roleid'));
        $this->assign('orderid', input('orderid') ? input('orderid') : '');
        $this->assign('status', input('status') ? input('status') : '');
        $this->assign('checkuser', session('username'));
        $this->assign('descript', input('descript') ? input('descript') : '');
        return $this->fetch();
    }

    /**
     * 拒绝
     */
    public function refuse()
    {
        if ($this->request->isAjax()) {

            $request = $this->request->request();

            //加锁
            $key = 'lock_refuseorder_' . $request['orderid'];
            if (!Redis::getInstance()->lock($key)) {
                return $this->apiReturn(1, [], '请勿重复操作');
            }
            $res = Api::getInstance()->sendRequest([
                'roleid'    => $request['roleid'],
                'orderid'   => $request['orderid'],
                'status'    => 2,
                'checkuser' => $request['checkuser'],
                'descript'  => $request['descript']
            ], 'charge', 'updatecheck');


            if ($res['code'] == 0) {
                //给玩家返还
                //查询订单详情
                $orderInfo = Api::getInstance()->sendRequest([
                    'orderno' => $request['orderid'],
                ], 'charge', 'orderdetail');

                if ($orderInfo['data']) {
                    if (($orderInfo['data']['status'] == 2 || $orderInfo['data']['status'] == 6) && $orderInfo['data']['isreturn'] == 0 && is_numeric($orderInfo['data']['imoney']) && $orderInfo['data']['imoney'] > 0) {
                        //拒绝或者银行未通过 && 未返还
                        //给玩家返还
                        //先更新状态
                        //拒绝已返还状态
                        $update = Api::getInstance()->sendRequest([
                            'orderno' => $request['orderid'],
                            'status'  => 1
                        ], 'charge', 'updatereturn');


                        if ($update['code'] == 0) {
                            //发钱
                            $socket = new QuerySocket();
//                            $a = $socket->addRoleMoney($request['roleid'], $orderInfo['data']['imoney']);
                            $a = $socket->addRoleMoney($request['roleid'], $orderInfo['data']['imoney'], 0, 1);
                            save_log('returnmoney', json_encode($update) . ' addmoneycode : ' . $a['iResult']);
                        }
                    }
                }
            }
            Redis::getInstance()->rm($key);
            //log记录
            GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);
            return $this->apiReturn($res['code'], $res['data'], $res['message']);
        }


        $this->assign('roleid', input('roleid'));
        $this->assign('orderid', input('orderid') ? input('orderid') : '');
        $this->assign('status', input('status') ? input('status') : '');
        $this->assign('checkuser', session('username'));
        $this->assign('descript', input('descript') ? input('descript') : '');
        return $this->fetch();
    }


    //冻结没收
    public function freeze()
    {
        if ($this->request->isAjax()) {
            $request = $this->request->request();
            //加锁
            $key = 'lock_refuseorder_' . $request['orderid'];
            if (!Redis::getInstance()->lock($key)) {
                return $this->apiReturn(1, [], '请勿重复操作');
            }

            $res = Api::getInstance()->sendRequest([
                'roleid'    => $request['roleid'],
                'orderid'   => $request['orderid'],
                'status'    => 2,
                'checkuser' => $request['checkuser'],
                'descript'  => $request['descript']
            ], 'charge', 'updatecheck');


            //拒绝没收状态
            if ($res['code'] == 0) {
                //手动
                $update = Api::getInstance()->sendRequest([
                    'orderno' => $request['orderid'],
                    'status'  => 2
                ], 'charge', 'updatereturn');
            }
            Redis::getInstance()->rm($key);
            //log记录
            GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);
            return $this->apiReturn($res['code'], $res['data'], $res['message']);
        }

        $this->assign('roleid', input('roleid'));
        $this->assign('orderid', input('orderid') ? input('orderid') : '');
        $this->assign('status', input('status') ? input('status') : '');
        $this->assign('checkuser', session('username'));
        $this->assign('descript', input('descript') ? input('descript') : '');
        return $this->fetch();
    }


    //补发
    public function bufa()
    {
        $request = $this->request->request();

        if (!$request['orderno']) {
            return $this->apiReturn(1, [], '参数有误');
        }
        //加锁
        $key = 'lock_bufaorder_' . $request['orderno'];
        if (!Redis::getInstance()->lock($key)) {
            return $this->apiReturn(2, [], '请勿重复操作');
        }

        $orderInfo = Api::getInstance()->sendRequest([
            'orderno' => $request['orderno'],
        ], 'charge', 'orderdetail');
        if ($orderInfo['data']) {
            if (($orderInfo['data']['status'] == 2 || $orderInfo['data']['status'] == 6) && $orderInfo['data']['isreturn'] == 0 && is_numeric($orderInfo['data']['imoney']) && $orderInfo['data']['imoney'] > 0) {
                //拒绝或者银行未通过 && 未返还
                //给玩家返还
                //先更新状态
                $update = Api::getInstance()->sendRequest([
                    'orderno' => $request['orderno'],
                    'status'  => 3,
                ], 'charge', 'updatereturn');


                if ($update['code'] == 0) {

                    //发钱
                    $socket = new QuerySocket();
//                    $a = $socket->addRoleMoney($request['roleid'], $orderInfo['data']['imoney']);
                    $a = $socket->addRoleMoney($request['roleid'], $orderInfo['data']['imoney'], 0);
                    save_log('returnmoney', json_encode($update) . ' addmoneycode : ' . $a['iResult']);
                    Redis::getInstance()->rm($key);
                    return $this->apiReturn(0, [], '补发成功');
                } else {
                    Redis::getInstance()->rm($key);
                    return $this->apiReturn(3, [], '补发失败');
                }
            } else {
                Redis::getInstance()->rm($key);
                return $this->apiReturn(4, [], '补发失败');
            }
        } else {
            Redis::getInstance()->rm($key);
            return $this->apiReturn(5, [], '未找到订单信息');
        }
    }


    //查看备注详情
    public function descript()
    {
        if ($this->request->isAjax()) {
            $orderno = input('orderno') ? input('orderno') : '';
            if (!$orderno) {
                return $this->apiReturn(1, [], '参数有误');
            }

            $descInfo = Api::getInstance()->sendRequest([
                'orderno' => $orderno,
            ], 'charge', 'checknote');

            return $this->apiReturn($descInfo['code'], $descInfo['data'], $descInfo['message']);
        }

        $orderno = input('orderno') ? input('orderno') : '';
        $this->assign('orderno', $orderno);
        return $this->fetch();
    }


    //手动打款
    public function handle()
    {
        if ($this->request->isAjax()) {
            $request = $this->request->request();
            //加锁
            $key = 'lock_handleorder_' . $request['orderid'];
            if (!Redis::getInstance()->lock($key)) {
                return $this->apiReturn(1, [], '请勿重复操作');
            }

            $res = Api::getInstance()->sendRequest([
                'roleid'    => $request['roleid'],
                'orderid'   => $request['orderid'],
                'status'    => 5,
                'checkuser' => $request['checkuser'],
                'descript'  => $request['descript']
            ], 'charge', 'updatecheck');


            if ($res['code'] == 0) {
                //冻结
                $update = Api::getInstance()->sendRequest([
                    'orderno' => $request['orderid'],
                    'status'  => 3
                ], 'charge', 'updatereturn');
            }
            Redis::getInstance()->rm($key);
            //log记录
            GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);
            return $this->apiReturn($res['code'], $res['data'], $res['message']);
        }

        $this->assign('roleid', input('roleid'));
        $this->assign('orderid', input('orderid') ? input('orderid') : '');
        $this->assign('status', input('status') ? input('status') : '');
        $this->assign('checkuser', session('username'));
        $this->assign('descript', input('descript') ? input('descript') : '');
        return $this->fetch();
    }

    //玩家基础信息
    public function baseplayer()
    {

        $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
        $res    = Api::getInstance()->sendRequest(['id' => $roleId], 'player', 'getrole');
        $data   = isset($res['data']) ? $res['data'] : [];
        $this->assign('roleid', $roleId);
        $this->assign('data', $data);
        return $this->fetch();
    }

    //玩家流水详情
    public function coinlog()
    {
        $changeType = config('site.bank_change_type');
        if ($this->request->isAjax()) {
            $page       = intval(input('page')) ? intval(input('page')) : 1;
            $limit      = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId     = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $changtype  = intval(input('changetype')) ? intval(input('changetype')) : 0;
            $strartdate = input('strartdate') ? input('strartdate') : '';
            $enddate    = input('enddate') ? input('enddate') : '';
            $res        = Api::getInstance()->sendRequest([
                'roleid'     => $roleId,
                'strartdate' => $strartdate,
                'enddate'    => $enddate,
                'page'       => $page,
                'changetype' => $changtype,
                'pagesize'   => $limit

            ], 'player', 'coin');

            if (isset($res['data']['list'])) {
                foreach ($res['data']['list'] as &$v) {
                    if ($v['changetype'] == 1 || $v['changetype'] == 2 || $v['changetype'] == 3 || $v['changetype'] == 12 || $v['changetype'] == 21) {
                        $v['premoney'] = $v['balance'] + $v['changemoney'];
                    } else {
                        $v['premoney'] = $v['balance'] - $v['changemoney'];
                    }
                    foreach ($changeType as $k2 => $v2) {
                        if ($v['changetype'] == $k2) {
                            $v['changename'] = $v2;
                            break;
                        }
                    }
                }
                unset($v);
            }
            return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], $res['total']);
        }

        $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
        $this->assign('roleid', $roleId);
        $this->assign('changeType', $changeType);
        return $this->fetch();
    }


    //总游戏记录
    public function gamelog()
    {

        if ($this->request->isAjax()) {
            $page       = intval(input('page')) ? intval(input('page')) : 1;
            $limit      = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId     = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomid     = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $strartdate = input('strartdate') ? input('strartdate') : '';
            $enddate    = input('enddate') ? input('enddate') : '';
            $winlost    = intval(input('winlost')) >= 0 ? intval(input('winlost')) : -1;
            $res        = Api::getInstance()->sendRequest([
                'roleid'     => $roleId,
                'roomid'     => $roomid,
                'strartdate' => $strartdate,
                'enddate'    => $enddate,
                'page'       => $page,
                'winlost'    => $winlost,
                'pagesize'   => $limit
            ], 'player', 'game');

            if (isset($res['data']['list'])) {
                foreach ($res['data']['list'] as &$v) {
                    $v['premoney'] = $v['lastmoney'] - $v['money'];
                }
                unset($v);
            }

            $sumdata = [
                'win'    => isset($res['data']['win']) ? $res['data']['win'] : 0,
                'sum'    => isset($res['data']['sum']) ? $res['data']['sum'] : 0,
                'lose'   => isset($res['data']['lose']) ? $res['data']['lose'] : 0,
                'escape' => isset($res['data']['escape']) ? $res['data']['escape'] : 0,
            ];
            return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], $res['total'], ['alltotal' => $sumdata]);
        }
        $res2       = Api::getInstance()->sendRequest([
            'id' => 0
        ], 'room', 'kind');
        $selectData = $res2['data'];
        $this->assign('selectData', $selectData);
        return $this->fetch();
    }


    //个人游戏记录
    public function selfgamelog()
    {
        if ($this->request->isAjax()) {
            $page       = intval(input('page')) ? intval(input('page')) : 1;
            $limit      = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId     = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomid     = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $strartdate = input('strartdate') ? input('strartdate') : '';
            $enddate    = input('enddate') ? input('enddate') : '';
            $winlost    = intval(input('winlost')) >= 0 ? intval(input('winlost')) : -1;
            $res        = Api::getInstance()->sendRequest([
                'roleid'     => $roleId,
                'roomid'     => $roomid,
                'strartdate' => $strartdate,
                'enddate'    => $enddate,
                'page'       => $page,
                'winlost'    => $winlost,
                'pagesize'   => $limit
            ], 'player', 'game');

            if (isset($res['data']['list'])) {
                foreach ($res['data']['list'] as &$v) {
                    $v['premoney'] = $v['lastmoney'] - $v['money'];
                }
            }
            unset($v);
            return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], $res['total']);
        }

        $roleId     = intval(input('roleid')) ? intval(input('roleid')) : 0;
        $res2       = Api::getInstance()->sendRequest([
            'id' => 0
        ], 'room', 'kind');
        $selectData = $res2['data'];
        $this->assign('selectData', $selectData);
        $this->assign('roleid', $roleId);
        return $this->fetch();
    }

    //每日房间游戏记录
    public function gamedailylog()
    {
        if ($this->request->isAjax()) {
            $page    = intval(input('page')) ? intval(input('page')) : 1;
            $limit   = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId  = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $orderby = intval(input('orderby')) ? intval(input('orderby')) : 0;
            $asc     = intval(input('asc')) ? intval(input('asc')) : 0;
            $date    = trim(input('date')) ? trim(input('date')) : '';
            $res     = Api::getInstance()->sendRequest([
                'roleid'   => $roleId,
                'orderby'  => $orderby,
                'date'     => $date,
                'page'     => $page,
                'asc'      => $asc,
                'pagesize' => $limit
            ], 'room', 'gamedailylog');

            if (isset($res['data']['ResultData']['list']) && $res['data']['ResultData']['list']) {
                foreach ($res['data']['ResultData']['list'] as &$v) {
                    //盈利
                    $v['winmoney'] = $v['winmoney'] / 1000;
                    $v['tax']      = $v['tax'] / 1000;
                    //活跃度
                    $v['totalwater'] = $v['totalwater'] / 1000;
                    $v['date']       = date("Y-m-d");

                }
                unset($v);
            }

            return $this->apiReturn($res['code'], isset($res['data']['ResultData']['list']) ? $res['data']['ResultData']['list'] : [], $res['message'], isset($res['data']['total']) ? $res['data']['total'] : 0, [
                'orderby' => isset($res['data']['ResultData']['orderby']) ? $res['data']['ResultData']['orderby'] : 0,
                'asc'     => isset($res['data']['ResultData']['asc']) ? $res['data']['ResultData']['asc'] : 0,
            ]);

        }

        $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
        $this->assign('roleid', $roleId);
        return $this->fetch();
    }


    //玩家充值记录
    public function chargelog()
    {
        if ($this->request->isAjax()) {
            $page   = intval(input('page')) ? intval(input('page')) : 1;
            $limit  = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;

            $res = Api::getInstance()->sendRequest([
                'roleid'   => $roleId,
                'page'     => $page,
                'pagesize' => $limit
            ], 'player', 'userpaylog');

            if (isset($res['data']) && $res['data']) {
                foreach ($res['data'] as &$v) {
                    //盈利
                    $v['balance']     = $v['balance'] / 1000;
                    $v['rolename']    = trim($v['rolename']);
                    $v['descript']    = trim($v['descript']);
                    $v['changemoney'] = $v['changemoney'] / 1000;
                }
                unset($v);
            }

            return $this->apiReturn($res['code'], isset($res['data']) ? $res['data'] : [], $res['message'], isset($res['total']) ? $res['total'] : 0);
        }

        $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
        $this->assign('roleid', $roleId);
        return $this->fetch();
    }

}
