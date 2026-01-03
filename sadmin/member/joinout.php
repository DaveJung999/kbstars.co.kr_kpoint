<?php
//=======================================================
// 설	명 : 회원 탈퇴
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 처음
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useApp'	 => 1, // cut_string()
	'useBoard2'	 => 1, // board2Count()
	'useSkin'	 => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once($thisPath.'config.php');

	$table_logon	= $SITE['th'] . "logon";
	$table_account	= $SITE['th'] . "account";
	$table_accountinfo=$SITE['th']. "accountinfo";

	// URL Link..

	// 넘오온값 체크
	if(!$_GET['bid'] and !$_GET['userid']) back("회원 아이디, 혹은 회원 고유번호가 넘어와야 합니다.");

	if($_GET['bid']) $sql_where = " uid='{$_GET['bid']}' ";
	else $sql_where	= " userid='{$_GET['userid']}' ";

	$sql = "SELECT * from {$table_logon} WHERE  $sql_where ";
	$logon = db_arrayone($sql);
	if($logon['uid']) {
		$sql = "SELECT * from {$table_accountinfo} where bid='{$logon['uid']}'";
		$rs_accountinfo = db_query($sql);
		while($row=db_array($rs_accountinfo)) {
			$accountinfo .= "$row['accounttype']-{$row['accountno']}-잔액({$row['balance']}원)<br>";
		}
	}

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 할당
$tpl->set_var('logon',$logon);
$tpl->set_var('acccountinfo',$accountinfo);

// 템플릿 마무리 할당
$form_default = " method='POST' action='joinoutok.php'>";
$form_default .= substr(href_qs("uid={$logon['uid']}","uid=",1),0,-1);
$tpl->set_var('form_default',$form_default);

// 마무리
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));
?>