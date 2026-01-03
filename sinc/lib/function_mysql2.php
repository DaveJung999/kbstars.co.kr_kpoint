<?php
//=======================================================
// 설	명 : sitePHPbasic MySQL관련 함수(function_mysql2.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/12/29
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/08/07 박선민 db_resultone(), db_arrayone() 추가함
// 03/08/14 박선민 db_istable() 추가함
// 03/10/31 박선민 db_resultone(), db_arrayone()에서 테이블없을시 return false
// 03/11/22 박선민 bugfix - db_istable()
// 04/12/29 박선민 더블쿼터,배열변수 문법정리
// 24/05/18 Gemini	PHP 7 마이그레이션
// 25/09/08 Gemini $_DEBUG 변수 Notice 오류 수정
//=======================================================
/* 포함함수
	db_connect(String $server, String $user, String $pass)	// DB 커넥션함수
	db_select(String $name, [String $db_conn])	// 데이터베이스 선택
	db_close()				// 이하 미정리
	db_query()
	db_array()
	db_arrayone()
	db_row()
	db_result()
	db_resultone()
	db_count()
	db_free()
	db_tablelist()
	db_istable($table,$db='')
	db_escape($string)
	db_error($msg='DB에 이상이 있습니다.',$query)
	db_insert_id()
	// db_num_fields()
*/

// (이하 참고 주석 생략)

/*-------------------------------------------------------------------
	함수명		db_connect
	인자		$server	$user	$pass
	반환값		$db_conn
	수정일		02/06/21
	설명		MySQL Server에 접속을 시도한다.
-------------------------------------------------------------------*/
$db_lastsql='';
$db_conn = null;
function db_connect($server, $user, $pass=''){
	global $db_conn;
	// mysql_connect()가 PHP 7에서 제거되었으므로 mysqli_connect()로 변경
	$db_conn = mysqli_connect($server, $user, $pass) or db_error('DB 로그인을 실패하였습니다.\\n로긴 아이디와 패스워드를 확인해 보시기 바랍니다.');
	// UTF-8 문자셋 설정 추가
	mysqli_set_charset($db_conn, "utf8mb4");
//	mysqli_query($db_conn, "set names euckr");
//	mysqli_query($db_conn, "set names latin1_swedish_ci");
	return $db_conn;
}

/*-------------------------------------------------------------------
	함수명		db_select
	인자		$name	$db_conn
	반환값		-
	수정일		02/06/21
	설명		MySQL Server에서 $name란 DB를 선택한다.
-------------------------------------------------------------------*/
function db_select($name, $db_conn=''){
	if(empty($db_conn)){
		global $db_conn;
	}
	// mysql_select_db()가 PHP 7에서 제거되었으므로 mysqli_select_db()로 변경
	mysqli_select_db($db_conn, $name) or db_error('DB 선택을 실패하였습니다.');
	// UTF-8 문자셋 설정 추가
	mysqli_set_charset($db_conn, "utf8mb4");
	return true;
}

/*-------------------------------------------------------------------
	함수명		db_close
	인자		-
	반환값
	수정일		2000. 11. 15
	설명		MySQL Server의 접속을 끊는다.
-------------------------------------------------------------------*/
function db_close($db_conn=''){
	if(empty($db_conn)){
		global $db_conn;
	}
	// mysql_close()가 PHP 7에서 제거되었으므로 mysqli_close()로 변경
	if($db_conn) mysqli_close($db_conn);
}

/*-------------------------------------------------------------------
	함수명		db_query
	인자		$query	$msg
	반환값		$result
	수정일		2000. 08. 20
	설명		SQL문을 실행한다. 에러시 $msg를 출력
-------------------------------------------------------------------*/
function db_query($query, $errmsg='', $db_conn=''){
	global $db_lastsql;
	$db_lastsql=$query;

	if(empty($db_conn)){
		global $db_conn;		
	}
	
	// ★★★★★ 수정된 부분 ★★★★★
	// $_DEBUG 변수가 있는지 확인 후 사용
	if(!empty($_DEBUG)) {
		print_r("<b>- [db_query] query : </b>");
		print_r("\n<br>---------------------------------------------\n<br>");
		print_r($query);
		print_r("\n<br>\n<br>");
	}
	
	// mysql_query()가 PHP 7에서 제거되었으므로 mysqli_query()로 변경
	$result = mysqli_query($db_conn, $query);
	
	// $_DEBUG 관련 디버깅 코드는 유지
	if(!empty($_DEBUG)) {
		/*print_r("<b>- [mysql2] result : </b>");
		print_r("\n<br>---------------------------------------------\n<br>");
		print_r($result);
		print_r("\n<br>\n<br>");*/
	}
	
	if(!$result){
		// 오류 발생 시 SQL 쿼리와 오류 메시지를 아파치 로그에 기록
		error_log("DB Query Error: " . mysqli_error($db_conn) . " for SQL: " . $query);
		db_error($errmsg, $query);
	}

	return $result;
}

/*-------------------------------------------------------------------
	함수명		db_dbquery
	인자		$query	$msg
	반환값		$result
	수정일		2000. 08. 20
	설명		SQL문을 실행한다. 에러시 $msg를 출력
-------------------------------------------------------------------*/
function db_dbquery($db, $query, $errmsg='', $db_conn=''){
	global $db_lastsql;
	$db_lastsql=$query;

	if(empty($db_conn)){
		global $db_conn;
	}
	// mysql_db_query()는 PHP 5.3에서 제거되었으므로 mysqli_select_db()와 mysqli_query() 조합으로 변경
	if (!mysqli_select_db($db_conn, $db)){
		db_error("DB 선택을 실패하였습니다.", $query);
	}

	$result = mysqli_query($db_conn, $query);

	if($result === false){
		// 오류 발생 시 SQL 쿼리와 오류 메시지를 아파치 로그에 기록
		error_log("DB DbQuery Error: " . mysqli_error($db_conn) . " for SQL: " . $query);
		db_error($errmsg, $query);
	}

	return $result;
}

/*-------------------------------------------------------------------
	함수명		db_array
	인자		$result
	반환값		$arr
	수정일		2000. 08. 02
	설명		$result의 레코드를 하나 불러들여 배열로 저장한다.
				(배열의 키값은 필드 이름이다)
-------------------------------------------------------------------*/
function db_array(&$result){
	if($result && mysqli_num_rows($result) > 0){
		$arr=mysqli_fetch_assoc($result);
		return $arr;
	}
	else
		return false;
}

function db_arrayone($query,$errmsg='',$db_conn=''){
	global $db_lastsql;
	$db_lastsql = $query;

	if(empty($db_conn)){
		global $db_conn;
	}

	$result = mysqli_query($db_conn, $query);

	if(!$result){
		switch (mysqli_errno($db_conn)){
			case 1146 :
				return false;
				break;
			default :
				error_log("db_arrayone Error: " . mysqli_error($db_conn) . " for SQL: " . $query);
				db_error($errmsg, $query);
		}
	}

	if(mysqli_num_rows($result) > 0){
		$arr=mysqli_fetch_assoc($result);
		mysqli_free_result($result);
		return $arr;
	} else {
		if ($result) mysqli_free_result($result);
		return false;
	}
}

/*-------------------------------------------------------------------
	함수명		db_row
	인자		$result
	반환값		$arr
	수정일		2000. 08. 02
	설명		$result의 레코드를 하나 불러들여 배열로 저장한다.
				(배열의 키값은 숫자다)
-------------------------------------------------------------------*/
function db_row(&$result){
	if($result && mysqli_num_rows($result) > 0){
		$arr=mysqli_fetch_row($result);
		return $arr;
	}
	else
		return false;
}

/*-------------------------------------------------------------------
	함수명		db_result
	인자		$result	$row	$field
	반환값		$tmp
	수정일		2000. 09. 18
	설명		$row번째 레코드에서 $field의 값을 구한다.
				(필드 이름 또는 인덱스로 접근 가능하도록 수정)
-------------------------------------------------------------------*/
function db_result(&$result, $row, $field){
	if($result && mysqli_num_rows($result) > $row){
		mysqli_data_seek($result, $row);

		// $field가 문자열(필드 이름)인지 확인
		if (is_string($field)) {
			// 필드 이름으로 데이터를 가져옴
			$row_data = mysqli_fetch_assoc($result);
		} else {
			// 숫자 인덱스로 데이터를 가져옴
			$row_data = mysqli_fetch_row($result);
		}
		
		// 해당 키 또는 인덱스의 값을 반환 (없으면 null)
		return $row_data[$field] ?? null;
	}
	else {
		return false;
	}
}

function db_resultone($query, $row, $field, $errmsg='', $db_conn=''){
	global $db_lastsql;
	$db_lastsql = $query;

	if(empty($db_conn)){
		global $db_conn;
	}

	$result = mysqli_query($db_conn, $query);

	if(!$result){
		switch (mysqli_errno($db_conn)){
			// case 1054 : // no column
			case 1146 : // no table
				return false;
				break;

			default :
				error_log("db_resultone Error: " . mysqli_error($db_conn) . " for SQL: " . $query);
				db_error($errmsg, $query);
		}
	}
	
	// ★★★★★ 수정된 부분 ★★★★★
	// $_DEBUG 변수가 있는지 확인 후 사용
	if(!empty($_DEBUG)) {
		/*print_r("<b>- [db_resultone] query : </b>");
		print_r("\n<br>---------------------------------------------\n<br>");
		print_r($query);
		print_r("\n<br>\n<br>");

		print_r("<b>- [db_resultone] result : </b>");
		print_r("\n<br>---------------------------------------------\n<br>");
		print_r($result);
		print_r("\n<br>\n<br>");*/
	}
	
	if($result && mysqli_num_rows($result) > $row){
		mysqli_data_seek($result, $row);

		// $field가 문자열(필드 이름)인지 확인
		if (is_string($field)) {
			$row_data = mysqli_fetch_assoc($result);
		} else {
			$row_data = mysqli_fetch_row($result);
		}
		
		$tmp = $row_data[$field] ?? null;
		
		if(!empty($_DEBUG)) {
			/*print_r("<b>- [db_resultone] row_data : </b>");
			print_r("\n<br>---------------------------------------------\n<br>");
			print_r($row_data);
			print_r("\n<br>\n<br>");


			print_r("<b>- [db_resultone] tmp : </b>");
			print_r("\n<br>---------------------------------------------\n<br>");
			print_r($tmp);
			print_r("\n<br>\n<br>");*/
		}


		mysqli_free_result($result);
		return $tmp;
	} else {
		if ($result) mysqli_free_result($result);
		return false;
	}
}

/*-------------------------------------------------------------------
	함수명		db_count
	인자		$result
	반환값		$rows
	수정일		2000. 09. 18
	설명		$result란 쿼리에서 불러들인 레코드의 총 갯수를 구한다.
-------------------------------------------------------------------*/
function db_count($result=''){
	global $db_conn;
	// $result가 유효한 mysqli_result 객체인지 확인
	if ($result instanceof mysqli_result) {
		return mysqli_num_rows($result); // SELECT에서 사용
	} else {
		return mysqli_affected_rows($db_conn); // INSERT, UPDATE, or DELETE query
	}
}

/*-------------------------------------------------------------------
	함수명		db_free
	인자		$result
	반환값		$rows
	수정일		2000. 09. 18
	설명		$result란 쿼리에서 불러들인 레코드의 총 갯수를 구한다.
-------------------------------------------------------------------*/
function db_free(&$result){
	if ($result instanceof mysqli_result) {
		mysqli_free_result($result);
	}
}

/*-------------------------------------------------------------------
	함수명		db_tablelist
	인자
	반환값
	수정일		2003. 02. 28
	설명		테이블 list를 구하거나, 특정 테이블 제외혹은 특정테이블만 가져옮
-------------------------------------------------------------------*/
function db_tablelist($db='',$regex='',$un=''){
	global $db_conn;
	// mysql_list_tables()가 제거되었으므로 "SHOW TABLES" 쿼리로 변경
	$list = mysqli_query($db_conn, "SHOW TABLES FROM `{$db}`") or db_error('4. 테이블리스트를 가져오는데 실패하였습니다.');
	if (!$list){
		return [];
	}

	$no = mysqli_num_rows($list);
	$tables = [];
	for($i=0;$i<$no;$i++){
		$row = mysqli_fetch_row($list);
		$table = $row['0'];
		if($regex){
			// eregi()가 제거되었으므로 preg_match()로 변경
			if($un){
				if(!preg_match("/{$regex}/i",$table)) $tables[] = $table;
			} else {
				if(preg_match("/{$regex}/i",$table)) $tables[] = $table;
			}
		} else $tables[] = $table;
	}
	mysqli_free_result($list);

	sort($tables);
	return $tables;
}

/*
	테이블 존재 여부
	05/03/26 박선민 테이블이 없을 경우, 기존 다른 query가 날아가는 버그가 있어서 임시 수정
*/
function db_istable($table,$database=''){
	global $SITE, $db_conn;

	if(strpos($table,'.') !== false){
		$a = explode('.',$table);
		$table = $a['1'];
		$database = $a['0'];
	}
	elseif( $database == '' ){
		if(isset($SITE['database'])) $database = $SITE['database'];
		else back('본 사이트는 테이블유무 함수를 사용할 수 없게 되어있습니다');
	}

	$tablesResult = mysqli_query($db_conn, "SHOW TABLES FROM `{$database}` LIKE '{$table}'");
	if ($tablesResult && mysqli_num_rows($tablesResult) > 0){
		mysqli_free_result($tablesResult);
		return true;
	}

	if ($tablesResult){
		mysqli_free_result($tablesResult);
	}

	return false;
}

/*-------------------------------------------------------------------
	함수명		db_error
	인자		$msg
	반환값		-
	수정일		2000. 08. 02
	설명		$msg를 출력후 뒤로 이동
-------------------------------------------------------------------*/
function db_error($msg='DB에 이상이 있습니다.',$query=''){
	global $SITE, $db_lastsql, $db_conn;

	if(mysqli_errno($db_conn)){
		$debug_mode = !empty($SITE['debug']); // $SITE['debug'] 확인

		if($debug_mode){
			if(!$query) $query = $db_lastsql;
			$error_msg = mysqli_errno($db_conn) . ": " . mysqli_error($db_conn);
			echo ("
				MySQL Error : {$msg} <br>
				<b>Error Message</b>: {$error_msg} <br>
				<b>Last SQL Query</b>: {$query}
				");

			exit;
		} else {
			$error_no = mysqli_errno($db_conn);
			echo ("
					<script>
						window.alert('DB 작업 중 에러가 발견되었습니다.\\n계속 에러가 발생된다면 종합질문페이지에 문의바랍니다.\\n\\n(errno: {$error_no})');
						history.go(-1);
					</script>
				");
			exit;
		} // end if
	} // end if
} // end func db_error()

/*-------------------------------------------------------------------
	함수명		db_escape
	인자		string
	반환값		string
	수정일		2005/11/20
	설명		 SQL Injection 대비 mysqli_real_escape_string() 처리
-------------------------------------------------------------------*/
function db_escape($str) {
	global $db_conn;
	if ($db_conn) {
		return mysqli_real_escape_string($db_conn, $str);
	} else {
		// DB 연결이 없을 경우를 대비한 대체 처리
		return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $str);
	}
}

/*-------------------------------------------------------------------
	함수명		db_insert_id
-------------------------------------------------------------------*/
function db_insert_id() {
	global $db_conn;
	return mysqli_insert_id($db_conn);
}
/*-------------------------------------------------------------------
	함수명		db_num_fields
	인자		$result
	반환값		필드(컬럼) 개수
	수정일		25/11/10
	설명		쿼리 결과 세트의 필드(컬럼) 개수를 구한다. (mysql_num_fields 대체)
-------------------------------------------------------------------*/
function db_num_fields(&$result) {
	if ($result instanceof mysqli_result) {
		return mysqli_num_fields($result);
	}
	return false;
}
?>