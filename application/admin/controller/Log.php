<?php

namespace app\admin\controller;

use think\Db;
use app\model\Log as LogModel;

class Log extends Main
{
    /**
     * 首页
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $data     = [
                'code'  => 0,
                'msg'   => '',
                'count' => 0,
                'data'  => []
            ];
            $where    = [];
            $username = $this->request->get('username') ? trim($this->request->get('username')) : '';
            $action   = $this->request->get('action') ? trim($this->request->get('action')) : '';
            $start    = $this->request->get('start') ? $this->request->get('start') : '';
            $end      = $this->request->get('end') ? $this->request->get('end') : '';
            $page     = $this->request->get('page') ? intval($this->request->get('page')) : 1;
            $limit    = $this->request->get('limit') ? intval($this->request->get('limit')) : 10;
            if ($username) {
                $where['username'] = ['like', '%' . $username . '%'];
            }
            if ($action) {
                $where['action'] = ['like', '%' . $action . '%'];
            }
            if ($start) {
                if ($end && $end > $start) {
                    $where['logday'] = [['egt', $start], ['lt', $end]];
                } else {
                    $where['logday'] = ['egt', $start];
                }
            }

            $logModel = new LogModel();
            $count    = $logModel->getCount($where);
            if (!$count) {
                return json($data);
            }
            $list          = $logModel->getList($where, $page, $limit, '*', ['id' => 'desc']);
            $data['data']  = $list;
            $data['count'] = $count;
            return json($data);
        }
        return $this->fetch();
    }


    /**
     * 日志记录详情
     */
    public function detail()
    {
        if ($this->request->isAjax()) {
            $data     = [
                'code'  => 0,
                'msg'   => '',
                'count' => 0,
                'data'  => []
            ];
            $where    = [];
            $username = $this->request->request('username') ? trim($this->request->request('username')) : '';
            $controller   = $this->request->request('controller') ? trim($this->request->request('controller')) : '';
            $method   = $this->request->request('method') ? trim($this->request->request('method')) : '';
            $start    = $this->request->request('start') ? $this->request->request('start') : '';
            $end      = $this->request->request('end') ? $this->request->request('end') : '';
            $page     = $this->request->request('page') ? intval($this->request->request('page')) : 1;
            $limit    = $this->request->request('limit') ? intval($this->request->request('limit')) : 10;
            if ($username) {
                $where['username'] = ['like', '%' . $username . '%'];
            }
            if ($controller) {
                if (!$method) {
                    $where['action'] = ['like', '%' . $controller . '%'];
                } else {
                    $where['action'] = ['like', '%' . $controller . '::'.$method.'%'];
                }
            }
            if ($start) {
                if ($end && $end > $start) {
                    $where['logday'] = [['egt', $start], ['elt', $end]];
                } else {
                    $where['logday'] = ['egt', $start];
                }
            }

            $logModel = new LogModel();
            $count    = $logModel->getCount($where);
            if (!$count) {
                return json($data);
            }
            $list          = $logModel->getList($where, $page, $limit, '*', ['id' => 'desc']);
            foreach ($list as &$v) {
                $arr = explode('::', $v['action']);
                //按\分隔，最后一个是操作控制器
                $controller = explode('\\', $arr[0]);
                $controller = $controller[count($controller) -1];
                //获取中文名
                $controllerName = isset(config('site.controller')[$controller]) ? config('site.controller')[$controller] : $controller;
                $v['controller'] = $controllerName;

                $methodName = isset(config('classes')[$controller][$arr[1]]) ? config('classes')[$controller][$arr[1]] : $arr[1];
                $v['method']    = $methodName;
                if (isset(config('dict')[$controller][$arr[1]])) {
                    $request = json_decode($v['request'], true);
                    $request = array_values($request);
                    $content = config('dict')[$controller][$arr[1]];
                    for ($i = 0; $i < count($request); $i++) {
                        $thisdata = isset($request[$i]) ? $request[$i]: '';
                        if (is_array($thisdata)) {
                            $thisdata = json_encode($thisdata);
                        }
                        $content = str_replace('{ct'.$i.'}', $thisdata , $content);
                    }
                    $v['content'] = $content;
                } else {
                    $v['content'] = $v['request'];
                }
            }
            unset($v);
            $data['data']  = $list;
            $data['count'] = $count;
            return json($data);
        }

        $this->assign('controller', config('site.controller'));
        return $this->fetch();
    }


    public function getMethod()
    {
        if ($this->request->isAjax()) {
            $data     = [
                'code'  => 0,
                'msg'   => '',
                'count' => 0,
                'data'  => []
            ];
            $controller = $this->request->request('controller') ? trim($this->request->request('controller')) : '';
            $config = config('classes');

            if (isset($config[$controller])) {
                $data['data'] = $config[$controller];
            } else {
                $data['code'] = 1;
            }
            return json($data);
        }
    }
}
