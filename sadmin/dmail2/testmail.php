<?php
//=======================================================
// 설 명 : 메일 리스트 편집
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/12/01
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/12/01 박선민 처음
// 24/05/21 Gemini PHP 7 마이그레이션
//=======================================================
$HEADER = array (
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용		
);
require(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '' . "/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$urlprefix	= ""; // ???list.php ???write.ephp ???ok.php
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함
	

	global $SITE;
	$table_dmailinfo = $SITE['th'] . "dmailinfo";

	// $dbinfo 가져오기
	include_once("{$thisPath}/config.php");

	$sql = "SELECT * FROM {$table_dmailinfo} WHERE db='" . (isset($_REQUEST['db']) ? $_REQUEST['db'] : '') . "'";
	$dmailinfo = db_arrayone($sql) or back("db값이 넘어오지 않았습니다");
	
	$form_default = " method='post' action='testmailok.php' >
<input type='hidden' name='db' value='" . (isset($_REQUEST['db']) ? $_REQUEST['db'] : '') . "'>";
	
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.(isset($dbinfo['skin']) ? $dbinfo['skin'] : 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.(isset($dbinfo['skin']) ? $dbinfo['skin'] : 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
$tpl->set_var('list'		,	isset($dmailinfo) ? $dmailinfo : null);
$tpl->set_var("form_default",	$form_default);

// 마무리
$tpl->echoHtml((isset($dbinfo) ? $dbinfo : null), $SITE, $thisUrl);
?>
