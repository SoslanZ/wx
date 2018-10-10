<?php
/*个人中心*/
namespace app\ssp\controller;

use app\ssp\controller\Permissions;
use \think\Db;
use \think\Session;
class Person extends Permissions
{
    //媒体列表
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
            $admin = Db::name('sspuser')->where('id',$this->getAdminId())->find();
            if(password($post['password_old']) == $admin['password']) {
                if(false === Db::name('sspuser')->where('id',$this->getAdminId())->update(['password'=>password($post['password'])])) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功','ssp/main/index');
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
    public function showagree(){

        return $this->fetch();
    }
    public function request(){
        if($this->request->isPost()){
            $post = $this->request->post();
            $header  = [];
            $header[] = "x-api-version:1.0.0";
            $header[] = "Content-Type: application/json; charset=utf-8";
            //初始化
            $curl = curl_init();
            //设置抓取的url
            curl_setopt($curl, CURLOPT_URL, 'https://api-sailfish.optaim.com/wx');
            
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
 
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            //设置post数据
            $post_data = array(
                "appid" => $post['wxid'],
                "slotid" => $post['slotid'],
                "reqid"=>"5ba716ad6cf481462b350331cd292940",
                "model"=>"iphone 7s",
                "OS"=>1,
                "OSV"=>"10.0.1",
                "wxv"=>"6.6.3",
                "wxpv"=>"2.2.1",
                "net"=>4,
                "w"=>582,
                "h"=>166,
                "lon"=>0,
                "lat"=>0,
                "page"=>"pages/index/index",
                "uuid"=>"3d533ab11a2c5fc241ba1c068c4438eb",
                "wxopt"=>[
                    'path'=>'pages/index/index',
                    'scene'=>1001
                ]
                );
            $post_data = json_encode($post_data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
            //执行命令
            $data = curl_exec($curl);

            $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE); 
          
            curl_close($curl);
            $res = [];
            if($httpCode==200){
                $data = json_decode($data,true);
                $res['code'] = 200;
                $res['url']  = $data['landurl'];
                $res['msg']  = '请求成功';
            }elseif($httpCode==204){
                $res['code'] = 200;
                $res['url']  = '';
                $res['msg']  = '暂无广告';
            }else{
                $res['code'] = 400;
                $res['url']  = '';
                $res['msg']  = '请求失败';
            }
            return json($res);
        }


        return $this->fetch();
    }



}
