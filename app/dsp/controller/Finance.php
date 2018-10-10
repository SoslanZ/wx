<?php
namespace app\dsp\controller;

use \think\Db;
use \think\Cookie;
use \think\Session;
use app\dsp\model\Dspuser as AdminModel;//管理员模型
use app\dsp\model\DspMenu;
use app\dsp\controller\Permissions;
class Finance extends Permissions
{

    public function recharge()
    {
        $list = Db::name('balance')->where('uid',$this->getAdminId())->find();
        if(!empty($list)){
            $list['all_money'] = sprintf('%0.2f',$list['money']+$list['vir_money']);
        }else{
            $list = ['dslimit'=>0,'money'=>0,'vir_money'=>0,'all_money'=>0];
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function index()
    {
        $where = [];
        $pwor  = $this->is_yunying() || $this->is_admin() || $this->is_caiwu();
        if(!$pwor){
            $where['uid'] = $this->getAdminId();
        }
        $list = Db::name('recharge_log')->field('a.*,b.nickname')->alias('a')->join('ssp_dspuser b','a.uid=b.id')->where($where)->order('a.id desc')->paginate(10,false,['query'=>$this->request->param()]);
        $this->assign('list',$list);
        $this->assign('pwor',$pwor);
        return $this->fetch();
    }
    
    public function invoice()
    {   
        return $this->fetch();
    }
    public function add()
    {
        $AdminModel = new AdminModel();
        $admin = $AdminModel->select();
        if ($this->request->isPost()){
            $data = $this->request->Post();
            $validate = new \think\Validate([
                    ['uid', 'require', '必须选择充值账号'],
                    ['money', 'require', '充值金额必填'],
            ]);
            $opera_id = $this->getAdminId();
            $type = $data['type'];
            if (!$validate->check($data)) {
                $this->error('提交失败：' . $validate->getError());
            }
            $ip = getIp();
            DB::startTrans();
            try {
                $bab = Db::name('balance')->where('uid',$data['uid'])->find();
                if (!empty($bab)) {
                    if($type==0){
                        Db::name('balance')->where(['uid'=>$data['uid']])->update(['money'=>$bab['money']+$data['money'],'update_time'=>time()]);
                    }else{
                        Db::name('balance')->where(['uid'=>$data['uid']])->update(['vir_money'=>$bab['vir_money']+$data['money'],'update_time'=>time()]);
                    }
                }else{
                    if($type==0){
                        Db::name('balance')->insert(['money'=>$data['money'],'uid'=>$data['uid'],'update_time'=>time(),'create_time'=>time()]);
                    }else{
                        Db::name('balance')->insert(['vir_money'=>$data['money'],'uid'=>$data['uid'],'update_time'=>time(),'create_time'=>time()]);
                    }
                }
                Db::name('recharge_log')->insert(['uid'=>$data['uid'],'recharge'=>$data['money'],'opera_id'=>$opera_id,'create_time'=>time(),'type'=>$type,'clent_ip'=>$ip]);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error('充值失败');
            }
            return $this->success('充值成功','dsp/finance/add');
        }
        $this->assign('admin',$admin);
        return $this->fetch();
    }
    public function dslimit(){
        if($this->request->isAjax()){
            $data = [];
            $save = $this->request->Post();
            if($save['dslimit']<50 || !is_numeric($save['dslimit'])){
                return $this->error('日限额必须大于50');
            }
            $bab = Db::name('balance')->where('uid',$this->getAdminId())->find();
            if(!empty($bab)){
                $res = Db::name('balance')->where(['uid'=>$this->getAdminId()])->update(['dslimit'=>$save['dslimit'],'update_time'=>time()]);
            }else{
                $res = Db::name('balance')->insert(['uid'=>$this->getAdminId(),'update_time'=>time(),'create_time'=>time(),'dslimit'=>$save['dslimit']]);
            }
            if($res == false){
                $this->error('设置失败');
            }else{
                return $this->success('设置成功','dsp/finance/recharge');
            }
        }
    }
    public function cost(){
        $where = [];
        $post = $this->request->param();
        if (isset($post['day']) and !empty($post['day'])) {
            $start = strtotime($post['day'].' 00:00:00');
            $end   = strtotime($post['day'].' 23:59:59');
            $post['day'] = date('Y-m-d',$start);
            $where['day'] = ['between',"$start,$end"];
        }else{
            $post['day'] ='';
        }
        if (isset($post['uid']) and $post['uid'] > 0) {
            $where['uid'] = $post['uid'];
        }else{
            $post['uid'] = 0;
        }
        $pwor  = $this->is_yunying() || $this->is_admin() || $this->is_caiwu();
        if(!$pwor){
            $user = [];
            $where['uid'] = $this->getAdminId();
        }else{
            $user = Db::name('dspuser')->field('id,nickname')->select();
        }
        $list = Db::name('day_cost')->alias('a')->field('a.*,b.nickname')->join('ssp_dspuser b','a.uid=b.id')->where($where)->order('a.day desc')->paginate(10,false,['query'=>$this->request->param()]);
        
        $this->assign('list',$list);
        $this->assign('search',$post);
        $this->assign('user',$user);
        $this->assign('pwor',$pwor);
        return $this->fetch();
    }
    public function surplus(){

        $post = $this->request->param();
        $where = [];
        $where['dsp_cate_id'] = 5;
        if (isset($post['nickname']) and !empty($post['nickname'])) {
            $where['nickname'] = ['like', '%' . $post['nickname'] . '%'];
        }else{
            $post['nickname'] = '';
        }
        if (isset($post['day']) and !empty($post['day'])) {
            $times = explode('~', $post['day']);
            $start = strtotime($times[0]."00:00:00");
            $end = strtotime($times[1]."23:59:59");
        }else{
            $start=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
            $end=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
            $post['day'] = date('Y-m-d',$start).' ~ '. date('Y-m-d',$start);
        }
        $time = ['day'=>['between',[$start,$end]]];

        $cost_all = Db::name('day_cost')->field('sum(money) as money,sum(vmoney) vir_money,uid')->where($time)->group('uid')->select();
        $u_cost =[];
        foreach($cost_all as $key=>$val){
            $u_cost[$val['uid']] = $val['money']+$val['vir_money'];
        }
        $res = Db::name('balance')->alias('a')->join('ssp_dspuser du',' a.uid=du.id','left')->where($where)->paginate(10,false,['query'=>$this->request->param()]);
        $up = $res->all();
        foreach($up as $key=>$val){
            if(!isset($u_cost[$val['uid']])){
                $u_cost[$val['uid']] = 0;
            }
        }
        $this->assign('search',$post);
        $this->assign('ycost',$u_cost);
        $this->assign('list',$res);
        return $this->fetch();
    }
   


}
