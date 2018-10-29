<?php
/*数据报告管理*/
namespace app\ssp\controller;

use think\Db;
use think\Page;
use \think\Session;
use app\ssp\controller\Permissions;
use app\ssp\model\MediaReporter;
class Reporter extends Permissions
{
	public function index()
    {
        $pwor  = $this->is_yunying() || $this->is_admin();
        $start = date('Y-m-d',strtotime('-7days'));
        $end   = date('Y-m-d',strtotime('-1days'));
        if (!$pwor){
            $media_data    = Db::name('media')->where('uid',$this->getAdminId())->select();
        } else {
            $media_data    = Db::name('media')->select();
        }
        $this->assign('media',$media_data);
        $this->assign('date1',"'$start - $end'");
        return $this->fetch();
    }
    private function getTime($start,$end){
        $date = [];
        for($i=$start;$i<=$end;$i+=86400){
            $date[]=date('Ymd',$i);
        }
        return $date;
    }
    private function getTitle($chart){
        $arr = array('展示量','点击量','预估收益(元)','千次展示收益(元)','点击率(%)');
        return $arr[$chart];
    }
    public function infodata(){

        $pwor  = $this->is_yunying() || $this->is_admin();
        $times  = $this->request->param('times');
        $media_id  = $this->request->param('media_id');
        $pos_id  = $this->request->param('pos_id');

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
        $where['day'] = ['between',"$start_time,$end_time"];
        if(!$pwor){
            $user_media    = Db::name('media')->where('uid',$this->getAdminId())->select();
            $user_media    = array_column($user_media, 'id');
            $where['media_id'] = ['in',$user_media];
        }
        if($media_id){
            $media_data = Db::name('adslot')->where('media_id',$media_id)->select();
            $slot_id = array_column($media_data, 'id');
            if (!empty($slot_id)){
                $where['slot_id'] = ['in',$slot_id];
            }else{
                $where['slot_id'] =0;
            }
        }
        if($pos_id){
            $where['slot_id'] = $pos_id;
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
    public function finadslot(){
        $search  = $this->request->param();
        $post = Db::name('adslot')->where('media_id',$search['media_id'])->select();
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
    public function getecharts(){

        $pwor  = $this->is_yunying() || $this->is_admin();
        $times  = $this->request->param('times');
        $media_id  = $this->request->param('media_id');
        $pos_id  = $this->request->param('pos_id');
        $chart = $this->request->has('chart') ? $this->request->param('chart', 0) : 0;
        if($times){
            $search_day  = explode(' ', $times);
            $start_time = strtotime($search_day[0]."00:00:00");
            $end_time   = strtotime($search_day[2]."23:59:59");
        }else{
            $start_time = strtotime(date('Y-m-d 00:00:00',strtotime('-7days')));
            $end_time   = strtotime(date('Y-m-d 23:59:59',strtotime('-1days')));
        }
        $days = $this->getTime($start_time,$end_time);

        $where = [];
        $where['day'] = ['between',"$start_time,$end_time"];
        if(!$pwor){
            $user_media    = Db::name('media')->where('uid',$this->getAdminId())->select();
            $user_media    = array_column($user_media, 'id');
            $where['media_id'] = ['in',$user_media];
        }
        if($media_id){
            $media_data = Db::name('adslot')->where('media_id',$media_id)->select();
            $slot_id = array_column($media_data, 'id');
            if (!empty($slot_id)){
                $where['slot_id'] = ['in',$slot_id];
            }else{
                $where['slot_id'] = 0;
            }
        }
        if($pos_id){
            $where['slot_id'] = $pos_id;
        }
        $data = Db::name('media_report')
                ->alias('a')->field("FROM_UNIXTIME(a.day,'%Y%m%d') days,ap.name as slot_name,me.mname media_name,sum(im) im,sum(ck) ck,sum(income) income")
                ->join('ssp_adslot ap',' a.slot_id=ap.id ','left')
                ->join('ssp_media me',' me.id=ap.media_id ','left')
                ->where($where)
                ->group('days')
                ->select();
        foreach($data as $k=>$val){
            $data[$k]['days']       = $val['days'];
            if($val['im']==0){
                $data[$k]['click_rate'] = 0;
                $data[$k]['cpm'] = 0;
            }else{
                $data[$k]['click_rate'] = sprintf("%.3f", ($val['ck']/$val['im'])*100);
                $data[$k]['cpm'] = sprintf("%.3f", ($val['income']*1000/$val['im']));
            }
            if($val['ck']==0){
                $data[$k]['cpc'] = 0;
            }else{
                $data[$k]['cpc'] = sprintf("%.3f", $val['income']/$val['ck']);
            }
        }
        $list    = [
            'click_rate'=>0,
            'show_num_all'=>0,
            'click_num_all'=>0,
            'click_rate_all'=>'',
            'estimated_income_all'=>0
        ];
        foreach($days as $key=>$val){
            if(!in_array($val,  array_column($data, 'days'))){
                 $data[]=array('days'=>$val,'im'=>0,'ck'=>0,'slot_name'=>'','media_name'=>'','income'=>0,'click_rate'=>0,'cpm'=>0,'cpc'=>0);
            }
        }
        array_multisort(array_column($data, 'days'),SORT_ASC,$data);
        $show_num=array_column($data,'im');
        $click_num=array_column($data,'ck');
        $estimated_income=array_column($data,'income');
        $click_rate=array_column($data,'click_rate');
        $cpm=array_column($data,'cpm');

        $line_data = [];
        $title = $this->getTitle($chart);
        $line_data = array(
            $show_num,$click_num,$estimated_income,$cpm,$click_rate
        );
        $line = [];
        $line['title'] = array($title);
        $line['data']  = $line_data[$chart];
        $line['date']  = array_column($data,'days');
        $line['show_num_all']     = array_sum($show_num);
        $line['click_num_all']    = array_sum($click_num);
        $line['estimated_income_all'] = array_sum($estimated_income);
        if($line['show_num_all']==0){
            $line['click_rate_all']=0;
            $line['cpm_all']=0;
        }else{
            $line['click_rate_all']  = sprintf("%.3f", ($line['click_num_all']/$line['show_num_all'])*100);
            $line['cpm_all'] = sprintf("%.3f", $line['estimated_income_all']*1000/$line['show_num_all']);
        }
        return json($line);
    }

}
