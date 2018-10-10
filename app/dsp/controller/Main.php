<?php
namespace app\dsp\controller;

use think\Db;
use \think\Session;
use app\dsp\controller\Permissions;
class Main extends Permissions
{
        public function index()
    {
        $search  = $this->request->param();
        $chart = 1;
        $pwor  = $this->is_yunying() || $this->is_admin();
        $start = date('Y-m-d 00:00:00',strtotime('-7days'));
        $end   = date('Y-m-d 23:59:59',strtotime('-1days'));
        $where = [];
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
        if (isset($search['chart'])&&!empty($search['chart'])){
            $chart = $search['chart'];
        }
        if(!$pwor){
            $where['uid'] = $this->getAdminId();
        }
        $date1 = date('Y-m-d',strtotime($start)).' - '.date('Y-m-d',strtotime($end));
        $this->getData($where,$start,$end,$chart);
        $this->assign('pwor',$pwor);
        $this->assign('chart',$chart);
        $this->assign('search',$search);
        $this->assign('date1',"'$date1'");
        return $this->fetch();
    }
     private function getData($where=[],$start,$end,$chart=1){
        //判断时间是否选择的是一天的数据
        $time_true= date('Y-m-d',strtotime($start)) == date('Y-m-d',strtotime($end)) ? true : false;
        //根据选择的时间获取时间段
        $days = $this->getTime($start,$end,$time_true);
        $start = strtotime($start);
        $end   = strtotime($end);
        $group ='';
        if($time_true){
            $where['day']   = ['between',"$start,$end"];
            $group .="hours";
        }else{
            $where['day'] = ['between',"$start,$end"];
            $group .="days";
        }
        //根据时间分组计算的数据
        $data =  Db::name('creative')->alias('a')
                ->field("FROM_UNIXTIME(ar.day,'%Y%m%d') days,sum(im) as im, FROM_UNIXTIME(`ar`.`day`,'%Y%m%d%H') hours,sum(ck) as ck,sum(cost) as cost")
                ->join('ssp_campaign ca',' ca.id=a.campaign_id','inner')
                ->join('ssp_advertiser_report ar',' ar.creative_id=a.id','inner')
                ->group($group)
                ->where($where)
                ->select();
                //echo Db::name('creative')->getLastSql();die;
        $new_data=[];
        foreach($days as $k=>$v){
            //是同一天
            if( ',' === $v[5]){
                foreach($data as $key=>$val){
                    if(date('H',strtotime($val['hours'].'0000'))==$k){
                        $new_data[$v]= array('ck'=>$val['ck'],'im'=>$val['im'],'cost'=>$val['cost']);
                    }
                }
                if(!array_key_exists($v, $new_data)){
                        $new_data[$v]= array('ck'=>0,'im'=>0,'cost'=>0);
                } 
            }else{
                foreach ( $data as $key=>$val){
                    if($val['days'] == $v){
                        $new_data[$v]= array('ck'=>$val['ck'],'im'=>$val['im'],'cost'=>$val['cost']);
                    }
                }
                if(!array_key_exists($v, $new_data)){
                    $new_data[$v]= array('ck'=>0,'im'=>0,'cost'=>0);
                } 
            }
        }
        $date=[];
        foreach($new_data as $key=>$val){
            if($val['im']==0){
                $new_data[$key]['ck_rate']      =  0;
            }else{
                $new_data[$key]['ck_rate']      =  sprintf("%.3f", ($val['ck']/$val['im'])*100);
            }
            if($val['ck']==0){
                $new_data[$key]['ck_avg']       =  0;
            }else{
                $new_data[$key]['ck_avg']       =  sprintf("%.3f", $val['cost']/$val['ck']);
            }
            $date[]="'$key'";
        }
        $im_num = array_column($new_data, 'im');
        $ck_num = array_column($new_data, 'ck');
        $ck_rate= array_column($new_data, 'ck_rate');
        $ck_avg = array_column($new_data, 'ck_avg');

        $line_title   = $this->getTitle($chart);
        $line_data =[];
        $line_data = array(
            '',
            implode(',',$im_num),implode(',',$ck_num),implode(',',$ck_rate),implode(',',$ck_avg)
        );

        $line = [];
        $line['title'] = "'$line_title'";
        $line['data']  = $line_data[$chart];
        $line['date']  = implode(",",$date);

        $this->getListData($this->getAdminId());

        $this->assign('line',$line);
    }
    private function getTime($start,$end,$true){
        $date = [];
        $start = strtotime($start);
        $end   = strtotime($end);
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
        $arr = array('','曝光量','点击量','点击率','点击均价(元)');
        return $arr[$chart];
    }
    private function getListData($uid){
        $list = [];
        $where = [];
        $group = '';
        $start = strtotime(date('Ymd 00:00:00',time()));
        $end = strtotime(date('Ymd 23:59:59',time()));
        $where['day']   = ['between',"$start,$end"];
        $pwor  = $this->is_yunying() || $this->is_admin();
        if(!$pwor){
            $where['uid']   = $uid;
        }
        $group .="days";
        //今日花费
        $today_cost =  Db::name('creative')->alias('a')
                ->field("FROM_UNIXTIME(ar.day,'%Y%m%d') days,sum(im) as im,sum(ck) as ck,sum(cost) as cost")
                ->join('ssp_campaign ca',' ca.id=a.campaign_id','inner')
                ->join('ssp_advertiser_report ar',' ar.creative_id=a.id','inner')
                ->group($group)
                ->where($where)
                ->select();
        unset($where['day']);
        //当前投放广告数
        $where['a.status']=1;
        $where['a.handle_status'] =1;
        $now_ad = Db::name('creative')->alias('a')
                ->join('ssp_campaign ca',' ca.id=a.campaign_id','left')
                ->group('a.id')
                ->where($where)
                ->count();
        //现金账户 & 虚拟账户
        $money = Db::name('balance')->where('uid',$uid)->find();
        $list['today_cost'] = array_sum(array_column($today_cost, 'cost'));
        $list['money'] = $money['money'];
        $list['vir_money'] = $money['vir_money'];
        $list['now_ad'] = $now_ad;
        $this->assign('list',$list);
    }

}
