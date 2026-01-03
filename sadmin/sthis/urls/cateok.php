<?php
//=======================================================
// 설  명 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/10/14 박선민 마지막 수정
// 25/11/10 Gemini AI PHP 7+ 호환성 수정 (mysql_* -> db_*, 변수 중괄호 {}, 탭 변환)
//=======================================================	
$HEADER=array(
	'priv'		=>'운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
		'usedb2'	=> 1, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $_SERVER['HTTP_HOST']); // [!] FIX: $HTTP_HOST -> $_SERVER['HTTP_HOST']

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
	$table = "urls";
	
	// 카테고리 테이블 구함
	$sql_where=" 1 ";
	switch( $dbinfo['cate_table'] ) {
		case "" :
			$table_cate=$table;
			break;
		case "this" :
			$table_cate=$table;
			$sql_where=" type='cate' ";
			break;
		default :
			$table_cate=$table . "_" . $dbinfo['cate_table'];
	}

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($mode) {
	case 'catewrite' :
		cateWriteOK();
		go_url("./cate.php?db={$db}&cateuid={$cateuid}");
		break;
	case 'catemodify' :
		cateModifyOK();
		go_url("cate.php?db={$db}");	
		break;
	case 'catedelete' :
		cateDeleteOK();
		go_url("cate.php?db={$db}");
		break;		
	default :
		back("잘못된 웹페이지에 접근하였습니다.");
}

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
// 카테고리 추가 부분
function cateWriteOK() {
	GLOBAL $dbinfo, $table_cate;
	
	$qs	= array(
				'cateuid'	=> "post,trim",
				'url'		=> "post,trim",
				'title'	=> "post,trim,notnull"
			);
	$qs=check_value($qs);

	// 해당 url이 선 등록되어 있는지 체크
	if($qs['url']) {
		$rs_url_exist=db_query("SELECT * from {$table_cate} where url='{$qs['url']}'");
		if(db_count()) {
			$url_exist=db_array($rs_url_exist);
			back("해당 URL이 이미 등록되어 있습니다.\\n제목: {$url_exist['title']}");
		}		
	} // end if

	if($qs['cateuid']){ // 서브카테고리 추가인경우
		$rs = db_query("SELECT * from {$table_cate} where uid='{$qs['cateuid']}'");
		$list = db_count() ? db_array($rs) : back("해당 부모 카테고리가 없습니다.");
		$qs['num']=$list['num'];
		$qs['re'] =getCateRe($table_cate,$list['num'],$list['re']);

		// [!] FIX: $table_cate, $qs 변수 중괄호 {} 적용
		$sql="INSERT INTO {$table_cate} SET num='{$qs['num']}',re='{$qs['re']}',title='{$qs['title']}',
												url='{$qs['url']}'";
	}
	else { // 탑카테고리 추가인경우
		// [!] FIX: $table_cate 변수 중괄호 {} 적용
		$max = db_result(db_query("SELECT MAX(num) as num FROM {$table_cate}"), 0, "num") + 1;
		// [!] FIX: $table_cate, $max, $qs 변수 중괄호 {} 적용
		$sql="INSERT INTO {$table_cate} SET num={$max}, title='{$qs['title']}',
												url='{$qs['url']}'";
	} // end if .. else ..

	if($dbinfo['cate_table']=="this") $sql .= ", type='cate'";
	
	db_query($sql);
	return db_insert_id();
}

// 카테고리 수정 부분
function cateModifyOK(){
	GLOBAL $dbinfo, $table_cate;

	$qs	= array(
				'cateuid'	=> "post,trim,notnull",
				'title'	=> "post,trim,notnull",
				'url'		=> "post,trim",
			);
	$qs=check_value($qs);

	// [!] FIX: $table_cate, $qs 변수 중괄호 {} 적용
	$sql="update {$table_cate} SET title='{$qs['title']}', 
								url='{$qs['url']}'";

	if($dbinfo['cate_table']=="this") $sql .= " WHERE type='cate' and uid={$qs['cateuid']}";
	else $sql .= " WHERE uid={$qs['cateuid']}";

	db_query($sql);
	return true;
}

// 카테고리 삭제부분
function cateDeleteOK(){
	GLOBAL $dbinfo, $table, $table_cate;
	
	$qs	= array(
				'cateuid'		=> "get,trim,notnull",
			);
	$qs=check_value($qs);

	// [!] FIX: $table_cate, $qs 변수 중괄호 {} 적용
	$rs_cateinfo = db_query("SELECT * from {$table_cate} WHERE uid='{$qs['cateuid']}'");
	$cateinfo= db_count() ? db_array($rs_cateinfo) : back("이미 삭제되었거나 삭제할 데이터가 없습니다.");

	// 하위 카테고리 uid,toreno 구함
	$subcate_uid[]=$cateinfo['uid'];
	if($dbinfo['cate_table']=="this")
		// [!] FIX: $table_cate, $cateinfo 변수 중괄호 {} 적용, re like 절에 따옴표 추가
		$sql="SELECT * from {$table_cate} WHERE type='cate' and num={$cateinfo['num']} and re like '{$cateinfo['re']}%'";
	else 
		// [!] FIX: $table_cate, $cateinfo 변수 중괄호 {} 적용, re like 절에 따옴표 추가
		$sql="SELECT * from {$table_cate} WHERE num={$cateinfo['num']} and re like '{$cateinfo['re']}%'";
	$rs2 = db_query($sql);
	for($i=0;$i<db_count($rs2);$i++) {
		$subcate_uid[] = db_result($rs2,$i,"uid");
	}
	
	// SQL문 where부분 만들기
	$sql_cates_where = " ( cateuid in (" . implode(",",$subcate_uid) . ") )	";

	// 해당 카테고리의 DB 데이터가 있다면 삭제못함
	if($dbinfo['cate_table']) {
		if($dbinfo['cate_table']=="this")
			// [!] FIX: $table_cate, $sql_cates_where 변수 중괄호 {} 적용
			$sql="select count(*) as count from {$table_cate} where type='docu' and {$sql_cates_where}";
		else
			// [!] FIX: $table, $sql_cates_where 변수 중괄호 {} 적용
			$sql="select count(*) as count from {$table} where {$sql_cates_where}";

		if((int)db_result(db_query($sql),0,"count")) {
			back("해당 카테고리와 관련된 DB 데이터가 있습니다.\\n해당 데이터를 먼저 삭제하시기 바랍니다.");
		}
	}

	// 해당 카테고리 삭제
	if($dbinfo['cate_table']=="this")
		// [!] FIX: $table_cate, $cateinfo 변수 중괄호 {} 적용, re like 절에 따옴표 추가
		$sql="DELETE FROM {$table_cate} WHERE type='cate' and num={$cateinfo['num']} and re like '{$cateinfo['re']}%'";
	else
		// [!] FIX: $table_cate, $cateinfo 변수 중괄호 {} 적용, re like 절에 따옴표 추가
		$sql="DELETE FROM {$table_cate} WHERE num={$cateinfo['num']} and re like '{$cateinfo['re']}%'";
	db_query($sql);
	
	// 카테고리값 시프트
	if(strlen($cateinfo['re']))
		// [!] FIX: $table_cate, $cateinfo 변수 중괄호 {} 적용, re like 절에 따옴표 추가
		$sql="update {$table_cate} SET re=concat( substring(re,1,length('{$cateinfo['re']}')-1), char(ord(substring(re,length('{$cateinfo['re']}'),1))-1 ), substring(re,length('{$cateinfo['re']}')+1) ) where num='{$cateinfo['num']}' and re like '" . substr($cateinfo['re'],0,-1) . "%' and strcmp(re,'{$cateinfo['re']}')>= 0";
	else 
		// [!] FIX: $table_cate, $cateinfo 변수 중괄호 {} 적용
		$sql="update {$table_cate} SET num=num-1 where num > {$cateinfo['num']}";
	db_query($sql);
	
	return true;
}



function getCateRe($table_cate, $num, $re) {
	GLOBAL $dbinfo;
	if($dbinfo['cate_table']=="this")
		// [!] FIX: $table_cate, $num, $re 변수 중괄호 {} 적용, re like 절에 따옴표 추가
		$sql="SELECT re, right(re,1) FROM {$table_cate} WHERE type='cate' and num='{$num}' AND length(re)=length('{$re}')+1 AND locate('{$re}', re)=1 ORDER BY re DESC LIMIT 1";
	else
		// [!] FIX: $table_cate, $num, $re 변수 중괄호 {} 적용, re like 절에 따옴표 추가
		$sql="SELECT re, right(re,1) FROM {$table_cate} WHERE num='{$num}' AND length(re)=length('{$re}')+1 AND locate('{$re}', re)=1 ORDER BY re DESC LIMIT 1";

	$rs = db_query($sql); // [!] FIX: @mysql_query -> db_query
	$row = @db_row($rs); // [!] FIX: @mysql_fetch_array -> @db_row
	@db_free($rs); // [!] FIX: 결과 세트 해제
	
	if($row) {
		$ord_head = substr($row['0'],0,-1);
		$ord_foot = chr(ord($row['1']) + 1);
		$re = $ord_head . $ord_foot;
	}
	else {
		$re .= "1";
	}
	return $re;
}