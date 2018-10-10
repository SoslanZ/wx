<?php
namespace app\ssp\controller;

use think\Db;
use \think\Session;
use app\ssp\controller\Permissions;
class Main extends Permissions
{
        public function index()
    {
        $search  = $this->request->param();
        $chart = 1;
        $pwor  = $this->is_yunying() || $this->is_admin();
        $start = date('Y-m-d 00:00:00',strtotime('-7days'));
        $end   = date('Y-m-d 23:59:59',strtotime('-1days'));
        if (!empty($search['times'])){
            $search_day  = explode(' ', $search['times']);
            $start = $search_day[0]." 00:00:00";
            $end   = $search_day[2]." 23:59:59";
        }
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
        $where = [];
        if (isset($search['chart'])&&!empty($search['chart'])){
            $chart = $search['chart'];
        }
        if (!$pwor){
            $media_data    = Db::name('media')->where('uid',$this->getAdminId())->select();
            $media_id      = array_column($media_data, 'id');
            if (!empty($media_id)){
                $adslot = Db::name('adslot')->where(['media_id'=>['in',$media_id]])->select();
                $where['slot_id'] = ['in',array_column($adslot,'id')];
            } else{
                $where['slot_id'] = ['eq',0];
                $adslot = [];
            }
        } else {
            $media_data    = Db::name('media')->select();
            $adslot        = Db::name('adslot')->select();
        }
        $this->getData($where,$start,$end,$chart);
        $date1 = date('Y-m-d',strtotime($start)).' - '.date('Y-m-d',strtotime($end));
        $this->assign('pwor',$pwor);
        $this->assign('chart',$chart);
        $this->assign('search',$search);
        $this->assign('media',$media_data);
        $this->assign('media_id',isset($search['media_id']) ? $search['media_id'] :0);
        $this->assign('pos_id',isset($search['pos_id']) ? $search['pos_id'] :0);
        $this->assign('date1',"'$date1'");
        $this->assign('adslot',$adslot);
        return $this->fetch();
    }
     private function getData($where=[],$start,$end,$chart=1){
        $start = strtotime($start);
        $end   = strtotime($end);

        $time_true= date('Y-m-d',$start) == date('Y-m-d',$end) ? true : false;

        $days = $this->getTime($start,$end,$time_true);

        $group ='';
        if($time_true){
            $group .="hours";
        }else{
            $group .="days";
        }

        $where['day'] = ['between',"$start,$end"];
        $data    = Db::name('media_report')
                    ->alias('a')->field("sum(ck) ck,sum(im) im,sum(income) income,FROM_UNIXTIME(a.day,'%Y%m%d') days,FROM_UNIXTIME(`a`.`day`,'%Y%m%d%H') hours,ap.name as slot_name")
                    ->join('ssp_adslot ap',' a.slot_id=ap.id ','left')
                    ->group($group)
                    ->where($where)
                    ->select();
        $new_data=[];
        foreach($days as $k=>$v){
            //是同一天
            if( ',' === $v[5]){
                foreach($data as $key=>$val){
                    if(date('H',strtotime($val['hours'].'0000'))==$k){
                        $new_data[$v]= array('ck'=>$val['ck'],'im'=>$val['im'],'income'=>$val['income']);
                    }
                }
                if(!array_key_exists($v, $new_data)){
                        $new_data[$v]= array('ck'=>0,'im'=>0,'income'=>0);
                } 
            }else{
                foreach ( $data as $key=>$val){
                    if($val['days'] == $v){
                        $new_data[$v]= array('ck'=>$val['ck'],'im'=>$val['im'],'income'=>$val['income']);
                    }
                }
                if(!array_key_exists($v, $new_data)){
                    $new_data[$v]= array('ck'=>0,'im'=>0,'income'=>0);
                } 
            }
        }
        $date=[];
        foreach($new_data as $key=>$val){
            if($val['im']==0){
                $new_data[$key]['cpm']    = 0;
                $new_data[$key]['ck_rate'] =  0;
            }else{
                $new_data[$key]['cpm']    = sprintf("%.3f", $val['income']*1000/$val['im']);
                $new_data[$key]['ck_rate']   =  sprintf("%.3f", ($val['ck']/$val['im'])*100);
            }
            //$new_data[$key]['cpc']             = sprintf("%.3f", $val['income']/$val['ck']);  
            $date[]="'$key'";
        }

        $show_num=array_column($new_data,'im');
        $click_num=array_column($new_data,'ck');
        $estimated_income=array_column($new_data,'income'); 
        $click_rate=array_column($new_data,'ck_rate');
        $cpm=array_column($new_data,'cpm');

        $line_data = [];
        $title = $this->getTitle($chart);
        $line_data = array(
            '',
            implode(',',$show_num),implode(',',$click_num),implode(',',$estimated_income),implode(',',$cpm)
            ,implode(',',$click_rate)
        );
        $line = [];
        $line['title'] = "'$title'";
        $line['data']  = $line_data[$chart];
        $line['date']  = implode(",",$date);
        $line['limit'] = 100;

        $start_y = strtotime(date('Ymd 00:00:00',strtotime('-1days')));
        $end_y   = strtotime(date('Ymd 23:59:59',strtotime('-1days')));

        $start_s = strtotime(date('Ymd 00:00:00',strtotime('-7days')));
        $end_s   = strtotime(date('Ymd 23:59:59',strtotime('-1days')));
        $y_where=[];
        $s_where=[];
        $al_where=[];
        if(isset($where['slot_id'])){
            $y_where['slot_id'] = $where['slot_id'];
            $s_where['slot_id'] = $where['slot_id'];
            $al_where['slot_id']= $where['slot_id'];
        }
        $s_where['day']=['between',"$start_s,$end_s"];
        $y_where['day'] = ['between',"$start_y,$end_y"];
        $list    = [
            'y_income'=>0,
            's_income'=>0,
            'income_all'=>0
        ];
        $list['y_income']=Db::name('media_report')->field('sum(income) as income')->where($y_where)->find()['income'];

        $list['s_income']= Db::name('media_report')->field('sum(income) as income')->where($s_where)->find()['income'];

        $list['income_all'] = Db::name('media_report')->field('sum(income) as income')->where($al_where)->find()['income'];
        $this->assign('list',$list);
        $this->assign('line',$line);
    }
    private function getTime($start,$end,$true){
        $date = [];
        if($true){
            $beginTime =strtotime(date('Y-m-d 00:00:00',$start));
            for($i = 0; $i < 24; $i++){
                $b = $beginTime + ($i * 3600);
                $e = $beginTime + (($i+1) * 3600)-1;
                $date[] = date("H:i",$b).",".date("H:i",$e);
            }
        }else{
            for($i=$start;$i<=$end;$i+=86400){
                $date[]=date('Ymd',$i);
            }
        }
        return $date;
    }
    private function getTitle($chart){
        $arr = array('','展示量','点击量','预估收益(元)','千次展示收益(元)','点击率(%)');
        return $arr[$chart];
    }

}
