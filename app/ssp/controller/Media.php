<?php
/*媒体*/
namespace app\ssp\controller;

use app\ssp\controller\Permissions;
use app\ssp\model\Media as mediaModel;
use app\ssp\model\Sspuser as adminModel;
use \think\Db;
use \think\Session;
use \think\Log;
vendor("PHPExcel.PHPExcel");
vendor('PHPExcel.PHPExcel.IOFactory.PHPExcel_IOFactory');
vendor('PHPExcel.PHPExcel.Reader.Excel2007');
vendor('PHPExcel.PHPExcel.Reader.Excel5');
class Media extends Permissions
{
    //媒体列表
	public function index()
    {

        $media_type = Db::name('type')->where('pid',0)->select();
        $this->assign('media_type',$media_type);
		return $this->fetch();
	}
    public function getMediaData(){

        $media = new mediaModel;
        $keywords = $this->request->has('keywords') ? $this->request->param('keywords', '') : '';
        $type_id = $this->request->has('type_id') ? $this->request->param('type_id', '') : '';
        $son_type_id = $this->request->has('son_type_id') ? $this->request->param('son_type_id', '') : '';
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
        $pwor = $this->is_yunying() || $this->is_admin();
        $where=[];
        $whereor =[];
        if (!empty($keywords)) {
            $whereor['name'] = ['like', '%' . $keywords . '%'];
            $whereor['mname'] = ['like', '%' . $keywords . '%'];
        }
        if (!$pwor){
            $where['uid']  = $this->getAdminId();
        }
        if($type_id&&$son_type_id){
            $where['type_id'] = str_pad($type_id,4,"0",STR_PAD_LEFT).str_pad($son_type_id,4,"0",STR_PAD_LEFT);
        }
        $count = $media->alias('a')->field('*,wxm.name as name,wxm.id as wxmid')
                ->join('ssp_wxmedia wxm',' a.id=wxm.media_id ','inner')
                ->order('a.create_time desc')
                ->whereOr($whereor)
                ->where($where)
                ->count();
        $list = $media->alias('a')->field('wxm.name as name,wxm.id as wxmid,wxm.wxid,wxm.type_id,wxm.media_id,wxm.create_time,wxm.status,a.uid,a.id,a.mname')
                ->join('ssp_wxmedia wxm',' a.id=wxm.media_id ','inner')
                ->order($byorder)
                ->whereOr($whereor)
                ->where($where)
                ->limit($start,$limit)
                ->select();
        $type_arr=[0,0];
        if (!empty($list)){
            foreach ($list as $key=>$val){
                $is_stats = Db::name('adslot')->where(['media_id'=>$val->media_id,'status'=>1])->select();
                if (!empty($is_stats)){
                    $val->pos_status = '开启';
                }else{
                    $val->pos_status = '关闭';
                }
                $val->status = $val->status==1 ? '正常':'审核';
                $type_arr = str_split($val->type_id,4);
                $type_arr[0]=(int)$type_arr[0];
                $type_arr[1]=(int)$type_arr[1];
                $type = Db::name('type')->field('name')->where(['id'=>['in',$type_arr]])->select();
                $val->type_id = implode('&nbsp;&nbsp;&nbsp;&nbsp;',array_column($type, 'name'));
                $val->url = url('ssp/media/editor',['wxmid'=>endecodeUserId($val->id)]);
            }
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
    //媒体添加
    public function add()
    {
        $admin_model = new adminModel();
        $media_model = new mediaModel();
        $pwor = $this->is_yunying() || $this->is_admin();
        $admin_data = $admin_model->select();
        if ($this->request->isPost()){
                $revice_data = $this->request->post();
                $validate = new \think\Validate([
                        ['wxid', 'require', '小程序ID不能是空'],
                        ['type_id', 'require', '父类型不能是空'],
                        ['son_type_id', 'require', '子类型不能是空'],
                        ['name', 'require', '小程序名称不能是空'],
                ]);
                $revice_data['create_time'] = time();
                if(empty($revice_data['type_id']) || empty($revice_data['son_type_id'])) return $this->error('请选择类型');
                $type_id=str_pad($revice_data['type_id'],4,"0",STR_PAD_LEFT);
                $son_type_id=str_pad($revice_data['son_type_id'],4,"0",STR_PAD_LEFT);
                $revice_data['type_id'] = $type_id.$son_type_id;
                if (!$validate->check($revice_data)) {
                    $this->error('提交失败：' . $validate->getError());
                }
                $wxid = Db::name('wxmedia')->where('wxid',$revice_data['wxid'])->find();
                if(!empty($wxid)){
                    $this->error('小程序ID存在');
                }
                if (!$pwor){
                    $revice_data['uid'] = $this->getAdminId();
                } else {
                    if (!isset($revice_data['uid']) || empty($revice_data['uid'])){
                        return $this->error('请选择所属账号');
                    }
                }
            DB::startTrans();
            try {
                Db::name('media')->insert(array('uid'=>$revice_data['uid'],'create_time'=>$revice_data['create_time'],'mname'=>$revice_data['name']));
                $media_id = Db::name('media')->getLastInsID();
                $revice_data['media_id'] = $media_id;
                unset($revice_data['uid']);
                unset($revice_data['son_type_id']);
                $res = Db::name('wxmedia')->insert($revice_data);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                Log::record("== xxx更新失败 ==", 'DEBUG'); 
                Log::record($e->getMessage(), 'DEBUG');
                return $this->error('添加媒体失败');
            }
            return $this->success('添加媒体成功','ssp/media/index');
        }
        $media_type = Db::name('type')->where('pid',0)->select();
        $this->assign('admin',$admin_data);
        $this->assign('media_type',$media_type);
        $this->assign('pwor',$pwor);
    	return $this->fetch();
    }
    //小程序修改
    public function editor()
    {
        $media_model = new mediaModel();
        //小程序ID
        $id = $this->request->has('wxmid') ? $this->request->param('wxmid') : 0;
        $id = endecodeUserId($id,'decode');
        if ($this->request->isPost()){
            $save_data = $this->request->post();
            $save_data['create_time'] = time();
            if($save_data['wxid']==''){
                return $this->error('小程序ID必填');
            }
            if(empty($save_data['type_id']) || empty($save_data['son_type_id'])) return $this->error('请选择类型');
            $type_id=str_pad($save_data['type_id'],4,"0",STR_PAD_LEFT);
            $son_type_id=str_pad($save_data['son_type_id'],4,"0",STR_PAD_LEFT);
            $save_data['type_id'] = $type_id.$son_type_id;
            DB::startTrans();
            try {
                Db::name('media')->where('id',$save_data['media_id'])->update(array('mname'=>$save_data['name']));
                unset($save_data['uid']);
                unset($save_data['son_type_id']);
                unset($save_data['media_id']);
                $res = Db::name('wxmedia')->where('id',$save_data['id'])->update($save_data);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                Log::record("== xxx更新失败 ==", 'DEBUG'); 
                Log::record($e->getMessage(), 'DEBUG');
                return $this->error('更新媒体失败');
            }
            return $this->success('添加媒体成功','ssp/media/index');
        }
        if ( !empty($id) && is_numeric($id) ){
            $list = Db::name('wxmedia')->where('id',$id)->find();
            $type_arr=[0,0];
            $type_arr = str_split($list['type_id'],4);
            $list['fid'] = (int)$type_arr[0];
            $list['sid'] = (int)$type_arr[1];
            $media_type = Db::name('type')->where('pid',0)->select();
            $this->assign('media_type',$media_type);
            $this->assign('list',$list);
        }else {
            return $this->error('非法操作');
        }
    	return $this->fetch();
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
                $res = Db::name('media')->where('id',$id)->update(['status'=>$status]);
                if(false === $res) {
                    return $this->error('设置失败');
                } else {
                    return $this->success('设置成功','ssp/media/index');
                }
            } 
        } else {
            return $this->error('id不正确');
        }
    }
    public function findson(){
        $id = $this->request->post('f_id');
        $media_type = Db::name('type')->where('pid',$id)->select();
        $list=[];
        $list["msg"]="";
        $list["code"]=0;
        $list["count"]=100;
        $list["data"]=$media_type;
        if(empty($media_type)){
            $list["msg"]="暂无数据";
        }
        return json($list);
    }
    public function batchadd(){

        $admin_model = new adminModel();
        $media_model = new mediaModel();
        $execl = new \PHPExcel();
        $pwor = $this->is_yunying() || $this->is_admin();
        $admin_data = $admin_model->select();
        if($this->request->isPost()){
            $post = $this->request->post();
            $validate = new \think\Validate([
                ['mname', 'require', '媒体名称不能是空'],
                ['file_name', 'require', '小程序信息文件必传'],
            ]);
            $file = $post['file_name'];
            if (!file_exists($file)) {
                $this->error('文件不存在请重新上传');
            }
            if (!$validate->check($post)) {
                $this->error('提交失败：' . $validate->getError());
            }
            if (!$pwor){
                $post['uid'] = $this->getAdminId();
            } else {
                if (!isset($post['uid']) || empty($post['uid'])){
                    return $this->error('请选择所属账号');
                }
            }
            $PHPReader = new \PHPExcel_Reader_Excel2007();
            if(!$PHPReader->canRead($file)){
            $PHPReader = new \PHPExcel_Reader_Excel5();
                if(!$PHPReader->canRead($file)){
                return $this->error('不支持文件格式');
                }
            }
            $objPHPExcel = $PHPReader->load($file);
            $excel_array=$objPHPExcel->getsheet(0)->toArray();
            array_shift($excel_array);
            $type = Db::name('type')->select();
            $new_type = [];
            foreach($type as $key=>$val){
                $new_type[$val['name']] = $val['id'];
            }
            DB::startTrans();
            try {
                Db::name('media')->insert(array('uid'=>$post['uid'],'create_time'=>time(),'mname'=>$post['mname']));
                $media_id = Db::name('media')->getLastInsID();
                $data = [];
                foreach($excel_array as $k=>$v) {
                    if($v[0]=='' || $v[1]=='' || $v[2]=='' || $v[3]=='') throw Exception("请检查文件填写");
                    $data[$k]['wxid'] = $v[0];
                    $data[$k]['name'] = $v[1];
                    if(isset($new_type[$v[2]]) && isset($new_type[$v[3]])){
                        $type_1 = str_pad($new_type[$v[2]],4,"0",STR_PAD_LEFT);
                        $type_2 = str_pad($new_type[$v[3]],4,"0",STR_PAD_LEFT);
                    }else{
                        throw Exception("请检查分类");
                    }
                    $pid = Db::name('type')->where('id',$new_type[$v[3]])->find()['pid'];
                    if($pid!=$new_type[$v[2]]) throw Exception("请检查分类关系");
                    $data[$k]['type_id'] = $type_1.$type_2;
                    $data[$k]['marks'] = '批量创建';
                    $data[$k]['media_id'] = $media_id;
                    $data[$k]['create_time'] = time();
                }
                Db::name('wxmedia')->insertAll($data);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                Log::record("==批量导入失败 ==", 'DEBUG'); 
                Log::record($e->getMessage(), 'DEBUG');
                return $this->error('批量导入失败,请重新上传'.$e->getMessage());
            }
            return $this->success('批量导入成功','ssp/media/index');


        }
        $this->assign('admin',$admin_data);
        $this->assign('pwor',$pwor);
        return $this->fetch();
    }
    public function down(){
        $file_url =ROOT_PATH . 'public' . DS . 'static' . DS .'public'.DS.'piliang.xlsx';
        if(!file_exists($file_url)){ //检查文件是否存在
            return $this->error('404');
        }
        $file = fopen($file_url, "r");
        Header("Content-type:application/text");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . filesize($file_url));
        Header("Content-Disposition: attachment; filename=小程序批量模板.xlsx"); // 输出文件内容
        echo fread($file, filesize($file_url));
        fclose($file);
    }

}
