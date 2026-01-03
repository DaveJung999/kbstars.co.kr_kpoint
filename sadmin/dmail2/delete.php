<?php
//=======================================================
// 설	명 : 게시판 삭제 비밀번호 입력 페이지(delete.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/12
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/10/12 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
	'useBoard2' => 1, // 보드관련 함수 포함
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

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$urlprefix	= ""; // ???list.php ???write.ephp ???ok.php
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// 2 . 기본 URL QueryString
	$qs_basic = "mode=&limitno=&limitrows=";
	if($_GET['getinfo'] != "cont") 
		$qs_basic .= "&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=";
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화

	// 3 . $dbinfo 가져오기
	include_once("{$thisPath}/config.php");

	$form_default	= " ACTION='{$thisUrl}/{$urlprefix}ok.php' method='POST'>";
	if($_GET['mode'] == "memo") // memo 삭제 요청의 경우(하지만 메모는 로그인한사람만 쓸 수 있음)
		$form_default.= substr(href_qs("mode=memodelete",$qs_basic,1),0,-1);
	else 
		$form_default.= substr(href_qs("mode=delete",$qs_basic,1),0,-1);
	
	// URL Link..
	$href["list"] = "{$thisUrl}/{$urlprefix}list.php?" . href_qs("",$qs_basic);

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
$tpl->set_var('form_default',$form_default);
$tpl->set_var('get'				,$_GET);	// get값으로 넘어온것들
$tpl->set_var('dbinfo'			,$dbinfo);	// dbinfo 정보 변수
$tpl->set_var('href'			,$href);

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
