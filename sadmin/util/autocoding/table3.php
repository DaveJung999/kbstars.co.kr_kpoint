<?php
//=======================================================
// 설  명 : 테이블을 기반으로한 자동 코딩(table.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/12/26
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/10/08 박선민 처음제작
// 03/12/26 박선민 수정
// 25/11/07 Gemini AI PHP 7+ 호환성 수정 (mysql_* -> db_*, 변수 중괄호 {}, 탭 변환)
//=======================================================
$HEADER=array(
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck'=>1, // check_value()
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
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
$SITE['database'] = "secret";
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 테이블리스트구하기
$tables = db_tablelist($SITE['database']);
foreach($tables as $value) {
	if($value==$_GET['table'])
		$tablelist .="\n<option value='{$value}' selected>{$value}</option>";
	else
		$tablelist .="\n<option value='{$value}'>{$value}</option>";
}
if(!$_GET['key']) $_GET['key']="uid";
?>
<form method=get action=<?=$_SERVER['PHP_SELF'] ?>>
<input type=hidden name=mode value='ok'>
테이블이름:<select name=table><?=$tablelist ?></select><br>
PRIMARY KEY:<input type=text size=20 name=key value="<?=$_GET['key']
?>">
<input type=submit value="자동코딩">
</form>
<hr>
<?php
if(!$_GET['mode']=="ok") exit;

// ok.php 자동코딩
ok_php();

// write.php 자동코딩
write_php();

function ok_php() {
	global $fields;
	$nowdate = date("y/m/d");
	$fields	= userTablelist($GLOBALS['SITE']['database'],$_GET['table']);
	foreach($fields as $key => $value) {
		if($value==$_GET['key']) unset($fields[$key]);
	} // end foreach

	
?>
	<TABLE WIDTH=0 BORDER=0 CELLPADDING=3 CELLSPACING=1 ALIGN=CENTER BGCOLOR=GRAY>
		<TR ALIGN=LEFT BGCOLOR=EEEEEE>
		<TD><FONT SIZE=2 COLOR=RED STYLE='font-family: fixedsys; font-size:12pt; font-color:red;'>(form name=gsearch, action=/sadmin/member/groupsearch.php)</TD>
		</TD>
		<TR ALIGN=LEFT BGCOLOR=FFFFFF>
		<TD><PRE><FONT SIZE=2 STYLE='font-family: fixedsys; font-size:8pt;'>
&lt;?php
//=======================================================
// 설  명 : 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: <?=$nowdate
?> 
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// <?=$nowdate
?> 박선민 처음제작
// <?=$nowdate
?> 박선민 마지막수정
//=======================================================
$HEADER=array(
	'priv'		=>'', // 인증유무 (0:모두에게 허용)
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck'=>1, // check_value()
	'useBoard2'=>1, // boardAuth()
	'useApp'	=>1, // remote_addr()
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

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 기본 URL QueryString
	$qs_basic = "";

	$table		= $SITE['th'] . "<?=$_GET['table']
?>";

	// dbinfo 설정
	$dbinfo=array(
				'priv_write'	=> 1,
				'priv_delete'	=> 99
			);
	/* dbinfo 테이블을 사용한다면
	$table_dbinfo	= $SITE['th'] . "boardinfo";
	// boardinfo 테이블 정보 가져와서 $dbinfo로 저장
	if($_REQUEST['db']) {
		$sql = "SELECT * from {$table_dbinfo} WHERE db='{$_REQUEST['db']}'";
		$dbinfo=db_arrayone($sql) or back("사용하지 않은 DB입니다.");

		$table	= "{$SITE['th']}board_" . $dbinfo['table_name']; // 게시판 테이블

		// 업로드 기본 디렉토리
		//$dbinfo['upload_dir'] = trim($dbinfo['upload_dir']) ? trim($dbinfo['upload_dir']) . "/{$SITE['th']}board_{$dbinfo['db']}" : dirname(__FILE__) . "/upload/{$SITE['th']}board_{$dbinfo['db']}";
	}
	else back("DB 값이 없습니다");
	*/

	// 공통적으로 사용할 $qs
	$qs=array(
<?php
	$i=0;
	foreach($fields as $value) {
		if(sizeof($fields)==1 or $i==sizeof($fields)-1) 
			echo "			\"{$value}\"".((strlen($value)<4)?"\t\t":"\t")."=> \"post,trim\"\n";
		else
			echo "			\"{$value}\"".((strlen($value)<4)?"\t\t":"\t")."=> \"post,trim\",\n";
		$i++;
	}
?>
		);
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']) {
	case 'write':
		$uid = write_ok($table,$qs);
		go_url($_REQUEST['goto'] ? $_REQUEST['goto'] : "read.php?" . href_qs("uid={$uid}",$qs_basic));
		break;
	case 'modify':
		modify_ok($table,$qs,"<?=$_GET['key']
?>");
		go_url($_REQUEST['goto'] ? $_REQUEST['goto'] : "read.php?" . href_qs("uid={$_REQUEST['uid']}",$qs_basic));
		break;
	case 'delete':
		delete_ok($table,"<?=$_GET['key']
?>");
		go_url($_REQUEST['goto'] ? $_REQUEST['goto'] : "./list.php?" . href_qs("",$qs_basic));
		break;	
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function write_ok($table,$qs)
{
	GLOBAL $dbinfo;
	// 권한체크
	if(!boardAuth($dbinfo, "priv_write")) back("추가 권한이 없습니다");

	//$qs['userid']	= "post,trim";

	// 넘어온값 체크
	$qs=check_value($qs);

	// 값 추가

	// $sql 완성
	$sql_set	= ""; // $sql_set 시작
	$sql="insert into {$table} SET
<?php
	$i=0;
	$set_clauses = []; // 1. 빈 배열 생성

	foreach($fields as $value) {
		// 2. 탭 정렬을 포함한 할당 문자열을 만듦
		$alignment = ((strlen($value)<6)?"\t\t":"\t");
		$set_clauses[] = "				`{$value}`{$alignment}='{\$qs['f_{$value}']}'";
	}

	// 3. 배열의 모든 요소를 ",\n" (콤마 + 줄바꿈)으로 결합하여 한 번에 출력
	echo implode(",\n", $set_clauses) . "\n";
?>
				{$sql_set}
		";
	db_query($sql);

	return db_insert_id();
} // end func write_ok

function modify_ok($table,$qs,$field)
{
	GLOBAL $dbinfo;

	$qs["$field"]	= "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다");
	// 넘어온값 체크
	$qs=check_value($qs);

	// 값 추가

	// 해당 데이터 읽기
	$sql_where	= " 1 "; // $sql_where 시작
	// [!] FIX: WHERE 절 불필요한 따옴표 제거
	$sql = "SELECT * FROM {$table} WHERE {$field}={$qs[$field]} and  $sql_where ";
	if( !$list=db_arrayone($sql) )
		back("해당 데이터가 없습니다");

	// 권한체크
	if(!boardAuth($dbinfo, "priv_delete")) {
		if($list['bid']!=$_SESSION['seUid']) back("수정 권한이 없습니다");
	}

	// $sql 완성
	$sql="update {$table}	SET
<?php
	$i=0;
	$set_clauses = []; // 1. 빈 배열 생성

	foreach($fields as $value) {
		// 2. 탭 정렬을 포함한 할당 문자열을 만듦
		$alignment = ((strlen($value)<6)?"\t\t":"\t");
		$set_clauses[] = "				`{$value}`{$alignment}='{\$qs['f_{$value}']}'";
	}

	// 3. 배열의 모든 요소를 ",\n" (콤마 + 줄바꿈)으로 결합하여 한 번에 출력
	echo implode(",\n", $set_clauses) . "\n";
?>
			WHERE
				{$field}={$qs[$field]} // [!] FIX: WHERE 절 불필요한 따옴표 제거
			AND
				 $sql_where 
		";
	db_query($sql);

	return db_count();
} // end func modify_ok

function delete_ok($table,$field)
{
	GLOBAL $dbinfo;
	$qs=array(
			"$field"	=> "request,trim,notnull=" . urlencode("고유넘버가 넘어오지 않았습니다.")
		);
	// 넘오온값 체크
	$qs=check_value($qs);

	// 해당 데이터 읽기
	$sql_where	= " 1 "; // $sql_where 시작
	// [!] FIX: WHERE 절 불필요한 따옴표 제거
	$sql = "SELECT * FROM {$table} WHERE {$field}={$qs[$field]} and  $sql_where ";
	if( !$list=db_arrayone($sql) )
		back("해당 데이터가 없습니다");

	// 권한체크
	if(!boardAuth($dbinfo, "priv_delete")) {
		if($list['bid']!=$_SESSION['seUid']) back("삭제 권한이 없습니다");
	}

	// [!] FIX: WHERE 절 불필요한 따옴표 제거
	db_query("DELETE FROM {$table} WHERE {$field}={$qs[$field]} AND  $sql_where ");

	return db_count();
} // end func delete_ok
?&gt;
		</FONT></PRE>
		</TD>
		</TR>
	</TABLE><BR><BR>
<?php
} // end func ok_php




function write_php() {
	$nowdate = date("y/m/d");
	
?>
	<TABLE WIDTH=0 BORDER=0 CELLPADDING=3 CELLSPACING=1 ALIGN=CENTER BGCOLOR=GRAY>
		<TR ALIGN=LEFT BGCOLOR=EEEEEE>
		<TD><FONT SIZE=2 COLOR=RED STYLE='font-family: fixedsys; font-size:12pt; font-color:red;'>(form name=gsearch, action=/sadmin/member/groupsearch.php)</TD>
		</TD>
		<TR ALIGN=LEFT BGCOLOR=FFFFFF>
		<TD><PRE><FONT SIZE=2 STYLE='font-family: fixedsys; font-size:8pt;'>
&lt;?php
//=======================================================
// 설  명 : 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: <?=$nowdate
?> 
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// <?=$nowdate
?> 박선민 처음제작
// <?=$nowdate
?> 박선민 마지막수정
//=======================================================
$HEADER=array(
	'priv'		=>'', // 인증유무 (0:모두에게 허용)
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck'=>1, // check_value()
	'useBoard2'=>1, // boardAuth()
	'useApp'	=>1, // remote_addr()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
page_security("", $_SERVER['HTTP_HOST']);

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
// Start... (DB 작업 및 display)
//=======================================================
<?php
	$inputs = userInputfield2($_GET['table']);
	unset($inputs[$_GET['key']]);
	if(sizeof($inputs))
		foreach($inputs as $value)
			echo "&lt;input type=text",htmlspecialchars($value,ENT_QUOTES),"&gt\n";
?>
?&gt;
		</FONT></PRE>
		</TD>
		</TR>
	</TABLE><BR><BR>
<?php
} // end func write_php

function userTablelist($database="",$table) {
	GLOBAL $SITE;
	if(!$database) $database = $SITE['database'];

	$aColumn = array();

	// [!] FIX: mysql_list_fields, mysql_num_fields, mysql_field_name을 SHOW COLUMNS로 대체
	$safe_table = '`' . str_replace('`', '', $table) . '`';
	$safe_db = '`' . str_replace('`', '', $database) . '`';
	
	$sql = "SHOW COLUMNS FROM {$safe_table} IN {$safe_db}";
	$result = db_query($sql);
	if(!$result) return false;

	while ($row = db_array($result)) {
		$aColumn[] = $row['Field'];
	}
	db_free($result);

	return $aColumn;
}


function userInputfield2($table,$list="php") {
	// [!] FIX: mysql_query, mysql_num_rows, mysql_fetch_array -> db_query, db_count, db_array
	$table_def = db_query("SHOW FIELDS FROM {$table}");
	$fields_cnt	 = db_count($table_def);
	$inputfield = array();

	for ($i = 0; $i < $fields_cnt; $i++) {
		$row_table_def	= db_array($table_def);
		$field			= $row_table_def['Field'];
		$type	= preg_replace('~\\(.*~', '', $row_table_def['Type']);
		if(preg_match("/char|int/i", $type)) {
			$len	= preg_replace('/.*\\(([0-9]+)\\).*/', '$1', $row_table_def['Type']);
			if(is_array($list)) {
				$data	= htmlspecialchars($list[$field]);
			}
			elseif($list=="php") {
				// [!] FIX: $data에 사용할 PHP 코드 문자열 수정
				$data	= "<?php echo \$list['{$field}'] ?>";
				//$data	= htmlspecialchars($data);
			}
			else {
				if (isset($row_table_def['Default'])) {
					//$data = $row_table_def['Default'];
					$data = htmlspecialchars($row_table_def['Default']);
				}
			} // end if.. else ..

			if ($len < 4) {
				$fieldsize = $maxlength = 4;
			} else {
				$fieldsize = (($len > 40) ? 40 : $len);
				$maxlength = $len;
			} // end if.. else ..

			$inputfield[$field]=" name='f_{$field}' value='{$data}' size={$fieldsize} maxlength={$maxlength}";
		} //end if
	} // end for
	db_free($table_def);

	return $inputfield;
} // end func inputfield
?>