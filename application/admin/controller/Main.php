<?php
namespace app\admin\controller;

use org\Auth;
use think\Controller;
use think\Db;
use think\Session;


class Main extends Controller
{

    protected $not_check;
    /**
     * 初始化
    */
    public function _initialize()
    {
        $module = $this->request->module();
        $username  = session('username');
        if (empty($username)) {
            $this->redirect('admin/user/login');
        }
        //排除权限
        $this->not_check = config('not_check');
        $this->checkAuth();
        $this->getMenu();
    }
    /**
     * 权限检查
     * @return bool
     */
    protected function checkAuth()
    {

        if (!Session::has('userid')) {
            $this->redirect('admin/login/index');
        }

        $module     = $this->request->module();
        $controller = $this->request->controller();
        $action     = $this->request->action();
        // 排除权限
        $notCheck = $this->not_check;
        if (!in_array($module . '/' . $controller . '/' . $action, $notCheck)) {
            $auth     = new Auth();
            $adminId = Session::get('userid');
            if (!$auth->check($module . '/' . $controller . '/' . $action, $adminId) && $adminId != 1) {
                $this->error('没有权限','');
            }
        }
    }
    
    /**
     * 获取侧边栏菜单
     */
    protected function getMenu()
    {
        $menu           = [];
        $adminId       = Session::get('userid');
            $auth           = new Auth();
            $auth_rule_list = Db::name('auth_rule')->where('status', 1)->order(['sort' => 'ASC'])->select();
            foreach ($auth_rule_list as $value) {
                if ($auth->check($value['name'], $adminId) || $adminId == 1) {
                    $menu[] = $value;
                }
            }
            $menu = !empty($menu) ? array2tree($menu) : [];

        $this->assign('menu', $menu);
    }
}
