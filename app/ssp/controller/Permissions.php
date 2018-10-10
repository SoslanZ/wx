<?php
namespace app\ssp\controller;

use \think\Cache;
use \think\Controller;
use think\Loader;
use \think\Config;
use think\Db;
use \think\Session;
class Permissions extends Controller
{


     protected function _initialize()
    {        
        //检查是否登录
        
        if(!Session::has('admin')) {
            
            $this->redirect('ssp/common/login');

        }
        if(Session::get('admin_cate_id') == 1){
            return true;
        }

        
        $where['module'] = $this->request->module();
        $where['controller'] = $this->request->controller();
        $where['function'] = $this->request->action();
        $where['type'] = 1;
        //获取除了域名和后缀外的url，是字符串
        $parameter = $this->request->path() ? $this->request->path() : null;
        //将字符串转化为数组
        $parameter = explode('/',$parameter);
        //剔除url中的模块、控制器和方法
        foreach ($parameter as $key => $value) {
            if($value != $where['module'] and $value != $where['controller'] and $value != $where['function']) {
                $param[] = $value;
            }
        }

        if(isset($param) and !empty($param)) {
            //确定有参数
            $string = '';
            foreach ($param as $key => $value) {
                //奇数为参数的参数名，偶数为参数的值
                if($key%2 !== 0) {
                    //过滤只有一个参数和最后一个参数的情况
                    if(count($param) > 2 and $key < count($param)-1) {
                        $string.=$value.'&';
                    } else {
                        $string.=$value;
                    }
                } else {
                    $string.=$value.'=';
                }
            } 
        } else {
            $string = [];
            $param = $this->request->param();
            foreach ($param as $key => $value) {
                if(!is_array($value)) {
                    //这里过滤掉值为数组的参数
                    $string[] = $key.'='.$value;
                }
            }
            $string = implode('&',$string);
        }
        
        //得到用户的权限菜单
        //
        
        $menus = Db::name('ssp_cate')->where('id',Session::get('admin_cate_id'))->value('permissions');
        //将得到的菜单id集成的字符串拆分成数组
        $menus = explode(',',$menus);

        if(!empty($string)) {
            //检查该带参数的url是否设置了权限
            $param_menu = Db::name('ssp_menu')->where($where)->where('parameter',$string)->find();
            if(!empty($param_menu)) {
                //该url的参数设置了权限，检查用户有没有权限
                if(false == in_array($param_menu['id'],$menus)) {
                    return $this->error('缺少权限');
                }
            } else {
                //该url带参数状态未设置权限，检查该url去掉参数时，用户有无权限
                $menu = Db::name('ssp_menu')->where($where)->find();
                if(!empty($menu)) {
                    if(empty($menu['parameter'])) {
                        if(!in_array($menu['id'],$menus)) {
                            return $this->error('缺少权限');
                        }
                    }
                }
            }
        } else {
            //用户访问的url里没有参数
            $menu = Db::name('ssp_menu')->where($where)->find();
            
            if(!empty($menu)) {
                if(empty($menu['parameter'])) {
                    if(!in_array($menu['id'],$menus)) {
                        return $this->error('缺少权限');
                    }
                }
            }  
        }
    }
    protected function is_admin()
    {
        return 1 == Session::get('admin_cate_id');
    }
    protected function is_yunying()
    {
        return 2 == Session::get('admin_cate_id');
    }
    protected function getAdminId(){
        return Session::get('admin');
    }
}
