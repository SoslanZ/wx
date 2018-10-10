<?php
namespace app\dsp\controller;
use \think\Cache;
use \think\Controller;
use think\Loader;
use think\Db;
use \think\Cookie;
use \think\Session;
class Common extends Controller
{
    /**
     * 清除全部缓存
     * @return [type] [description]
     */
    public function clear()
    {
        if(false == Cache::clear()) {
        	return $this->error('清除缓存失败');
        } else {
        	return $this->success('清除缓存成功');
        }
    }


    /**
     * 图片上传方法
     * @return [type] [description]
     */
    public function upload($module='admin',$use='admin_thumb',$type = 0)
    {
        $type = $this->request->has('type') ? $this->request->param('type') : $type;
        if (!$type){
            return json(array('error'=>10001));
        }
        if($this->request->file('file')){
            $file = $this->request->file('file');
        }else{
            $res['code']=100;
            $res['msg']='没有上传文件';
            return json($res);
        }
        $size = 200*1024;

        $module = $this->request->has('module') ? $this->request->param('module') : $module;//模块

        $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . $module . DS . $use;

        $info = $file->validate(['size'=>$size,'ext'=>'jpg,png'])->rule('date')->move($path);

        $is_file = $path.DS.$info->getSaveName();

        if ($info && file_exists($is_file)) {
            $res['code'] = 200;
            $res['type'] =  $type;
            $res['msg'] =  '上传成功';
            $res['path'] = $this->request->domain().DS . 'uploads' . DS . $module . DS . $use.DS.$info->getSaveName();
            
        } else {
            $res['code'] = 100;
            $res['msg'] = $file->getError();
        }
        return json($res);

    }

    /**
     * 登录
     * @return [type] [description]
     */
    public function login()
    {
        if(Session::has('admin') == false) { 
            if($this->request->isPost()) {
                //是登录操作
                $post = $this->request->post();
                //验证  唯一规则： 表名，字段名，排除主键值，主键名
                $validate = new \think\Validate([
                    ['name', 'require|alphaDash', '用户名不能为空|用户名格式只能是字母、数字、——或_'],
                    ['password', 'require', '密码不能为空'],
                    ['captcha','require|captcha','验证码不能为空|验证码不正确'],
                ]);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    $this->error('提交失败：' . $validate->getError());
                }
                $name = Db::name('dspuser')->where('name',$post['name'])->find();
                if(empty($name)) {
                    //不存在该用户名
                    return $this->error('用户名不存在');
                } else {
                    //验证密码
                    $post['password'] = password($post['password']);
                    if($name['password'] != $post['password']) {
                        return $this->error('密码错误');
                    } else {
                        //是否记住账号
                        if(!empty($post['remember']) and $post['remember'] == 1) {
                            //检查当前有没有记住的账号
                            if(Cookie::has('usermember')) {
                                Cookie::delete('usermember');
                            }
                            //保存新的
                            Cookie::forever('usermember',$post['name']);
                        } else {
                            //未选择记住账号，或属于取消操作
                            if(Cookie::has('usermember')) {
                                Cookie::delete('usermember');
                            }
                        }
                        
                        Session::set("admin",$name['id']); //保存新的
                        Session::set("admin_cate_id",$name['dsp_cate_id']); //保存新的
                        //记录登录时间和ip
                        Db::name('dspuser')->where('id',$name['id'])->update(['login_ip' =>  $this->request->ip(),'login_time' => time()]);
                        return $this->success('登录成功,正在跳转...','dsp/index/index');
                    }
                }
            } else {
                if(Cookie::has('usermember')) {
                    $this->assign('usermember',Cookie::get('usermember'));
                }
                return $this->fetch();
            }
        } else {
            $this->redirect('dsp/index/index');
        }   
    }

    /**
     * 管理员退出，清除名字为admin的session
     * @return [type] [description]
     */
    public function logout()
    {
        Session::delete('admin');
        Session::delete('admin_cate_id');
        if(Session::has('admin') or Session::has('admin_cate_id')) {
            return $this->error('退出失败');
        } else {
            return $this->success('正在退出...','dsp/common/login');
        }
    }
}
