<?php
//=======================================================
// 설	명 : 회원 정보 수정 처리(/smember/profile.ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/02
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/11/24 박선민 추가수정
// 04/07/02 박선민 회원정보 변경에 따른 회원정도 세션값도 변경
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
		usedb2	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1, // 값 체크함수
		useBoard => 1, // 보드관련 함수 포함
	'useApp'	 => 1,
	'useClassSendmail' =>	1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $HTTP_HOST);

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
	$table			= $SITE['th'] . "logon";	// 회원 아이디/패스워드 테이블
	$table_logon	= $SITE['th'] . "logon";
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출

switch($_REQUEST['mode']) {
	case 'joinout':
		joinout();
		go_url("$Action_domain/sadmin/member/msearch.php",0,"회원 탈퇴가 정상적으로 처리되었습니다.");
		break;
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================


// 삭제
function joinout() {
	Global $table;

	$rs=db_query("DELETE FROM {$table} WHERE uid='{$_POST['uid']}' ");
	if(!db_count())	back("회원 본인 확인을 실패하였습니다. 확인 바랍니다.");
} // end func.
?>