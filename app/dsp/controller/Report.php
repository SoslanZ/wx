<?php
/*数据报告管理*/
namespace app\dsp\controller;

use think\Db;
use think\Pagination;
use \think\Session;
use app\dsp\controller\Permissions;
use app\dsp\model\MediaReporter;
class Report extends Permissions
{
	public function index()
    {
        $pwor  = $this->is_yunying() || $this->is_admin();
        $start = date('Y-m-d 00:00:00',strtotime('-7days'));
        $end   = date('Y-m-d 23:59:59',strtotime('-1days'));
        $where = [];
        if(!empty($search['tday'])){
            $start = date('Y-m-d 00:00:00',time());
            $end   = date('Y-m-d 23:59:59',time());
        }
        if(!empty($search['yday'])){
            $start = date('Y-m-d 00:00:00',strtotime('-1days'));
            $end   = date('Y-m-d 23:59:59',strtotime('-1days'));
        }
        if(!empty($search['sday'])){
            $start = date('Y-m-d 00:00:00',strtotime('-7days'));
            $end   = date('Y-m-d 23:59:59',strtotime('-1days'));
        }
        if(!empty($search['lday'])){
            $start = date('Y-m-d 00:00:00',strtotime('-30days'));
            $end   = date('Y-m-d 23:59:59',strtotime('-1days'));
        }
        if (!$pwor){
            $campaign = Db::name('campaign')->field('id,name')->where('uid',$this->getAdminId())->select();
            //$campaign = array_column($campaign, 'id');
            //$creative_data    = Db::name('creative')->field('id,name')->where(['campaign_id'=>['in',$campaign]])->select();
        } else {
            $campaign = Db::name('campaign')->field('id,name')->select();
            //$creative_data    = Db::name('creative')->field('id,name')->select();
        }
        $date1 = date('Y-m-d',strtotime($start)).' - '.date('Y-m-d',strtotime($end));
        $this->assign('campaign',$campaign);
        $this->assign('date1',"'$date1'");
        return $this->fetch();
    }
    private function getTime($start,$end,$true){
        $date = [];
        if($true){
            $beginTime =strtotime(date('Y-m-d 00:00:00',$start));
            for($i = 0; $i < 24; $i++){
                $b = $beginTime + ($i * 3600);
                $date[] = date("YmdH",$b);
            }
        }else{
            for($i=$start;$i<=$end;$i+=86400){
                $date[]=date('Ymd',$i);
            }
        }
        return $date;
    }
    private function getTitle($chart){
        $arr = array('曝光量','点击量','花费','点击率','点击均价');
        return $arr[$chart];
    }
    public function gettabledata(){
        $pwor  = $this->is_yunying() || $this->is_admin();
        $times  = $this->request->param('times');
        $creative_id  = $this->request->param('creative_id');
        $campaign_id  = $this->request->param('campaign_id');
        $btt  = $this->request->param('btt');
        $field  = $this->request->param('field');
        $order  = $this->request->param('order');
        $page=input("get.page")?input("get.page"):1;
        $page=intval($page);
        $limit=input("get.limit")?input("get.limit"):1;
        $limit=intval($limit);
        $start=$limit*($page-1);
        if($field && $order){
            $byorder = "$field $order";
        }else{
            $byorder = 'days desc';
        }
        if($times){
            $search_day  = explode(' ', $times);
            $start_time = strtotime($search_day[0]."00:00:00");
            $end_time   = strtotime($search_day[2]."23:59:59");
        }else{
            $start_time = strtotime(date('Y-m-d',strtotime('-7days'))." 00:00:00");
            $end_time   = strtotime(date('Y-m-d',strtotime('-1days'))." 23:59:59");
        }
        if($btt==1){
            $start_time = strtotime(date('Y-m-d 00:00:00',time()));
            $end_time   = strtotime(date('Y-m-d 23:59:59',time()));
        }
        if($btt==2){
            $start_time = strtotime(date('Y-m-d 00:00:00',strtotime('-1days')));
            $end_time   = strtotime(date('Y-m-d 23:59:59',strtotime('-1days')));
        }
        if($btt==3){
            $start_time = strtotime(date('Y-m-d 00:00:00',strtotime('-7days')));
            $end_time   = strtotime(date('Y-m-d 23:59:59',strtotime('-1days')));
        }
        if($btt==4){
            $start_time = strtotime(date('Y-m-d 00:00:00',strtotime('-30days')));
            $end_time   = strtotime(date('Y-m-d 23:59:59',strtotime('-1days')));
        }
        $group ='';
        $where=[];
        $where['day']   = ['between',"$start_time,$end_time"];
        $where['im']=['neq',''];
        $time_true= date('Y-m-d',$start_time) == date('Y-m-d',$end_time) ? true : false;
        $days = $this->getTime($start_time,$end_time,$time_true);
        if($time_true){
            $fields ="FROM_UNIXTIME(ar.day,'%Y%m%d%H') days,";
        }else{
            $fields ="FROM_UNIXTIME(ar.day,'%Y%m%d') days,";
        }
        if($creative_id){
            $where['creative_id'] = $creative_id;
        }
        if($campaign_id){
            $where['campaign_id'] = $campaign_id;
        }
        if(!$pwor){
            $where['uid'] = $this->getAdminId();
        }
        $count = $data =  Db::name('creative')->alias('a')
                ->field("$fields sum(im) as im,sum(ck) as ck,sum(cost) as cost")
                ->join('ssp_campaign ca',' ca.id=a.campaign_id','inner')
                ->join('ssp_advertiser_report ar',' ar.creative_id=a.id','inner')
                ->group('days,campaign_id,a.id')
                ->where($where)
                ->count();

        $data =  Db::name('creative')->alias('a')
                ->field("$fields sum(im) as im,sum(ck) as ck,sum(cost) as cost,a.name as cre_name,ca.name as cap_name,FORMAT(sum(ck)/sum(im)*100,3)*1000 ck_rate,FORMAT(sum(cost)/sum(ck),3)*1000 ck_avg")
                ->join('ssp_campaign ca',' ca.id=a.campaign_id','inner')
                ->join('ssp_advertiser_report ar',' ar.creative_id=a.id','inner')
                ->group('days,campaign_id,a.id')
                ->limit($start,$limit)
                ->order($byorder)
                ->where($where)
                ->select();
        foreach($data as $k=>$val){
            if($time_true){
                $data[$k]['days'] = date('Y-m-d H:00',strtotime($val['days']."0000"));
            }else{
                $data[$k]['days'] = date('Y-m-d',strtotime($val['days']));
            }
            $data[$k]['ck_rate'] = sprintf("%.3f", $val['ck_rate']/1000);
            
            $data[$k]['ck_avg'] = sprintf("%.3f", $val['ck_avg']/1000);

        }
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
    public function getecharts(){
        $pwor  = $this->is_yunying() || $this->is_admin();
        $times  = $this->request->param('times');
        $creative_id  = $this->request->param('creative_id');
        $campaign_id  = $this->request->param('campaign_id');
        $chart = $this->request->has('chart') ? $this->request->param('chart', 0) : 0;
        $btt  = $this->request->has('btt') ? $this->request->param('btt', 0) : 0;
        if($times){
            $search_day  = explode(' ', $times);
            $start_time = strtotime($search_day[0]."00:00:00");
            $end_time   = strtotime($search_day[2]."23:59:59");
        }else{
            $start_time = strtotime(date('Y-m-d',strtotime('-7days'))." 00:00:00");
            $end_time   = strtotime(date('Y-m-d',strtotime('-1days'))." 23:59:59");
        }
        if($btt==1){
            $start_time = strtotime(date('Y-m-d 00:00:00',time()));
            $end_time   = strtotime(date('Y-m-d 23:59:59',time()));
        }
        if($btt==2){
            $start_time = strtotime(date('Y-m-d 00:00:00',strtotime('-1days')));
            $end_time   = strtotime(date('Y-m-d 23:59:59',strtotime('-1days')));
        }
        if($btt==3){
            $start_time = strtotime(date('Y-m-d 00:00:00',strtotime('-7days')));
            $end_time   = strtotime(date('Y-m-d 23:59:59',strtotime('-1days')));
        }
        if($btt==4){
            $start_time = strtotime(date('Y-m-d 00:00:00',strtotime('-30days')));
            $end_time   = strtotime(date('Y-m-d 23:59:59',strtotime('-1days')));
        }
        $group ='';
        $where=[];
        $where['day']   = ['between',"$start_time,$end_time"];
        $time_true= date('Y-m-d',$start_time) == date('Y-m-d',$end_time) ? true : false;
        $days = $this->getTime($start_time,$end_time,$time_true);
        if($time_true){
            $field ="FROM_UNIXTIME(ar.day,'%Y%m%d%H') days,";
        }else{
            $field ="FROM_UNIXTIME(ar.day,'%Y%m%d') days,";
        }
        if($creative_id){
            $where['creative_id'] = $creative_id;
        }
        if($campaign_id){
            $where['campaign_id'] = $campaign_id;
        }
        if(!$pwor){
            $where['uid'] = $this->getAdminId();
        }
        $data =  Db::name('creative')->alias('a')
                ->field("$field sum(im) as im,sum(ck) as ck,sum(cost) as cost")
                ->join('ssp_campaign ca',' ca.id=a.campaign_id','inner')
                ->join('ssp_advertiser_report ar',' ar.creative_id=a.id','inner')
                ->group('days')
                ->where($where)
                ->select();
        foreach($data as $k=>$val){
            if($val['im']==0){
                $data[$k]['ck_rate'] = 0;
            }else{
                $data[$k]['ck_rate'] = sprintf("%.3f", ($val['ck']/$val['im'])*100);
            }
            if($val['ck']==0){
                $data[$k]['ck_avg'] = 0;
            }else{
                $data[$k]['ck_avg'] = sprintf("%.3f", ($val['cost']/$val['ck']));
            }
        }
        foreach ($days as $key=>$val){

            if(!in_array($val, array_column($data, 'days'))){
                $data[]=array('days'=>$val,'ck'=>0,'im'=>0,'cost'=>0,'ck_rate'=>0,'ck_avg'=>0);
            }
        }
        foreach($data as $k=>$val){
             if($time_true){
                $data[$k]['days'] = date('Y-m-d H',strtotime($val['days']."0000"));
            }else{
                $data[$k]['days'] = date('Y-m-d',strtotime($val['days']));
            }
        }
        array_multisort(array_column($data, 'days'),SORT_ASC,$data);

        $im=array_column($data,'im');
        $ck=array_column($data,'ck');
        $cost=array_column($data,'cost'); 
        $ck_rate=array_column($data,'ck_rate');
        $ck_avg=array_column($data,'ck_avg');
        $title = $this->getTitle($chart);
        $line_data = [];
        $line_data = array(
            $im,$ck,$cost,$ck_rate,$ck_avg
        );
        $line = [];
        $line['title'] = array($title);
        $line['data']  = $line_data[$chart];
        $line['date']  = array_column($data,'days');
        $line['btt']  = date('Y-m-d',$start_time)." - ".date('Y-m-d',$end_time);
        return json($line);
    }
    public function findadvert(){
        $search  = $this->request->param();
        $post = Db::name('creative')->field('id,name')->where('campaign_id',$search['campaign_id'])->select();
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
}
