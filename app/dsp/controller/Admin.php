<?php
/*管理员*/
namespace app\dsp\controller;

use \think\Db;
use \think\Cookie;
use \think\Session;
use app\dsp\model\Dspuser as adminModel;//管理员模型
use app\dsp\model\DspMenu;
use app\dsp\controller\Permissions;
class Admin extends Permissions
{
    /**
     * 管理员列表
     * @return [type] [description]
     */
    public function index()
    {
        //实例化管理员模型
        $model = new adminModel();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['nickname'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if (isset($post['dsp_cate_id']) and $post['dsp_cate_id'] > 0) {
            $where['dsp_cate_id'] = $post['dsp_cate_id'];
        }
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_time'] = [['>=',$min_time],['<=',$max_time]];
        }
        $admin = empty($where) ? $model->order('create_time desc')->paginate(20) : $model->where($where)->order('create_time desc')->paginate(20,false,['query'=>$this->request->param()]);
        $role = Db::name('dsp_cate')->select();
        $this->assign('admin',$admin);
        $info['cate'] = $role;
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 管理员的添加及修改
     * @return [type] [description]
     */
    public function publish()
    {
    	//获取管理员id
    	$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
    	$model = new adminModel();
    	if($id > 0) {
    		//是修改操作
    		if($this->request->isPost()) {
    			//是提交操作
    			$post = $this->request->post();
    			//验证  唯一规则： 表名，字段名，排除主键值，主键名
	            $validate = new \think\Validate([
	                ['name', 'require|alphaDash', '管理员名称不能为空|用户名格式只能是字母、数组、——或_'],
	                ['dsp_cate_id', 'require', '请选择管理员分组'],
	            ]);
	            //验证部分数据合法性
	            if (!$validate->check($post)) {
	                $this->error('提交失败：' . $validate->getError());
	            }
	            //验证用户名是否存在
	            $name = $model->where(['name'=>$post['name'],'id'=>['neq',$post['id']]])->select();
	            if(!empty($name)) {
	            	return $this->error('提交失败：该用户名已被注册');
	            }
	            //验证昵称是否存在
	            $nickname = $model->where(['nickname'=>$post['nickname'],'id'=>['neq',$post['id']]])->select();
	            if(!empty($nickname)) {
	            	return $this->error('提交失败：该昵称已被占用');
	            }
	            if(false == $model->allowField(true)->save($post,['id'=>$id])) {
	            	return $this->error('修改失败');
	            } else {
	            	return $this->success('修改管理员信息成功','dsp/admin/index');
	            }
    		} else {
                if ($id == 1 ) return $this->error('网站所有者不能修改');
    			//非提交操作
    			$info['admin'] = $model->where('id',$id)->find();
    			$info['admin_cate'] = Db::name('dsp_cate')->select();
    			$this->assign('info',$info);
    			return $this->fetch();
    		}
    	} else {
    		//是新增操作
    		if($this->request->isPost()) {
    			//是提交操作
    			$post = $this->request->post();
    			//验证  唯一规则： 表名，字段名，排除主键值，主键名
	            $validate = new \think\Validate([
	                ['name', 'require|alphaDash', '用户名不能为空|用户名格式只能是字母、数组、——或_'],
	                ['password', 'require|confirm', '密码不能为空|两次密码不一致'],
	                ['password_confirm', 'require', '重复密码不能为空'],
	                ['dsp_cate_id', 'require', '请选择管理员分组'],
	            ]);
	            //验证部分数据合法性
	            if (!$validate->check($post)) {
	                $this->error('提交失败：' . $validate->getError());
	            }
	            //验证用户名是否存在
	            $name = $model->where('name',$post['name'])->select();
	            if(!empty($name)) {
	            	return $this->error('提交失败：该用户名已被注册');
	            }
	            //验证昵称是否存在
	            $nickname = $model->where('nickname',$post['nickname'])->select();
	            if(!empty($nickname)) {
	            	return $this->error('提交失败：该昵称已被占用');
	            }
	            //密码处理
	            $post['password'] = password($post['password']);
	            if(false == $model->allowField(true)->save($post)) {
	            	return $this->error('添加管理员失败');
	            } else {
	            	return $this->success('添加管理员成功','dsp/admin/index');
	            }
    		} else {
    			//非提交操作
    			$info['admin_cate'] = Db::name('dsp_cate')->select();
    			$this->assign('info',$info);
    			return $this->fetch();
    		}
    	}
    }

    /**
     * 修改密码
     * @return [type] [description]
     */
    public function editPassword()
    {
    	$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
    	if($id > 0) {
    		if($id == Session::get('admin')) {
    			$post = $this->request->post();
    			//验证  唯一规则： 表名，字段名，排除主键值，主键名
	            $validate = new \think\Validate([
	                ['password', 'require|confirm', '密码不能为空|两次密码不一致'],
	                ['password_confirm', 'require', '重复密码不能为空'],
	            ]);
	            //验证部分数据合法性
	            if (!$validate->check($post)) {
	                $this->error('提交失败：' . $validate->getError());
	            }
    			$admin = Db::name('dspuser')->where('id',$id)->find();
    			if(password($post['password_old']) == $admin['password']) {
    				if(false == Db::name('dspuser')->where('id',$id)->update(['password'=>password($post['password'])])) {
    					return $this->error('修改失败');
    				} else {
    					return $this->success('修改成功','dsp/main/index');
    				}
    			} else {
    				return $this->error('原密码错误');
    			}
    		} else {
    			return $this->error('不能修改别人的密码');
    		}
    	} else {
            $id = Session::get('admin');
            $this->assign('id',$id);
    		return $this->fetch();
    	}
    }


    /**
     * 管理员删除
     * @return [type] [description]
     */
    public function delete()
    {
    	if($this->request->isAjax()) {
    		$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
    		if($id == 1) {
    			return $this->error('网站所有者不能被删除');
    		}
    		if($id == Session::get('admin')) {
    			return $this->error('自己不能删除自己');
    		}
    		if(false == Db::name('dspuser')->where('id',$id)->delete()) {
    			return $this->error('删除失败');
    		} else {
    			return $this->success('删除成功','dsp/admin/index');
    		}
    	}
    }

    
    /**
     * 管理员权限分组列表
     * @return [type] [description]
     */
    public function adminCate()
    {
    	$model = new \app\dsp\model\DspCate;

        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['name'] = ['like', '%' . $post['keywords'] . '%'];
        }
 
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_time'] = [['>=',$min_time],['<=',$max_time]];
        }
        
        $cate = empty($where) ? $model->order('create_time desc')->paginate(20) : $model->where($where)->order('create_time desc')->paginate(20,false,['query'=>$this->request->param()]);
        
    	$this->assign('cate',$cate);
    	return $this->fetch();

    }


    /**
     * 管理员角色添加和修改操作
     * @return [type] [description]
     */
    public function adminCatePublish()
    {
        //获取角色id
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        $model = new \app\dsp\model\DspCate();
        $menuModel = new DspMenu();
        if($id > 0) {
            //是修改操作
            if($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                //验证  唯一规则： 表名，字段名，排除主键值，主键名
                $validate = new \think\Validate([
                    ['name', 'require', '角色名称不能为空'],
                ]);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    $this->error('提交失败：' . $validate->getError());
                }
                //验证用户名是否存在
                $name = $model->where(['name'=>$post['name'],'id'=>['neq',$post['id']]])->select();
                if(!empty($name)) {
                    return $this->error('提交失败：该角色名已存在');
                }
                //处理选中的权限菜单id，转为字符串
                if(!empty($post['admin_menu_id'])) {
                    $post['permissions'] = implode(',',$post['admin_menu_id']);
                } else {
                    $post['permissions'] = '0';
                }
                if(false == $model->allowField(true)->save($post,['id'=>$id])) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改角色信息成功','dsp/admin/adminCate');
                }
            } else {
                //非提交操作
                $info['cate'] = $model->where('id',$id)->find();
                if(!empty($info['cate']['permissions'])) {
                    //将菜单id字符串拆分成数组
                    $info['cate']['permissions'] = explode(',',$info['cate']['permissions']);
                }
                $menus = Db::name('dsp_menu')->select();
                $info['menu'] = $this->menulist($menus);
                $this->assign('info',$info);
                return $this->fetch();
            }
        } else {
            //是新增操作
            if($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                //验证  唯一规则： 表名，字段名，排除主键值，主键名
                $validate = new \think\Validate([
                    ['name', 'require', '角色名称不能为空'],
                ]);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    $this->error('提交失败：' . $validate->getError());
                }
                //验证用户名是否存在
                $name = $model->where('name',$post['name'])->find();
                if(!empty($name)) {
                    return $this->error('提交失败：该角色名已存在');
                }
                //处理选中的权限菜单id，转为字符串
                if(!empty($post['admin_menu_id'])) {
                    $post['permissions'] = implode(',',$post['admin_menu_id']);
                }
                if(false == $model->allowField(true)->save($post)) {
                    return $this->error('添加角色失败');
                } else {
                    return $this->success('添加角色成功','dsp/admin/adminCate');
                }
            } else {
                //非提交操作
                $menus = Db::name('dsp_menu')->select();
                $info['menu'] = $this->menulist($menus);
                $this->assign('info',$info);
                return $this->fetch();
            }
        }
    }


    public function preview()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        $model = new \app\dsp\model\DspCate();
        $info['cate'] = $model->where('id',$id)->find();
        if(!empty($info['cate']['permissions'])) {
            //将菜单id字符串拆分成数组
            $info['cate']['permissions'] = explode(',',$info['cate']['permissions']);
        }
        $menus = Db::name('dsp_menu')->select();
        $info['menu'] = $this->menulist($menus);
        $this->assign('info',$info);
        return $this->fetch();
    }


    protected function menulist($menu,$id=0,$level=0){
        
        static $menus = array();
        $size = count($menus)-1;
        foreach ($menu as $value) {
            if ($value['pid']==$id) {
                $value['level'] = $level+1;
                if($level == 0)
                {
                    $value['str'] = str_repeat('',$value['level']);
                    $menus[] = $value;
                }
                elseif($level == 2)
                {
                    $value['str'] = '&emsp;&emsp;&emsp;&emsp;'.'└ ';
                    $menus[$size]['list'][] = $value;
                }
                elseif($level == 3)
                {
                    $value['str'] = '&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;'.'└ ';
                    $menus[$size]['list'][] = $value;
                }
                else
                {
                    $value['str'] = '&emsp;&emsp;'.'└ ';
                    $menus[$size]['list'][] = $value;
                }
                
                $this->menulist($menu,$value['id'],$value['level']);
            }
        }
        return $menus;
    }


    /**
     * 管理员角色删除
     * @return [type] [description]
     */
    public function adminCateDelete()
    {
        if($this->request->isAjax()) {
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if($id > 0) {
                if($id == 1) {
                    return $this->error('超级管理员角色不能删除');
                }
                if(false == Db::name('dsp_cate')->where('id',$id)->delete()) {
                    return $this->error('删除失败');
                } else {
                    return $this->success('删除成功','dsp/admin/adminCate');
                }
            } else {
                return $this->error('id不正确');
            }
        }
    }
}
