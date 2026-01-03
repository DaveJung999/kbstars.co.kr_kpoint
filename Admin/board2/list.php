<?php
//=======================================================
// 설	명 : 게시판 종합관리(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/09/30
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 02/09/30 박선민 마지막 수정
// 03/08/25 박선민 마지막 수정
// 24/05/20 Gemini PHP 7 마이그레이션
// 25/09/08 Gemini UI 개선 (링크 -> 버튼 변경)
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp' => 1,
	'useSkin' =>	1,
	'useBoard2' => 1,
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
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
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'sdate', 'edate', 'search', 'pay_cate', 'term_id', 'act', 'email', 'idnum', 'userid', 'level', 'tel', 'priv', 'total_num'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

$qs_basic = "db={$db}".					//table 이름
			"&mode=".					// mode값은 list.php에서는 당연히 빈값
			"&cateuid={$cateuid}".		//cateuid
			"&pern={$pern}" .			// 페이지당 표시될 게시물 수
			"&sc_column={$sc_column}".	//search column
			"&sc_string=" . urlencode(stripslashes($sc_string)) . //search string
			"&page={$page}";				//현재 페이지

$table=$SITE['th'] . "board2info";	//게시판 관리 테이블
$thisPath	= dirname(__FILE__);

// 관리자페이지 환경파일 읽어드림
$sql = "select * from {$SITE['th']}admininfo where skin='{$SITE['th']}' or skin='basic' order by uid DESC";
$rs = db_query($sql);
$pageinfo = db_count($rs) ? db_array($rs) : back("관리자페이지 환경파일을 읽을 수가 없습니다");

// URL Link
$href['myadmin_insert']="/sadmin/myadmin264/tbl_change.php?lang=ko&server=1&table={$table}&goto=" . urlencode("/sadmin/board/list.php?db=board2info");

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// Boardinfo를 읽어드림
$tpl = new phemplate("","remove_nonjs");
if( !is_file("{$thisPath}/stpl/basic_skin/list.htm") ) $dbinfo['skin']="basic_skin";
$tpl->set_file('html',"{$thisPath}/stpl/basic_skin/list.htm",TPL_BLOCK);

$sql = "SELECT * FROM {$table} ORDER BY db, title";
$rs = db_query($sql);
$total = db_count($rs);	// 읽혀진 게시물의 총 갯수

for($i=0; $i<$total; $i++) {
	$list = db_array($rs);

	$list['ad_num'] = $i + 1;

	// 게시판 관련 테이블 자동 생성
	$table_name = ($SITE['th'] ?? '').'board2_'.($list['db'] ?? '');
	
	if(!userCreateByTableinfo('board2', $table_name)){
		echo "{$table_name} 게시판 테이블 생성 중 문제 발생";
		exit;
	}
	if(($list['enable_cate'] ?? null) == 'Y' && !userCreateByTableinfo("board2_cate", $table_name."_cate")){
		echo "{$table_name}_cate 게시판 카테고리 테이블 생성 중 문제 발생";
		exit;
	}
	if(($list['enable_memo'] ?? null) == 'Y' && !userCreateByTableinfo("board2_memo", $table_name."_memo")){
		echo "{$table_name}_memo 게시판 메모 테이블 생성 중 문제 발생";
		exit;
	}
	if(($list['enable_readlog'] ?? null) == 'Y' && !userCreateByTableinfo("board2_readlog", $table_name."_readlog")){
		echo "{$table_name}_readlog 게시판 읽기 로그 테이블 생성 중 문제 발생";
		exit;
	}

	$list['rdate'] = date("Y/m/d", (int)$list['rdate']);
	$list['title'] = $list['title'] ? htmlspecialchars($list['title']) : "제목없음…";

	// URL Link...
	$href['myadmin_update'] = "setup.php?db={$list['db']}&in_mode=basic";
	$href['go'] = "/sboard2/list.php?db={$list['db']}";
	$href['myadmin_delete'] = "ok.php?mode=delete&uid={$list['uid']}";

	// 해당 게시판의 게시물 수
	$list['count_table'] = db_resultone("select count(*) as count from {$SITE['th']}board2_{$list['db']}", 0, "count");
	$list['count_yesterday_table'] = db_resultone("select count(*) as count from {$SITE['th']}board2_{$list['db']} where rdate > (UNIX_TIMESTAMP() - 86400)", 0, "count");

	// ★★★★★ 버튼으로 변경된 부분 ★★★★★
	// 1. 카테고리 관리 버튼
	if(($list['enable_cate'] ?? null) != 'N') {
		$list['s_menu'] = "<input type='button' value='카테고리' class='CCbox03' style='cursor:pointer;' onclick=\"location.href='/sboard2/cate.php?db={$list['db']}'\">";
	} else {
		// 카테고리 사용 안함일 경우 버튼을 비활성화
		$list['s_menu'] = "없음";
	}


	// 2. 미리보기 링크
	$list['go'] = "<a href='{$href['go']}' target='_blank'>{$list['title']}</a>";

	// 3. 수정 버튼
	$list['up_menu'] = "<input type='button' value='수정' class='CCbox03' style='cursor:pointer;' onclick=\"location.href='{$href['myadmin_update']}'\">";

	// 4. 삭제 버튼
	$delete_onclick_js = "if(confirm('게시판 설정 정보만 삭제됩니다 (게시물 테이블은 삭제되지 않습니다).\\n정말 삭제하시겠습니까?')) { location.href='{$href['myadmin_delete']}'; }";
	$list['del_menu'] = "<input type='button' value='삭제' class='CCbox03' style='cursor:pointer;' onclick=\"{$delete_onclick_js}\">";
	// ★★★★★ 변경 완료 ★★★★★

	$tpl->set_var('list',$list);
	$tpl->process('LIST','list', TPL_APPEND);
} // end for

// 템플릿에 페이지 정보 변수 전달
$tpl->set_var('pageinfo', $pageinfo);
$tpl->set_var('href', $href);

// 템플릿 출력
// $val 변수가 정의되지 않아 추가
$val = "\\1stpl/basic_skin/images/";
echo preg_replace("/([\"|\'])images\//", $val, $tpl->process('', 'html', TPL_OPTIONAL));
	
## User functions.. . (사용자 함수 정의)
// 테이블 존재유무 확인
function mysql_table_exists($table) {
	// 기존 로직보다 더 효율적인 방식으로 수정
	$rs = db_query("SHOW TABLES LIKE '{$table}'");
	$rs_cnt = db_count($rs);
	
	return $rs_cnt > 0;
} // end func

// 테이블이 존재하지 않을 경우 admin_tableinfo 테이블정보대로 table생성
function mysql_table_create($table,$createtable){
	global $SITE;

	// 테이블이 이미 존재하는지 먼저 확인
	if (mysql_table_exists($createtable)) {
		return 1; // 이미 존재하므로 성공으로 간주하고 종료
	}
	
	$sql = "select sql_syntax from {$SITE['th']}admin_tableinfo where table_name='{$table}'";
	$rs=db_query($sql);

	if(db_count($rs)){
		$sql_syntax = db_result($rs, 0, "sql_syntax");
		$sql="CREATE TABLE {$createtable} ({$sql_syntax}) ;";

		if(@db_query($sql))
			return 1;
		else // 생성에 실패한 경우
			return -1;
	} else {
		return 0;
	}
} // end func

// 테이블이 존재하지 않을 경우 admin_tableinfo 테이블정보대로 table생성
// 03/12/15
function userCreateByTableinfo($table, $createtable){
	global $SITE;

	// 테이블이 이미 존재하는지 먼저 확인합니다.
	if (mysql_table_exists($createtable)) {
		return 1; // 이미 존재하면 성공으로 간주하고 함수를 즉시 종료합니다.
	}
	
	$sql = "select `sql_syntax`,`comment` from {$SITE['th']}admin_tableinfo where table_name='{$table}'";

	if($tableinfo=db_arrayone($sql)){
	
		$sql="CREATE TABLE {$createtable} ({$tableinfo['sql_syntax']})";
		$sql .= " COMMENT='{$tableinfo['comment']}'";
		if(@db_query($sql))
			return 1;
		else // 생성에 실패한 경우
			return -1;
	}
	else return 0;
} // end func
?>