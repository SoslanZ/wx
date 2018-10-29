<?php
namespace app\ssp\controller;
use \think\Cache;
use \think\Controller;
use think\Loader;
use think\Db;
use \think\Cookie;
use \think\Session;
use \think\Log;
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

    public function upload($module='admin',$use='payimg')
    {
        if($this->request->file('file')){
            $file = $this->request->file('file');
        }else{
            $res['code']=100;
            $res['msg']='没有上传文件';
            return json($res);
        }
        $size = 300*1024;

        $module = $this->request->has('module') ? $this->request->param('module') : $module;//模块

        $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . $module . DS . $use;

        $info = $file->validate(['size'=>$size,'ext'=>'jpg,png'])->rule('date')->move($path);

        $is_file = $path.DS.$info->getSaveName();

        if ($info && file_exists($is_file)) {
            $res['code'] = 200;
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
                    ['name', 'require', '用户名不能为空|用户名格式只能是字母、数字、——或_'],
                    ['password', 'require', '密码不能为空'],
                    ['captcha','require|captcha','验证码不能为空|验证码不正确'],
                ]);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    $this->error('提交失败：' . $validate->getError());
                }
                $name = Db::name('sspuser')->where('name',$post['name'])->find();
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
                        if($name['thumb']==0){
                            return $this->success('请阅读协议,正在跳转...',url('ssp/common/agreement',['id'=>$name['id']]));die;
                        }
                        Session::set("admin",$name['id']); //保存新的
                        Session::set("admin_cate_id",$name['ssp_cate_id']); //保存新的
                        //记录登录时间和ip
                        Db::name('sspuser')->where('id',$name['id'])->update(['login_ip' =>  $this->request->ip(),'login_time' => time()]);
                        Log::record("== xxx登录成功 ==", 'DEBUG');
                        Log::record(json_encode($name), 'DEBUG');
                        return $this->success('登录成功,正在跳转...','ssp/index/index');
                    }
                }
            } else {
                if(Cookie::has('usermember')) {
                    $this->assign('usermember',Cookie::get('usermember'));
                }
                return $this->fetch();
            }
        } else {
            $this->redirect('ssp/index/index');
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
            return $this->success('正在退出...','ssp/common/login');
        }
    }
    public function agreement(){
        if($this->request->isPost()){
            $data = $this->request->Post();
            $res = Db::name('sspuser')->where('id',$data['id'])->update(['thumb'=>1]);
            if($res){
                $user = Db::name('sspuser')->where('id',$data['id'])->find();
                if($user){
                    Session::set("admin",$user['id']); //保存新的
                    Session::set("admin_cate_id",$user['ssp_cate_id']); //保存新的
                    //记录登录时间和ip
                    Db::name('sspuser')->where('id',$user['id'])->update(['login_ip' =>  $this->request->ip(),'login_time' => time()]);
                    return $this->success('登录成功,正在跳转...','ssp/index/index');
                }else{
                    return $this->success('无此用户请重新登陆','ssp/common/login');
                }
            }else{
                return $this->success('操作失败','ssp/common/login');
            }
        }
        if(!$this->request->param('id')){
            return $this->error('非法操作');
        }
        $this->assign('id',$this->request->param('id'));
        return $this->fetch();
    }
    public function register(){
        if($this->request->isPost()){
            header("Content-type: text/html; charset=utf-8");
            $post = $this->request->post();

            $validate = new \think\Validate([
                ['name', 'require|email', '用户名只能是邮箱'],
                ['nickname', 'require', '昵称不能为空'],
                ['password', 'require', '密码不能为空'],
                ['phone', 'require', '手机号不能为空'],
                ['remarks', 'require', '说明不能为空'],
                ['captcha','require|captcha','验证码不能为空|验证码不正确'],
            ]);
            if (!$validate->check($post)) {
                return $this->error('提交失败：' . $validate->getError());
            }
            if($post['password']!=$post['qrpassword']){
                return $this->error('两次密码输入的不一致');
            }
            $regex = "/\/|\～|\，|\。|\！|\？|\“|\”|\【|\】|\『|\』|\：|\；|\《|\》|\’|\‘|\ |\·|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
            $post['remarks'] =preg_replace($regex," ",trim($post['remarks']));
            $insert_arr =[
                'name'=>$post['name'],
                'password'=>$post['password'],
                'nickname'=>$post['nickname'],
                'phone'=>$post['phone'],
                'remarks'=>$post['remarks'],
                'time'=>time()
            ];
            $token = myencode($insert_arr,'encode');
            $name = Db::name('sspuser')->where('name',$post['name'])->find();
            if(!empty($name)){
                return $this->error('邮箱已存在');
            }
            $nickname = Db::name('sspuser')->where('nickname',$post['nickname'])->find();
            if(!empty($nickname)){
                return $this->error('昵称已存在');
            }
            $url = url("ssp/common/callback_reg",array('verify'=>$token));
            $message = "<a href='".$url."' target= '_blank'>$url</a><br/>如果以上链接无法点击，请将它复制到你的浏览器地址栏中进入访问，该链接24小时内有效。";
            $res = SendMail($post['name'],$message);
            if($res){
                Log::record("== xxx注册成功 ==", 'DEBUG');
                Log::record(json_encode($post), 'DEBUG');
                return $this->success('请去邮箱中激活账号完成注册...','ssp/common/login');
            }else{
                Log::record("== xxx注册失败 ==", 'DEBUG');
                Log::record(json_encode($post), 'DEBUG');
                return $this->error('邮件发送失败');
            }
        }
        return $this->fetch();
    }
    public function callback_reg(){

        $verify = $this->request->has('verify') ? $this->request->param('verify', '', 'string') : '';
        $user_arr = myencode($verify);
        if(!empty($user_arr) && is_array($user_arr) && count($user_arr)==6){
                $name = $user_arr['name'];
                $nickname = $user_arr['nickname'];
                $password = password($user_arr['password']);
                $phone = $user_arr['phone'];
                $remarks = $user_arr['remarks'];
                $time = $user_arr['time'];
                if((int)$time+86400>=time()){
                    $sure_1 = Db::name('sspuser')->where('name',$name)->find();
                    if(!empty($sure_1)){
                        return $this->redirect(url("ssp/common/login"));die;
                    }
                    $sure_2 = Db::name('sspuser')->where('nickname',$nickname)->find();
                    if(!empty($sure_2)){
                        return $this->redirect(url("ssp/common/login"));die;
                    }
                    $insert_arr = [
                        'nickname'=>$nickname,
                        'name'=>$name,
                        'password'=>$password,
                        'thumb'=>0,
                        'create_time'=>time(),
                        'update_time'=>time(),
                        'login_time'=>null,
                        'login_ip'=>null,
                        'ssp_cate_id'=>5,
                        'tf'=>0,
                        'fc'=>0,
                        'phone'=>$phone,
                        'remarks'=>$remarks
                    ];
                    $res = Db::name('sspuser')->insert($insert_arr);
                    if($res){
                        Log::record("== xxx激活成功 ==", 'DEBUG');
                        Log::record(json_encode($insert_arr), 'DEBUG');
                        return $this->success('注册成功','ssp/common/login');
                    }else{
                        Log::record("== xxx激活失败 ==", 'DEBUG');
                        Log::record(json_encode($insert_arr), 'DEBUG');
                        return $this->error('注册失败');
                    }
                }else{
                    $url = url("ssp/common/register");
                    echo '<script  type="text/javascript" charset="utf-8">alert("已过期请重新注册");window.location.href="'.$url.'";</script>';die;
                }
        }else{
            return $this->redirect(url("ssp/common/register"));die;
        }
    }
}
