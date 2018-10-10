<?php
namespace app\dsp\controller;


use \think\Db;
use app\dsp\model\Dspuser as adminModel;//管理员模型
use app\dsp\controller\Permissions;

class Advertiser extends Permissions
{
    public function index()
    {
        $model = new adminModel();

        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['nickname'] = ['like', '%' . $post['keywords'] . '%'];
        }
        $admin = empty($where) ? $model->order('create_time desc')->paginate(20) : $model->where($where)->order('create_time desc')->paginate(20,false,['query'=>$this->request->param()]);
        $fc = Db::name('fc')->select();
        $tf = Db::name('tf')->select();
        $this->assign('fc',$fc);
        $this->assign('tf',$tf);
        $this->assign('admin',$admin);
        return $this->fetch();
    }
    public function setting(){
        if ($this->request->isPost()){
            $save = $this->request->param();
            $old_val = DB::name('dspuser')->field("tf,fc")->where("id",$save['uid'])->find();
            DB::startTrans();
            try {
                if($save['type'] == 1){
                    Db::name('dspuser')->where(['id'=>$save['uid']])->update(['tf'=>$save['parm']]);
                    Db::name('advertiser_log')->insert(['opera_id'=>$this->getAdminId(),'uid'=>$save['uid'],'uptime'=>time(),'type'=>$save['type'],'val'=>$save['parm'],'old_val'=>$old_val['tf']]);

                }elseif($save['type'] == 2){
                    Db::name('dspuser')->where(['id'=>$save['uid']])->update(['fc'=>$save['parm']]);
                    Db::name('advertiser_log')->insert(['opera_id'=>$this->getAdminId(),'uid'=>$save['uid'],'uptime'=>time(),'type'=>$save['type'],'val'=>$save['parm'],'old_val'=>$old_val['fc']]);
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('设置失败');
            }
            return $this->success('设置成功','dsp/advertiser/index');
        }
        return $this->error('非法');
    }
}
