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
	// table
	$table_kmember		= $SITE['th'].'kmember';
	$table_kpoint		= $SITE['th'] . "kpoint";
	$table_kpointinfo	= $SITE['th'] . "kpointinfo";


//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']) {
	case 'userinfoadd' :
		$dbinfo_userinfo=array(
				'table_kmember'	=> $table_kmember,
				'priv_write'	=> '운영자,포인트관리자',
				'priv_delete'	=> '운영자,포인트관리자'
			);
		$mid = userinfoAdd($dbinfo_userinfo);
		go_url("klist.php",0,"회원이 추가되었습니다(회원고유번호: {$mid})");
		break;		
	case 'userinfomodify' :
		$dbinfo_userinfo=array(
				'table_kmember'	=> $table_kmember,
				'priv_write'	=> 1,
				'priv_delete'	=> 99
			);
		userinfoModify($dbinfo_userinfo);
		go_url("klist.php",0,'회원정보가 수정되었습니다.');
		break;
	case 'index_delete':
		index_delete_ok($total_num);
		go_url("klist.php",0,"회원정보가 완전히 삭제되었습니다.");
		break;

} // end switch
//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function userinfoAdd($dbinfo)
{
	global $table_kpoint, $table_kpointinfo;
	
	if(!privAuth($dbinfo, 'priv_write')) back("이용이 제한되었습니다(레벨부족). 확인바랍니다.");
	$qs=array("accountno"	=> "post,trim,notnull=" . urlencode("카드번호를 입력하시기 바랍니다."),
				"name"		=> "post,trim,notnull=" . urlencode("회원님 이름을 입력하시기 바랍니다."),
		);
	$qs=check_value($qs);

	// DB에 등록된 userid인지 체크
	$sql =  "SELECT * FROM {$dbinfo['table_kmember']} WHERE accountno = ".db_escape($qs['accountno']);
	if(db_count(db_query($sql)))
		back("이미등록되어 있는 카드번호 입니다. ");
	
	// DB에 등록된 userid인지 체크
	$sql =  "SELECT * FROM {$dbinfo['table_kmember']} WHERE name = ".db_escape($qs['name'])." and idnum = ".db_escape($qs['idnum']);
	if(db_count(db_query($sql)))
		back("이미등록되어 있는 회원입니다. \\n\\n회원리스트에서 검색 후 수정하여 주시기 바랍니다. ");
	
	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$skip_fields = array('mid' , 'upfiles' , 'upfiles_totalsize', 'hit' , 'hitip' , 'hitdownload' , 'vote' , 'voteip' , 'rdate');
	$sql_set = "";
	if($fieldlist = userGetAppendFields($dbinfo['table_kmember'],$skip_fields)) {
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
				if($value=='passwd') $sql_set .= ", passwd	=password(".db_escape($qs['passwd']).") ";
				else $sql_set .= ", $value = '" . db_escape($qs[$value]) . "' ";
			}
			elseif(isset($_POST[$value])) $sql_set .= ", $value = '" . db_escape($_POST[$value]) . "' ";
		}
	}
	////////////////////////////////
	$sql_set = substr($sql_set,1); // "처음 콤머 삭제"
	$sql="INSERT INTO {$dbinfo['table_kmember']} SET
				rdate	= UNIX_TIMESTAMP(),
				$sql_set
				
		";
	db_query($sql);
	$qs['mid'] = db_insert_id();
	
	//시즌정보
	$sql_season = " SELECT *, sid as s_id FROM savers_secret.season ORDER BY s_start DESC limit 1 ";
	$list_season = db_arrayone($sql_season);
	
	//카드 정보 생성
	$rs_accountinfo=db_query("SELECT * FROM $table_kpointinfo WHERE accountno=".db_escape($qs['accountno'])." and s_id = ".db_escape($list_season['s_id'])." LIMIT 1");
	if(!db_count($rs_accountinfo)) {
		$tankyoupoint = 0;
		$sql = "INSERT INTO $table_kpointinfo (`bid`,`accountno`, `userid`, `name`, `s_id`, `s_name`, `accounttype`, `transfertype`,  `balance`, `comment`, `errorno`, `errornotice`, `rdate`) VALUES (".db_escape($qs['mid']).", ".db_escape($qs['accountno']).",".db_escape($qs['userid']).", ".db_escape($qs['name']).", ".db_escape($list_season['s_id']).", ".db_escape($list_season['s_name']).", ".db_escape($list_season['s_name'])." 적립포인트', '모든이체불가', '$tankyoupoint', '현금 환불이 되지 않는 계좌입니다.', '0', '', UNIX_TIMESTAMP())";
		db_query($sql);
		if(!$accountno = db_insert_id()) {
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
				'mid'		=> "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다."),
		);
	$qs=check_value($qs);

	$sql_where = "";
	if(!$sql_where) $sql_where= " 1 ";

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$default_fields = array('mid' , 'upfiles' , 'upfiles_totalsize', 'hit' , 'hitip' , 'hitdownload' , 'vote' , 'voteip' , 'rdate');
	$sql_set = "";
	if($fieldlist = userGetAppendFields($dbinfo['table_kmember'],$default_fields)) {
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
				if($value=='passwd') $sql_set .= ", passwd	=password(".db_escape($qs['passwd']).") ";
				else $sql_set .= ", $value = '" . db_escape($qs[$value]) . "' ";
			}
			elseif(isset($_POST[$value])) $sql_set .= ", $value = '" . db_escape($_POST[$value]) . "' ";
		}
	}
	////////////////////////////////
	$sql_set = substr($sql_set,1); // "콤머 삭제"
	$sql="UPDATE {$dbinfo['table_kmember']}
			SET
				mdate	= UNIX_TIMESTAMP(),
				$sql_set
			WHERE
				mid = ".db_escape($qs['mid'])."
		";
	db_query($sql);
	return ;
} // end func.

function index_delete_ok($total_num)
{
	Global $qs, $dbinfo, $table_kmember, $sql_set;

	// $qs 추가,변경
	if($total_num == "") back("고유번호가 없습니다.");

	$qs2 = explode(";", 	$total_num);
	$cnt = count($qs2);
	
	$sql_add_logon = "";
	for($i= 1;$i<$cnt;$i++) {
		if($qs2[$i] != "")	$sql_add_logon = $sql_add_logon." mid='".db_escape($qs2[$i])."' OR";
	}
	
	$sql_add_logon = substr($sql_add_logon, 0, -2);
	$sql = "DELETE  FROM	$table_kmember WHERE $sql_add_logon ";
	db_query($sql);

	return true;
} // end func.


// 추가 입력해야할 필드
// 03/12/08
function userGetAppendFields($table,$default_fields) 
{
	GLOBAL $SITE;

	if(!is_array($default_fields) && sizeof($default_fields)<1)
		$default_fields = array();
	
	$fieldlist = array();
	
	// mysql_list_fields는 PHP 7에서 제거되었으므로 SHOW COLUMNS 사용
	$sql = "SHOW COLUMNS FROM `".db_escape($table)."`";
	$result = db_query($sql);
	
	while($row = db_array($result)) {
		$a_fields = $row['Field'];
		
		if(!in_array($a_fields,$default_fields)) {
			$fieldlist[] = $a_fields;
		}
	}

	if(sizeof($fieldlist)) return $fieldlist;
	else return false;
}
?>