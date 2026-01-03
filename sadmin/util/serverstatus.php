<?php
//=======================================================
// 설  명 : 개발환경과 구동환경 비교(serverstatus.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/10/01
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02/10/01 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		=>'운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'usedb2'	=>0 // DB 커넥션 사용 (0:미사용, 1:사용)
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================
?>
<html>
<body>
<h1 align="center">This Server Status</h1>
<table align="center">
<tr>
	<td> </td>
	<td> 개발 환경 </td>
	<td> 현재 서버 환경 </td>
</tr>
<?php
function v($name, $test, $now){
	echo ("
		<tr>
			<td>$name</td>
			<td>$test</td>
	");
	
	if(is_array($now)) $now=join($now,"<br>");

	if($test==$now)
		echo "			<td>{$now}</td>";
	else 
		echo "			<td><font color=red>{$now}</font></td>";
	echo "		</tr>";
}

v("Server","Apache/1.3.26 (Unix) PHP/4.2.3",$SERVER_SOFTWARE);
v("phpversion","4.2.3",phpversion());
v("zend version","1.2.0",zend_version());
v("gpc_order","GPC",get_cfg_var("gpc_order"));
v("magic_quotes_gpc", 1,get_magic_quotes_gpc());
v("magic_quotes_runtime", 0,get_magic_quotes_runtime());
v("max_execution_time",30,get_cfg_var("max_execution_time"));
v("post_max_size","10M",get_cfg_var("post_max_size"));
v("upload_max_filesize","10M",get_cfg_var("upload_max_filesize"));
v("register_globals",1,get_cfg_var("register_globals"));
v("Session Support",1,extension_loaded("session"));
v("Mcrypt Support",1,extension_loaded("mcrypt"));
v("Session Auto_start",0,get_cfg_var("session.auto_start"));
v("MySQL Support",1,extension_loaded("mysql"));
v("Gd Support",1,extension_loaded("gd"));
v("모듈 List","xml<br>sysvshm<br>sysvsem<br>standard<br>session<br>posix<br>pcre<br>mysql<br>mbstring<br>mailparse<br>gd<br>ftp<br>zlib<br>Zend Optimizer<br>apache",get_loaded_extensions());
//print_r(ini_get_all());
?>
</table>
</body>
</html>
