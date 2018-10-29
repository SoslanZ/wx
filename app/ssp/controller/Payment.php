<?php
namespace app\ssp\controller;

use think\Db;

use app\ssp\controller\Permissions;

class Payment extends Permissions
{
    public function index()
    {
        $search  = $this->request->param();
        $where = [];
        if(!empty($search['times'])){
            $search_day  = explode(' ', $search['times']);
            $start_time = strtotime($search_day[0]."00:00:00");
            $end_time   = strtotime($search_day[2]."23:59:59");
            $where['remit_time'] = ['between',"$start_time,$end_time"];
        }else{
            $search['times'] = '';
        }
        $list = Db::name('payment')->alias('a')
                ->field('a.*,b.nickname')->join('ssp_sspuser b','a.uid=b.id')->order('a.oper_time desc')
                ->where($where)
                ->paginate(10,false,['query'=>$this->request->param()]);
        $count_money = Db::name('payment')->where($where)->sum('money');
        $this->assign('list',$list);
        $this->assign('count_money',$count_money);
        $this->assign('search',$search);
        return $this->fetch();
    }
    public function add()
    {
        if($this->request->isPost()){
            $post = $this->request->Post();
            $validate = new \think\Validate([
                    ['voucher', 'require', '凭证不能是空'],
                    ['uid', 'require', '打款账号必选'],
                    ['money', 'require', '金额不能是空'],
                    ['remit_time', 'require', '打款账号必选'],
                    ['set_start_time', 'require', '结算时间必选'],
                    ['set_end_time', 'require', '结算时间必选'],
            ]);
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            $post['remit_time']     = strtotime($post['remit_time']);
            $post['set_start_time'] = strtotime($post['set_start_time']);
            $post['set_end_time']   = strtotime($post['set_end_time']);
            $post['ip'] = getIp();
            $post['oper_id'] = $this->getAdminId();
            $post['oper_time'] = time();
            $res = Db::name('payment')->insert($post);
            if($res === false){
                return $this->error('录入失败');
            }else{
                return $this->success('录入成功','ssp/payment/index');
            }
        }
        $users = Db::name('sspuser')->select();
        $this->assign('users',$users);
        return $this->fetch();
    }
    //对账数据
    public function recon()
    {
        $start = date('Y-m-d',strtotime('-7days'));
        $end   = date('Y-m-d',strtotime('-1days'));
        $users = Db::name('sspuser')->select();
        $this->assign('users',$users);
        $this->assign('date1',"'$start ~ $end'");
        return $this->fetch();
    }
    public function recondata()
    {
        $times  = $this->request->param('times');
        $media_id  = $this->request->param('media_id');
        $uid  = $this->request->param('uid');
        $field  = $this->request->param('field');
        $order  = $this->request->param('order');
        if($field && $order){
            $byorder = "$field $order";
        }else{
            $byorder = 'days desc';
        }
        $page=input("get.page")?input("get.page"):1;
        $page=intval($page);
        $limit=input("get.limit")?input("get.limit"):1;
        $limit=intval($limit);
        $start=$limit*($page-1);
        if($times){
            $search_day  = explode(' ', $times);
            $start_time = strtotime($search_day[0]."00:00:00");
            $end_time   = strtotime($search_day[2]."23:59:59");
        }else{
            $start_time = strtotime('-7days');
            $end_time   = strtotime('-1days');
        }
        $where = [];
        $where['a.day'] = ['between',"$start_time,$end_time"];
        if($media_id){
            $media_data = Db::name('adslot')->where('media_id',$media_id)->select();
            $slot_id = array_column($media_data, 'id');
            if (!empty($slot_id)){
                $where['slot_id'] = ['in',$slot_id];
            }else{
                $where['slot_id'] =0;
            }
        }
        if($uid){
            $medias = Db::name('media')->where('uid',$uid)->select();
            $ids = array_column($medias, 'id');
            if (!empty($ids)){
                $where['me.id'] = ['in',$ids];
            }else{
                $where['me.id'] =0;
            }
        }
        //$where['im']=['neq',''];
        $count = Db::name('media_report')
                ->alias('a')->field("FROM_UNIXTIME(a.day,'%Y%m%d') days,ap.name as slot_name,me.mname media_name,sum(im) im,sum(ck) ck,sum(income) income")
                ->join('ssp_adslot ap',' a.slot_id=ap.id ','left')
                ->join('ssp_media me',' me.id=ap.media_id ','left')
                ->where($where)
                ->group('days,a.slot_id')
                ->count();
        $data = Db::name('media_report')
                ->alias('a')->field("FROM_UNIXTIME(a.day,'%Y%m%d') days,ap.name as slot_name,me.mname media_name,sum(im) im,sum(ck) ck,sum(income) income,FORMAT(sum(ck)/sum(im)*100,3)*1000 click_rate,FORMAT(sum(income)*1000/sum(im),3)*1000 cpm,FORMAT(sum(income)/sum(ck),3)*1000 cpc")
                ->join('ssp_adslot ap',' a.slot_id=ap.id ','left')
                ->join('ssp_media me',' me.id=ap.media_id ','left')
                ->where($where)
                ->order($byorder)
                ->limit($start,$limit)
                ->group('days,a.slot_id')
                ->select();
        foreach($data as $k=>$val){
            $data[$k]['click_rate'] = sprintf("%.3f", $val['click_rate']/1000);
            $data[$k]['cpm'] = sprintf("%.3f", $val['cpm']/1000);
            $data[$k]['cpc'] = sprintf("%.3f", $val['cpc']/1000);
        }
        //array_multisort(array_column($data, 'days'),SORT_DESC,$data);
        $json_data = [];
        $json_data['msg']='';
        $json_data["code"]=0;
        $json_data["count"]=$count;
        $json_data["data"]=$data;
        if(empty($data)){
            $json_data["msg"]="暂无数据";
        }
        return json($json_data);
    }
    public function findmedia(){
        $search  = $this->request->param();
        $post = Db::name('media')->where('uid',$search['uid'])->select();
        $list=[];
        $list["msg"]="";
        $list["code"]=0;
        $list["count"]=100;
        $list["data"]=$post;
        if(empty($post)){
            $list["msg"]="暂无数据";
        }
        return json($list);
    }
    public function alldata(){
        $times  = $this->request->param('times');
        $media_id  = $this->request->param('media_id');
        $uid  = $this->request->param('uid');

        if($times){
            $search_day  = explode(' ', $times);
            $start_time = strtotime($search_day[0]."00:00:00");
            $end_time   = strtotime($search_day[2]."23:59:59");
        }else{
            $start_time = strtotime('-7days');
            $end_time   = strtotime('-1days');
        }
        $where = [];
        $where['a.day'] = ['between',"$start_time,$end_time"];
        if($media_id){
            $media_data = Db::name('adslot')->where('media_id',$media_id)->select();
            $slot_id = array_column($media_data, 'id');
            if (!empty($slot_id)){
                $where['slot_id'] = ['in',$slot_id];
            }else{
                $where['slot_id'] =0;
            }
        }
        if($uid){
            $medias = Db::name('media')->where('uid',$uid)->select();
            $ids = array_column($medias, 'id');
            if (!empty($ids)){
                $where['me.id'] = ['in',$ids];
            }else{
                $where['me.id'] =0;
            }
        }
        $data = Db::name('media_report')
                ->alias('a')->field("FROM_UNIXTIME(a.day,'%Y%m%d') days,sum(im) im,sum(ck) ck,sum(income) income")
                ->join('ssp_adslot ap',' a.slot_id=ap.id ','left')
                ->join('ssp_media me',' me.id=ap.media_id ','left')
                ->where($where)
                ->group('days,a.slot_id')
                ->select();
        $all_data = [];
        $all_data['im'] = array_sum(array_column($data,'im'));
        $all_data['ck'] = array_sum(array_column($data,'ck'));
        $all_data['income'] = array_sum(array_column($data,'income'));
        return json($all_data);
    }


}
