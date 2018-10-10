<?php
namespace app\dsp\controller;

use \think\Db;
use \think\Session;
use app\dsp\controller\Permissions;
use app\dsp\model\Campaign as CampaignModel;

class Campaign extends Permissions
{

    public function index(){
        $search  = $this->request->param();
        $pwor  = $this->is_yunying() || $this->is_admin();
        $where = [];
        $time_where = [];
        if (isset($search['name'])&&!empty($search['name'])){
            $where['a.name'] = ['like', '%' . $search['name'] . '%'];
        }else{
            $search['name']='';
        }
        if (isset($search['day'])&&!empty($search['day'])){
            $times = explode('~', $search['day']);
            $start_time = strtotime($times[0]."00:00:00");
            $end_time = strtotime($times[1]."23:59:59");
            $time_where['day'] = ['between', [$start_time,$end_time]];
        }else{
            $search['day']='';
        }
        if(!$pwor){
            $where['uid'] = $this->getAdminId();
        }
        $where['a.status']=['neq',3];
        $CampaignModel = new CampaignModel();
        $list =  $CampaignModel->alias('a')
                    ->field('a.*,group_concat(cr.id) as creative')
                    ->join('ssp_creative cr',' a.id=cr.campaign_id','left')
                    ->group('a.id')
                    ->where($where)
                    ->order('a.id desc')
                    ->paginate(10,false,['query'=>$this->request->param()]);
        unset($where['a.status']);
        $all_list =  Db::name('campaign')->alias('a')
                    ->field('a.*,group_concat(cr.id) as creative')
                    ->join('ssp_creative cr',' a.id=cr.campaign_id','left')
                    ->group('a.id')
                    ->where($where)
                    ->order('a.id desc')
                    ->select();
        $creative_id = [];
        foreach($all_list as $k=>$val){
            if(!empty($val['creative'])){
                $creative_id[]=$val['creative'];
            }
        }
        $creative_ids = implode(',',$creative_id);
        $time_where['creative_id'] =['in',$creative_ids];
        $sum_list = DB::name('advertiser_report')->field('sum(im) as im,sum(ck) as ck,sum(cost) as cost')
            ->where($time_where)
            ->find();
        $sum_list['ck_rate'] = $sum_list['im'] ? sprintf("%.3f", ($sum_list['ck']/$sum_list['im'])*100) : 0;
        unset($time_where['creative_id']);
        foreach ( $list as $k=>$val)
        {
            $time_where['creative_id'] = ['in',$val->creative];
            $repor = DB::name('advertiser_report')->field('sum(im) as im,sum(ck) as ck,sum(cost) as cost')
            ->where($time_where)
            ->find();
            $val->ck = $repor['ck'] ? $repor['ck'] : 0 ;
            $val->im = $repor['im'] ? $repor['im'] : 0 ;
            $val->cost = $repor['cost'] ? $repor['cost'] : 0 ;
            @$val->rate = $val->im ? sprintf("%.3f", ($val->ck/$val->im)*100) : 0;
            @$val->ck_rate = $val->cost ? sprintf("%.3f", $val->cost/$val->ck) : 0;
        }
        $this->assign('sum_list',$sum_list);

        $this->assign('search',$search);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function add(){
        $CampaignModel = new CampaignModel();
        if($this->request->isPost()){
            $insert_data = $this->request->Post();
            $validate = new \think\Validate([
                    ['name', 'require', '推广计划名称不能是空'],
                    ['budget', 'require', '每日最高预算必填'],
                    ['strategy', 'require', '投放速度必选'],
            ]);
            $insert_data['uid'] = $this->getAdminId();
            if (!$validate->check($insert_data)) {
                $this->error('提交失败：' . $validate->getError());
            }
            if( false == $CampaignModel->allowField(true)->save($insert_data)){
                return $this->error('添加推广计划失败');
            }else {
                return $this->success('添加推广计划成功','dsp/advert/add');
            }
        }
        return $this->fetch();
    }
    public function editor(){
        $CampaignModel = new CampaignModel();
        $id = $this->request->has('id') ? $this->request->param('id') : 0;
        $id = endecodeUserId($id,'decode');
        if ($this->request->isPost()){
            $save_data = $this->request->post();
            if( false === $CampaignModel->allowField(true)->isUpdate(true)->save($save_data)){
                return $this->error('更新信息失败');
            }else {
                return $this->success('更新信息成功','dsp/campaign/index');
            }
        }
        if ( $id ){
            $list = $CampaignModel->where('id',$id)->find();
            $this->assign('list',$list);
        }else {
            return $this->error('错误');
        }
        return $this->fetch();
    }
    public function btnstatus(){
        $id = $this->request->has('id') ? $this->request->param('id') : 0;
        $status = $this->request->has('status') ? $this->request->param('status') : 0;
        if($this->request->isPost()){
            if(!empty($id)){
                DB::startTrans();
                try {
                    if($status==0){
                        Db::name('creative')->where(['campaign_id'=>$id,'handle_status'=>['neq',3]])->update(['handle_status'=>$status]);
                    }
                    Db::name('campaign')->where(['id'=>$id,'status'=>['neq',3]])->update(['status'=>$status]);
                Db::commit();
                } catch (\Exception $e) {
                Db::rollback();
                return $this->error('失败');
                }
                $this->success('成功','dsp/campaign/index');
            }else{
                return $this->error('错误！');
            }
        }else{
            return $this->error('错误！');
        }
    }
    public function del(){
        $id = $this->request->has('id') ? $this->request->param('id') : 0;
        if($id){
            if($this->request->isPost()){
                DB::startTrans();
                try {
                    Db::name('creative')->where(['campaign_id'=>$id])->update(['handle_status'=>3]);
                    Db::name('campaign')->where(['id'=>$id])->update(['status'=>3]);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    return $this->error('失败');
                }
                $this->success('成功','dsp/campaign/index');
            }
        }else{
            $this->error('非法操作');
        }
        
    }

}
