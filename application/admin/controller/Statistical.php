<?php

namespace app\admin\controller;

use app\common\Api;

class Statistical extends Main
{
    /**
     * 游戏每日输赢数据
     */
    public function gamedata()
    {
        if ($this->request->isAjax()) {
            $page    = intval(input('page')) ? intval(input('page')) : 1;
            $limit   = intval(input('limit')) ? intval(input('limit')) : 10;
            $orderby = intval(input('orderby')) ? intval(input('orderby')) : 1;
            $asc     = intval(input('asc')) ? intval(input('asc')) : 0;
            $date    = trim(input('strartdate')) ? trim(input('strartdate')) : '';
            $kindid  = trim(input('kindid')) ? trim(input('kindid')) : 0;


            $res = Api::getInstance()->sendRequest([

                'date'     => $date,
                'page'     => $page,
                'pagesize' => $limit,
                'orderby'  => $orderby,
                'asc'      => $asc,
                'kindid'   => $kindid,

            ], 'room', 'roomdailywin');

            if (isset($res['data']['list']) && $res['data']['list']) {
                foreach ($res['data']['list'] as &$v) {
                    //盈利 sprintf("%.2f",$num);
                    $v['totalwater'] = sprintf("%.2f", $v['totalwater'] / 1000);
                    $v['totalwin']   = sprintf("%.2f", $v['totalwin'] / 1000);
                    $v['blacktax']   = sprintf("%.2f", $v['blacktax'] / 1000);
                    $v['killpoint']  = sprintf("%.2f", $v['killpoint'] / 1000);
                    $v['percent']    = $v['percent'] . '%';
                    $v['addtime']    = substr($v['addtime'], 0, 10);

                    //活跃度


                }
                unset($v);
            }


//            return $this->apiReturn($res['code'], isset($res['data']['ResultData']['list'])?$res['data']['ResultData']['list'] : [] , $res['message'], $res['data']['total'], [
            return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], isset($res['total']) ? $res['total'] : 0, [
                'orderby' => isset($res['data']['orderby']) ? $res['data']['orderby'] : 0,
                'asc'     => isset($res['data']['asc']) ? $res['data']['asc'] : 0,
            ]);

        }


        $kindList = Api::getInstance()->sendRequest(['id' => 0], 'room', 'kindlist');

        $this->assign('kindlist', $kindList['data']);

        $selectData = $this->getRoomList();
        $this->assign('selectData', $selectData);
        return $this->fetch();
    }

    public function getRoomList()
    {
        $res = Api::getInstance()->sendRequest([
            'id' => 0
        ], 'room', 'kind');
        return $res['data'];
    }

    /**
     * 平台每日统计Platform Daily Statistics
     */
    public function platformDailyStatistics()
    {

        if ($this->request->isAjax()) {
            $date    = trim(input('strartdate')) ? trim(input('strartdate')) : '';

            $res     = Api::getInstance()->sendRequest([
                'date'     => $date,
            ], 'system', 'gamedata');
            if (isset($res['data']) && $res['data']!=null) {
                $this->assign('data',$res['data']);
                $this->assign('mydata', $date);
                $this->assign('flag', 3);
                return $this->apiReturn(3, isset($res['data']) ? $res['data'] : [], '', 0, $date);
            }else{
                $this->assign('mydata',$date);
                if($date==''){
                    $date    =  date("Y-m-d");
                }
                return $this->apiReturn(4, isset($res['data']) ? $res['data'] : [], '', 0, $date);
            }


        }
        $date    =  date("Y-m-d");
//        $date    =  '2019-09-23';
        $res     = Api::getInstance()->sendRequest([
            'date'     => $date,
        ], 'system', 'gamedata');
        if (isset($res['data']) && $res['data'] != null) {
            $this->assign('data', $res['data']);
            $this->assign('mydata', $date);
            $this->assign('flag', 3);
        } else {
            $this->assign('mydata', $date);
            $this->assign('flag', 4);
        }
        return $this->fetch();
    }




    /**
     * 玩家每日游戏房间记录
     */

    public function gamedailylog()
    {

        if ($this->request->isAjax()) {
            $page    = intval(input('page')) ? intval(input('page')) : 1;
            $limit   = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId  = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomId  = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $orderby = intval(input('orderby')) ? intval(input('orderby')) : 0;
            $asc     = intval(input('asc')) ? intval(input('asc')) : 0;
            $date    = trim(input('date')) ? trim(input('date')) : '';
            $res     = Api::getInstance()->sendRequest([
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


//            return $this->apiReturn($res['code'], isset($res['data']['ResultData']['list'])?$res['data']['ResultData']['list'] : [] , $res['message'], $res['data']['total'], [
            return $this->apiReturn($res['code'], isset($res['data']['ResultData']['list']) ? $res['data']['ResultData']['list'] : [], $res['message'], isset($res['data']['total']) ? $res['data']['total'] : 0, [
                'orderby' => isset($res['data']['ResultData']['orderby']) ? $res['data']['ResultData']['orderby'] : 0,
                'asc'     => isset($res['data']['ResultData']['asc']) ? $res['data']['ResultData']['asc'] : 0,
            ]);

        }
        return $this->fetch();
    }


    //游戏在线统计图
    public function gameonline()
    {
        $limit = 1000;
        $page  = 1;
        $start = date('Y-m-d');
        $end   = date('Y-m-d');
        $query = [
            'startdate' => $start,
            'enddate'   => $end,
            'page'      => $page,
            'pagesize'  => $limit,
        ];

        $res = Api::getInstance()->sendRequest($query, 'game', 'gameonline');
        //时间  ios  安卓
        $dates = $numbers = $numbers2 = [];
        if (isset($res['data']) && $res['data']) {
            foreach ($res['data'] as $v) {
                $dates[]    = $v['addtime'];
                $numbers[]  = $v['hallonline'];
                $numbers2[] = $v['roomonline'];
            }
        }
        return $this->apiReturn($res['code'], ['dates' => $dates, 'numbers' => $numbers, 'numbers2' => $numbers2], $res['message']);
    }

    public function gameonlinedata()
    {
        if ($this->request->isAjax()) {
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $page  = intval(input('page')) ? intval(input('page')) : 1;
            $start = input('start') ? input('start') : date('Y-m-d');
            $end   = input('end') ? input('end') : date('Y-m-d');
            $query = [
                'startdate' => $start,
                'enddate'   => $end,
                'page'      => $page,
                'pagesize'  => $limit,
            ];
            $res   = Api::getInstance()->sendRequest($query, 'game', 'gameonline');
            //时间  ios  安卓
            return $this->apiReturn($res['code'], isset($res['data']) ? $res['data'] : [], $res['message'], $res['total']);
        }
        return $this->fetch();
    }

}