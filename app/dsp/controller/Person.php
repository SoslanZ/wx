<?php
namespace app\dsp\controller;

use app\dsp\controller\Permissions;
use \think\Db;
use \think\Session;
class Person extends Permissions
{
	public function index()
    {

		return $this->fetch();
	}
    public function changepwd()
    {

        return $this->fetch();
    }
    public function editPassword()
    {
        if(Session::get('admin')) {
            $post = $this->request->post();
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
            $validate = new \think\Validate([
                ['password', 'require|confirm', '密码不能为空|两次密码不一致'],
                ['password_confirm', 'require', '重复密码不能为空'],
            ]);
            //验证部分数据合法性
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            $admin = Db::name('dspuser')->where('id',$this->getAdminId())->find();
            if(password($post['password_old']) == $admin['password']) {
                if(false === Db::name('dspuser')->where('id',$this->getAdminId())->update(['password'=>password($post['password'])])) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功','dsp/person/changepwd');
                }
            } else {
                return $this->error('原密码错误');
            }
        } else {
            return $this->error('不能修改别人的密码');
        }
    }
    public function down(){
        $file_url =ROOT_PATH . 'public' . DS . 'static' . DS .'public'.DS.'instructions.pdf';
        if(!file_exists($file_url)){ //检查文件是否存在
            echo '404';die;
        }
        $file = fopen($file_url, "r");
        Header("Content-type:application/pdf");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . filesize($file_url));
        Header("Content-Disposition: attachment; filename=旗鱼小程序平台插件使用说明.pdf"); // 输出文件内容
        echo fread($file, filesize($file_url));
        fclose($file);
    }



}
