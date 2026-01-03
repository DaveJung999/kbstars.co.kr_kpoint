<?
//=======================================================
// 설  명 : 회원 가입 폼(join.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
//=======================================================
$HEADER = array();
$HEADER['priv']		= '회원'; // 인증유무 (비회원,회원,운영자,서버관리자)
$HEADER['goto_nopriv']	= '/'; 
$HEADER['private']	= '1'; // 브라우저 캐쉬
$HEADER['useSkin']	= 1; // 템플릿 사용
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

//$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
//$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once('config.php');	// $dbinfo 가져오기
	
	$table_seller = $SITE['th'] . "seller";
	// 이미 가입신청되었는지 체크
	$sql = "select * from $table_seller where bid='{$_SESSION['seUid']}'";
	$rs=db_query($sql);
	if(db_count($rs)) back('이미 판매자 가입 신청이 되었습니다.\\n판매자페이지로 이동합니다.','/sshop2/scm');

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file('skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate('skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
$form_default = " method='post' action='{$thisUrl}join_sellerok.php'  ENCTYPE='multipart/form-data'>";
$form_default .= href_qs("mode=write",'mode=',1);
$form_default = substr($form_default,0,-1);
$tpl->set_var('form_default',	$form_default);

// 마무리
$tpl->echoHtml($dbinfo, $SITE);
?>