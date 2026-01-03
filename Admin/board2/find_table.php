<?php
//=======================================================
// 설	명 : 회원 아이디 존재 유무 체크(/sjoin/certifyid.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/08/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 02/08/14 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
		'version' => 1,
	'html_echo' => 2	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
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
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table			= $SITE['th'] . "board2info";
	$dbinfo['skin']	= "basic";
$thisPath	= dirname(__FILE__);

	// 넘어온 값 체크
	if(!$_GET['db']) back_close("게시판 아이디 값이 넘어오지 않았습니다.");

	// 등록을 제한하는 아이디 리스트

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
$tpl = new phemplate("","remove_nonjs");
if( !is_file("{$thisPath}/stpl/basic_skin/list.htm") ) $dbinfo['skin']="basic_skin";
$tpl->set_file('html', "{$thisPath}/stpl/basic_skin/find_table.htm",1); // here 1 mean extract blocks

$rs_logon = db_query("SELECT uid FROM {$table} WHERE db='{$_GET['db']}'");

if(db_count()){
	$tpl->set_var("MSG","닫기");
	$tpl->process("result","userid_use");
}
else { // DB에 등록되지 않았다면
	$tpl->set_var("MSG","신청");
	$tpl->process("result","userid_nouse");
} // end if

// 마무리
$val="\\1stpl/{$dbinfo['skin']}/images/";
echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); ?>
