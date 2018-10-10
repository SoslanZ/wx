<?php
//admin模块公共函数
/**
 * 管理员密码加密方式
 * @param $password  密码
 * @param $password_code 密码额外加密字符
 * @return string
 */
function password($password, $password_code='lshi4AsSUrUOwWV')
{
    return md5(md5($password) . md5($password_code));
}

/**
 * 管理员操作日志
 * @param  [type] $data [description]
 * @return [type]       [description]
 */
function addlog($operation_id='')
{
   return true;
	
}


/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}
function getSkey(){
	$charid = strtoupper(md5(uniqid(mt_rand(), true)));
	return substr($charid, 0, 8) . substr($charid, 8, 4);
}
function endecodeUserId($string, $action = 'encode') {
    $startLen = 13;
    $endLen = 8;

    $coderes = '';
    #TOD 暂设定uid字符长度最大到9
    if ($action=='encode') {
        $uidlen = strlen($string);
        $salt = 'yourself_code';
        $codestr = $string.$salt;
        $encodestr = hash('md4', $codestr);
        $coderes = $uidlen.substr($encodestr, 5,$startLen-$uidlen).$string.substr($encodestr, -12,$endLen);
        $coderes = strtoupper($coderes);
    }elseif($action=='decode'){
        $strlen = strlen($string);
        $uidlen = $string[0];
        $coderes = substr($string, $startLen-$uidlen+1,$uidlen);
    }
    return  $coderes;
}
