<?php
//=======================================================
// 설	명 : 관리자페이지 카테고리 처리(cateok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/07/23
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/07/23 박선민 마지막 수정
// 24/08/12 Gemini	PHP 7.x, MariaDB 호환성 업데이트 
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1,
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'sdate', 'edate', 'search', 'pay_cate', 'term_id', 'act', 'email', 'idnum', 'userid', 'level', 'tel', 'priv', 'total_num'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	
	global $conn, $SITE;

	$table = $SITE['th'] . "admin_menu";

	// 카테고리 테이블 구함
	$sql_where=" 1 ";
	switch( $dbinfo['cate_table'] ?? '' ) {
		case "" :
			$table_cate=$table;
			break;
		case "this" :
			$table_cate=$table;
			$sql_where=" type='cate' ";
			break;
		default :
			$table_cate=$table . "_" . ($dbinfo['cate_table'] ?? '');
	}

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출

switch($mode) {
	case 'catewrite' :
		$cateuid = cateWriteOK();
		echo "
				<script language='javascript'>
				<!--				
						parent.frames.sadminleft.location.reload();	
				//-->
				</script>
		";
		go_url("./cate.php?db=$db&cateuid=$cateuid");
		break;
	case 'catemodify' :
		cateModifyOK();
		echo "
				<script language='javascript'>
				<!--				
						parent.frames.sadminleft.location.reload();	
				//-->
				</script>
		";
		go_url("cate.php?db=$db");	
		break;
	case 'catedelete' :
		cateDeleteOK();
		echo "
				<script language='javascript'>
				<!--				
						parent.frames.sadminleft.location.reload();	
				//-->
				</script>
		";
		go_url("cate.php?db=$db");
		break;		
	default :
		back("잘못된 웹페이지에 접근하였습니다.");
}


// 카테고리 추가 부분
function cateWriteOK() {
	GLOBAL $conn, $dbinfo, $table_cate;
	
	$qs	= array(
				'cateuid'	 =>	"post,trim",
				'url'		 =>	"post,trim",
				'title'	 =>	"post,trim,notnull"
			);
	$qs=check_value($qs);

	// SQL Injection 방지
	$cateuid_safe = db_escape($qs['cateuid']);
	$title_safe = db_escape($qs['title']);
	$url_safe = db_escape($qs['url']);

	if($qs['cateuid']){ // 서브카테고리 추가인경우
		$sql = "SELECT * FROM `{$table_cate}` WHERE uid='{$cateuid_safe}'";
		$result = db_query($sql);
		$list = $result && db_count($result) > 0 ? db_array($result) : back("해당 부모 카테고리가 없습니다.");
		
		$qs['num'] = $list['num'] ?? 0;
		$qs['re'] = getCateRe($table_cate, $list['num'] ?? 0, $list['re'] ?? '');

		$sql="INSERT INTO `{$table_cate}` SET num='{$qs['num']}',re='{$qs['re']}',title='{$title_safe}', url='{$url_safe}'";
	}
	else { // 탑카테고리 추가인경우
		$result = db_query("SELECT MAX(num) as num FROM `{$table_cate}`");
		$max_num = $result ? (int)(db_array($result)['num'] ?? 0) : 0;
		$max = $max_num + 1;
		$sql="INSERT INTO `{$table_cate}` SET num={$max}, title='{$title_safe}', url='{$url_safe}'";
	} // end if .. else ..

	if(($dbinfo['cate_table'] ?? '') =="this") $sql .= ", type='cate'";
	
	db_query($sql);
	return db_insert_id();
}

// 카테고리 수정 부분
function cateModifyOK(){
	GLOBAL $conn, $dbinfo, $table_cate;

	$qs	= array(
				'cateuid'	 =>	"post,trim,notnull",
				'title'	 =>	"post,trim,notnull",
				'url'		 =>	"post,trim",
			);
	$qs=check_value($qs);

	// SQL Injection 방지
	$cateuid_safe = db_escape($qs['cateuid']);
	$title_safe = db_escape($qs['title']);
	$url_safe = db_escape($qs['url']);

	$sql="UPDATE `{$table_cate}` SET title='{$title_safe}', url='{$url_safe}'";

	if(($dbinfo['cate_table'] ?? '') =="this") $sql .= " WHERE type='cate' AND uid={$cateuid_safe}";
	else $sql .= " WHERE uid={$cateuid_safe}";

	db_query($sql);
	return true;
}

// 카테고리 삭제부분
function cateDeleteOK(){
	GLOBAL $conn, $dbinfo, $table, $table_cate;
	
	$qs	= array(
				'cateuid'		 =>	"get,trim,notnull",
			);
	$qs=check_value($qs);

	$cateuid_safe = db_escape($qs['cateuid']);
	$rs_cateinfo = db_query("SELECT * FROM `{$table_cate}` WHERE uid='{$cateuid_safe}'");
	$cateinfo = $rs_cateinfo && db_count($rs_cateinfo) > 0 ? db_array($rs_cateinfo) : back("이미 삭제되었거나 삭제할 데이터가 없습니다.");

	// 하위 카테고리 uid 구함
	$subcate_uid=[$cateinfo['uid']];
	$re_safe = db_escape($cateinfo['re']);
	if(($dbinfo['cate_table'] ?? '') =="this")
		$sql="SELECT uid FROM `{$table_cate}` WHERE type='cate' AND num={$cateinfo['num']} AND re LIKE '{$re_safe}%' AND uid != {$cateinfo['uid']}";
	else
		$sql="SELECT uid FROM `{$table_cate}` WHERE num={$cateinfo['num']} AND re LIKE '{$re_safe}%' AND uid != {$cateinfo['uid']}";
	
	$rs2 = db_query($sql);
	if ($rs2) {
		while($row = db_array($rs2)) {
			$subcate_uid[] = $row['uid'];
		}
		db_free($rs2);
	}
	
	// SQL문 where부분 만들기
	$sql_cates_where = " ( cateuid IN (" . implode(",", $subcate_uid) . ") ) ";

	// 해당 카테고리의 DB 데이터가 있다면 삭제못함
	if(isset($dbinfo['cate_table'])) {
		if($dbinfo['cate_table']=="this")
			$sql="SELECT count(*) as count FROM `{$table_cate}` WHERE type='docu' AND {$sql_cates_where}";
		else
			$sql="SELECT count(*) as count FROM `{$table}` WHERE {$sql_cates_where}";

		$result = db_query($sql);
		$count = $result ? (int)(db_array($result)['count'] ?? 0) : 0;
		if($count > 0) {
			back("해당 카테고리와 관련된 DB 데이터가 있습니다.\\n해당 데이터를 먼저 삭제하시기 바랍니다.");
		}
	}

	// 해당 카테고리 삭제
	if(($dbinfo['cate_table'] ?? '') =="this")
		$sql="DELETE FROM `{$table_cate}` WHERE type='cate' AND num={$cateinfo['num']} AND re LIKE '{$re_safe}%'";
	else
		$sql="DELETE FROM `{$table_cate}` WHERE num={$cateinfo['num']} AND re LIKE '{$re_safe}%'";
	db_query($sql);
	
	// 카테고리값 시프트
	if(strlen($cateinfo['re'] ?? ''))
		$sql="UPDATE `{$table_cate}` SET re=CONCAT( SUBSTRING(re,1,LENGTH('{$re_safe}')-1), CHAR(ORD(SUBSTRING(re,LENGTH('{$re_safe}'),1))-1 ), SUBSTRING(re,LENGTH('{$re_safe}')+1) ) WHERE num='{$cateinfo['num']}' AND re LIKE '" . db_escape(substr($re_safe,0,-1)) . "%' AND re > '{$re_safe}'";
	else
		$sql="UPDATE `{$table_cate}` SET num=num-1 WHERE num > {$cateinfo['num']}";
	db_query($sql);
	
	return true;
}


## User Functions...
function getCateRe($table_cate, $num, $re) {
	GLOBAL $conn, $dbinfo;
	$re_safe = db_escape($re);
	$num_safe = (int)$num;

	if(($dbinfo['cate_table'] ?? '') =="this")
		$sql="SELECT re, RIGHT(re,1) as last_char FROM `{$table_cate}` WHERE type='cate' AND num='{$num_safe}' AND LENGTH(re)=LENGTH('{$re_safe}')+1 AND LOCATE('{$re_safe}', re)=1 ORDER BY re DESC LIMIT 1";
	else
		$sql="SELECT re, RIGHT(re,1) as last_char FROM `{$table_cate}` WHERE num='{$num_safe}' AND LENGTH(re)=LENGTH('{$re_safe}')+1 AND LOCATE('{$re_safe}', re)=1 ORDER BY re DESC LIMIT 1";

	$result = db_query($sql);
	$row = $result ? db_array($result) : null;
	if($row) {
		$ord_head = substr($row['re'],0,-1);
		$ord_foot = chr(ord($row['last_char']) + 1);
		$re = $ord_head . $ord_foot;
	}
	else {
		$re .= "1";
	}
	db_free($result);
	return $re;
}
?>