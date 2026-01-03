<?php
//=======================================================
// 설	명 : 심플리스트
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useApp' => 1, // cut_string()
	'useBoard2' => 1, // board2Count()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
$sql = "select * from kbsavers.new21_logon";
$rs = db_query($sql);
while($logon = db_array($rs)){
	$userinfo = db_arrayone("select * from kbsavers.new21_userinfo where bid='{$logon['uid']}'");
	
	if($logon['class'] == 'person') $logon['priv']='회원';
	if($logon['class'] == 'root') $logon['priv']='회원,운영자,root';

	$sql="INSERT INTO kbsavers2.`new21_logon` SET
		`uid`		='{$logon['uid']}',
		`userid`	='{$logon['userid']}',
		`passwd`	='{$logon['passwd']}',
		`name`		='{$logon['name']}',
		`nickname`	='{$logon['nickname']}',
		`email`		='{$logon['email']}',
		`yesmail`	='{$logon['yesmail']}',
		`emailcheck`	='{$logon['emailcheck']}',
		`priv`		='{$logon['priv']}',
		`level`		='{$logon['level']}',
		`tel`		='{$userinfo['tel']}',
		`hp`		='{$userinfo['hp']}',
		`country`	='{$userinfo['country']}',
		`idnum`		='{$userinfo['idnum']}',
		`birth`		='{$userinfo['birth']}',
		`wedding`	='{$userinfo['wedding']}',
		`zip`		='{$userinfo['zip']}',
		`address`	='{$userinfo['address']}',
		`region`	='{$userinfo['region']}',
		`homepage`	='{$userinfo['homepage']}',
		`business`	='{$userinfo['business']}',
		`position`	='{$userinfo['position']}',
		`job`		='{$userinfo['job']}',
		`company`	='{$userinfo['company']}',
		`orders`	='{$userinfo['orders']}',
		`recommender`	='{$userinfo['recommender']}',
		`p_name`	='{$userinfo['p_name']}',
		`p_idnum`	='{$userinfo['p_idnum']}',
		`p_tel`		='{$userinfo['p_tel']}',
		`c_confirm`	='{$userinfo['c_confirm']}',
		`c_num`		='{$userinfo['c_num']}',
		`c_name`	='{$userinfo['c_name']}',
		`c_owner`	='{$userinfo['c_owner']}',
		`c_zip`		='{$userinfo['c_zip']}',
		`c_address`	='{$userinfo['c_address']}',
		`c_kind`	='{$userinfo['c_kind']}',
		`c_detail`	='{$userinfo['c_detail']}',
		`rdate`		='{$userinfo['rdate']}',
		`mdate`		='{$userinfo['mdate']}',
		`ip`		='{$userinfo['ip']}',
		`host`		='{$userinfo['host']}',
		`open`		='{$userinfo['open']}',
		`intro`		='{$userinfo['intro']}',
		`content`	='{$userinfo['content']}',
		`comment`	='{$userinfo['comment']}'
		";	
	
	echo $sql;
	if($_REQUEST['mode'] == 'ok') db_query($sql);
} 

?>
