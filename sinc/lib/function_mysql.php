<?php
//=======================================================
// 설	명 : sitePHPbasic MySQL관련 함수(function_mysql.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/02/28
// Project: sitePHPbasic
// ChangeLog
//	 DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02/08/07 박선민 마지막 수정
// 03/02/28 박선민 db_tablelist추가와 일부 수정
// 24/05/18 Gemini	PHP 7 마이그레이션
//=======================================================
/* 포함함수
	db_connect(String $server, String $user, String $pass)	// DB 커넥션함수
	db_select(String $name, [String $db_conn])	// 데이터베이스 선택
	db_close()				// 이하 미정리
	db_query()
	db_array()
	db_row()
	db_result()
	db_count()
	db_free()
	db_tablelist()
	db_error($msg="DB에 이상이 있습니다.",$query)


[참고]
//테이블 존재 유무 확인 평션
function mysql_table_exists($table,$db){
	global $db_conn;
	// PHP 7에서는 mysql_list_tables가 제거되었으므로 SHOW TABLES 쿼리 사용
	$tables_result = mysqli_query($db_conn, "SHOW TABLES FROM `{$db}`");
	if ($tables_result){
		while (list($temp)=mysqli_fetch_array($tables_result)){
			if($temp == $table){
				mysqli_free_result($tables_result);
				return true;
			}
		}
		mysqli_free_result($tables_result);
	}
	return false;
}

//관리자페이지에서쓸만한 테이블 최적화 루틴
global $db_conn;
$tables = mysqli_query($db_conn, "SHOW TABLES FROM {$db}");
if ($tables){
	while (list($table_name) = mysqli_fetch_array($tables)){
		$sql = "OPTIMIZE TABLE `{$table_name}`";
		mysqli_query($db_conn, $sql) or exit(mysqli_error($db_conn));
	}
	mysqli_free_result($tables);
}
*/

/*-------------------------------------------------------------------
	함수명		db_connect
	인자		$server	$user	$pass
	반환값		$db_conn
	수정일		02/06/21
	설명		MySQL Server에 접속을 시도한다.
-------------------------------------------------------------------*/
$db_lastsql="";
$db_conn = null;
function db_connect($server, $user, $pass=""){
	global $db_conn;
	// mysql_connect()가 PHP 7에서 제거되었으므로 mysqli_connect()로 변경
	$db_conn = @mysqli_connect($server, $user, $pass) or db_error("DB 로그인을 실패하였습니다.\\n로긴 아이디와 패스워드를 확인해 보시기 바랍니다.");
	// UTF-8 문자셋 설정 추가
	@mysqli_set_charset($db_conn, "utf8mb4");
	return $db_conn;
}

/*-------------------------------------------------------------------
	함수명		db_select
	인자		$name	$db_conn
	반환값		-
	수정일		02/06/21
	설명		MySQL Server에서 $name란 DB를 선택한다.
-------------------------------------------------------------------*/
function db_select($name, $db_conn=""){
	if(empty($db_conn)){
		global $db_conn;
	}
	// mysql_select_db()가 PHP 7에서 제거되었으므로 mysqli_select_db()로 변경
	@mysqli_select_db($db_conn, $name) or db_error("DB 선택을 실패하였습니다.");
	// UTF-8 문자셋 설정 추가
	@mysqli_set_charset($db_conn, "utf8mb4");
	return true;
}

/*-------------------------------------------------------------------
	함수명		db_close
	인자		-
	반환값		
	수정일		2000. 11. 15
	설명		MySQL Server의 접속을 끊는다.
-------------------------------------------------------------------*/
function db_close($db_conn=""){
	if(empty($db_conn)){
		global $db_conn;
	}
	// mysql_close()가 PHP 7에서 제거되었으므로 mysqli_close()로 변경
	if($db_conn) @mysqli_close($db_conn);
}

/*-------------------------------------------------------------------
	함수명		db_query
	인자		$query	$msg
	반환값		$result
	수정일		2000. 08. 20
	설명		SQL문을 실행한다. 에러시 $msg를 출력
-------------------------------------------------------------------*/
function db_query($query, $errmsg="", $db_conn=""){
	global $db_lastsql;
	$db_lastsql=$query;

	if(empty($db_conn)){
		global $db_conn;
	}
	// mysqli_query()가 PHP 7에서 제거되었으므로 mysqli_query()로 변경
	$result = @mysqli_query($db_conn, $query) or db_error($errmsg, $query);

	return $result;
}

/*-------------------------------------------------------------------
	함수명		db_dbquery
	인자		$query	$msg
	반환값		$result
	수정일		2000. 08. 20
	설명		SQL문을 실행한다. 에러시 $msg를 출력
-------------------------------------------------------------------*/
function db_dbquery($db, $query, $errmsg="", $db_conn=""){
	global $db_lastsql;
	$db_lastsql=$query;

	if(empty($db_conn)){
		global $db_conn;
	}
	// mysql_db_query()는 PHP 5.3에서 제거되었으므로 mysqli_select_db()와 mysqli_query() 조합으로 변경
	if (!@mysqli_select_db($db_conn, $db)){
		db_error("DB 선택을 실패하였습니다.", $query);
	}
	
	$result = @mysqli_query($db_conn, $query) or db_error($errmsg, $query);

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
	if($result && mysqli_num_rows($result)){
		// mysqli_fetch_assoc()로 변경
		$arr=@mysqli_fetch_assoc($result) or db_error("1. SQL문 실행후 데이터 받아오는데 실패하였습니다");
		return $arr;
	}
	else
		return false;
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
	if($result && mysqli_num_rows($result)){
		$arr=@mysqli_fetch_row($result) or db_error("2. SQL문 실행후 데이터 받아오는데 실패하였습니다");
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
-------------------------------------------------------------------*/
function db_result(&$result, $row, $field){
	if($result && mysqli_num_rows($result)){
		// mysqli_result()는 PHP 8.1에서 제거되었으므로 mysqli_data_seek와 mysqli_fetch_row를 사용하도록 변경
		mysqli_data_seek($result, $row);
		$row_data = mysqli_fetch_row($result);
		return $row_data[$field];
	}
	else
		return false;
}

/*-------------------------------------------------------------------
	함수명		db_count
	인자		$result
	반환값		$rows
	수정일		2000. 09. 18
	설명		$result란 쿼리에서 불러들인 레코드의 총 갯수를 구한다.
-------------------------------------------------------------------*/
function db_count($result=''){
	if($result) 
		return mysqli_num_rows($result); // SELECT에서 사용
	else
		return mysqli_affected_rows($GLOBALS['db_conn']); // INSERT, UPDATE, or DELETE query 
}

/*-------------------------------------------------------------------
	함수명		db_free
	인자		$result
	반환값		$rows
	수정일		2000. 09. 18
	설명		$result란 쿼리에서 불러들인 레코드의 총 갯수를 구한다.
-------------------------------------------------------------------*/
function db_free(&$result){
	return @mysqli_free_result($result);
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
	$list = @mysqli_query($db_conn, "SHOW TABLES FROM `{$db}`") or db_error('4. 테이블리스트를 가져오는데 실패하였습니다.');
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

	array_multisort($tables,SORT_ASC);
	return $tables;
}

/*-------------------------------------------------------------------
	함수명		db_error
	인자		$msg
	반환값		-
	수정일		2000. 08. 02
	설명		$msg를 출력후 뒤로 이동
-------------------------------------------------------------------*/
function db_error($msg="DB에 이상이 있습니다.",$query="",$exit=0){
	global $SITE, $db_lastsql, $db_conn;
	
	if(mysqli_errno($db_conn)){
		if(isset($SITE['debug']) && $SITE['debug']){
			if(!$query) $query=$db_lastsql;
			$msg= mysqli_errno($db_conn) . ": {$msg}<br>" . mysqli_error($db_conn);
			echo ("
				MySQL Error : {$msg} <br>
				Last SQL Query : $query
				");
			
			exit;
		} else {
			$msg= mysqli_errno($db_conn) . ": {$msg}";
			echo (" 
					<script> 
						window.alert('DB 작업중 에러가 발견되었습니다.\\n계속 에러가 발생된다면 종합질문페이지에 문의바랍니다.\\n\\nerrno {$msg}');
						history.go(-1);
					</script>
				");
			exit;
		} // end if
	} // end if
} // end func db_error()
?>
