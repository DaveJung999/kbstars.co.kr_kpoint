<?php
//=======================================================
// 설	명 : 게시판 카테고리 소트(catesort.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/08
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/10/08 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
		usedb2	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		useSkin =>	1, // 템플릿 사용
		useBoard => 1,
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
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// table
	$table_groupinfo= $SITE['th'] . "groupinfo";
	$table_joininfo	= $SITE['th'] . "joininfo";
	$table_joininfo_cate= $SITE['th'] . "joininfo_cate";
	
	$dbinfo = array (
			skin 			 =>	'basic',
			table 			 =>	$table_joininfo,
			table_cate 		 =>	$table_joininfo_cate,
			sql_where		 =>	" gid='{$_REQUEST['gid']}' ",
			sql_where_cate	 =>	" gid='{$_REQUEST['gid']}' ",
			);

	// 해당 그룹에 가입되어 있지 않다면 볼 수 없슴
	$sql = "SELECT * from {$table_groupinfo} where uid='{$_REQUEST['gid']}'";
	$groupinfo	= db_arrayone($sql) or back_close("해당 그룹은 존재하지 않습니다.");

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 할당
$tpl->set_var('groupinfo',$groupinfo);
$form_default = "method=post action='groupok.php' >";
$form_default .= substr(href_qs("gid={$groupinfo['uid']}&mode=gjoininfo_levelchange&uids={$_REQUEST['uids']}",'gid=',1),0,-1);
$tpl->set_var('form_default', $form_default);

// 오픈창으로 뜨니깐, 사이트 헤더테일 넣지 않고 바로
// 마무리
$val="\\1{$thisUrl}/skin/{$dbinfo['skin']}/images/";
echo preg_replace("~([\"'])images/~", "\1{$val}", $tpl->process('', 'html', TPL_OPTIONAL));
?>
