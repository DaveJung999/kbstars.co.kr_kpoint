<?php
//=======================================================
// 설  명 : 관리자 회원 관리 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'	=>1, // DB 커넥션 사용
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
				
	include_once("{$thisPath}/dbinfo.php");	// $dbinfo 가져오기


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

	// SQL 인젝션 방지
	$accountno_qs = db_escape($qs['accountno']);
	$name_qs = db_escape($qs['name']);
	$idnum_qs = db_escape($qs['idnum'] ?? ''); // 널 병합 및 이스케이프

	// DB에 등록된 accountno인지 체크
	$sql = "SELECT *
				FROM {$dbinfo['table_logon']}
				WHERE accountno = '{$accountno_qs}'"; // 배열 키 수정 및 이스케이프된 변수 사용
	if(db_count(db_query($sql)))
		back("이미등록되어 있는 카드번호 입니다. ");
	
	// DB에 등록된 name 및 idnum 조합인지 체크
	$sql = "SELECT *
				FROM {$dbinfo['table_logon']}
				WHERE name = '{$name_qs}' and idnum = '{$idnum_qs}'"; // 배열 키 수정 및 이스케이프된 변수 사용
	if(db_count(db_query($sql)))
		back("이미등록되어 있는 회원입니다. \\n\\n회원리스트에서 검색 후 수정하여 주시기 바랍니다. ");
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('mid' , 'upfiles' , 'upfiles_totalsize', 'hit' , 'hitip' , 'hitdownload' , 'vote' , 'voteip' , 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table_logon'],$skip_fields)) { // 배열 키 수정
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) { 
				case 'email' :
					$qs['email'] = check_email($_POST['email'] ?? ''); // 널 병합 처리
					break;
				case 'passwd' :
					if(!trim($_POST['passwd'] ?? '')) continue 2; // 비밀번호가 입력되어 있지 않으면 넘김 (널 병합 처리)
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
				$qs_value_escaped = db_escape($qs[$value]); // 쿼리 삽입 전 이스케이프
				if($value=='passwd') $sql_set .= ", passwd =password('{$qs_value_escaped}') "; // 이스케이프된 값 사용, 문자열 보간
				else $sql_set .= ", {$value} = '{$qs_value_escaped}' "; // 이스케이프된 값 사용, 문자열 보간
			}
			elseif(isset($_POST[$value])) {
				$post_value_escaped = db_escape($_POST[$value]); // 쿼리 삽입 전 이스케이프
				$sql_set .= ", {$value} = '{$post_value_escaped}' "; // 이스케이프된 값 사용, 문자열 보간
			}
		}
	}
	////////////////////////////////
	$sql_set = substr($sql_set,1); // "처음 콤머 삭제"
	$sql="INSERT INTO {$dbinfo['table_logon']}
				SET
				rdate	= UNIX_TIMESTAMP(),
				{$sql_set}
				
		"; // table_kmember -> table_logon으로 수정, 키 수정, SQL SET 구문 수정
	db_query($sql);
	$qs['mid'] = db_insert_id(); // mysql_insert_id() -> db_insert_id()로 수정
	
	//시즌정보
	$sql_season = "SELECT *, sid as s_id
						FROM savers_secret.season
						ORDER BY s_start DESC limit 1 ";
	$list_season = db_arrayone($sql_season);

	// SQL 인젝션 방지를 위해 쿼리에 필요한 $qs 변수들을 다시 이스케이프 처리
	$mid_qs = db_escape($qs['mid']);
	$accountno_qs = db_escape($qs['accountno']);
	$userid_qs = db_escape($qs['userid'] ?? ''); // 널 병합 및 이스케이프
	$name_qs = db_escape($qs['name']);
	$s_id_season = db_escape($list_season['s_id']);
	$s_name_season = db_escape($list_season['s_name']);

	//카드 정보 생성
	$rs_accountinfo=db_query("SELECT *
							 	FROM {$dbinfo['table_kpointinfo']}
								WHERE accountno='{$accountno_qs}'
								  AND s_id = '{$s_id_season}' LIMIT 1"); // 배열 키 수정 및 이스케이프된 변수 사용
	if(!db_count($rs_accountinfo)) {
		$tankyoupoint = 0;
		// INSERT 쿼리 수정: 배열 키 수정, 문자열 보간 및 타입 오류 수정
		$account_type = db_escape("{$list_season['s_name']} 적립포인트");
		$sql = "INSERT INTO {$dbinfo['table_kpointinfo']}
					(`bid`,`accountno`, `userid`, `name`, `s_id`, `s_name`,
					 `accounttype`, `transfertype`, `balance`, `comment`,
					 `errorno`, `errornotice`, `rdate`)
					VALUES ('{$mid_qs}', '{$accountno_qs}', '{$userid_qs}', '{$name_qs}', '{$s_id_season}', '{$s_name_season}',
					'{$account_type}', '', '{$tankyoupoint}', '',
					'0', '', UNIX_TIMESTAMP())"; // 배열 키 수정 및 변수 이스케이프된 값 사용
		db_query($sql);
		if(!$accountno_id = db_insert_id()) { // mysql_insert_id() -> db_insert_id()로 수정
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
				'bid'		=> "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다."), // 배열 키에 따옴표 추가
		);
	$qs=check_value($qs);

	if(!$sql_where) $sql_where= " 1 ";
	
	// SQL 인젝션 방지
	$bid_qs = db_escape($qs['bid']);
	$accountno_post = db_escape($_POST['accountno'] ?? ''); // 널 병합 및 이스케이프
	$comment_post = db_escape($_POST['comment'] ?? ''); // 널 병합 및 이스케이프
	$s_id_post = db_escape($_POST['s_id'] ?? ''); // 널 병합 및 이스케이프

	//logon 테이블 업데이트
	$sql="UPDATE {$dbinfo['table_logon']}
			SET
				mdate	= UNIX_TIMESTAMP(),
				accountno = '{$accountno_post}',
				comment = '{$comment_post}'
			WHERE
				uid = '{$bid_qs}'
		"; // 배열 키 수정 및 변수 이스케이프된 값 사용
	db_query($sql);
	
	//kmember 테이블 업데이트
	$sql="UPDATE {$dbinfo['table_kpointinfo']}
			SET
				mdate	= UNIX_TIMESTAMP(),
				accountno = '{$accountno_post}',
				comment = '{$comment_post}'
			WHERE bid = '{$bid_qs}'
			  AND s_id = '{$s_id_post}'
		"; // 배열 키 수정 및 변수 이스케이프된 값 사용
	db_query($sql);
	return ;
} // end func.

function index_delete_ok($dbinfo)
{

	// $qs
	$qs=array(
				'total_num' => "get,trim,notnull=" . urlencode("고유값이 넘어오지 않았습니다."), // 배열 키에 따옴표 추가
				's_id'		=> "get,trim"  // 배열 키에 따옴표 추가
		);
	$qs=check_value($qs);
	
	// SQL 인젝션 방지
	$s_id_qs = db_escape($qs['s_id']);

	$qs2 = explode(";", 	$qs['total_num']);
	$cnt = count($qs2);
	
	$sql_add_logon = '';
	$sql_where_logon = '';
	
	for($i= 1;$i<$cnt;$i++) {
		if($qs2[$i] != "")	{
			$bid_escaped = db_escape($qs2[$i]);
			$sql_add_logon .= " bid='{$bid_escaped}' OR"; // 이스케이프된 값 사용 및 쿼리 조건 명확화
			$sql_where_logon .= " uid='{$bid_escaped}' OR"; // 이스케이프된 값 사용 및 쿼리 조건 명확화
		}
	}
	
	$sql_add_logon = substr($sql_add_logon, 0, -3); // OR 제거
	$sql_where_logon = substr($sql_where_logon, 0, -3); // OR 제거

	//시즌 카드정보 삭제
	db_query("DELETE FROM {$dbinfo['table_kpointinfo']}
				WHERE s_id = '{$s_id_qs}' AND ({$sql_add_logon}) "); // 배열 키 수정 및 변수 이스케이프된 값 사용

	//포인트정보 삭제
	db_query("DELETE FROM {$dbinfo['table_kpoint']}
				WHERE s_id = '{$s_id_qs}' AND ({$sql_add_logon}) "); // 배열 키 수정 및 변수 이스케이프된 값 사용
	
	// 경품정보삭제
	db_query("DELETE FROM {$dbinfo['table_kpresent']}
				WHERE s_id = '{$s_id_qs}' AND ({$sql_add_logon}) "); // 배열 키 수정 및 변수 이스케이프된 값 사용
	
	//포인트정보 0으로
	db_query("UPDATE {$dbinfo['table_logon']}
				 SET `balance`= '0'
				WHERE {$sql_where_logon} "); // 배열 키 수정 및 변수 이스케이프된 값 사용

	return true;
} // end func.


function delete_complete_ok($dbinfo)
{

	// $qs
	$qs=array(
				'bid' => "get,trim,notnull=" . urlencode("회원 고유값이 넘어오지 않았습니다."), // 배열 키에 따옴표 추가
				's_id'		=> "get,trim,notnull=" . urlencode("시즌정보가 넘어오지 않았습니다."), // 배열 키에 따옴표 추가
		);
	$qs=check_value($qs);
	
	// SQL 인젝션 방지
	$bid_qs = db_escape($qs['bid']);
	$s_id_qs = db_escape($qs['s_id']);

	//카드정보 삭제
	db_query("DELETE FROM {$dbinfo['table_kpointinfo']}
				WHERE s_id = '{$s_id_qs}' AND bid = '{$bid_qs}'"); // 배열 키 수정 및 변수 이스케이프된 값 사용

	//포인트정보 삭제
	db_query("DELETE FROM {$dbinfo['table_kpoint']}
				WHERE s_id = '{$s_id_qs}' AND bid = '{$bid_qs}'"); // 배열 키 수정 및 변수 이스케이프된 값 사용
	
	// 경품정보삭제
	db_query("DELETE FROM {$dbinfo['table_kpresent']}
				WHERE s_id = '{$s_id_qs}' AND bid = '{$bid_qs}'"); // 배열 키 수정 및 변수 이스케이프된 값 사용
	
	//포인트정보 0으로
	db_query("UPDATE {$dbinfo['table_logon']}
				 SET `balance`= '',
					 `accountno` = ''
				WHERE uid = '{$bid_qs}'"); // 배열 키 수정 및 변수 이스케이프된 값 사용

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
	
	// PHP 4의 mysql_list_fields, mysql_num_fields, mysql_field_name을
	// MariaDB 호환성을 갖는 SHOW COLUMNS 쿼리 및 db_query/db_array로 대체
	
	$sql = "SHOW COLUMNS FROM {$table}";
	$rs_fields = db_query($sql);

	if(db_count($rs_fields) > 0) {
		while($list = db_array($rs_fields)) {
			$a_fields = $list['Field'];
			
			if(!in_array($a_fields,$default_fields)) {
				$fieldlist[] = $a_fields;
			}
		}
		db_free($rs_fields); // 쿼리 결과 해제
	}


	if(sizeof($fieldlist)) return $fieldlist;
	else return false;
}
?>