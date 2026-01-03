<?php
//=======================================================
// 설	명 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/08/14 박선민 마지막 수정
//=======================================================
$HEADER=array(
		'usedb2' =>  1,
		'useSkin' =>  1, // 템플릿 사용
	);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$thisPath		= dirname(__FILE__);
	$thisUrl		= "."; // 마지막 "/"이 빠져야함
	include_once("{$thisPath}/config.php");	// $dbinfo 가져오기
	
	$table_payment		= $SITE['th'] . "payment";	// 지불 테이블
	$table_companyinfo	= $SITE['th'] . "companyinfo";	// 회사정보테이블

	// 넘어온값 체크
	if(!$_GET['rdate']) back_close('중요한 값이 넘어오지 않았습니다.');
	
	// 수정모드라면
	if($_GET['mode'] == "modify"){
		$sql = "SELECT * from {$table_companyinfo} where bid='{$_SESSION['seUid']}' and c_num ='{$_GET['c_num']}'";
		$list = db_arrayone($sql) or back_close('회사 정보가 없습니다.');

		$form_default = " method='post' action='{$thisUrl}/comok.php'>";
		$form_default .= href_qs("mode=cominfo_modify&rdate={$_GET['rdate']}","mode=",1);
		$form_default = substr($form_default,0,-1);		
	} else {
		$form_default = " method='post' action='{$thisUrl}/comok.php'>";
		$form_default .= href_qs("mode=cominfo_write&rdate={$_GET['rdate']}","mode=",1);
		$form_default = substr($form_default,0,-1);
	}
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
$tpl->set_var("form_default",	$form_default);

// 마무리
$val="\\1{$thisUrl}/skin/{$dbinfo['skin']}/images/";
echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html', TPL_OPTIONAL)); 
?>
