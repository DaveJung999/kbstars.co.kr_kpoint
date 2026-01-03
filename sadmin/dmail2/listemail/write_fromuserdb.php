<?php
//=======================================================
// 설 명 : 회원db에서 email추출하여 메일 삽입
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/05/04
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/05/04 박선민 마지막 수정
// 25/08/13 Gemini PHP 7+ 호환성 및 보안 강화
//=======================================================
$HEADER=array(
	'priv'		 =>	'운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'	 =>	1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin'	 =>	1, // 템플릿 사용
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
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
	// GET 파라미터를 안전하게 받습니다.
	$db = $_GET['db'] ?? null;
	if (empty($db)) {
		back('db값이 넘어오지 않았습니다. err 23');
	}

$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// $dbinfo 가져오기
	include_once("{$thisPath}/config.php");

	// htmlspecialchars를 사용하여 XSS 공격 방지
	$safe_db = htmlspecialchars($db, ENT_QUOTES, 'UTF-8');
	$form_default = " method='post' action='ok.php'>
					<input type='hidden' name='db' value='{$safe_db}'>
					<input type='hidden' name='mode' value='writefromuserdb'>";
	// substr() 불필요하게 제거

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$skinfile = basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) {
	$dbinfo['skin'] = 'basic';
}
$tpl = new phemplate($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html', $skinfile, TPL_BLOCK);


// 템플릿 마무리 할당
$tpl->set_var("form_default", $form_default);
$tpl->set_var('get', $_GET);

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>