<?php

namespace app\admin\controller;

use app\common\Api;
use app\common\GameLog;
use redis\Redis;
use socket\QuerySocket;
use app\admin\controller\traits\getSocketRoom;


use app\admin\controller\traits\search;


class Player extends Main
{
    use getSocketRoom;
    use search;
    private $socket = null;

    public function __construct()
    {
        parent::__construct();
        $this->socket = new QuerySocket();
    }

    /**
     * 在线玩家
     */
    public function online()
    {
        if ($this->request->isAjax()) {
            $page    = intval(input('page')) ? intval(input('page')) : 1;
            $limit   = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId  = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomId  = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $orderby = intval(input('orderby')) ? intval(input('orderby')) : 0;
            $asc     = intval(input('asc')) ? intval(input('asc')) : 0;
//            $mobile     = trim(input('mobile')) ? trim(input('mobile')) : '';

            $res = Api::getInstance()->sendRequest([
                'roleid'   => $roleId,
                'roomid'   => $roomId,
                'orderby'  => $orderby,
                'page'     => $page,
                'asc'      => $asc,
                //                'mobile'      => $mobile,
                'pagesize' => $limit
            ], 'player', 'online');
            if (isset($res['data']['list']) && $res['data']['list']) {
                foreach ($res['data']['list'] as &$v) {
                    //盈利
                    $v['totalget'] = $v['totalin'] - $v['totalout'];
                    //活跃度
                    $v['huoyue'] = $v['totalin'] != 0 ? round($v['totalwater'] / $v['totalin'], 2) : 0;

                }
                unset($v);
            }
            return $this->apiReturn($res['code'], isset($res['data']['list'])?$res['data']['list'] : [] , $res['message'], $res['total'], [
                'orderby' => isset($res['data']['orderby']) ? $res['data']['orderby'] : 0,
                'asc'     => isset($res['data']['asc']) ? $res['data']['asc'] : 0,
            ]);

        }
        $selectData = $this->getRoomList();
        $this->assign('selectData', $selectData);
        return $this->fetch();
    }

    /**
     * * Notes: 返回前端select标签里的内容
     * @return mixed
     */
    public function getRoomList()
    {
        $res = Api::getInstance()->sendRequest([
            'id' => 0
        ], 'room', 'kind');
        return $res['data'];
    }


    /**
     * 所有玩家
     */
    public function all()
    {
        if ($this->request->isAjax()) {
            $page    = intval(input('page')) ? intval(input('page')) : 1;
            $limit   = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId  = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomId  = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $orderby = intval(input('orderby')) ? intval(input('orderby')) : 0;
            $asc     = intval(input('asc')) ? intval(input('asc')) : 0;
            $mobile  = trim(input('mobile')) ? trim(input('mobile')) : '';
            $ipaddr  = trim(input('ipaddr')) ? trim(input('ipaddr')) : '';

            $res = Api::getInstance()->sendRequest([
                'roleid'   => $roleId,
                'roomid'   => $roomId,
                'orderby'  => $orderby,
                'page'     => $page,
                'asc'      => $asc,
                'ipaddr'   => $ipaddr,
                'mobile'   => $mobile,
                'pagesize' => $limit
            ], 'player', 'all');

            if (isset($res['data']['list']) && $res['data']['list']) {
                foreach ($res['data']['list'] as &$v) {
                    //盈利
                    $v['totalget'] = $v['totalin'] - $v['totalout'];
                    //活跃度
                    $v['huoyue'] = $v['totalin'] != 0 ? round($v['totalwater'] / $v['totalin'], 2) : 0;
                }
                unset($v);
            }

            return $this->apiReturn($res['code'], isset($res['data']['list'])?$res['data']['list'] : [] , $res['message'], $res['total'], [
                'orderby' => isset($res['data']['orderby']) ? $res['data']['orderby'] : 0,
                'asc'     => isset($res['data']['asc']) ? $res['data']['asc'] : 0,
            ]);

        }
        $selectData = $this->getRoomList();
        $this->assign('selectData', $selectData);
        return $this->fetch();
    }

    /**
     * 游戏日志(玩家列表点击)
     */
    public function gameLog()
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
        $selectData = $this->getRoomList();
        $this->assign('selectData', $selectData);
        $this->assign('roleid', $roleId);
        return $this->fetch();
    }


    /**
     * 玩家详情(玩家列表点击)
     */
    public function playerDetail()
    {
        if ($this->request->isAjax()) {

        }

        $roleId     = intval(input('roleid')) ? intval(input('roleid')) : 0;
        $selectData = $this->getRoomList();
        $res        = Api::getInstance()->sendRequest([
            'Id' => $roleId
        ], 'player', 'getbank');
        $bankname   = config('site.bank');


        if ($res['data']) {
            $this->assign('username', trim($res['data']['username']));
            $this->assign('roleid', $res['data']['roleid']);
            $this->assign('bankcardno', trim($res['data']['bankcardno']));
            $this->assign('bankname3', trim($res['data']['bankname']));
            $this->assign('mytip', '33');
        } else {
            $this->assign('username', '');
            $this->assign('roleid', $roleId);
            $this->assign('bankcardno', '');
            $this->assign('bankname3', '');
            $this->assign('mytip', '22');
        }

        $this->assign('bankname', $bankname);

        return $this->fetch();
    }


    public function updatebank($roleid, $username, $bankcardno, $bankname)
    {

        $request  = $this->request->request();
        $updadata = [
            'roleid'     => $roleid,
            'username'   => $username,
            'bankcardno' => $bankcardno,
            'bankname'   => $bankname,
        ];
//        var_dump($updadata);die;
        $res = Api::getInstance()->sendRequest($updadata, 'player', 'updatebank');
        //log记录
        GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);

    }


    //更新银行卡
    public function updateSocketBank()
    {
        if ($this->request->isAjax()) {
            $roleid     = input('roleid') ? input('roleid') : '';
            $username   = input('username') ? input('username') : '';
            $bankcardno = input('bankcardno') ? input('bankcardno') : '';
            $len        = strlen($bankcardno);
            if ($len < 13) {
                return $this->apiReturn(3, [], '银行卡号少于13为');
            }
            $bankname = input('bankname') ? input('bankname') : '';
            if (!$username || !$bankcardno || !$bankname || !$roleid) {
                return $this->apiReturn(2, [], '输入不能为空');
            }
            $socket = new QuerySocket();
            $result = $socket->updateBank($roleid, $username, $bankcardno, $bankname);
            GameLog::logData(__METHOD__, $this->request->request(), 1);
            if ($result["iResult"] == 0) {
                ob_clean();
                return $this->apiReturn(0, [], '修改成功!');
            } else {
//                var_dump($result["iResult"]);die;
//                $this->updatebank();
                $this->updatebank($roleid, $username, $bankcardno, $bankname);
                return $this->apiReturn(1, [], '修改成功');
            }

        }

    }

    //查询角色是否锁定
    public function getRoleStatus()
    {
        $RoleID = input('roleid') ? input('roleid') : '';
        $socket = new QuerySocket();
        $result = $socket->searchRoleStatus($RoleID);
        //4锁定
        if ($result === 3) {
//                ob_clean();
            return $this->apiReturn(3, [], '用户未被锁定!');
        } else {
            return $this->apiReturn(4, [], '用户已被锁定');
        }
    }

    //更新角色状态
    public function updateRoleStatus()
    {

        if ($this->request->isAjax()) {
            $roleid     = intval(input('roleid')) ? intval(input('roleid')) : '';
            $day        = intval(input('day')) ? intval(input('day')) : 300;
            $roleStatus = $this->getRoleStatus()->getdata();
            $socket     = new QuerySocket();

            if ($roleStatus['code'] === 4) {
                //解锁角色
                $result = $socket->unlockRoleStatus($roleid);
                if ($result["iResult"] == 0) {
                    ob_clean();
                    GameLog::logData(__METHOD__, $this->request->request(), 1);
                    return $this->apiReturn(0, [], '角色解锁成功!');
                } else {
                    GameLog::logData(__METHOD__, $this->request->request(), 0);
                    return $this->apiReturn(1, [], '角色解锁失败');
                }
            } else {
                //锁定角色lockRoleStatus

                $result = $socket->lockRoleStatus($roleid, $day);
                if ($result["iResult"] == 0) {
                    ob_clean();
                    GameLog::logData(__METHOD__, $this->request->request(), 1);
                    return $this->apiReturn(0, [], '角色锁定成功!');
                } else {
                    GameLog::logData(__METHOD__, $this->request->request(), 0);
                    return $this->apiReturn(1, [], '角色锁定失败');
                }

            }

        }

    }

    //更新角色状态
    public function updateRoleStatus2()
    {

        if ($this->request->isAjax()) {
            $roleid = intval(input('roleid')) ? intval(input('roleid')) : '';
            $socket = new QuerySocket();
            $result = $socket->unlockRoleStatus($roleid);
            if ($result["iResult"] == 0) {
                ob_clean();
                GameLog::logData(__METHOD__, $this->request->request(), 1);
                return $this->apiReturn(0, [], '角色锁定成功!');
            } else {
                GameLog::logData(__METHOD__, $this->request->request(), 0);
                return $this->apiReturn(1, [], '角色锁定失败');
            }


        }

    }

    //设置房间概率信息
    public function setSocketRoomRate()
    {
        $roomid  = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $init    = intval(input('init')) ? intval(input('init')) : 0;
        $current = intval(input('current')) ? intval(input('current')) : 0;

        if (abs($init) > 2000000 || abs($current) > 2000000) {
            return $this->apiReturn(1, [], '库存值不能超过绝对值200万');
        }
        $roomsData = $this->getSocketRoom($this->socket, $roomid);

        $this->socket->setRoom($roomid, $roomsData['nCtrlRatio'], $init, $current, $roomsData['szStorageRatio']);
        GameLog::logData(__METHOD__, $this->request->request(), 1);
        ob_clean();
        return $this->apiReturn(0, [], '修改成功');
    }


    /**
     * Notes: 游戏日志（单独菜单）
     * @return mixed
     */
    public function gamelog2()
    {


        if ($this->request->isAjax()) {
//            var_dump(3);die;

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
                'totaltax' => isset($res['data']['totaltax']) ? $res['data']['totaltax'] : 0,
            ];

            return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], $res['total'], ['alltotal' => $sumdata]);
        }
        $selectData = $this->getRoomList();
        $this->assign('selectData', $selectData);
        return $this->fetch();
    }

    /**
     * 金币日志
     */
    public function coinLog()
    {
        $changeType = config('site.bank_change_type');
        if ($this->request->isAjax()) {
            $page      = intval(input('page')) ? intval(input('page')) : 1;
            $limit     = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId    = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $strartdate = input('strartdate') ? input('strartdate') : '';
            $enddate    = input('enddate') ? input('enddate') : '';
            $changtype = intval(input('changetype')) ? intval(input('changetype')) : 0;
            $res       = Api::getInstance()->sendRequest([
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

    /**
     * Notes: 金币日志（单独菜单）
     * @return mixed
     */
    public function coinlog2()
    {
        $changeType = config('site.bank_change_type');
        if ($this->request->isAjax()) {
            $page       = intval(input('page')) ? intval(input('page')) : 1;
            $limit      = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId     = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $strartdate = input('strartdate') ? input('strartdate') : '';
            $enddate    = input('enddate') ? input('enddate') : '';
            $changetype = intval(input('changetype')) ? intval(input('changetype')) : 0;
            $res        = Api::getInstance()->sendRequest([
                'roleid'     => $roleId,
                'strartdate' => $strartdate,
                'enddate'    => $enddate,
                'page'       => $page,
                'changetype' => $changetype,
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
            return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], $res['total'], $res['data']['sum']);
        }


        $this->assign('changeType', $changeType);
        return $this->fetch();
    }


    /**
     * Notes: 超级玩家列表
     * @return mixed
     */
    public function super()
    {
        if ($this->request->isAjax()) {
            $page   = intval(input('page')) ? intval(input('page')) : 1;
            $limit  = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;

            $res = Api::getInstance()->sendRequest(['roleid' => $roleId, 'page' => $page, 'pagesize' => $limit], 'SuperUser', 'list');
            return $this->apiReturn($res['code'], $res['data']['ResultData']['list'], $res['message'], $res['data']['total'],
                [
                    $res['data']['ResultData']['totalinsum'],
                    $res['data']['ResultData']['totaloutsum'],
                ]);
        }
        return $this->fetch();
    }

    /**
     * Notes: 新增超级玩家
     * @return mixed
     */
    public function addSuper()
    {
        if ($this->request->isAjax()) {
            $request  = $this->request->request();
            $validate = validate('Player');
            $validate->scene('addSuper');
            if (!$validate->check($request)) {
                return $this->apiReturn(1, [], $validate->getError());
            }
            $socket = new QuerySocket();
            $res1   = $socket->setSuperPlayer($request['roleid'], $request['rate']);
            if ($res1['iResult'] == 0) {
                $res = Api::getInstance()->sendRequest(['roleid' => $request['roleid'], 'rate' => $request['rate']], 'SuperUser', 'add');
                //log记录
                GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);
                return $this->apiReturn($res['code'], $res['data'], $res['message']);
            } else {
                GameLog::logData(__METHOD__, $request, 0, $res1);
                return $this->apiReturn(1, [], '添加失败');
            }

        }
        return $this->fetch();
    }

    /**
     * Notes: 编辑超级玩家
     * @return mixed
     */
    public function editSuper()
    {
        if ($this->request->isAjax()) {
            $request  = $this->request->request();
            $validate = validate('Player');
            $validate->scene('editSuper');
            if (!$validate->check($request)) {
                return $this->apiReturn(1, [], $validate->getError());
            }
            $res = Api::getInstance()->sendRequest(['roleid' => $request['roleid'], 'rate' => $request['rate']], 'SuperUser', 'update');

            //log记录
            GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);
            return $this->apiReturn($res['code'], $res['data'], $res['message']);
        }

        $roleid = input('roleid');
        $rate   = intval(input('rate')) ? intval(input('rate')) : 0;
        $this->assign('roleid', $roleid);
        $this->assign('rate', $rate);
        return $this->fetch();
    }

    /**
     * Notes: 删除超级玩家
     * @return mixed
     */
    public function deleteSuper()
    {
        $request  = $this->request->request();
        $validate = validate('Player');
        $validate->scene('deleteSuper');
        if (!$validate->check($request)) {
            return $this->apiReturn(1, [], $validate->getError());
        }
        $res = Api::getInstance()->sendRequest(['roleid' => $request['roleid']], 'SuperUser', 'delete');

        //log记录
        GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);
        return $this->apiReturn($res['code'], $res['data'], $res['message']);
    }

    /**
     * 给玩家扣款
     */
    public function cutmoney()
    {
        if ($this->request->isAjax()) {
            $page    = intval(input('page')) ? intval(input('page')) : 1;
            $limit   = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId  = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $start   = input('start') ? input('start') : '';
            $end     = input('end') ? input('end') : '';
            $classid = input('classid') ? input('classid') : -1;


            $data           = ['page' => $page, 'pagesize' => $limit];
            $data['typeid'] = 1;
            if ($roleId) {
                $data['roleid'] = $roleId;
            }
            if ($classid && $classid != -1) {
                $data['classid'] = $classid;
            }
            if ($start) {
                $data['starttime'] = $start;
                if ($end) {
                    $data['endtime'] = $end;
                }
            }
            $res = Api::getInstance()->sendRequest($data, 'charge', 'list');

            return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], $res['total'], isset($res['data']['total']) ? $res['data']['total'] : 0);
        }
        return $this->fetch();
    }

    /**
     * 向玩家扣款
     */
    public function addCutmoney()
    {
        if ($this->request->isAjax()) {
            $request = $this->request->request();

            if ($request['totalmoney'] < 0) {
                return $this->apiReturn(3, [], '扣款金额必须为正数');
            }
            //加锁
            $key = 'lock_addtransfer_' . $request['roleid'];
            if (!Redis::getInstance()->lock($key)) {
                return $this->apiReturn(2, [], '请勿重复操作');
            }

            $data      = [
                'roleid'     => $request['roleid'],
                'classid'    => 4,
                'totalmoney' => $request['totalmoney'],
                'uid'        => session('userid'),
                'adduser'    => session('username'),
                'typeid'     => 1,
                'descript'   => $request['descript'] ? $request['descript'] : ''
            ];
            $socket    = new QuerySocket();
            $moneytype = $request['moneytype'];
            $res       = 0;
            if ($moneytype == 3) {
                $res = $socket->addRoleMoney($data['roleid'], $data['totalmoney'] * 1000, 1);
            } else {
                $res = $socket->addRoleMoneyYuer($data['roleid'], $data['totalmoney'] * 1000);
            }
            if ($res['iResult'] == 0) {
                $res = Api::getInstance()->sendRequest($data, 'charge', 'add');
                //log记录
                GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);
                Redis::getInstance()->rm($key);
                if ($res['code'] == 101) {
                    return $this->apiReturn(0, $res['data'], '请求成功');
                }
                return $this->apiReturn($res['code'], $res['data'], $res['message']);
            } else if ($res['iResult'] == 5) {

                return $this->apiReturn(3, [], '扣款失败账户余额不足！');
            }
            Redis::getInstance()->rm($key);
            return $this->apiReturn(3, [], '添加失败');
        }
        return $this->fetch();
    }

    /**
     * 向玩家转账
     */
    public function transfer()
    {
        if ($this->request->isAjax()) {
            $page    = intval(input('page')) ? intval(input('page')) : 1;
            $limit   = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId  = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $start   = input('start') ? input('start') : '';
            $end     = input('end') ? input('end') : '';
            $classid = input('classid') ? input('classid') : -1;


            $data           = ['page' => $page, 'pagesize' => $limit];
            $data['typeid'] = 0;
            if ($roleId) {
                $data['roleid'] = $roleId;
            }
            if ($classid && $classid != -1) {
                $data['classid'] = $classid;
            }
            if ($start) {
                $data['starttime'] = $start;
                if ($end) {
                    $data['endtime'] = $end;
                }
            }
            $res = Api::getInstance()->sendRequest($data, 'charge', 'list');

            return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], $res['total'], isset($res['data']['total']) ? $res['data']['total'] : 0);
        }
        return $this->fetch();
    }

    /**
     * 向玩家转账
     */
    public function addTransfer()
    {
        if ($this->request->isAjax()) {
            $request  = $this->request->request();
            $validate = validate('Player');
            $validate->scene('addTransfer');
            if (!$validate->check($request)) {
                return $this->apiReturn(1, [], $validate->getError());
            }

            if ($request['totalmoney'] < 0) {
                return $this->apiReturn(3, [], '转账金额必须为正数');
            }

            //加锁
            $key = 'lock_addtransfer_' . $request['roleid'];
            if (!Redis::getInstance()->lock($key)) {
                return $this->apiReturn(2, [], '请勿重复操作');
            }

            $data   = [
                'roleid'     => $request['roleid'],
                'classid'    => $request['classid'],
                'totalmoney' => $request['totalmoney'],
                'uid'        => session('userid'),
                'adduser'    => session('username'),
                'typeid'     => 0,
                'descript'   => $request['descript'] ? $request['descript'] : ''
            ];
            $socket = new QuerySocket();
//            $res    = $socket->addRoleMoney($data['roleid'], $data['totalmoney'] * 1000);
            $res = $socket->addRoleMoney($data['roleid'], $data['totalmoney'] * 1000, 0);
            if ($res['iResult'] == 0) {

                $res = Api::getInstance()->sendRequest($data, 'charge', 'add');
                //log记录
                GameLog::logData(__METHOD__, $request, (isset($res['code']) && $res['code'] == 0) ? 1 : 0, $res);
                Redis::getInstance()->rm($key);
                if ($res['code'] == 101) {
                    return $this->apiReturn(0, $res['data'], '请求成功');
                }
                return $this->apiReturn($res['code'], $res['data'], $res['message']);
            }
            Redis::getInstance()->rm($key);
            return $this->apiReturn(3, [], '添加失败');
        }
        return $this->fetch();
    }


    //玩家重置密码
    public function resetPwd()
    {
        $request  = $this->request->request();
        $roleId   = intval(input('roleid')) ? intval(input('roleid')) : 0;
        $pwd      = input('pwd') ? input('pwd') : '';
        $checkPwd = input('pwd2') ? input('pwd2') : '';

        if (!$roleId || !$pwd || !$checkPwd) {
            return $this->apiReturn(1, [], '必填项不能为空');
        }
        if ($pwd != $checkPwd) {
            return $this->apiReturn(2, [], '两次输入密码不一致');
        }

//        $res = Api::getInstance()->sendRequest([
//            'roleid'   => $roleId,
//            'password' => $pwd
//        ], 'player', 'updatepwd');
//        if ($res['code'] == 0) {
//
//        }
        $socket = new QuerySocket();
        $res    = $socket->setPlayerPwd($roleId, $pwd);

        //log记录
        GameLog::logData(__METHOD__, $request, (isset($res['iResult']) && $res['iResult'] == 0) ? 1 : 0, $res);
        if (isset($res['iResult']) && $res['iResult'] == 0) {
            return $this->apiReturn($res['iResult'], [], '修改成功');
        } else {
            return $this->apiReturn(1, [], '修改失败');
        }

    }

    //玩家强退
    public function forceQuit()
    {
        if ($this->request->isAjax()) {
            $roleId = input('roleid') ? input('roleid') : '';
            if (!$roleId) {
                return json(['code' => 1, 'msg' => '请输入正确的玩家ID']);
            }
            //log记录
            $res = $this->socket->setForceQuit($roleId);
            GameLog::logData(__METHOD__, $this->request->request(), (isset($res['iResult']) && $res['iResult'] == 0) ? 1 : 0, $res);
            if (isset($res['iResult']) && $res['iResult'] == 0) {
                return $this->apiReturn($res['iResult'], [], '操作成功');
            } else {
                return $this->apiReturn(1, [], '操作失败');
            }
        }
        return $this->fetch();
    }

    //玩家牌型
    public function cardtype()
    {
        $roomlist = Api::getInstance()->sendRequest(['id' => 0], 'room', 'kind');
        if ($this->request->isAjax()) {
            $roomId = intval(input('roomId')) ? intval(input('roomId')) : 0;
            $page   = intval(input('page')) ? intval(input('page')) : 1;
            $limit  = intval(input('limit')) ? intval(input('limit')) : 15;

            $res = $this->socket->getProfitPercent($roomId);
            if ($res) {
                if ($roomlist['data']) {
                    foreach ($res as &$v) {
                        foreach ($roomlist['data'] as $v2) {
                            if ($v['nRoomId'] == $v2['roomid']) {
                                $v['roomname'] = $v2['roomname'];
                            }
                        }

                        $v['lTotalRunning'] /= 1000;
                        $v['lTotalProfit']  /= 1000;
                    }
                    unset($v);
                }
            }


            //假分页
            $result = [];

            $from  = ($page - 1) * $limit;
            $to    = $page * $limit - 1;
            $count = count($res);
            if ($count > 0 && $count >= $from) {
                for ($i = $from; $i <= $to; $i++) {
                    if (isset($res[$i])) {
                        $result[] = $res[$i];
                    }
                }
            }
            ob_clean();
            return $this->apiReturn(0, $result, '', $count);
        }
        //$roomList = Api::getInstance()->sendRequest(['id' => 0], 'room', 'kind');
        $kindList = Api::getInstance()->sendRequest(['id' => 0], 'room', 'kindlist');
        $this->assign('roomlist', $roomlist['data']);
        $this->assign('kindlist', $kindList['data']);
        return $this->fetch();
    }


    //查看玩家详情
    public function detail()
    {

//        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
//        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
//        $res = Api::getInstance()->sendRequest([
//            'uniqueid' => $uniqueid,
//            'roomid' => $roomId,
//        ], 'game', 'drawinfo');
        //        $res = json_decode($res['data']['gamedetail'], true);
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $userid   = input('userid ') ? input('userid ') : 0;
        $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $page     = intval(input('page')) ? intval(input('page')) : 1;
        $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
        $res      = Api::getInstance()->sendRequest([
            'uniqueid' => $uniqueid,
            'roomid'   => $roomId,
            'userid'   => $userid,
            'page'     => $page,
            'pagesize' => $limit
        ], 'game', 'getcart');


        $res = json_decode($res['data'][0]['gamedetail'], true);


        if ($res) {
            $this->assign('bet', $res['bet']);
            $this->assign('basescore', $res['bet']['basescore']);
            $this->assign('chuntian', $res['bet']['chuntian']);
            $this->assign('totaltime', $res['bet']['totaltime']);
            $this->assign('boomtime', $res['bet']['boomtime']);
            $this->assign('callscore', $res['bet']['callscore']);
            $this->assign('qiangscore', $res['bet']['qiangscore']);
            $this->assign('boomtime', $res['bet']['boomtime']);
            if (isset($res['lose'])) {
                $this->assign('win', $res['lose']);
            } else if (isset($res['lost'])) {
                $this->assign('win', $res['lost']);
            } else {
                $this->assign('win', $res['win']);
            }

            if (isset($res['card']['player2']) && isset($res['card']['player1']) && !isset($res['card']['player0'])) {
                if ($res['bet']['player2'] == 'single') {
                    $this->assign('nplay2', '不加倍');
                } else {
                    $this->assign('nplay2', '加倍');
                }
                if ($res['bet']['player1'] == 'single') {
                    $this->assign('nplay0', '不加倍');
                } else {
                    $this->assign('nplay0', '加倍');
                }

            }
            if (isset($res['card']['player2']) && isset($res['card']['player0']) && !isset($res['card']['player1'])) {
                if ($res['bet']['player2'] == 'single') {
                    $this->assign('nplay2', '不加倍');
                } else {
                    $this->assign('nplay2', '加倍');
                }
                if ($res['bet']['player0'] == 'single') {
                    $this->assign('nplay0', '不加倍');
                } else {
                    $this->assign('nplay0', '加倍');
                }

            }
            if (isset($res['card']['player1']) && isset($res['card']['player0']) && !isset($res['card']['player2'])) {
                if ($res['bet']['player1'] == 'single') {
                    $this->assign('nplay2', '不加倍');
                } else {
                    $this->assign('nplay2', '加倍');
                }
                if ($res['bet']['player0'] == 'single') {
                    $this->assign('nplay0', '不加倍');
                } else {
                    $this->assign('nplay0', '加倍');
                }

            }

            $this->assign('dizhu', $res['host']['userid']);


            if (isset($res['card']['host1'])) {
                $res['card']['host1'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['card']['host1']);
                $host1                = explode(",", $res['card']['host1']);
                $host1                = array_slice($host1, 0, 17);
                $this->assign('host1', $host1);

                if ($res['remaincard']['host1']) {
                    $res['remaincard']['host1'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['host1']);
                    $rehost1                    = explode(",", $res['remaincard']['host1']);
//                    $rehost1 =array_slice($rehost1,0,17);
                    $rehost1 = array_slice($rehost1, 0, count($rehost1) - 1);
                    $this->assign('rehost1', $rehost1);
                } else {
                    $victory = array("victory");
                    $this->assign('rehost1', $victory);
                    $this->assign('vich', 'vic');
                    $this->assign('vic0', 'vic44');
                    $this->assign('vic2', 'vic44');
                }
                $this->assign('hostname', trim($res['roleid']['host1']));
            } else if (isset($res['card']['host2'])) {
                $res['card']['host2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['card']['host2']);
                $host1                = explode(",", $res['card']['host2']);
                $host1                = array_slice($host1, 0, 17);
                $this->assign('host1', $host1);

                if ($res['remaincard']['host2']) {
                    $res['remaincard']['host2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['host2']);
                    $rehost1                    = explode(",", $res['remaincard']['host2']);
//                    $rehost1 =array_slice($rehost1,0,17);
                    $rehost1 = array_slice($rehost1, 0, count($rehost1) - 1);
                    $this->assign('rehost1', $rehost1);
                } else {
                    $victory = array("victory");
                    $this->assign('rehost1', $victory);
                    $this->assign('vich', 'vic');
                    $this->assign('vic0', 'vic44');
                    $this->assign('vic2', 'vic44');
                }
                $this->assign('hostname', trim($res['roleid']['host2']));
            } else {
                $res['card']['host2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['card']['host0']);
                $host1                = explode(",", $res['card']['host2']);
                $host1                = array_slice($host1, 0, 17);
                $this->assign('host1', $host1);

                if ($res['remaincard']['host0']) {
                    $res['remaincard']['host2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['host0']);
                    $rehost1                    = explode(",", $res['remaincard']['host2']);
//                    $rehost1 =array_slice($rehost1,0,17);
                    $rehost1 = array_slice($rehost1, 0, count($rehost1) - 1);
                    $this->assign('rehost1', $rehost1);
                } else {
                    $victory = array("victory");
                    $this->assign('rehost1', $victory);
                    $this->assign('vich', 'vic');
                    $this->assign('vic0', 'vic3');
                    $this->assign('vic2', 'vic5');
                }
                $this->assign('hostname', trim($res['roleid']['host0']));
            }


//var_dump($res['card']);die;
            if (isset($res['card']['player0'])) {
                $res['card']['player0'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['card']['player0']);
                $player0                = explode(",", $res['card']['player0']);
                $player0                = array_slice($player0, 0, 17);
                $this->assign('player0', $player0);

                if ($res['remaincard']['player0']) {
                    $res['remaincard']['player0'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['player0']);
                    $replayer0                    = explode(",", $res['remaincard']['player0']);
//                    $replayer0 =array_slice($replayer0,0,17);
                    $replayer0 = array_slice($replayer0, 0, count($replayer0) - 1);
                    $this->assign('replayer0', $replayer0);
                } else {
                    $victory = array("victory");
                    $this->assign('replayer0', $victory);
                    $this->assign('vic0', 'vic');
                    $this->assign('vich', 'vic3');
                    $this->assign('vic2', 'vic5');
                }
                $this->assign('player0name', trim($res['roleid']['player0']));
            }

            if (isset($res['card']['player2'])) {
                $res['card']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['card']['player2']);
                $player2                = explode(",", $res['card']['player2']);
                $player2                = array_slice($player2, 0, 17);
                $this->assign('player2', $player2);

                if ($res['remaincard']['player2']) {
                    $res['remaincard']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['player2']);
                    $replayer2                    = explode(",", $res['remaincard']['player2']);
//                    $replayer2 =array_slice($replayer2,0,17);
                    $replayer2 = array_slice($replayer2, 0, count($replayer2) - 1);
                    $this->assign('replayer2', $replayer2);
                } else {
                    $victory = array("victory");
                    $this->assign('replayer2', $victory);
                    $this->assign('vic2', 'vic');
                    $this->assign('vich', 'vic1');
                    $this->assign('vic0', 'vic2');
                }
                $this->assign('player2name', trim($res['roleid']['player2']));
            }
//            else{
            if (isset($res['card']['player1'])) {
                $res['card']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['card']['player1']);
                $player2                = explode(",", $res['card']['player2']);
                $player2                = array_slice($player2, 0, 17);
                $this->assign('player2', $player2);

                if ($res['remaincard']['player1']) {
                    $res['remaincard']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['player1']);
                    $replayer2                    = explode(",", $res['remaincard']['player2']);
//                    $replayer2 =array_slice($replayer2,0,17);
                    $replayer2 = array_slice($replayer2, 0, count($replayer2) - 1);
                    $this->assign('replayer2', $replayer2);
                } else {
                    $victory = array("victory");
                    $this->assign('replayer2', $victory);
                    $this->assign('vic2', 'vic');
                    $this->assign('vich', 'vic1');
                    $this->assign('vic0', 'vic2');
                }
                $this->assign('player2name', trim($res['roleid']['player1']));
            }


            if (isset($res['card']['player2']) && isset($res['card']['player1']) && !isset($res['card']['player0'])) {
                $res['card']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['card']['player1']);
                $player2                = explode(",", $res['card']['player2']);
                $player2                = array_slice($player2, 0, 17);
                $this->assign('player2', $player2);

                if ($res['remaincard']['player1']) {
                    $res['remaincard']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['player1']);
                    $replayer2                    = explode(",", $res['remaincard']['player2']);
//                    $replayer2 =array_slice($replayer2,0,17);
                    $replayer2 = array_slice($replayer2, 0, count($replayer2) - 1);
                    $this->assign('replayer2', $replayer2);
                } else {
                    $victory = array("victory");
                    $this->assign('replayer2', $victory);
                    $this->assign('vic2', 'vic');
                    $this->assign('vich', 'vic1');
                    $this->assign('vic0', 'vic2');
                }

                $res['card']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['card']['player2']);
                $player2                = explode(",", $res['card']['player2']);
                $player2                = array_slice($player2, 0, 17);
                $this->assign('player0', $player2);

                if ($res['remaincard']['player2']) {
                    $res['remaincard']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['player2']);
                    $replayer2                    = explode(",", $res['remaincard']['player2']);
//                    $replayer2 =array_slice($replayer2,0,17);
                    $replayer2 = array_slice($replayer2, 0, count($replayer2) - 1);
                    $this->assign('replayer0', $replayer2);
                } else {
                    $victory = array("victory");
                    $this->assign('replayer0', $victory);
                    $this->assign('vic0', 'vic');
                    $this->assign('vich', 'vic1');
                    $this->assign('vic0', 'vic2');
                }

                $this->assign('player0name', trim($res['roleid']['player1']));
                $this->assign('player2name', trim($res['roleid']['player2']));
            }


//            $this->assign('hostname', trim($res['roleid']['host1']));
//            $this->assign('player0name', trim($res['roleid']['player0']));
//            $this->assign('player2name', trim($res['roleid']['player2']));


        }


        return $this->fetch();
    }

    public function lookPartnerCard()
    {
        if ($this->request->isAjax()) {

            $page   = intval(input('page')) ? intval(input('page')) : 1;
            $limit  = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
//            $roomId = $this->request->request('roomid');
//            var_dump($roleId );die;
            $res = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
        return $this->fetch();
    }

    /**
     * 查看伙牌
     */
    public function lookPartnerCardZjh()
    {
        if ($this->request->isAjax()) {
            $page   = intval(input('page')) ? intval(input('page')) : 1;
            $limit  = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res    = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['card']        = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card']        = explode(",", $item['card']);
                    $item['coinbefore']  = $item['coinbefore'] / 1000;
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['coinafter']   = $item['coinafter'] / 1000;
                }
                unset($item);
            }


            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
        return $this->fetch();
    }

    public function lookPartnerCardMpqz()
    {
        if ($this->request->isAjax()) {
            $page   = intval(input('page')) ? intval(input('page')) : 1;
            $limit  = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res    = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['card']        = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card']        = explode(",", $item['card']);
                    $item['coinbefore']  = $item['coinbefore'] / 1000;
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['coinafter']   = $item['coinafter'] / 1000;
                }
                unset($item);
            }


            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
        return $this->fetch();
    }

    public function lookPartnerCardBrnn()
    {
        if ($this->request->isAjax()) {
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId   = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res      = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'uniqueid' => $uniqueid,
                'tag'      => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['card']        = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card']        = explode(" ", $item['card']);
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['totalbet']    = $item['totalbet'] / 1000;
                }
                unset($item);
            }


            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
        return $this->fetch();
    }

    public function lookPartnerCardDzpk()
    {
        if ($this->request->isAjax()) {
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId   = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res      = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'uniqueid' => $uniqueid,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['card']        = str_replace(['黑', '梅', '方', '红', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card']        = explode(",", $item['card']);
                    $item['tablecard']   = str_replace(['黑', '梅', '方', '红', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['tablecard']);
                    $item['tablecard']   = explode(",", $item['tablecard']);
                    $item['coinbefore']  = $item['coinbefore'] / 1000;
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['coinafter']   = $item['coinafter'] / 1000;
                }
                unset($item);
            }


            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
        return $this->fetch();
    }

    public function lookPartnerCardHlsb()
    {
        if ($this->request->isAjax()) {
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId   = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res      = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'uniqueid' => $uniqueid,
                'tag'      => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
//                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['1', '2', '3', '4', '5', '6'], $item['card']);
                    $item['card']        = explode(" ", $item['card']);
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['totalbet']    = $item['totalbet'] / 1000;
                }
                unset($item);
            }


            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
        return $this->fetch();
    }


    public function lookPartnerCardMj()
    {
        if ($this->request->isAjax()) {
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId   = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = input('uniqueid') ? input('uniqueid') : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res      = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'uniqueid' => $uniqueid,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');

            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
//                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['1', '2', '3', '4', '5', '6'], $item['card']);
                    $item['card']        = str_replace(['一', '二', '三', '四', '五', '六', '七', '八', '九', '万'], ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'w'], $item['card']);
                    $item['card']        = str_replace(['东', '南', '西', '北', '白', '发', '中'], ['df', 'nf', 'xf', 'bf', 'bb', 'fc', 'hz'], $item['card']);
                    $item['tablecard']   = str_replace(['一', '二', '三', '四', '五', '六', '七', '八', '九', '万'], ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'w'], $item['tablecard']);
                    $item['tablecard']   = str_replace(['东', '南', '西', '北', '白', '发', '中'], ['df', 'nf', 'xf', 'bf', 'bb', 'fc', 'hz'], $item['tablecard']);
                    $item['card']        = explode(",", $item['card']);
                    $item['tablecard']   = explode(",", $item['tablecard']);
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['coinbefore']  = $item['coinbefore'] / 1000;
                    $item['coinafter']   = $item['coinafter'] / 1000;
                }
                unset($item);
            }


            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
        return $this->fetch();
    }

    public function detailMj()
    {
        if ($this->request->isAjax()) {
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId   = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = input('uniqueid') ? input('uniqueid') : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res      = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'uniqueid' => $uniqueid,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');

            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {

//                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['1', '2', '3', '4', '5', '6'], $item['card']);
                    $item['card']        = str_replace(['一', '二', '三', '四', '五', '六', '七', '八', '九', '万'], ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'w'], $item['card']);
                    $item['card']        = str_replace(['东', '南', '西', '北', '白', '发', '中'], ['df', 'nf', 'xf', 'bf', 'bb', 'fc', 'hz'], $item['card']);
                    $item['tablecard']   = str_replace(['一', '二', '三', '四', '五', '六', '七', '八', '九', '万'], ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'w'], $item['tablecard']);
                    $item['tablecard']   = str_replace(['东', '南', '西', '北', '白', '发', '中'], ['df', 'nf', 'xf', 'bf', 'bb', 'fc', 'hz'], $item['tablecard']);
                    $item['card']        = explode(",", $item['card']);
                    $item['tablecard']   = explode(",", $item['tablecard']);
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['coinbefore']  = $item['coinbefore'] / 1000;
                    $item['coinafter']   = $item['coinafter'] / 1000;
                }
                unset($item);
            }


            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid', $uniqueid);
        $this->assign('roomId', $roomId);
        return $this->fetch();
    }

    public function lookPartnerCardBjl()
    {
        if ($this->request->isAjax()) {
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId   = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res      = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'uniqueid' => $uniqueid,
                'tag'      => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['banker', 'playercouple', 'player', 'equal', 'bankercouple'], ['庄', '闲对', '闲 ', '和', '庄对'], $item['card']);
//                    $item['card'] =explode(" ", $item['card']);
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['totalbet']    = $item['totalbet'] / 1000;
                }
                unset($item);
            }


            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
        return $this->fetch();
    }

    public function lookPartnerCardLonghudou()
    {
        if ($this->request->isAjax()) {
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId   = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res      = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'uniqueid' => $uniqueid,
                'tag'      => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
//                    $item['card'] = str_replace(['banker', 'playercouple', 'player', 'equal', 'bankercouple'], ['庄', '闲对', '闲 ', '和', '庄对'], $item['card']);
//                    $item['card'] =explode(" ", $item['card']);
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['totalbet']    = $item['totalbet'] / 1000;
                }
                unset($item);
            }

            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
        return $this->fetch();
    }

    public function lookPartnerCardBcbm()
    {
        if ($this->request->isAjax()) {
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId   = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res      = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'uniqueid' => $uniqueid,
                'tag'      => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['totalbet']    = $item['totalbet'] / 1000;
                }
                unset($item);
            }

            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
        return $this->fetch();
    }


    //查看玩家详情
    public function detailZjh()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid   = input('userid ') ? input('userid ') : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res      = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid'   => $roomId,
                'userid'   => $userid,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['card']        = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card']        = explode(",", $item['card']);
                    $item['coinbefore']  = $item['coinbefore'] / 1000;
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['coinafter']   = $item['coinafter'] / 1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid', $uniqueid);
        $this->assign('roomId', $roomId);


        return $this->fetch();
    }

    public function detailMpqz()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid   = input('userid ') ? input('userid ') : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res      = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid'   => $roomId,
                'userid'   => $userid,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['card']        = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card']        = explode(",", $item['card']);
                    $item['coinbefore']  = $item['coinbefore'] / 1000;
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['coinafter']   = $item['coinafter'] / 1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid', $uniqueid);
        $this->assign('roomId', $roomId);


        return $this->fetch();
    }

    public function detailHlsb()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid   = input('userid ') ? input('userid ') : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res      = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid'   => $roomId,
                'userid'   => $userid,
                'tag'      => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
//                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);

                    $item['card']        = explode(" ", $item['card']);
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['totalbet']    = $item['totalbet'] / 1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid', $uniqueid);
        $this->assign('roomId', $roomId);


        return $this->fetch();
    }


    public function detailDzpk()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid   = input('userid ') ? input('userid ') : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res      = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid'   => $roomId,
                'userid'   => $userid,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['card']        = str_replace(['黑', '梅', '方', '红', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card']        = explode(",", $item['card']);
                    $item['tablecard']   = str_replace(['黑', '梅', '方', '红', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['tablecard']);
                    $item['tablecard']   = explode(",", $item['tablecard']);
                    $item['coinbefore']  = $item['coinbefore'] / 1000;
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['coinafter']   = $item['coinafter'] / 1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid', $uniqueid);
        $this->assign('roomId', $roomId);


        return $this->fetch();
    }

    public function detailBrnn()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid   = input('userid ') ? input('userid ') : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res      = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid'   => $roomId,
                'userid'   => $userid,
                'tag'      => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['card']        = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card']        = explode(" ", $item['card']);
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['totalbet']    = $item['totalbet'] / 1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid', $uniqueid);
        $this->assign('roomId', $roomId);


        return $this->fetch();
    }

    public function detailBjl()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid   = input('userid ') ? input('userid ') : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res      = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid'   => $roomId,
                'userid'   => $userid,
                'tag'      => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['banker', 'playercouple', 'player', 'equal', 'bankercouple'], ['庄', '闲对', '闲 ', '和', '庄对'], $item['card']);

//                    $item['card'] =explode(" ", $item['card']);
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['totalbet']    = $item['totalbet'] / 1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid', $uniqueid);
        $this->assign('roomId', $roomId);


        return $this->fetch();
    }

    public function detailLonghudou()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid   = input('userid ') ? input('userid ') : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res      = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid'   => $roomId,
                'userid'   => $userid,
                'tag'      => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {

                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['totalbet']    = $item['totalbet'] / 1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid', $uniqueid);
        $this->assign('roomId', $roomId);


        return $this->fetch();
    }

    public function detailBcbm()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid   = input('userid ') ? input('userid ') : 0;
            $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res      = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid'   => $roomId,
                'userid'   => $userid,
                'tag'      => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if (!empty($res['data'])) {
                foreach ($res['data'] as &$item) {
                    $item['changemoney'] = $item['changemoney'] / 1000;
                    $item['totalbet']    = $item['totalbet'] / 1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId   = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid', $uniqueid);
        $this->assign('roomId', $roomId);


        return $this->fetch();
    }

    //所有同场玩家情况
    public function coplayer()
    {
        if ($this->request->isAjax()) {

            $page   = intval(input('page')) ? intval(input('page')) : 1;
            $limit  = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
//            $roomId = $this->request->request('roomid');
//            var_dump($roleId );die;
            $res = Api::getInstance()->sendRequest([
                'userid'   => $roleId,
                'roomid'   => $roomId,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
        return $this->fetch();
    }

}
