<?php
/*广告位管理*/
namespace app\ssp\controller;

use app\ssp\controller\Permissions;
use app\ssp\model\Media as mediaModel;
use app\ssp\model\Adslot as adslotModel;
use \think\Db;
use \think\Session;

class Adslot extends Permissions
{
    //广告位管理
	public function index(){

        $mediaModel = new mediaModel();
        $pwor = $this->is_yunying() || $this->is_admin();
        $wheres=[];
        if (!$pwor){
            $wheres['uid'] = $this->getAdminId();
        }else{
            $wheres = [];
        }
        $media_data = $mediaModel->where($wheres)->select();
     
        $this->assign('pwor',$pwor);
        $this->assign('media_data',$media_data);
		return $this->fetch();
	}
    public function getAdslotData(){
        $adslotModel = new adslotModel();
        $adslot_name = $this->request->has('adslot_name') ? $this->request->param('adslot_name', '') : '';
        $media_id = $this->request->has('media_id') ? $this->request->param('media_id', 0) : 0;
        $pwor = $this->is_yunying() || $this->is_admin();
        $field  = $this->request->param('field');
        $order  = $this->request->param('order');
        if($field && $order){
            $byorder = "$field $order";
        }else{
            $byorder = 'create_time desc';
        }
        $page=input("get.page")?input("get.page"):1;
        $page=intval($page);
        $limit=input("get.limit")?input("get.limit"):1;
        $limit=intval($limit);
        $start=$limit*($page-1);

        $where=[];
        if (!empty($adslot_name)) {
            $where['name'] = ['like', '%' . $adslot_name . '%'];
        }
        if (!empty($media_id)) {
            $where['media_id'] = $media_id;
        }
        if (!$pwor){
            $where['uid'] = $this->getAdminId();
        }

        $type = array(1=>'Banner(582*166px)',2=>'插屏(600*500px)',3=>'悬浮窗(50*50px)',4=>'悬浮窗(60*60px)',5=>'悬浮窗(70*70px)');
        $count = $adslotModel->alias('a')->field('a.name,a.id,a.skey,a.template_id,a.create_time,a.status,a.media_id,m.mname as media_name,m.uid')
                ->join('ssp_media m',' a.media_id=m.id ','left')
                ->where($where)
                //->order($byorder)
                ->count();
        $list = $adslotModel->alias('a')->field('a.name,a.id,a.skey,a.template_id,a.create_time,a.status,a.media_id,m.mname as media_name,m.uid')
                ->join('ssp_media m',' a.media_id=m.id ','left')
                ->where($where)
                ->limit($start,$limit)
                ->order($byorder)
                ->select();
        foreach($list as $key=>$val){
            $val->template_id = $type[$val->template_id];
            $val->url = url('ssp/adslot/editor',['id'=>endecodeUserId($val->id)]);
        }
  
        $json_data = [];
        $json_data['msg']='';
        $json_data["code"]=0;
        $json_data["count"]=$count;
        $json_data["data"]=$list;
        if(empty($list)){
            $json_data["msg"]="暂无数据";
        }
        return json($json_data);

    }
    //新增广告位
    public function add(){
        $mediaModel = new mediaModel();
        //运营的超级管理员的权限一样
        $pwor = $this->is_yunying() || $this->is_admin();
        if ( $this->request->isPost()){
            $insert_data = $this->request->post();
            $validate = new \think\Validate([
                    ['name', 'require', '广告位名称不能是空'],
                    ['media_id', 'require', '所属媒体必选'],
                    ['template_id', 'require', '广告形式必选'],
            ]);
            if ($insert_data['template_id']==3) {
                $insert_data['template_id'] = $insert_data['template_s'];
            }
            if (!$validate->check($insert_data)) {
                $this->error('提交失败：' . $validate->getError());
            }
            $insert_data['skey'] = getSkey();
            $adslotModel = new adslotModel();

            if( false == $adslotModel->allowField(true)->save($insert_data)){
                return $this->error('添加广告位失败');
            }else {
                return $this->success('添加广告位成功','ssp/adslot/index');
            }
        }
        if (!$pwor){
            $where['uid'] =$this->getAdminId();
        }else{
            $where = [];
        }
        $media_data = $mediaModel->where($where)->select();
        $this->assign('media_data',$media_data);
        $this->assign('pwor',$pwor);
    	return $this->fetch();
    }
    //编辑广告位
    public function editor(){
        $id   = $this->request->has('id') ? $this->request->param('id') : 0;
        $id = endecodeUserId($id,'decode');
        $pwor = $this->is_yunying() || $this->is_admin();
        if ($this->request->isPost()){
            $save_data = $this->request->Post();
            $res = Db::name('adslot')->where('id',$save_data['id'])->update(['name'=>$save_data['name']]);
            if( false === $res){
                return $this->error('修改失败');
            }else {
                return $this->success('修改广告位成功','ssp/adslot/index');
            }
        }
        if (!empty($id) && is_numeric($id)){
            $mediaModel = new mediaModel();
            $adslotModel    = new adslotModel();
            $list       = $adslotModel->alias('a')
                          ->field('a.*,m.mname as media_name')
                          ->join('ssp_media m',' a.media_id=m.id ','left')
                          ->where('a.id',$id)->find();
            if (!$pwor){
                $where['uid'] = $this->getAdminId();
            }else{
                $where = [] ;
            }
            $media_data = $mediaModel->where($where)->select();
            $this->assign('media_data',$media_data);
            $this->assign('list',$list);
            $this->assign('pwor',$pwor);
            $this->assign('id',$id);
            return $this->fetch();
        }else {
            return $this->error('错误');
        }
    }
    public function status()
    {
        //获取文件id
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if($id > 0) {
            if($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                $status = $post['status'];
                $res = Db::name('adslot')->where('id',$id)->update(['status'=>$status]);
                if(false == $res) {
                    return $this->error('设置失败');
                } else {
                    return $this->success('设置成功','ssp/adslot/index');
                }
            } 
        } else {
            return $this->error('id不正确');
        }
    }

}
