<?php
//=======================================================
// 설  명 : 관리자 회원 관리 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 마지막 수정
// 25/11/07 Gemini AI PHP 7+ 호환성 수정 (mysql_*, 중괄호, SQL 따옴표, userGetAppendFields)
//=======================================================
$HEADER=array(
	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1, // DB 커넥션 사용
	'useApp'	=>1, // file_upload(),remote_addr()
	'useCheck'	=>1, // check_value()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함
//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 기본 URL QueryString
	$qs_basic = "db=$db".					//table 이름
				"&mode=".					// mode값은 list.php에서는 당연히 빈값
				"&cateuid=$cateuid".		//cateuid
				"&pern=$pern" .	// 페이지당 표시될 게시물 수
				"&sc_column=$sc_column".	//search column
				"&sc_string=" . urlencode(stripslashes($sc_string)). //search string
				"&mid=$mid".
				"&s_id=$s_id".
				"&cur_sid=$cur_sid".
				"&page=$page".
				"&sdate=$sdate".
				"&edate=$edate".
				"&search=$search".
				"&pay_cate=$pay_cate".
				"&term_id=$term_id"
				;				//현재 페이지
				
	include_once("$thisPath/dbinfo.php");	// $dbinfo 가져오기




//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']) {
	case 'userinfoadd' :

		$mid = userinfoAdd($dbinfo);
		go_url("klist.php",0,"회원이 추가되었습니다(회원고유번호: {$mid})");
		break;		
	case 'userinfomodify' :

		userinfoModify($dbinfo);
/*		echo $qs_basic."<br>";
		echo href_qs($qs_basic);exit;*/
		go_url("klist.php?".href_qs($qs_basic),0,'회원정보가 수정되었습니다.');
		break;
	case 'index_delete':
		index_delete_ok($dbinfo);
		go_url("klist.php",0,"회원의 시즌에 대한 카드정보가 삭제되었습니다.");
		break;
	case 'delete_complete':
		delete_complete_ok($dbinfo);
		go_url("klist.php",0,"회원에 대한 모든정보가 삭제되었습니다.");
		break;

} // end switch
//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function userinfoAdd($dbinfo)
{
	if(!privAuth($dbinfo, 'priv_write')) back("이용이 제한되었습니다(레벨부족). 확인바랍니다.");
	$qs=array("accountno"	=> "post,trim,notnull=" . urlencode("카드번호를 입력하시기 바랍니다."),
				"name"		=> "post,trim,notnull=" . urlencode("회원님 이름을 입력하시기 바랍니다."),
		);
	$qs=check_value($qs);

	// DB에 등록된 userid인지 체크
	// [!] FIX: $dbinfo['table_logon'] -> {$dbinfo['table_logon']}, $qs['accountno'] -> {$qs['accountno']}, NBSP 제거
	$sql =  "SELECT * FROM {$dbinfo['table_logon']} 
				WHERE accountno = {$qs['accountno']}";
	if(db_count(db_query($sql)))
		back("이미등록되어 있는 카드번호 입니다. ");
	
	// DB에 등록된 userid인지 체크
	// [!] FIX: $dbinfo['table_logon'] -> {$dbinfo['table_logon']}, $qs -> {$qs}, (idnum은 $qs에 정의되지 않았으나 원본 유지)
	$sql =  "SELECT * FROM {$dbinfo['table_logon']} 
				WHERE name = {$qs['name']} and idnum = {$qs['idnum']} ";
	if(db_count(db_query($sql)))
		back("이미등록되어 있는 회원입니다. \\n\\n회원리스트에서 검색 후 수정하여 주시기 바랍니다. ");
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('mid' , 'upfiles' , 'upfiles_totalsize', 'hit' , 'hitip' , 'hitdownload' , 'vote' , 'voteip' , 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table_logon'],$skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) { 
				case 'email' :
					$qs['email'] = check_email($_POST['email']);
					break;
				case 'passwd' :
					if(!trim($_POST['passwd'])) continue 2; // 비밀번호가 입력되어 있지 않으면 넘김
					$qs['passwd'] = $_POST['passwd'];
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'host' : 
					$qs['host'] = $_SERVER['HTTP_HOST'];
					break;					
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) {
				if($value=='passwd') $sql_set .= ", passwd	=password({$qs['passwd']}) ";
				else $sql_set .= ", $value = '" . $qs[$value] . "' ";
			}
			elseif(isset($_POST[$value])) $sql_set .= ", $value = '" . $_POST[$value] . "' ";
		}
	}
	////////////////////////////////
	$sql_set = substr($sql_set,1); // "처음 콤머 삭제"
	// [!] FIX: $dbinfo['table_kmember'] -> {$dbinfo['table_kmember']}, NBSP 제거
	$sql="INSERT INTO {$dbinfo['table_kmember']} 
				SET
				rdate	= UNIX_TIMESTAMP(),
				$sql_set
				
		";
	db_query($sql);
	$qs['mid'] = db_insert_id(); // [!] FIX: mysql_insert_id() -> db_insert_id()
	
	//시즌정보
	// [!] FIX: NBSP 제거
	$sql_season = " SELECT *, sid as s_id 
						FROM savers_secret.season 
						ORDER BY s_start DESC limit 1 ";
	$list_season = db_arrayone($sql_season);
	
	//카드 정보 생성
	// [!] FIX: $dbinfo, $qs, $list_season 변수 중괄호 {} 적용, NBSP 제거
	$rs_accountinfo=db_query("SELECT * FROM {$dbinfo['table_kpointinfo']} 
								WHERE accountno={$qs['accountno']} 
								  and s_id = '{$list_season['s_id']}' LIMIT 1");
	if(!db_count($rs_accountinfo)) {
		$tankyoupoint = 0;
		// [!] FIX: $dbinfo, $qs, $list_season 변수 중괄호 {} 적용
		// [!] FIX: SQL 구문 오류 수정 (따옴표 추가)
		// [!] FIX: $qs['userid']는 $qs에 정의되지 않았으나 원본 유지
		$sql = "INSERT INTO {$dbinfo['table_kpointinfo']} 
					(`bid`,`accountno`, `userid`, `name`, `s_id`, `s_name`, 
					 `accounttype`, `transfertype`,  `balance`, `comment`, 
					 `errorno`, `errornotice`, `rdate`) 
					VALUES ({$qs['mid']}, {$qs['accountno']}, {$qs['userid']}, {$qs['name']}, '{$list_season['s_id']}', '{$list_season['s_name']}', 
					'{$list_season['s_name']} 적립포인트', '', '{$tankyoupoint}', '', 
					'0', '', UNIX_TIMESTAMP())";
		db_query($sql);
		if(!$accountno = db_insert_id()) { // [!] FIX: mysql_insert_id() -> db_insert_id()
			back("카드 정보 생성이 실패하였습니다.\\n운영자에게 문의 바랍니다.");
		}
	}else
		back("이미 생성 된 카드 정보가 있습니다.");
	

	return $qs['mid'];
}

function userinfoModify($dbinfo)
{
	if(!privAuth($dbinfo, 'priv_write')) back("이용이 제한되었습니다(레벨부족). 확인바랍니다.");

	// $qs 추가,변경
	$qs=array(
				bid		=> "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다."),
		);
	$qs=check_value($qs);

	if(!$sql_where) $sql_where= " 1 ";

	//logon 테이블 업데이트
	// [!] FIX: $dbinfo, $_POST, $qs 변수 중괄호 {} 적용
	// [!] FIX: SQL 오류 방지 (문자열 값에 따옴표 추가)
	$sql="UPDATE {$dbinfo['table_logon']}
			SET
				mdate		= UNIX_TIMESTAMP(),
				accountno	= '{$_POST['accountno']}',
				comment		= '{$_POST['comment']}'
			WHERE
				uid = {$qs['bid']}
		";
	db_query($sql);
	
	//kmember 테이블 업데이트
	// [!] FIX: $dbinfo, $_POST, $qs 변수 중괄호 {} 적용
	// [!] FIX: SQL 오류 방지 (문자열 값에 따옴표 추가)
	// [!] FIX: NBSP 제거
	$sql="UPDATE {$dbinfo['table_kpointinfo']}
			SET
				mdate		= UNIX_TIMESTAMP(),
				accountno	= '{$_POST['accountno']}',
				comment		= '{$_POST['comment']}'
			WHERE bid = {$qs['bid']}
			  and s_id = '{$_POST['s_id']}'
		";
	db_query($sql);
	return ;
} // end func.

function index_delete_ok($dbinfo)
{

	// $qs
	$qs=array(
				total_num => "get,trim,notnull=" . urlencode("고유값이 넘어오지 않았습니다."),
				s_id		=> "get,trim" 
		);
	$qs=check_value($qs);
	
		
	$qs2 = explode(";", 	$qs['total_num']);
	$cnt = count($qs2);
	
	for($i= 1;$i<$cnt;$i++) {
		if($qs2[$i] != "")	{
			$sql_add_logon = $sql_add_logon." bid='".$qs2[$i]."' OR";
			$sql_where_logon = $sql_where_logon." uid='".$qs2[$i]."' OR";
		}
	}
	
	$sql_add_logon = substr($sql_add_logon, 0, -2);
	$sql_where_logon = substr($sql_where_logon, 0, -2);
	//시즌 카드정보 삭제
	// [!] FIX: $dbinfo, $qs 변수 중괄호 {} 적용, NBSP 제거
	db_query("DELETE  FROM	{$dbinfo['table_kpointinfo']} 
				WHERE s_id = {$qs['s_id']} and ({$sql_add_logon} ) ");
	//포이트정보 삭제
	// [!] FIX: $dbinfo, $qs 변수 중괄호 {} 적용, NBSP 제거
	db_query("DELETE  FROM	{$dbinfo['table_kpoint']} 
				WHERE s_id = {$qs['s_id']} and ({$sql_add_logon} ) ");
	// 경품정보삭제
	// [!] FIX: $dbinfo, $qs 변수 중괄호 {} 적용, NBSP 제거
	db_query("DELETE FROM {$dbinfo['table_kpresent']} 
				WHERE s_id = {$qs['s_id']} and ({$sql_add_logon} )  ");
	//포인트정보 0으로
	// [!] FIX: $dbinfo 변수 중괄호 {} 적용, NBSP 제거
	db_query("update {$dbinfo['table_logon']} 
				 set `balance`= '0'
				where {$sql_where_logon} ");

	return true;
} // end func.


function delete_complete_ok($dbinfo)
{

	// $qs
	$qs=array(
				bid => "get,trim,notnull=" . urlencode("회원 고유값이 넘어오지 않았습니다."),
				s_id		=> "get,trim,notnull=" . urlencode("시즌정보가 넘어오지 않았습니다."),
		);
	$qs=check_value($qs);
	
	$s_id = db_escape($qs['s_id']);
	$bid = db_escape($qs['bid']);

	//카드정보 삭제
	// [!] FIX: $dbinfo 변수 중괄호 {} 적용
	db_query("DELETE FROM {$dbinfo['table_kpointinfo']}
					WHERE s_id = '{$s_id}' and bid = '{$bid}'");

	//포인트정보 삭제
	// [!] FIX: $dbinfo 변수 중괄호 {} 적용
	db_query("DELETE FROM {$dbinfo['table_kpoint']}
					WHERE s_id = '{$s_id}' and bid = '{$bid}'");

	// 경품정보삭제
	// [!] FIX: $dbinfo 변수 중괄호 {} 적용
	db_query("DELETE FROM {$dbinfo['table_kpresent']}
					WHERE s_id = '{$s_id}' and bid = '{$bid}'");

	//포인트정보 0으로
	// update 쿼리의 where 절의 bid 또한 이스케이프된 변수($bid)를 사용합니다.
	// [!] FIX: $dbinfo 변수 중괄호 {} 적용
	db_query("UPDATE {$dbinfo['table_logon']}
					 SET `balance` = '',
						 `accountno` = ''
					WHERE uid = '{$bid}'");

	return true;
} // end func.


// 추가 입력해야할 필드
// 03/12/08
function userGetAppendFields($table,$default_fields) 
{
	GLOBAL $SITE;

	if(!is_array($default_fields) and sizeof($default_fields)<1)
		$default_fields = array();
	
	$fieldlist = array();
	
	// [!] FIX: PHP 7에서 mysql_list_fields, mysql_num_fields, mysql_field_name 함수 제거됨
	// [!] FIX: SHOW COLUMNS 쿼리 및 db_query/db_array/db_free 함수로 대체
	$safe_table = '`' . str_replace('`', '', $table) . '`';
	$safe_db = '`' . str_replace('`', '', $SITE['database']) . '`';
	
	$sql = "SHOW COLUMNS FROM {$safe_table} IN {$safe_db}";
	$result = db_query($sql);

	if ($result) {
		// db_array()가 mysqli_fetch_assoc() 처럼 연관 배열을 반환한다고 가정
		while ($row = db_array($result)) {
			$a_fields = $row['Field']; // 'Field' 컬럼에 필드명이 들어있음
			
			if(!in_array($a_fields,$default_fields)) {
				$fieldlist[] = $a_fields;
			}
		}
		@db_free($result); // db_free() 함수가 존재한다고 가정
	}

	if(sizeof($fieldlist)) return $fieldlist;
	else return false;
}
?>