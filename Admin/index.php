<?php
//=======================================================
// 설	명 : 메인 첫 페이지 샘플(/index_basic.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/19
// Project: sitePHPbasic
// ChangeLog
//	 DATE	 수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/07/19 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'	=>"운영자,사진관리자,경기관리자,이벤트관리자,뉴스관리자,주니어관리자,포인트관리자", // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'html_echo'	=>0, // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
	'html_skin'	=>"" // html header 파일(/stpl/basic/index_$HEADER['html'].php 파일을 읽음)
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

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

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
//print_r($_SESSION);exit;

	if ($_SESSION['sePriv']['이벤트관리자']){
		$left_menu = "/scate/?db=site_kbevent";
		$title_cate = "이벤트관리자";
	}
	if ($_SESSION['sePriv']['게임관리자']){
		$left_menu = "/scate/?db=site_kbgame";
		$title_cate = "게임관리자";
	}
	if ($_SESSION['sePriv']['사진관리자']){
		$left_menu = "/scate/?db=site_kbphoto";
		$title_cate = "사진관리자";
	}
	if ($_SESSION['sePriv']['뉴스관리자']){
		$left_menu = "/scate/?db=site_kbnews";
		$title_cate = "뉴스관리자";
	}
	if ($_SESSION['sePriv']['운영자']){
		$left_menu = "/scate/?db=site_admin";
		$title_cate = "운영자";
	}
?>
<html>
<head>
	<title>::: KB STARS 관리자페이지 :::</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<frameset rows="71,*" frameborder="no" border="0" framespacing="0">
	<frame src="top.php" name="topFrame" scrolling="No" noresize="noresize" id="topFrame" />
	<frameset cols="250,*" frameborder="no" border="0" framespacing="0">
		<frame name="leftFrame" src="<?=$left_menu;?>" scrolling='auto'>
		<frame name="mainFrame" src="blank.php" scrolling='auto'>
	</frameset>
</frameset>
<noframes><body>
</body>
</noframes>
</html>



