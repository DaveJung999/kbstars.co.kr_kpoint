<?php
//=======================================================
// 설	명 : 게시판 글읽기(read.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/08/25 박선민 마지막 수정
// 2025/08/13 Gemini	 PHP 7.x, MariaDB 11.x 환경에 맞춰 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'version' => 1,
	'useClassTemplate' =>	1, // 템플릿 사용
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	'html_echo' => 2	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

// Ready.. . (변수 초기화 및 넘어온값 필터링)
$timestamp_today=mktime(0, 0, 0, date('m'), date('d'), date('Y'));

$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];

if($db){
	if (preg_match("/^as$|[^a-z0-9_\-]/i", $db)){
		back("table값이 잘못되었습니다");
		exit;
	}
}
$table=$SITE['th'] . "board_" . $db;	//???_board_$dbz

// 기본 Query
$basic_href = "db={$db}".			//table 이름
				"&mode=".		// read.php에선 당연히 값이 없어야 될 것임
				"&cateuid={$cateuid}".		//category
				"&pern={$pern}" .				// 페이지당 표시될 게시물 수
				"&sc_column={$sc_column}".	//search column
				"&sc_string={$sc_string}".	//search string
				"&page={$page}";				//현재 페이지

// Start...
// 테이블 정보 확인
$rs = db_query("SELECT * FROM {$SITE['th']}board2info WHERE db = '". db_escape($db) ."'" );
$dbinfo = db_count() ? db_array($rs) : back("사용하지 않는 게시판입니다.");

// 조회수 증가
db_query("update {$table} set hit=hit +1, hitip='". db_escape($_SERVER['REMOTE_ADDR']) ."' where uid='". db_escape($uid) ."' and hitip<>'". db_escape($_SERVER['REMOTE_ADDR']) ."'");

// 해당 게시물 불러들임
$result=db_query("SELECT * from {$table} WHERE uid='". db_escape($uid) ."'");
$list = db_count() ? db_array($result) : back("게시물이 존재하지 않습니다.");
$list = change_magic_quotes($list,"strip");
$list['rdate'] = date("Y년 m월 d일 H시 i분", $list['rdate']);

// 잠시 고침
//$pos = get_pos($table, $list['num']);	// 이전, 다음 게시물 구하기
//$cur_page = get_current_page($table, $list['num'], $list['reno'], $board_info['pern']);	// 현재 페이지 구하기

// userid에 email링크 삽입
$list['userid'] = $list['email'] ? "<a href='mailto:{$list['email']}' title='{$list['email']}'>{$list['userid']}</a>" : $list['userid'];
$list['title'] = htmlspecialchars($list['title']);
$list['file_name'] = $list['file_name'] ? "<a href='./upload/{$table}/{$list['file_name']}'><img src='images/save.gif' border=0>({$list['file_size']} byte)</a>" : "";
$list['content'] = replace_string($list['content'], $list['docu_type']);	// 문서 형식에 맞추어서 내용 변경

// 허가에 따른 버튼 출력
$href_list = "./list.php?" . href_qs("",$basic_href);
$href_write = isset($dbinfo['enable_write']) ? "./write.php?" . href_qs("mode=write&uid={$list['uid']}&num={$list['num']}",$basic_href) : "#";
$href_reply = isset($dbinfo['enable_reply']) ? "./write.php?" . href_qs("mode=reply&uid={$list['uid']}&num={$list['num']}",$basic_href) : "#";
$href_modify = isset($dbinfo['enable_modify']) ? "./write.php?" . href_qs("mode=modify&uid={$list['uid']}&num={$list['num']}",$basic_href) : "#";
$href_delete = isset($dbinfo['enable_delete']) ? "./ok.php?" . href_qs("mode=delete&uid={$list['uid']}",$basic_href) : "#";
$href_vote = isset($dbinfo['enable_vote']) ? "#" : "#";
// 이전 다음 게시물 타이틀 구하기
$prev_result = db_query("SELECT title FROM {$table} where num = {$list['num']} + 1 and reno = 0 ");
$next_result = db_query("SELECT title FROM {$table} where (num = {$list['num']} - 1) and (reno = 0) ");

$title_prev = db_count($prev_result) ? htmlspecialchars(db_result($prev_result,0,"title")) : "";
$title_next = db_count($next_result) ? htmlspecialchars(db_result($next_result,0,"title")) : "";

// 템플릿 기반 웹 페이지 제작
/*
PHP : /board/read.php
tpl : read.htm
매크로
SITE_HEAD	: 사이트 기본 해더 HTML
SITE_TAIL	: 사이트 기본 테일 HTML

//해당 게시물 보기
LIST_UID	: 해당 게시물의 고유넘버
LIST_NUM	: 해당 게시물 실제 번호
LIST_TITLE	: 해당 게시물 제목
LIST_USERID	: 해당 게시물 올린이 아이디
LIST_NAME	: 해당 게시물 올린이 이름
LIST_EMAIL	: 해당 게시물 올린이 메일
LIST_CONTENT : 해당 게시물 내용
LIST_RDATE	: 해당 게시물 올린 시간
LIST_HIT	: 해당 게시물 방문자수
// 아래부분
HREF_WRITE		: 글쓰기 링크
HREF_REPLAY		: 답변하기 링크
HREF_MODIFY		: 수정하기 링크
HREF_DELETE		: 글 삭제하기 링크
HREF_VOTE		: 추천하기 링크
HREF_PREV		: 이전게시물 이동 링크
HREF_NEXT		: 다음게시물 이동 링크
TITLE_PREV		: 이전게시물 제목
TITLE_NEXT		: 다음게시물 제목

구문
noresult		: 게시물이 하나도 없을때
yesresult		: 게시물이 있을 경우
noresult_prev	: 게시물이 하나도 없을때
yesresult_prev	: 게시물이 있을 경우
noresult_next	: 게시물이 하나도 없을때
yesresult_next	: 게시물이 있을 경우

[주의점]
- 그림 파일은 필히 "images/???"가 되어야함..
"images/를 프로그램을 통해 정확한 디렉토리로 치환하기 때문....
*/
$tpl= new FastTemplate(dirname(__FILE__) . "/stpl/{$dbinfo['skin']}");

$tpl->define(array('base' => 	"read.htm"));
$tpl->define_dynamic('yesresult','base');
$tpl->define_dynamic('noresult','base');
$tpl->define_dynamic('yesresult_prev','base');
$tpl->define_dynamic('noresult_prev','base');
$tpl->define_dynamic('yesresult_next','base');
$tpl->define_dynamic('noresult_next','base');

// 이전 게시물이 있다면...
if($title_prev){
	//$tpl->parse('YESRESULT_PREV','yesresult_prev');
	$tpl->parse('NORESULT_PREV','noresult_prev');
	$tpl->assign(array( NORESULT_PREV =>	"" ));
}
else { //없다면
	$tpl->parse('YESRESULT_PREV','yesresult_prev');
	//$tpl->parse('NORESULT_PREV','noresult_prev');
	$tpl->assign(array( 'YESRESULT_PREV' =>	"" ));
}

// 다음 게시물이 있다면
if($title_next){
	//$tpl->parse('YESRESULT_NEXT','yesresult_next');
	$tpl->parse('NORESULT_NEXT','noresult_next');
	$tpl->assign(array( NORESULT_NEXT =>	"" ));
}
else { //없다면
	$tpl->parse('YESRESULT_NEXT','yesresult_next');
	//$tpl->parse('NORESULT_NEXT','noresult_next');
	$tpl->assign(array( 'YESRESULT_NEXT' =>	"" ));
}

// 템플릿 마무리 할당
$tpl->assign(array( 'SITE_HEAD' =>	$SITE['head'],
					'SITE_TAIL' =>	$SITE['tail'],
					'HREF_LIST' =>	$href_list,
					'HREF_WRITE' =>	$href_write,
					'HREF_REPLY' =>	$href_reply,
					'HREF_MODIFY' =>	$href_modify,
					'HREF_DELETE' =>	$href_delete,
					'HREF_VOTE' =>	$href_vote,
					'HREF_PREV' =>	$href_prevpage,
					'HREF_NEXT' =>	$href_nextpage,
					'TITLE_PREV' =>	$uview['title'],
					'TITLE_NEXT' =>	$dview['title'],
					'LIST_UID' =>	$list['uid'],
					'LIST_NUM' =>	$list['num'],
					'LIST_TITLE' =>	$list['title'],
					'LIST_USERID' =>	$list['userid'],
					'LIST_NAME' =>	$list['name'],
					'LIST_EMAIL' =>	$list['email'],
					'LIST_CONTENT' =>	$list['content'],
					'LIST_RDATE' =>	$list['rdate'],
					'LIST_HIT' =>	$list['hit']
				));
$tpl->parse( 'BASE', 'base');
//$tpl->FastPrint('BASE');
$makehtml=$tpl->fetch('BASE');
$val="\"./stpl/{$dbinfo['skin']}/images/";
$makehtml = preg_replace("/([\"|\'])images\//i","{$val}",$makehtml);
echo $makehtml;
?>
