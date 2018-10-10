#!/usr/bin/php
<?php
try
{
	date_default_timezone_set('Asia/Shanghai');

	$dbh = new PDO('mysql:host=127.0.0.1;dbname=ssp', 'root', 'BE#AO9X#zspa9wRW', array(PDO::MYSQL_ATTR_INIT_COMMAND=>'set names utf8'));

	$sql = 'UPDATE ssp_creative SET material_status=1,material=? WHERE ID=?';

	$sth = $dbh->prepare($sql);

	$sql = 'SELECT id,material FROM ssp_creative WHERE material_status = 0';
	foreach($dbh->query($sql) as $row)
	{
		$filename=[];
		$pic = json_decode($row['material'],1);

		$interact = isset($pic['interact']) ? $pic['interact'] : 0;

		if($interact==0){
				$main_pic = $pic['image']['main']['url'];
				$main_filename = pathinfo($main_pic)['basename'];
				$main_dir = pathinfo($main_pic)['dirname'];
				$day = substr($main_dir,-8);

				$land_pic = $pic['image']['land']['url'];
				$land_filename = pathinfo($land_pic)['basename'];

				//本地图片路径
				$base = "/data/wxmssp/public/uploads/admin/admin_thumb/".$day.'/';

				$main = $base.$main_filename;

				$land = $base.$land_filename;

				$mkdir= "ssh optz@106.75.66.177 'mkdir -p /var/current/zyz/cdn/wxmssp/$day'";

				exec($mkdir); 

				$main_dst = 'optz@106.75.66.177:/var/current/zyz/cdn/wxmssp/'.$day.'/'.$main_filename;

				$land_dst = 'optz@106.75.66.177:/var/current/zyz/cdn/wxmssp/'.$day.'/'.$land_filename;

				$main_cmd = "scp $main $main_dst";

				$land_cmd = "scp $land $land_dst";

				exec($main_cmd);

				exec($land_cmd);

				$json_data = [
						'image'=>[
							'main'=>['url'=>"http://cdn.optaim.com/wxmssp/".$day.'/'.$main_filename],
							'land'=>['url'=>"http://cdn.optaim.com/wxmssp/".$day.'/'.$land_filename],
							'icon'=>['url'=>''],
		                    'deep'=>['url'=>'']
						],
						'text'=>[
		                    'title'=>'',
		                     'desc'=>''
		                ],
		                'land'=>'',
		                'pages'=>'',
		                'interact'=>$interact
				];
			}else{

				//主图
				$main_pic = $pic['image']['main']['url'];
				$main_filename = pathinfo($main_pic)['basename'];
				$main_dir = pathinfo($main_pic)['dirname'];
				$main_day = substr($main_dir,-8);

				$base = "/data/wxmssp/public/uploads/admin/admin_thumb/".$main_day.'/';
				$main = $base.$main_filename;

				$mkdir= "ssh optz@106.75.66.177 'mkdir -p /var/current/zyz/cdn/wxmssp/$main_day'";

				exec($mkdir);
				$main_dst = 'optz@106.75.66.177:/var/current/zyz/cdn/wxmssp/'.$main_day.'/'.$main_filename;
				$main_cmd = "scp $main $main_dst";
				exec($main_cmd);
				//跳转图片
				$deep_pic = $pic['image']['deep']['url'];
				$deep_filename = pathinfo($deep_pic)['basename'];
				$deep_dir = pathinfo($deep_pic)['dirname'];
				$deep_day = substr($deep_dir,-8);

				$base = "/data/wxmssp/public/uploads/admin/admin_thumb/".$deep_day.'/';
				$deep = $base.$deep_filename;

				$mkdir= "ssh optz@106.75.66.177 'mkdir -p /var/current/zyz/cdn/wxmssp/$deep_day'";

				exec($mkdir);
				$deep_dst = 'optz@106.75.66.177:/var/current/zyz/cdn/wxmssp/'.$deep_day.'/'.$deep_filename;
				$deep_cmd = "scp $deep $deep_dst";
				exec($deep_cmd);
				//icon图片
				$icon_pic = $pic['image']['icon']['url'];
				$icon_filename = pathinfo($icon_pic)['basename'];
				$icon_dir = pathinfo($icon_pic)['dirname'];
				$icon_day = substr($icon_dir,-8);

				$base = "/data/wxmssp/public/uploads/admin/admin_thumb/".$icon_day.'/';
				$icon = $base.$icon_filename;

				$mkdir= "ssh optz@106.75.66.177 'mkdir -p /var/current/zyz/cdn/wxmssp/$icon_day'";

				exec($mkdir);
				$icon_dst = 'optz@106.75.66.177:/var/current/zyz/cdn/wxmssp/'.$icon_day.'/'.$icon_filename;
				$icon_cmd = "scp $icon $icon_dst";
				exec($icon_cmd);

				$json_data = [
	                    'image'=>[
	                        'main'=>['url'=>"http://cdn.optaim.com/wxmssp/".$main_day.'/'.$main_filename],
	                        'land'=>['url'=>''],
	                        'icon'=>['url'=>"http://cdn.optaim.com/wxmssp/".$icon_day.'/'.$icon_filename],
	                        'deep'=>['url'=>"http://cdn.optaim.com/wxmssp/".$deep_day.'/'.$deep_filename]
	                    ],
	                    'text'=>[
	                        'title'=>isset($pic['text']['title']) ? $pic['text']['title'] :'',
	                        'desc'=>''//$insert_data['desc']
	                    ],
	                    'land'=>isset($pic['land']) ? $pic['land'] :'',
	                    'pages'=>isset($pic['pages']) ? $pic['pages'] :'',
	                    'interact'=>$interact
	                ];

			}
		$material = json_encode($json_data);
		$sth->bindValue(1, $material,PDO::PARAM_STR);
		$sth->bindValue(2, $row['id'],PDO::PARAM_INT);
		$sth->execute();
	}
}
catch(Exception $e)
{
	echo $e->getMessage().PHP_EOL;
}