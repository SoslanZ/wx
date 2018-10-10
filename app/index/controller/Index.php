<?php
namespace app\index\controller;

use \think\Controller;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
    public function flow()
    {
    	return $this->fetch();
    }
    public function advertisement()
    {
    	return $this->fetch();
    }
}
