<?php

namespace app\admin\controller;

use app\admin\controller\traits\search;
use app\common\Api;
use app\common\GameLog;
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
        $roomid = input('roomid');
        $roomsData = $this->getSocketRoom($this->socket, $roomid);
        return $this->apiReturn(0, $roomsData, 'success');
    }


    //设置房间概率信息
    public function setSocketRoomRate()
    {
        $roomid = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $init = intval(input('init')) ? intval(input('init')) : 0;
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


    //设置房间库存信息
    public function setSocketRoomStorage()
    {
        if ($this->request->isAjax()) {
            $request = $this->request->request();
            $roomid = $request['roomid'];
            $storage = json_decode($request['data'], true);
            ksort($storage);

            $storageStr = '';
            foreach ($storage as $k => $v) {
                if (abs($k) > 2000000) {
                    return $this->apiReturn(1, [], '库存值不能超过绝对值200万');
                }
                $storageStr .= $k . '#' . $v . '#';
            }
            $storageStr = rtrim($storageStr, '#');
            $roomsData = $this->getSocketRoom($this->socket, $roomid);
            $this->socket->setRoom($roomid, $roomsData['nCtrlRatio'], $roomsData['nInitStorage'], $roomsData['nCurrentStorage'], $storageStr);

            GameLog::logData(__METHOD__, $request, 1);
            ob_clean();
            return $this->apiReturn(0, [], '修改成功');
        }

        $roomid = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $roomsData = $this->getSocketRoom($this->socket, $roomid);
        $roomsData['szStorageRatio'] = trim($roomsData['szStorageRatio']);

        $array = [];
        if ($roomsData['szStorageRatio']) {

            $storage = explode('#', $roomsData['szStorageRatio']);
            $info = array_chunk($storage, 2);

            if ($info) {
                foreach ($info as $k => $v) {
                    $array[] = [
                        'rate' => $v[1],
                        'storage' => $v[0]
                    ];
                }
            }
        }


        $this->assign('lists', $array);
        $this->assign('thisroomid', $roomid);
        return $this->fetch('setstorage');
    }


    //百人场数据
    public function getHundredData()
    {
        $roomid = input('id');
        $res = Api::getInstance()->sendRequest(['id' => $roomid], 'room', 'draw');
        if (isset($res['data']['list'])) {
            $index = 1;
            foreach ($res['data']['list'] as &$v) {
                $v['id'] = $index++;
            }
            unset($v);
        }
        return $this->apiReturn($res['code'], isset($res['data']['list']) ? $res['data']['list'] : [], $res['message'], $res['total'], isset($res['data']['topten']) ? $res['data']['topten'] : []);
    }

    /**
     * 设置玩家胜率
     */
    public function setPlayerRate()
    {

        if ($this->request->isAjax()) {
            $roleid = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $ratio = intval(input('ratio')) ? intval(input('ratio')) : 0;
            $time = intval(input('time')) ? intval(input('time')) : 0;
//            $timeinterval = intval(input('timeinterval')) ? intval(input('timeinterval')) : 0;
            $timeinterval = 10000000;
            $socket = new QuerySocket();
            $socket->setRoleRate($roleid, $ratio, $time, $timeinterval);
            ob_clean();
            GameLog::logData(__METHOD__, $this->request->request());
            return $this->apiReturn(0, [], '修改成功');
        }

        $roleid = intval(input('roleid')) ? intval(input('roleid')) : '';
        $ratio = intval(input('ratio')) ? intval(input('ratio')) : '';
        $time = intval(input('time')) ? intval(input('time')) : '';
//        $timeinterval = intval(input('timeinterval')) ? intval(input('timeinterval')) : '';
        $readonly = intval(input('readonly')) ? intval(input('readonly')) : '';

        $this->assign('roleid', $roleid);
        $this->assign('ratio', intval($ratio));
        $this->assign('time', intval($time));
//        $this->assign('timeinterval', intval($timeinterval));
        $this->assign('read', intval($readonly));
        return $this->fetch();
    }

    /**
     * 查看伙牌
     */
    public function lookPartnerCard()
    {
        if ($this->request->isAjax()) {

            $page = intval(input('page')) ? intval(input('page')) : 1;
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
//            $roomId = $this->request->request('roomid');
//            var_dump($roleId );die;
            $res = Api::getInstance()->sendRequest([
//                'roleid'   => $roleId,
                'userid' => $roleId,
                'roomid' => $roomId,
                'page' => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('roomid', $roomId);
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
        $userid = input('userid ') ? input('userid ') : 0;
        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $page     = intval(input('page')) ? intval(input('page')) : 1;
        $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
        $res = Api::getInstance()->sendRequest([
            'uniqueid' => $uniqueid,
            'roomid' => $roomId,
            'userid' => $userid ,
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
            } else  if (isset($res['lost'])) {
                $this->assign('win', $res['lost']);
            }else{
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
                $host1 = explode(",", $res['card']['host1']);
                $host1 = array_slice($host1, 0, 17);
                $this->assign('host1', $host1);

                if ($res['remaincard']['host1']) {
                    $res['remaincard']['host1'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['host1']);
                    $rehost1 = explode(",", $res['remaincard']['host1']);
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
                $host1 = explode(",", $res['card']['host2']);
                $host1 = array_slice($host1, 0, 17);
                $this->assign('host1', $host1);

                if ($res['remaincard']['host2']) {
                    $res['remaincard']['host2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['host2']);
                    $rehost1 = explode(",", $res['remaincard']['host2']);
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
                $host1 = explode(",", $res['card']['host2']);
                $host1 = array_slice($host1, 0, 17);
                $this->assign('host1', $host1);

                if ($res['remaincard']['host0']) {
                    $res['remaincard']['host2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['host0']);
                    $rehost1 = explode(",", $res['remaincard']['host2']);
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
                $player0 = explode(",", $res['card']['player0']);
                $player0 = array_slice($player0, 0, 17);
                $this->assign('player0', $player0);

                if ($res['remaincard']['player0']) {
                    $res['remaincard']['player0'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['player0']);
                    $replayer0 = explode(",", $res['remaincard']['player0']);
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
                $player2 = explode(",", $res['card']['player2']);
                $player2 = array_slice($player2, 0, 17);
                $this->assign('player2', $player2);

                if ($res['remaincard']['player2']) {
                    $res['remaincard']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['player2']);
                    $replayer2 = explode(",", $res['remaincard']['player2']);
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
                $player2 = explode(",", $res['card']['player2']);
                $player2 = array_slice($player2, 0, 17);
                $this->assign('player2', $player2);

                if ($res['remaincard']['player1']) {
                    $res['remaincard']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['player1']);
                    $replayer2 = explode(",", $res['remaincard']['player2']);
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
                $player2 = explode(",", $res['card']['player2']);
                $player2 = array_slice($player2, 0, 17);
                $this->assign('player2', $player2);

                if ($res['remaincard']['player1']) {
                    $res['remaincard']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['player1']);
                    $replayer2 = explode(",", $res['remaincard']['player2']);
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
                $player2 = explode(",", $res['card']['player2']);
                $player2 = array_slice($player2, 0, 17);
                $this->assign('player0', $player2);

                if ($res['remaincard']['player2']) {
                    $res['remaincard']['player2'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $res['remaincard']['player2']);
                    $replayer2 = explode(",", $res['remaincard']['player2']);
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


    /**
     * 房间总览
     */
    public function index()
    {
        $res = Api::getInstance()->sendRequest(['id' => 0], 'room', 'list');
        $this->assign('roomlist', isset($res['data']['ResultData']) ? $res['data']['ResultData'] : []);
        $this->assign('historytotal', isset($res['data']['historytotal']) ? $res['data']['historytotal'] : 0);
        $this->assign('currentscore', isset($res['data']['currentscore']) ? $res['data']['currentscore'] : 0);
        $this->assign('totalonline', isset($res['data']['totalonline']) ? $res['data']['totalonline'] : 0);
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
        return $this->fetch('danren');
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
//        return $this->fetch('danren');
//        return $this->fetch('zjh');
        return $this->fetch('mpqz');
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
//        return $this->fetch('danren');
        return $this->fetch();
    }

    /**
     * 获取房间数据
     * @return mixed
     */
    public function roomData()
    {
        $roomid = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $res = Api::getInstance()->sendRequest([
            'id' => $roomid
        ], 'room', 'list');
        return $this->apiReturn(
            $res['code'],
            isset($res['data']['ResultData'][0]) ? $res['data']['ResultData'][0] : [],
            $res['message'],
            $res['total']);
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
//        return $this->fetch('bairen');
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
//        return $this->fetch('bairen');
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
//        return $this->fetch('bairen');
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
//        return $this->fetch('bairen');
        return $this->fetch('bcbm');
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
//        return $this->fetch('bairen');
        return $this->fetch('longhudou');
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
//        return $this->fetch('bairen');
        return $this->fetch();
    }

    /**
     * Notes:红包扫雷
     */
    public function hbsl()
    {
        $kindId = 9004;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch('danren');
    }


    /**
     * Notes:水果拉霸
     */
    public function sglb()
    {
        $kindId = 3224;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
//        return $this->fetch('bairen');
        return $this->fetch('danren');
    }

    /**
     * Notes:欢乐骰宝
     */
    public function hlsb()
    {
        $kindId = 5007;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
//        return $this->fetch('bairen');
        return $this->fetch();
    }

    /**
     * Notes:炸金花
     */


    public function zjh()
    {
        $kindId = 9008;
        if ($this->request->isAjax()) {

        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
//        return $this->fetch('danren');
        return $this->fetch();
    }



    /**
     * 明牌抢庄
     */
    public function qzniuniu()
//    public function mpqz()
    {
        $kindId = 1010;
        if ($this->request->isAjax()) {

        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
//        return $this->fetch('danren');
//        return $this->fetch('zjh');
        return $this->fetch('mpqz');
    }






    /**
     * 查看伙牌
     */
    public function lookPartnerCardZjh()
    {
        if ($this->request->isAjax()) {
            $page = intval(input('page')) ? intval(input('page')) : 1;
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res = Api::getInstance()->sendRequest([
                'userid' => $roleId,
                'roomid' => $roomId,
                'page' => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card'] =explode(",", $item['card']);
                    $item['coinbefore'] =$item['coinbefore']/1000;
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['coinafter'] =$item['coinafter']/1000;
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
            $page = intval(input('page')) ? intval(input('page')) : 1;
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res = Api::getInstance()->sendRequest([
                'userid' => $roleId,
                'roomid' => $roomId,
                'page' => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card'] =explode(",", $item['card']);
                    $item['coinbefore'] =$item['coinbefore']/1000;
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['coinafter'] =$item['coinafter']/1000;
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
            $page = intval(input('page')) ? intval(input('page')) : 1;
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res = Api::getInstance()->sendRequest([
                'userid' => $roleId,
                'roomid' => $roomId,
                'uniqueid' => $uniqueid,
                'tag' => 1,
                'page' => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card'] =explode(" ", $item['card']);
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['totalbet'] =$item['totalbet']/1000;
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
            $page = intval(input('page')) ? intval(input('page')) : 1;
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res = Api::getInstance()->sendRequest([
                'userid' => $roleId,
                'roomid' => $roomId,
                'uniqueid' => $uniqueid,
                'page' => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['黑', '梅', '方', '红', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card'] =explode(",", $item['card']);
                    $item['tablecard'] = str_replace(['黑', '梅', '方', '红', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['tablecard']);
                    $item['tablecard'] =explode(",", $item['tablecard']);
                    $item['coinbefore'] =$item['coinbefore']/1000;
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['coinafter'] =$item['coinafter']/1000;
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
            $page = intval(input('page')) ? intval(input('page')) : 1;
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res = Api::getInstance()->sendRequest([
                'userid' => $roleId,
                'roomid' => $roomId,
                'uniqueid' => $uniqueid,
                'tag' => 1,
                'page' => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
//                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['1', '2', '3', '4', '5', '6'], $item['card']);
                    $item['card'] =explode(" ", $item['card']);
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['totalbet'] =$item['totalbet']/1000;
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
            $page = intval(input('page')) ? intval(input('page')) : 1;
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = input('uniqueid') ? input('uniqueid') : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res = Api::getInstance()->sendRequest([
                'userid' => $roleId,
                'roomid' => $roomId,
                'uniqueid' => $uniqueid,
                'page' => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');

            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
//                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['1', '2', '3', '4', '5', '6'], $item['card']);
                    $item['card'] = str_replace(['一', '二', '三', '四', '五', '六','七','八','九','万'], ['1', '2', '3', '4', '5', '6','7','8','9', 'w'], $item['card']);
                    $item['card'] = str_replace(['东', '南', '西', '北', '白', '发','中'], ['df', 'nf', 'xf', 'bf', 'bb', 'fc','hz'], $item['card']);
                    $item['tablecard'] = str_replace(['一', '二', '三', '四', '五', '六','七','八','九','万'], ['1', '2', '3', '4', '5', '6','7','8','9', 'w'], $item['tablecard']);
                    $item['tablecard'] = str_replace(['东', '南', '西', '北', '白', '发','中'], ['df', 'nf', 'xf', 'bf', 'bb', 'fc','hz'], $item['tablecard']);
                    $item['card'] =explode(",", $item['card']);
                    $item['tablecard'] =explode(",", $item['tablecard']);
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['coinbefore'] =$item['coinbefore']/1000;
                    $item['coinafter'] =$item['coinafter']/1000;
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
            $page = intval(input('page')) ? intval(input('page')) : 1;
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = input('uniqueid') ? input('uniqueid') : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res = Api::getInstance()->sendRequest([
                'userid' => $roleId,
                'roomid' => $roomId,
                'uniqueid' => $uniqueid,
                'page' => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');

            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {

//                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['1', '2', '3', '4', '5', '6'], $item['card']);
                    $item['card'] = str_replace(['一', '二', '三', '四', '五', '六','七','八','九','万'], ['1', '2', '3', '4', '5', '6','7','8','9', 'w'], $item['card']);
                    $item['card'] = str_replace(['东', '南', '西', '北', '白', '发','中'], ['df', 'nf', 'xf', 'bf', 'bb', 'fc','hz'], $item['card']);
                    $item['tablecard'] = str_replace(['一', '二', '三', '四', '五', '六','七','八','九','万'], ['1', '2', '3', '4', '5', '6','7','8','9', 'w'], $item['tablecard']);
                    $item['tablecard'] = str_replace(['东', '南', '西', '北', '白', '发','中'], ['df', 'nf', 'xf', 'bf', 'bb', 'fc','hz'], $item['tablecard']);
                    $item['card'] =explode(",", $item['card']);
                    $item['tablecard'] =explode(",", $item['tablecard']);
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['coinbefore'] =$item['coinbefore']/1000;
                    $item['coinafter'] =$item['coinafter']/1000;
                }
                unset($item);
            }


            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);

        }

        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid',$uniqueid);
        $this->assign('roomId',$roomId);
        return $this->fetch();
    }

    public function lookPartnerCardBjl()
    {
        if ($this->request->isAjax()) {
            $page = intval(input('page')) ? intval(input('page')) : 1;
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res = Api::getInstance()->sendRequest([
                'userid' => $roleId,
                'roomid' => $roomId,
                'uniqueid' => $uniqueid,
                'tag' => 1,
                'page' => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['banker', 'playercouple', 'player', 'equal', 'bankercouple'], ['庄', '闲对', '闲 ', '和', '庄对'], $item['card']);
//                    $item['card'] =explode(" ", $item['card']);
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['totalbet'] =$item['totalbet']/1000;
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
            $page = intval(input('page')) ? intval(input('page')) : 1;
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res = Api::getInstance()->sendRequest([
                'userid' => $roleId,
                'roomid' => $roomId,
                'uniqueid' => $uniqueid,
                'tag' => 1,
                'page' => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
//                    $item['card'] = str_replace(['banker', 'playercouple', 'player', 'equal', 'bankercouple'], ['庄', '闲对', '闲 ', '和', '庄对'], $item['card']);
//                    $item['card'] =explode(" ", $item['card']);
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['totalbet'] =$item['totalbet']/1000;
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
            $page = intval(input('page')) ? intval(input('page')) : 1;
            $limit = intval(input('limit')) ? intval(input('limit')) : 10;
            $roleId = intval(input('roleid')) ? intval(input('roleid')) : 0;
            $uniqueid = intval(input('uniqueid')) ? intval(input('uniqueid')) : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $res = Api::getInstance()->sendRequest([
                'userid' => $roleId,
                'roomid' => $roomId,
                'uniqueid' => $uniqueid,
                'tag' => 1,
                'page' => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['totalbet'] =$item['totalbet']/1000;
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
            $userid = input('userid ') ? input('userid ') : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid' => $roomId,
                'userid' => $userid ,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card'] =explode(",", $item['card']);
                    $item['coinbefore'] =$item['coinbefore']/1000;
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['coinafter'] =$item['coinafter']/1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid',$uniqueid);
        $this->assign('roomId',$roomId);


        return $this->fetch();
    }

    public function detailMpqz()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid = input('userid ') ? input('userid ') : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid' => $roomId,
                'userid' => $userid ,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card'] =explode(",", $item['card']);
                    $item['coinbefore'] =$item['coinbefore']/1000;
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['coinafter'] =$item['coinafter']/1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid',$uniqueid);
        $this->assign('roomId',$roomId);


        return $this->fetch();
    }
    public function detailHlsb()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid = input('userid ') ? input('userid ') : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid' => $roomId,
                'userid' => $userid ,
                'tag' => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
//                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);

                    $item['card'] =explode(" ", $item['card']);
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['totalbet'] =$item['totalbet']/1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid',$uniqueid);
        $this->assign('roomId',$roomId);


        return $this->fetch();
    }


    public function detailDzpk()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid = input('userid ') ? input('userid ') : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid' => $roomId,
                'userid' => $userid ,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['黑', '梅', '方', '红', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card'] =explode(",", $item['card']);
                    $item['tablecard'] = str_replace(['黑', '梅', '方', '红', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['tablecard']);
                    $item['tablecard'] =explode(",", $item['tablecard']);
                    $item['coinbefore'] =$item['coinbefore']/1000;
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['coinafter'] =$item['coinafter']/1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid',$uniqueid);
        $this->assign('roomId',$roomId);


        return $this->fetch();
    }

    public function detailBrnn()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid = input('userid ') ? input('userid ') : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid' => $roomId,
                'userid' => $userid ,
                'tag' => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['黑桃', '梅花', '方块', '红桃', '大王4', '小王'], ['T', 'M', 'F', 'H', 'bg_brand_king_01', 'bg_brand_wang_01'], $item['card']);
                    $item['card'] =explode(" ", $item['card']);
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['totalbet'] =$item['totalbet']/1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid',$uniqueid);
        $this->assign('roomId',$roomId);


        return $this->fetch();
    }
    public function detailBjl()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid = input('userid ') ? input('userid ') : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid' => $roomId,
                'userid' => $userid ,
                'tag' => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['card'] = str_replace(['banker', 'playercouple', 'player', 'equal', 'bankercouple'], ['庄', '闲对', '闲 ', '和', '庄对'], $item['card']);

//                    $item['card'] =explode(" ", $item['card']);
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['totalbet'] =$item['totalbet']/1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid',$uniqueid);
        $this->assign('roomId',$roomId);


        return $this->fetch();
    }

    public function detailLonghudou()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid = input('userid ') ? input('userid ') : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid' => $roomId,
                'userid' => $userid ,
                'tag' => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {

                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['totalbet'] =$item['totalbet']/1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid',$uniqueid);
        $this->assign('roomId',$roomId);


        return $this->fetch();
    }
    public function detailBcbm()
    {
        if ($this->request->isAjax()) {
            $uniqueid = input('uniqueid') ? input('uniqueid') : '';
            $userid = input('userid ') ? input('userid ') : 0;
            $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
            $page     = intval(input('page')) ? intval(input('page')) : 1;
            $limit    = intval(input('limit')) ? intval(input('limit')) : 10;
            $res = Api::getInstance()->sendRequest([
                'uniqueid' => $uniqueid,
                'roomid' => $roomId,
                'userid' => $userid ,
                'tag' => 1,
                'page'     => $page,
                'pagesize' => $limit
            ], 'game', 'getcart2');
            if(!empty($res['data'])){
                foreach ($res['data'] as &$item) {
                    $item['changemoney'] =$item['changemoney']/1000;
                    $item['totalbet'] =$item['totalbet']/1000;
                }
                unset($item);
            }
            return $this->apiReturn($res['code'], $res['data'], $res['message'], $res['total']);
        }
        $uniqueid = input('uniqueid') ? input('uniqueid') : '';
        $roomId = intval(input('roomid')) ? intval(input('roomid')) : 0;
        $this->assign('uniqueid',$uniqueid);
        $this->assign('roomId',$roomId);


        return $this->fetch();
    }

    /**
     * Notes:抢庄牌九
     */
    public function qzpj()
    {
        $kindId = 10000;
        if ($this->request->isAjax()) {
        }
        $roomList = $this->getRoomById($kindId);
        $this->assign('roomlist', $roomList);
        return $this->fetch('bairen');
    }
}
