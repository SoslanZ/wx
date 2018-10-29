<?php
namespace app\dsp\controller;

use \think\Db;
use app\dsp\controller\Permissions;
use app\dsp\model\Campaign as CampaignModel;
use app\dsp\model\Media as MediaModel;
use app\dsp\model\Creative as CreativeModel;
use app\dsp\model\Dspuser as adminModel;//管理员模型
class Advert extends Permissions
{

	public function index(){
        $CampaignModel = new CampaignModel();
        $CreativeModel = new CreativeModel();
        $pwor  = $this->is_yunying() || $this->is_admin();
        $search  = $this->request->param();
        $where = [];
        $campaign_where=[];
        $repor_where = [];
        if (isset($search['advert_name'])&&!empty($search['advert_name'])){
            $where['a.name'] = ['like', '%' . $search['advert_name'] . '%'];
        }else{
            $search['advert_name']='';
        }

        if (isset($search['day'])&&!empty($search['day'])){
            $times = explode('~', $search['day']);
            $start_time = strtotime($times[0]."00:00:00");
            $end_time = strtotime($times[1]."23:59:59");
            $repor_where['day'] = ['between', [$start_time,$end_time]];
        }else{
            $search['day']='';
        }

        if(!$pwor){
            $where['uid'] = $this->getAdminId();
            $campaign_where['uid'] = $this->getAdminId();
        }
        if (isset($search['campaign_id'])&&!empty($search['campaign_id'])){
            $where['campaign_id'] = ['eq',$search['campaign_id']];
        }else{
            $search['campaign_id']='';
        }
        $where['handle_status']=['neq',3];
        $list =  $CreativeModel->alias('a')
                    ->field('a.*,ca.uid')
                    ->join('ssp_campaign ca',' a.campaign_id=ca.id','left')
                    ->group('a.id')
                    ->where($where)
                    ->order('a.id desc')
                    ->paginate(10,false,['query'=>$this->request->param()]);

        foreach ($list as $k=>$val){
            $repor_where['creative_id']=$val->id;
            $repor = DB::name('advertiser_report')->field('sum(im) as im,sum(ck) as ck,sum(cost) as cost')
            ->where($repor_where)
            ->find();
            $val->ck = $repor['ck'] ? $repor['ck'] : 0 ;
            $val->im = $repor['im'] ? $repor['im'] : 0 ;
            $val->cost = $repor['cost'] ? $repor['cost'] : 0 ;
            if($val->im==0){
                $val->rate = 0;
            }else{
                $val->rate = sprintf("%.3f", ($val->ck/$val->im)*100);
            }
            if($val->ck==0){
                $val->ck_rate = 0;
            }else{
                $val->ck_rate = sprintf("%.3f", $val->cost/$val->ck);
            }

        }
        $campaign = $CampaignModel->where($campaign_where)->select();

        $this->assign('search',$search);
        $this->assign('campaign',$campaign);
        $this->assign('list',$list);
        $this->assign('pwor',$pwor);
		return $this->fetch();
	}

    public function add(){
        $CampaignModel = new CampaignModel();
        $CreativeModel = new CreativeModel();
        $tf = Db::name('dspuser')->where(['id'=>$this->getAdminId()])->find()['tf'];
        $pwor  = $this->is_yunying() || $this->is_admin();
        $campaign_where=[];
        if(!$pwor){
            $campaign_where['uid'] = $this->getAdminId();
        }
        if ($this->request->isPost()){
            $insert_data = $this->request->Post();

            $validate = new \think\Validate([
                    ['campaign_id', 'require', '推广计划不能是空'],
                    ['name', 'require', '广告名称'],
                    ['template_id', 'require', '请选择广告样式'],
                    ['filter_os', 'require', '操作系统定向必须选择'],
                    ['filter_net', 'require', '网络类型定向必须选择'],
                    ['filter_sex', 'require', '性别定向定向必须选择'],
                    ['start_ts', 'require', '投放日期'],

            ]);
            if ($insert_data['tou_day'] == 1){
                if($insert_data['tou_day_1']=='') return $this->error('请选择时间');
                $insert_data['start_ts'] =strtotime($insert_data['tou_day_1']);
                $insert_data['end_ts'] =0;
            }else{
                $day = explode('~', $insert_data['tou_day_2']);
                if($day[0]=='' || $day[1]=='') return $this->error('请选择时间');
                $insert_data['start_ts'] = strtotime($day[0]."00:00:00");
                $insert_data['end_ts']   = strtotime($day[1].'23:59:59');
            }

            if($insert_data['type'] == 1){
                if($insert_data['media_id']=='') return $this->error('媒体必选');
                $insert_data['type_val'] = $insert_data['media_id'];
            }else{
                if($insert_data['type_id']=='') return $this->error('分类必选');

                $type_ids = explode(',', $insert_data['type_id']);
                //获取所有选中的二级分类
                $type_checked_two= Db::name('type')->where(['id'=>['in',$type_ids],'pid'=>['neq',0]])->select();
                //给二级分类分组
                $tw = [];
                foreach($type_checked_two as $key=>$val){
                    $tw[$val['pid']][]= $val['id'];
                }
                $check_all = [];
                $type_all= Db::name('type')->select();
                foreach($type_all as $key=>$val){
                    $check_all[$val['pid']][]=$val['id'];
                }
                $check = [];
                foreach($tw as $key=>$val){
                    if(count($tw[$key])==count($check_all[$key])){
                        $check[]=str_pad($key,4,"0",STR_PAD_LEFT).'0000';
                    }else{
                        foreach($val as $k=>$v){
                        $check[] = str_pad($key,4,"0",STR_PAD_LEFT).str_pad($v,4,"0",STR_PAD_LEFT);
                        }
                    }
                }
                $insert_data['type_val'] = implode(',', $check);
            }
            unset($insert_data['media_id']);unset($insert_data['type_id']);
            if ($insert_data['tou_time'] == 1){
                $insert_data['filter_hour'] = '';
            }
            if($insert_data['bid_price']*100 < $tf*100){
                return $this->error('请输入大于'.$tf.'的出价价格');
            }

            if(empty($insert_data['img_2'])){
                return $this->error('请上传广告图片');
            }else{
                $filename_2 = pathinfo($insert_data['img_2'])['basename'];
                $file_dir_2 = pathinfo($insert_data['img_2'])['dirname'];
                $file_day_2 = substr($file_dir_2,-8);
                $path_2 = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'admin' . DS . 'admin_thumb'.DS.$file_day_2.DS.$filename_2;
                if(!file_exists($path_2)){
                    return $this->error('上传的广告图片不存在请重新上传');
                }
            }
            if($insert_data['clk_type']==1){
                if(empty($insert_data['img_1'])){
                    return $this->error('请上传预览图片');
                }else{
                    $filename_1 = pathinfo($insert_data['img_1'])['basename'];
                    $file_dir_1 = pathinfo($insert_data['img_1'])['dirname'];
                    $file_day_1 = substr($file_dir_1,-8);
                    $path_1 = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'admin' . DS . 'admin_thumb'.DS.$file_day_1.DS.$filename_1;
                    if(!file_exists($path_1)){
                        return $this->error('上传的预览图片不存在请重新上传');
                    }
                }
                    $json_data = [];
                    $json_data = [
                        'image'=>[
                            'main'=>['url'=>$insert_data['img_2']],
                            'land'=>['url'=>$insert_data['img_1']],
                            'icon'=>['url'=>''],
                            'deep'=>['url'=>'']
                        ],
                        'text'=>[
                            'title'=>'',
                            'desc'=>''
                        ],
                        'land'=>'',
                        'pages'=>'',
                        'interact'=>0
                    ];
                    $insert_data['material'] =json_encode($json_data);
            }else{
                //跳转
                if(empty($insert_data['img_3']) || empty($insert_data['img_4'])){
                    return $this->error('请上传跳转所需图片');
                }else{
                    $filename_3 = pathinfo($insert_data['img_3'])['basename'];
                    $file_dir_3 = pathinfo($insert_data['img_3'])['dirname'];
                    $file_day_3 = substr($file_dir_3,-8);
                    $path_3 = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'admin' . DS . 'admin_thumb'.DS.$file_day_3.DS.$filename_3;

                    $filename_4 = pathinfo($insert_data['img_4'])['basename'];
                    $file_dir_4 = pathinfo($insert_data['img_4'])['dirname'];
                    $file_day_4 = substr($file_dir_4,-8);
                    $path_4 = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'admin' . DS . 'admin_thumb'.DS.$file_day_4.DS.$filename_4;
                    if(!file_exists($path_3) || !file_exists($path_4)){
                        return $this->error('上传的跳转所需图片不存在,请重新上传');
                    }
                }
                if(empty($insert_data['title'])||empty($insert_data['land'])||empty($insert_data['pages'])){
                    return $this->error('请填写跳转所需标题,小程序ID,跳转地址');
                }
                if(mb_strlen($insert_data['title'],'utf8')>13 ) return $this->error('标题长度过长');
                //if(strlen($save_data['desc'])>25 ) return $this->error('简介长度过长');
                if(mb_strlen($insert_data['pages'],'utf8')>50 ) return $this->error('小程序跳转地址过长');
                    $json_data = [];
                    $json_data = [
                        'image'=>[
                            'main'=>['url'=>$insert_data['img_2']],
                            'land'=>['url'=>''],
                            'icon'=>['url'=>$insert_data['img_4']],
                            'deep'=>['url'=>$insert_data['img_3']]
                        ],
                        'text'=>[
                            'title'=>$insert_data['title'],
                            'desc'=>''//$insert_data['desc']
                        ],
                        'land'=>$insert_data['land'],
                        'pages'=>$insert_data['pages'],
                        'interact'=>1
                    ];
                    $insert_data['material'] =json_encode($json_data);
            }

            $insert_data['filter_net'] = array_sum($insert_data['filter_net']);
            $insert_data['filter_os'] = array_sum($insert_data['filter_os']);
            $insert_data['filter_sex'] = array_sum($insert_data['filter_sex']);

            if (!$validate->check($insert_data)) {
                $this->error('提交失败：' . $validate->getError());
            }
            if( false == $CreativeModel->allowField(true)->save($insert_data)){
                return $this->error('添加广告失败');
            }else {
                return $this->success('添加广告成功','dsp/campaign/index');
            }
        }
        $campaign_where['status']=['neq',3];
        $camp = $CampaignModel->where($campaign_where)->select();
        $wxmedia = Db::name('wxmedia')->select();
        $type = Db::name('type')->where('pid',0)->select();
        $new_type = [];
        foreach($type as $key=>$val){

            $son = Db::name('type')->field('name,id value')->where('pid',$val['id'])->select();
            //array_unshift($son,array('name'=>$val['name'].'(all)','value'=>$val['id']));
            $new_type[] = array('name'=>$val['name'],'value'=>$val['id'],'children'=>$son);

        }
        $this->assign('camp',$camp);
        $this->assign('tf',$tf);
        $this->assign('wxmedia',$wxmedia);
        //$this->assign('new_type_1',$new_type);
        $this->assign('new_type',json_encode($new_type));
    	return $this->fetch();
    }
    public function editor(){
        $MediaModel = new MediaModel();
        $CreativeModel = new CreativeModel();
        $tf = Db::name('dspuser')->where(['id'=>$this->getAdminId()])->find()['tf'];
        $id = $this->request->has('id') ? $this->request->param('id') : 0;
        $id = endecodeUserId($id,'decode');
        if ($this->request->isPost()){
            $save_data = $this->request->post();
            //p($save_data);die;
            $validate = new \think\Validate([
                    //['campaign_id', 'require', '推广计划不能是空'],
                    ['name', 'require', '广告名称'],
                    ['template_id', 'require', '请选择广告样式'],
                    ['filter_os', 'require', '操作系统定向必须选择'],
                    ['filter_net', 'require', '网络类型定向必须选择'],
                    ['filter_sex', 'require', '性别定向定向必须选择'],
                    ['start_ts', 'require', '投放日期'],
            ]);
            if($save_data['type'] == 1){
                if($save_data['media_id']=='') return $this->error('媒体必选');
                $save_data['type_val'] = $save_data['media_id'];
            }else{
                if($save_data['type_id']=='') return $this->error('分类必选');

                $type_ids = explode(',', $save_data['type_id']);
                //获取所有选中的二级分类
                $type_checked_two= Db::name('type')->where(['id'=>['in',$type_ids],'pid'=>['neq',0]])->select();
                //给二级分类分组
                $tw = [];
                foreach($type_checked_two as $key=>$val){
                    $tw[$val['pid']][]= $val['id'];
                }
                $check_all = [];
                $type_all= Db::name('type')->select();
                foreach($type_all as $key=>$val){
                    $check_all[$val['pid']][]=$val['id'];
                }
                $check = [];
                foreach($tw as $key=>$val){
                    if(count($tw[$key])==count($check_all[$key])){
                        $check[]=str_pad($key,4,"0",STR_PAD_LEFT).'0000';
                    }else{
                        foreach($val as $k=>$v){
                        $check[] = str_pad($key,4,"0",STR_PAD_LEFT).str_pad($v,4,"0",STR_PAD_LEFT);
                        }
                    }
                }

                $save_data['type_val'] = implode(',', $check);
            }
            unset($save_data['media_id']);unset($save_data['type_id']);
            if ($save_data['tou_day'] == 1){
                if($save_data['tou_day_1']=='') return $this->error('请选择时间');
                $save_data['start_ts'] =strtotime($save_data['tou_day_1']);
                $save_data['end_ts'] ='';
            }else{
                $day = explode('~', $save_data['tou_day_2']);
                if($day[0]=='' || $day[1]=='') return $this->error('请选择时间');
                $save_data['start_ts'] = strtotime($day[0]."00:00:00");
                $save_data['end_ts']   = strtotime($day[1].'23:59:59');
            }
            if ($save_data['tou_time'] == 1){
                $save_data['filter_hour'] = '';
            }
            if($save_data['bid_price']*100 < $tf*100){
                return $this->error('请输入大于'.$tf.'的出价价格');
            }
            if(empty($save_data['img_2'])){
                return $this->error('请上传广告图片');
            }else{
                $filename_2 = pathinfo($save_data['img_2'])['basename'];
                $file_dir_2 = pathinfo($save_data['img_2'])['dirname'];
                $file_day_2 = substr($file_dir_2,-8);
                $path_2 = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'admin' . DS . 'admin_thumb'.DS.$file_day_2.DS.$filename_2;
                if(!file_exists($path_2)){
                    return $this->error('上传的广告图片不存在请重新上传');
                }
            }
            if($save_data['clk_type']==1){
                if(empty($save_data['img_1'])){
                    return $this->error('请上传预览图片');
                }else{
                    $filename_1 = pathinfo($save_data['img_1'])['basename'];
                    $file_dir_1 = pathinfo($save_data['img_1'])['dirname'];
                    $file_day_1 = substr($file_dir_1,-8);
                    $path_1 = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'admin' . DS . 'admin_thumb'.DS.$file_day_1.DS.$filename_1;
                    if(!file_exists($path_1)){
                        return $this->error('上传的预览图片不存在请重新上传');
                    }
                }
                    $json_data = [];
                    $json_data = [
                        'image'=>[
                            'main'=>['url'=>$save_data['img_2']],
                            'land'=>['url'=>$save_data['img_1']],
                            'icon'=>['url'=>''],
                            'deep'=>['url'=>'']
                        ],
                        'text'=>[
                            'title'=>'',
                            'desc'=>''
                        ],
                        'land'=>'',
                        'pages'=>'',
                        'interact'=>0
                    ];
                    $save_data['material_status']=0;
                    $save_data['material'] =json_encode($json_data);
            }else{
                //跳转
                if(empty($save_data['img_3']) || empty($save_data['img_4'])){
                    return $this->error('请上传跳转所需图片');
                }else{
                    $filename_3 = pathinfo($save_data['img_3'])['basename'];
                    $file_dir_3 = pathinfo($save_data['img_3'])['dirname'];
                    $file_day_3 = substr($file_dir_3,-8);
                    $path_3 = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'admin' . DS . 'admin_thumb'.DS.$file_day_3.DS.$filename_3;

                    $filename_4 = pathinfo($save_data['img_4'])['basename'];
                    $file_dir_4 = pathinfo($save_data['img_4'])['dirname'];
                    $file_day_4 = substr($file_dir_4,-8);
                    $path_4 = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'admin' . DS . 'admin_thumb'.DS.$file_day_4.DS.$filename_4;
                    if(!file_exists($path_3) || !file_exists($path_4)){
                        return $this->error('上传的跳转所需图片不存在,请重新上传');
                    }
                }
                if(empty($save_data['title'])||empty($save_data['land'])||empty($save_data['pages'])){
                    return $this->error('请填写跳转所需标题,小程序ID,跳转地址');
                }
                if(mb_strlen($save_data['title'],'utf8')>13 ) return $this->error('标题长度过长');
                //if(strlen($save_data['desc'])>25 ) return $this->error('简介长度过长');
                if(mb_strlen($save_data['pages'],'utf8')>50 ) return $this->error('小程序跳转地址过长');
                    $json_data = [];
                    $json_data = [
                        'image'=>[
                            'main'=>['url'=>$save_data['img_2']],
                            'land'=>['url'=>''],
                            'icon'=>['url'=>$save_data['img_4']],
                            'deep'=>['url'=>$save_data['img_3']]
                        ],
                        'text'=>[
                            'title'=>$save_data['title'],
                            'desc'=>''//$save_data['desc']
                        ],
                        'land'=>$save_data['land'],
                        'pages'=>$save_data['pages'],
                        'interact'=>1
                    ];
                    $save_data['material_status']=0;
                    $save_data['material'] =json_encode($json_data);
            }
            $save_data['status'] = 0;
            $save_data['filter_net'] = array_sum($save_data['filter_net']);
            $save_data['filter_os'] = array_sum($save_data['filter_os']);
            $save_data['filter_sex'] = array_sum($save_data['filter_sex']);
            if (!$validate->check($save_data)) {
                $this->error('提交失败：' . $validate->getError());
            }
            if( false === $CreativeModel->allowField(true)->isUpdate(true)->save($save_data)){
                return $this->error('更新信息失败');
            }else {
                return $this->success('更新信息成功','dsp/advert/index');
            }
        }
        if ( $id ){
            $list = $CreativeModel->where('id',$id)->find();
            $list->material = json_decode($list->material,1);
            $material = $list->material;
            $material['image']['main']['url'] = isset($material['image']['main']['url'])?$material['image']['main']['url']:'';
            $material['image']['land']['url'] = isset($material['image']['land']['url'])?$material['image']['land']['url']:'';
            $material['image']['icon']['url'] = isset($material['image']['icon']['url'])?$material['image']['icon']['url']:'';
            $material['image']['deep']['url'] = isset($material['image']['deep']['url'])?$material['image']['deep']['url']:'';
            $material['text']['title']        = isset($material['text']['title']) ? $material['text']['title']:'';
            $material['text']['desc']         = isset($material['text']['desc']) ? $material['text']['desc']:'';
            $material['land']         = isset($material['land']) ? $material['land']:'';
            $material['pages']        = isset($material['pages']) && !empty($material['pages']) ? $material['pages']:'/pages/index/index';
            $material['interact']     = isset($material['interact']) ? $material['interact']:0;
            $list->start_ts = $list->start_ts ? date('Y-m-d',$list->start_ts) : 0;
            $list->end_ts   = $list->end_ts ? date('Y-m-d',$list->end_ts) : 0;
            $list->material = $material;
            if ($list->end_ts){
                $list->tou_day =2;
            }else{
                $list->tou_day =1;
            }
            if (preg_match("/1/",$list->filter_hour,$m)){
                $list->tou_time = 2;
            }else{
                $list->tou_time = 1;
            }
            $type_val= explode(',', $list->type_val);
            $wxmedia = Db::name('wxmedia')->select();
            //$type = Db::name('type')->select();
            foreach($wxmedia as $key=>$val){
                if($list->type==1 && in_array($val['id'],$type_val)){
                    $wxmedia[$key]['selected'] = 'selected';
                }else{
                    $wxmedia[$key]['selected'] = '';
                }
            }
            if($list->type==2){
                $type_check = [];
                foreach($type_val  as $key=>$val){
                    $type_arr = str_split($val,4);
                    if((int)$type_arr[1] == 0) {
                        $son = Db::name('type')->where('pid',(int)$type_arr[0])->select();
                        $son = array_column($son,'id');
                        array_push($son,(int)$type_arr[0]);
                        $type_check = array_merge($type_check,$son);
                    }else{
                        //$type_check[] = (int)$type_arr[0];
                        $type_check[] = (int)$type_arr[1];
                    }
                }
                //p($type_check);
                $list->type_val = json_encode($type_check);
            }

            $type = Db::name('type')->where('pid',0)->select();
            $new_type = [];
            foreach($type as $key=>$val){

                $son = Db::name('type')->field('name,id value')->where('pid',$val['id'])->select();
                $new_type[] = array('name'=>$val['name'],'value'=>$val['id'],'children'=>$son);
            }
            $this->assign('tf',$tf);
            $this->assign('wxmedia',$wxmedia);
            $this->assign('new_type',json_encode($new_type));
            $this->assign('list',$list);
        }else {
            return $this->error('非法操作');
        }
        return $this->fetch();
    }

    public function status(){
        $CreativeModel = new CreativeModel();
        $id = $this->request->has('id') ? $this->request->param('id') : 0;
        $id = endecodeUserId($id,'decode');
        $list = $CreativeModel->where('id',$id)->find();
        if(!$list){
            return $this->error('广告不存在');
        }
        $status  = $this->request->param('status');
        $reson  = $this->request->param('reson');
        if($id && $status == 1){

            $res = $CreativeModel->where('id',$id)->update(['status'=>1,'reson'=>$reson]);

          Db::name('auditlog')->insert(['opera_id'=>$this->getAdminId(),'upstatus'=>1,'create_time'=>time(),'creative_id'=>$id]);

        }else if($id && $status == 2 && $reson!=''){

            $res = $CreativeModel->where('id',$id)->update(['status'=>2,'reson'=>$reson]);
            Db::name('auditlog')->insert(['opera_id'=>$this->getAdminId(),'upstatus'=>2,'create_time'=>time(),'creative_id'=>$id]);

        }else{
            $res = false;
        }
        if(!$res){
            return $this->error('审核失败');
        }else{
            return $this->success('审核完成','dsp/advert/audit');
        }
    }
    //广告审核
    public function audit(){
        $CreativeModel = new CreativeModel();
        $AdminModel =    new adminModel();

        $search  = $this->request->param();
        $where = [];
        if (isset($search['name'])&&!empty($search['name'])){
            $where['name'] = ['like', '%' . $search['name'] . '%'];
        }else{
            $search['name']='';
        }
        if (isset($search['uid'])&&!empty($search['uid'])){
            $campaign = Db::name('campaign')->where('uid',$search['uid'])->select();
            $campaign = array_column($campaign, 'id');
            $where['campaign_id'] = ['in',$campaign];
        }else{
            $search['uid']='';
        }
        if (isset($search['status'])&&!empty($search['status'])){
            if($search['status']==3){
                $where['status'] = ['eq',0];
            }else{
                $where['status'] = ['eq',$search['status']];
            }
        }else{
            $search['status']='';
        }
        $where['handle_status'] =['neq',3];
        $advert_data = $CreativeModel->where($where)->order('status asc,id desc')->paginate(10,false,['query'=>$this->request->param()]);
        foreach($advert_data as $k=>$val){
            $pic = json_decode($val->material,1);
            $val->main = $pic['image']['main']['url'];
            $val->land = $pic['image']['land']['url'];
        }
        $admin_data = $AdminModel->where('dsp_cate_id',5)->select();

        $this->assign('list',$advert_data);
        $this->assign('search',$search);
        $this->assign('admin_data',$admin_data);
        return $this->fetch();
    }
    //用户操作状态
    public function btnstatus(){
        $id = $this->request->has('id') ? $this->request->param('id') : 0;
        $status = $this->request->has('status') ? $this->request->param('status') : 0;
        if($this->request->isPost()){
            if(!empty($id)){
                $res=Db::name('creative')->where(['id'=>$id])->update(['handle_status'=>$status]);
                if($res){
                    return $this->success('成功','dsp/advert/index');
                }else{
                    return $this->error('失败');
                }
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
                    Db::name('creative')->where(['id'=>$id])->update(['handle_status'=>3]);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    return $this->error('失败');
                }
                $this->success('成功','dsp/advert/index');
            }
        }else{
            $this->error('非法操作');
        }
    }
    public function findtype(){
        $type_id = $this->request->has('type_id') ? $this->request->param('type_id') : 0;
        $media_id = $this->request->has('media_id') ? $this->request->param('media_id') : 0;
        $where = [];
        $list = [];
        if($type_id ==1 ){
            $data = DB::name('wxmedia')->field('id,name')->where('media_id',$media_id)->select();
        }
        if($type_id ==2){

            $data = DB::name('wxmedia')->field('id,name')->where('media_id',$media_id)->select();
        }
        $list=[];
        $list["msg"]="";
        $list["code"]=0;
        $list["count"]=100;
        $list["data"]=$data;
        if(empty($data)){
            $list["msg"]="暂无数据";
        }
        return json($list);
    }

}
