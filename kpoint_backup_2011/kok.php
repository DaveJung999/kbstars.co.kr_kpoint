<?php
//=======================================================
// 설	 명 : 템플릿 샘플
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/11/20
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/11/20 박선민 마지막 수정
//=======================================================
$HEADER = array(
//	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1, // DB 커넥션 사용
	'useCheck'		=>1, // DB 커넥션 사용
	'usePoint'	=>1
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함

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

include_once($thisPath.'dbinfo.php');	// $dbinfo 가져오기

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출

switch($_REQUEST['mode']) {
	case 'kpoint':

		// season 선택시에
		if($_GET['csn']) {
			$sql = "select * from {$dbinfo['table_kmember']} where accountno={$_GET['csn']}";
			if(!$kmember = db_arrayone($sql)) back('미 등록 회원입니다. 등록 후 다시 시도 해 주시기 바랍니다.', 'kmember.php?accountno='.$_GET['csn']);
		}
		else {
			back('카드번호 정보가 없습니다. 잘못된 요청입니다');	
		}	

		$s_id = kpoint_ok($dbinfo, $kmember);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&mid={$kmember['mid']}&s_id=$s_id&cur_sid=$s_id",$qs_basic);
		back($msg,$goto);
		break;
	case 'kpointadd':
		$mid = kpointadd_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&mid={$_REQUEST['mid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'kpointmodify':
		$mid = kpointmodify_ok($dbinfo, $uid);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&mid={$_REQUEST['mid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'kpointdelete':
		$mid = kpointdelete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		elseif($dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'kread.php?'.href_qs("mode=inquiry&mid={$_REQUEST['mid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back('',$goto);
		break;
	case 'kseason':
		$mid = kseason_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&mid={$_REQUEST['mid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'memSeasonReg':
		memSeasonReg_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'klist.php?' . href_qs("s_id=$s_id",$qs_basic);
		back($msg,$goto);
		break;
	case 's_modify':
		$mid = s_modify_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&mid={$_REQUEST['mid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 's_delete':
		$mid = s_delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		elseif($dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'kread.php?'.href_qs("mode=inquiry&mid={$_REQUEST['mid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back('',$goto);
		break;
	case 'p_write':
		$mid = p_write_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&mid={$_REQUEST['mid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'p_modify':
		$mid = p_modify_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&mid={$_REQUEST['mid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'p_delete':
		$mid = p_delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		elseif($dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'kread.php?'.href_qs("mode=inquiry&mid={$_REQUEST['mid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back('',$goto);
		break;
	case 'winpoint_add':
		$total = winpoint_add_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'klist.php?' . href_qs("",$qs_basic);
		$msg = "$total 명의 회원에게 승리포인트가 지급되었습니다.";
		back($msg,$goto);
		break;
	case 'winpoint_delete':
		$total = winpoint_delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		elseif($dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'klist.php?'.href_qs("",$qs_basic);
		$msg = "$total 명의 회원에게 지급된 승리포인트가 삭제되었습니다.";
		back($msg,$goto);
		break;
	default :
		// mode_??? 함수가 있으면 해당 함수 실행
		if( isset($_GET['mode']) && preg_match('/^[a-z0-9\-\_]+$/i', $_GET['mode']) )
			and function_exists('mode_'.$_GET['mode']) ) {
			$func = 'mode_'.$_GET['mode'];
			$func();			
		}
		else
			back('잘못된 요청입니다.');
} // end switch	
	
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================

function kpoint_ok($dbinfo, $kmember)
{

	// 포인트 적립해줌
	if( $_GET['csn']) {
		$rdate_date = $_GET['kdate'];
		$h = substr($_GET['kdate'], 8,2);
		$i = substr($_GET['kdate'], 10,2);
		$s = substr($_GET['kdate'], 12,2);
		$m = substr($_GET['kdate'], 4,2);
		$d = substr($_GET['kdate'], 6,2);
		$y = substr($_GET['kdate'], 0,4);
					
		$rdate = mktime($h, $i, $s, $m, $d, $y);
		$rdate_date = "$y-$m-$d";
		
		//게임정보, 시즌정보 가져오기
		$sql_game = "select * from savers_secret.game where from_unixtime(g_start,'%Y-%m-%d') ='$rdate_date' and (g_home = '13' or g_away = '13') ";
		$list_game = db_arrayone($sql_game);
		if ( !is_array($list_game) ) back("'$rdate_date' 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요.");
		
		$weekday = strftime ( "%a", $list_game['g_start'] );

		$sql_season = "select * from savers_secret.season where sid = {$list_game['sid']} ";
		$list_season = db_arrayone($sql_season);
		
		// 홈경기 중 어웨이  경기.........
		if ( $list_game['g_home'] == '13' && strpos($list_game['g_ground'], "천안") === false ){
			$list_game['g_home'] = "";
			$list_game['g_away'] = "13";
		}
		////////////////////////////
		
		if ($list_game['g_home'] == '13' ){
			if ($weekday =='Sat' or $weekday == 'Sun'){
				$deposit = 100;
				$type = "홈경기(주말)";
				$remark = "홈경기(주말) 포인트적립";
			}else{
				$deposit = 200;
				$type = "홈경기(주중)";
				$remark = "홈경기(주중) 포인트적립";
			}
		}else if ($list_game['g_away'] == '13'){
			$deposit = 300;
			$type = "어웨이경기";
			$remark = "어웨이경기 포인트적립";
		}

		// 이미 적립했는지
		$sql = "select pid from {$dbinfo['table_kpoint']} where accountno={$_GET['csn']} and rdate_date='$rdate_date' and remark='$remark'";
		if(!db_resultone($sql,0,'pid')) {
			new21Kpoint($kmember['mid'], $kmember['accountno'], $deposit, $remark, $type,'사이트', $rdate, $list_season['sid'], $list_season['s_name']) ;
		}else{
			back("'$rdate_date, $remark'으로 이미 등록 되었습니다. 확인 바랍니다.");
		}
	}	
	return $list_season['sid'];
} // end func

function kpointadd_ok($dbinfo)
{
	// $qs
	$qs=array(
				mid		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				accountno	=> "post,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'	=> "post,trim",
				s_name	=> "post,trim",
				rdate_date	=> "post,trim",
				type	=> "post,tirm",
				remark	=> "post,tirm",
				deposit	=> "post,tirm",
				branch	=> "post,tirm"
		);
	$qs=check_value($qs);
		
	$rdate = strtotime( $qs['rdate_date'] ) ;
	
	//게임정보, 시즌정보 가져오기
	if ($qs['type'] == '홈경기(주중)' || $qs['type'] == '홈경기(주말)' || $qs['type'] == '어웨이경기' ){
		$sql_game = "select * from savers_secret.game where from_unixtime(g_start,'%Y-%m-%d') ='{$qs['rdate_date']}' and (g_home = '13' or g_away = '13') ";
		$list_game = db_arrayone($sql_game);
		if ( !is_array($list_game) ) back("{$qs['rdate_date']} 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요.");

		$weekday = strftime ( "%a", $list_game['g_start'] );
	
		$sql_season = "select * from savers_secret.season where sid = {$list_game['sid']} ";
		$list_season = db_arrayone($sql_season);
		
		// 홈경기 중 어웨이  경기.........
		if ( $list_game['g_home'] == '13' && strpos($list_game['g_ground'], "천안") === false ){
			$list_game['g_home'] = "";
			$list_game['g_away'] = "13";
		}
		////////////////////////////
		
		if ($list_game['g_home'] == '13' ){
			if ($weekday =='Sat' or $weekday == 'Sun'){
				$qs['deposit'] = 100;
				$qs['type'] = "홈경기(주말)";
				$qs['remark'] = "홈경기(주말) 포인트적립";
			}else{
				$qs['deposit'] = 200;
				$qs['type'] = "홈경기(주중)";
				$qs['remark'] = "홈경기(주중) 포인트적립";
			}
		}else if ($list_game['g_away'] == '13'){
			$qs['deposit'] = 300;
			$qs['type'] = "어웨이경기";
			$qs['remark'] = "어웨이경기 포인트적립";
		}
		
	}
		
		
	// 포인트 적립해줌
	if( $qs['accountno']) {
		// 이미 적립했는지
		$sql = "select pid from {$dbinfo['table_kpoint']} where accountno={$qs['accountno']} and rdate_date='{$qs['rdate_date']}' and remark='{$qs['remark']}'";
		if(!db_resultone($sql,0,'pid')) 
			new21Kpoint($qs['mid'], $qs['accountno'], $qs['deposit'], $qs['remark'], $qs['type'], $qs['branch'], $rdate, $qs['s_id'], $qs['s_name']) ;
		else
			back("{$qs['rdate_date']}, {$qs['remark']}으로 이미 등록 되었습니다. 확인 바랍니다.");
		
	}	
	return $qs['mid'];
} // end func

function kpointmodify_ok($dbinfo)
{
	// $qs
	$qs=array(
				pid		=> "post,trim,notnull=" . urlencode("수정 할 고유값이 넘어오지 않았습니다."),
				mid		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				accountno	=> "post,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'	=> "post,trim",
				s_name	=> "post,trim",
				rdate_date	=> "post,trim",
				type	=> "post,tirm",
				remark	=> "post,tirm",
				deposit	=> "post,tirm",
				branch	=> "post,tirm"
		);
	$qs=check_value($qs);
		
	$rdate = strtotime( $qs['rdate_date'] ) ;
	
	//게임정보, 시즌정보 가져오기
	if ($qs['type'] == '홈경기(주중)' || $qs['type'] == '홈경기(주말)' || $qs['type'] == '어웨이경기' ){
		$sql_game = "select * from savers_secret.game where from_unixtime(g_start,'%Y-%m-%d') ='{$qs['rdate_date']}' and (g_home = '13' or g_away = '13') ";
		$list_game = db_arrayone($sql_game);
		if ( !is_array($list_game) ) back("{$qs['rdate_date']} 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요.");

		$weekday = strftime ( "%a", $list_game['g_start'] );
	
		$sql_season = "select * from savers_secret.season where sid = {$list_game['sid']} ";
		$list_season = db_arrayone($sql_season);
		
		// 홈경기 중 어웨이  경기.........
		if ( $list_game['g_home'] == '13' && strpos($list_game['g_ground'], "천안") === false ){
			$list_game['g_home'] = "";
			$list_game['g_away'] = "13";
		}
		////////////////////////////
		
		if ($list_game['g_home'] == '13' ){
			if ($weekday =='Sat' or $weekday == 'Sun'){
				$qs['deposit'] = 100;
				$qs['type'] = "홈경기(주말)";
				$qs['remark'] = "홈경기(주말) 포인트적립";
			}else{
				$qs['deposit'] = 200;
				$qs['type'] = "홈경기(주중)";
				$qs['remark'] = "홈경기(주중) 포인트적립";
			}
		}else if ($list_game['g_away'] == '13'){
			$qs['deposit'] = 300;
			$qs['type'] = "어웨이경기";
			$qs['remark'] = "어웨이경기 포인트적립";
		}
		
	}


	// 포인트 적립해줌
	if( $qs['pid']) {
		// 수정......
		new21Kpoint_modify($qs['pid'], $qs['mid'], $qs['accountno'], $qs['deposit'], $qs['remark'], $qs['type'], $qs['branch'], $rdate, $qs['s_id'], $qs['s_name']) ;
	}	
	return $qs['mid'];
} // end func


function kpointdelete_ok(&$dbinfo)
{
	Global $qs_basic, $thisUrl;
	$qs=array(
			pid			=> "request,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다."),
			mid		=> "request,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
			accountno	=> "request,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
			's_id'	=> "request,trim",
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT * FROM {$dbinfo['table_kpoint']} WHERE pid={$qs['pid']} ";
	$kpoint = db_arrayone($sql) or back("이미 삭제되었거나 잘못된 요청입니다");

	// 회원의 적립통장 구함(여러 적립포인트 중에서 가장 처음에 생성되고 정상인 계좌로)
	$sql = "select * from {$dbinfo['table_kpointinfo']} where bid={$qs['mid']} and s_id={$qs['s_id']} and errorno='0' order by uid limit 1";
	if(!$kpointinfo = db_arrayone($sql)) back("적립포인트 정보가 없거나 적립포인트 정보에 문제가 있습니다");

	// 삭제
	db_query("DELETE FROM {$dbinfo['table_kpoint']} WHERE pid={$qs['pid']} ");

	$sql = "SELECT sum(deposit) as psum FROM {$dbinfo['table_kpoint']} WHERE bid={$qs['mid']} and s_id={$qs['s_id']} ";
	$ksum = db_arrayone($sql) or back("잘못된 요청입니다");

	db_query("update {$dbinfo['table_kpointinfo']} set `balance`= {$ksum['psum']} where `uid` = {$kpointinfo['uid']} and s_id = {$qs['s_id']}");
	db_query("update {$dbinfo['table_kmember']} set `balance`= {$ksum['psum']} where `mid` = {$qs['mid']}");

	return $qs['mid'];
} // end func memoDelete_ok


function kseason_ok($dbinfo)
{
	// $qs
	$qs=array(
				mid		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'name'	=> "post,trim",
				accountno	=> "post,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'	=> "post,trim",
				s_name	=> "post,trim"
		);
	$qs=check_value($qs);
		
	//카드 정보 생성
	$rs_accountinfo=db_query("SELECT * FROM {$dbinfo['table_kpointinfo']} WHERE accountno={$qs['accountno']} and s_id = {$qs['s_id']} LIMIT 1");
	if(!db_count($rs_accountinfo)) {
		$tankyoupoint = 0;
		$sql = "INSERT INTO {$dbinfo['table_kpointinfo']} (`bid`,`accountno`, `userid`, `name`, `s_id`, `s_name`, `accounttype`, `transfertype`, `balance`, `comment`, `errorno`, `errornotice`, `rdate`) 
					VALUES ({$qs['mid']}, {$qs['accountno']},'{$qs['userid']}', '{$qs['name']}', {$qs['s_id']}, '{$qs['s_name']}', '{$qs['s_name']} 적립포인트', '모든이체불가', '$tankyoupoint', '현금 환불이 되지 않는 계좌입니다.', '0', '', UNIX_TIMESTAMP())";
		db_query($sql);
		if(!$accountno = db_insert_id()) {
			back("카드 정보 생성이 실패하였습니다.\\n다시 시도해 주시기 바랍니다.");
		}
	}else
		back("이미 생성 된 카드 정보가 있습니다.");
	
	return $qs['mid'];
} // end func

// 회원별 시즌 전체 생성................davej...........2008-10-08
function memSeasonReg_ok($dbinfo)
{
	// $qs
	$qs=array(
				s_id		=> "post,trim,notnull=" . urlencode("시즌정보의 고유값이 넘어오지 않았습니다.")
		);
	$qs=check_value($qs);
		
	//카드 정보 생성
	$rs_accountinfo=db_query("SELECT * FROM {$dbinfo['table_kpointinfo']} WHERE accountno={$qs['accountno']} and s_id = {$qs['s_id']} LIMIT 1");
	if(!db_count($rs_accountinfo)) {
		$tankyoupoint = 0;
		$sql = "INSERT INTO {$dbinfo['table_kpointinfo']} (`bid`,`accountno`, `userid`, `name`, `s_id`, `s_name`, `accounttype`, `transfertype`, `balance`, `comment`, `errorno`, `errornotice`, `rdate`) 
					VALUES ({$qs['mid']}, {$qs['accountno']},'{$qs['userid']}', '{$qs['name']}', {$qs['s_id']}, '{$qs['s_name']}', '{$qs['s_name']} 적립포인트', '모든이체불가', '$tankyoupoint', '현금 환불이 되지 않는 계좌입니다.', '0', '', UNIX_TIMESTAMP())";
		db_query($sql);
		if(!$accountno = db_insert_id()) {
			back("카드 정보 생성이 실패하였습니다.\\n다시 시도해 주시기 바랍니다.");
		}
	}else
		back("이미 생성 된 카드 정보가 있습니다.");
	
	return $qs['mid'];
} // end func


function s_modify_ok($dbinfo)
{
	// $qs
	$qs=array(
				uid		=> "post,trim,notnull=" . urlencode("수정 할 고유값이 넘어오지 않았습니다."),
				mid		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'name'	=> "post,trim",
				accountno	=> "post,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'	=> "post,trim",
				s_name	=> "post,trim"
		);
	$qs=check_value($qs);
		
	//카드 정보 생성
	$rs_accountinfo=db_query("SELECT * FROM {$dbinfo['table_kpointinfo']} WHERE accountno={$qs['accountno']} and s_id ={$qs['s_id']} and uid <> {$qs['uid']}  LIMIT 1");
	if(!db_count($rs_accountinfo)) {
		$sql = "update {$dbinfo['table_kpointinfo']} set `bid` = {$qs['mid']},`accountno` = {$qs['accountno']}, `userid` = '{$qs['userid']}', `name` = '{$qs['name']}', `s_id` = {$qs['s_id']}, `s_name` = '{$qs['s_name']}', `accounttype` = '{$qs['s_name']} 적립포인트', `transfertype` = '모든이체불가', `balance` = '$tankyoupoint', `comment` = '현금 환불이 되지 않는 계좌입니다.', `errorno` = '0', `errornotice` = '', `rdate` =  UNIX_TIMESTAMP()  where uid = {$qs['uid']} ";
		db_query($sql);
	}else
		back("{$qs['s_name']} 에 이미 등록 된 카드 정보가 있습니다. 다시 확인 하시기 바랍니다.");
	
	return $qs['mid'];
} // end func


function s_delete_ok(&$dbinfo)
{
	Global $qs_basic, $thisUrl;
	$qs=array(
				uid		=> "request,trim,notnull=" . urlencode("삭제 할 고유값이 넘어오지 않았습니다."),
				mid		=> "request,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'name'	=> "request,trim",
				accountno	=> "request,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'	=> "request,trim",
				s_name	=> "request,trim"
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT * FROM {$dbinfo['table_kpointinfo']} WHERE uid={$qs['uid']} ";
	$kpointinfo = db_arrayone($sql) or back("이미 삭제되었거나 잘못된 요청입니다");

	// 삭제
	db_query("DELETE FROM {$dbinfo['table_kpointinfo']} WHERE uid={$qs['uid']} ");

	return $qs['mid'];
} // end func memoDelete_ok


function p_write_ok($dbinfo)
{
	// $qs
	$qs=array(
				'mid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				pdate	=> "post,trim",
				's_id'	=> "post,trim",
				present	=> "post,trim",
				memo	=> "post,trim",
				'point'	=> "post,trim"
		);
	$qs=check_value($qs);
	
	$pdate = strtotime($qs['pdate']);
	
	//경품지급 등록
	$rs_kpresent=db_query("SELECT * FROM {$dbinfo['table_kpresent']} WHERE bid = {$qs['mid']} and present='{$qs['present']}' and s_id = {$qs['s_id']} LIMIT 1");
	if(!$kpresent = db_count($rs_kpresent)) {
		$sql = "INSERT INTO {$dbinfo['table_kpresent']} (`bid`, `s_id`, `point`, `pdate`, `present`,  `memo`, `rdate`) 
				VALUES ({$qs['mid']}, {$qs['s_id']},{$qs['point']}, '$pdate', '{$qs['present']}', '{$qs['memo']}',  UNIX_TIMESTAMP())";
		db_query($sql);
		if(!$accountno = db_insert_id()) {
			back("경품지급 등록이 실패하였습니다.\\n다시 시도해 주시기 바랍니다.");
		}
	}else
		back("{$qs['pdate']}에 {$qs['present']} 경품이 이미 지급 되었습니다.");
	
	return $qs['bid'];
} // end func

function p_modify_ok($dbinfo)
{
	// $qs
	$qs=array(
				uid		=> "post,trim,notnull=" . urlencode("수정 할 고유값이 넘어오지 않았습니다."),
				'mid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				pdate	=> "post,trim",
				's_id'	=> "post,trim",
				present	=> "post,trim",
				memo	=> "post,trim",
				point	=> "post,trim"
		);
	$qs=check_value($qs);
		
	$pdate = strtotime($qs['pdate']);
	
	//카드 정보 생성
	$rs_kpresent=db_query("SELECT * FROM {$dbinfo['table_kpresent']} WHERE present='{$qs['present']}' and s_id = {$qs['s_id']} and uid <> {$qs['uid']} LIMIT 1");
	if(!$kpresent = db_count($rs_kpresent)) {
		$sql = "update {$dbinfo['table_kpresent']} set `bid` = {$qs['mid']},`s_id` = {$qs['s_id']}, `point` = {$qs['point']}, `pdate` = '$pdate', `present` = '{$qs['present']}', `memo` = '{$qs['memo']}', `rdate` =  UNIX_TIMESTAMP()  where uid = {$qs['uid']} ";
		db_query($sql);
	}else
		back("{$qs['pdate']}에 {$qs['present']} 경품이 이미 지급 되었습니다.");
	
	return $qs['bid'];
} // end func


function p_delete_ok(&$dbinfo)
{
	Global $qs_basic, $thisUrl;
	$qs=array(
				uid		=> "request,trim,notnull=" . urlencode("삭제 할 고유값이 넘어오지 않았습니다."),
				'mid'		=> "request,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				pdate	=> "request,trim",
				's_id'	=> "request,trim",
				present	=> "request,trim",
				memo	=> "request,trim",
				point	=> "request,trim"
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql_kpresent="SELECT * FROM {$dbinfo['table_kpresent']} WHERE uid={$qs['uid']} LIMIT 1";
	$kpresent = db_arrayone($sql_kpresent) or back("이미 삭제되었거나 잘못된 요청입니다");

	// 삭제
	db_query("DELETE FROM {$dbinfo['table_kpresent']} WHERE uid={$qs['uid']} ");

	return $qs['bid'];
} // end func memoDelete_ok


function winpoint_add_ok($dbinfo)
{
	// $qs
	$qs=array(
				's_id'	=> "post,trim",
				s_name	=> "post,trim",
				rdate_date	=> "post,trim",
				type	=> "post,tirm",
				remark	=> "post,tirm",
				deposit	=> "post,tirm",
				branch	=> "post,tirm"
		);
	$qs=check_value($qs);
		
	$rdate = strtotime( $qs['rdate_date'] ) ;
	
	//게임정보, 시즌정보 가져오기
	$sql_game = "select * from savers_secret.game where from_unixtime(g_start,'%Y-%m-%d') ='{$qs['rdate_date']}' and (g_home = '13' or g_away = '13') ";
	$list_game = db_arrayone($sql_game);
	if ( !is_array($list_game) ) back("{$qs['rdate_date']} 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요.");
	if($list_game['g_home']==13) {
		$strWin = ($list_game['home_score'] > $list_game['away_score']) ? "승" : "패";
	}
	else {
		$strWin = ($list_game['home_score'] < $list_game['away_score']) ? "승" : "패";
	}
	if ($strWin == '패') back("{$qs['rdate_date']} 경기는 패한 경기입니다.");
		
	// 해당 게시물 읽어오기
	$sql = "SELECT * FROM {$dbinfo['table_kpoint']} WHERE rdate_date='{$qs['rdate_date']}' and type='{$qs['type']}'  ";
	$rs_kpoint = db_query($sql) ;
	$total = db_count($rs_kpoint);
	if($total > 0)
		back("{$qs['rdate_date']}에 승리포인트를 받은 회원이 $total 명 있습니다.\\n\\n포인트가 중복 될 수 있습니다.\\n\\n승리포인트일괄 삭제 후 다시 시도 해 주세요.", "klist.php");

	// 해당 게시물 읽어오기
	$sql = "SELECT * FROM {$dbinfo['table_kpoint']} WHERE rdate_date='{$qs['rdate_date']}' and (type='홈경기(주중)' or type='홈경기(주말)' or type='어웨이경기' )";
	$rs_kpoint = db_query($sql) ;
	$total = db_count($rs_kpoint);
	if(!$total){
		back("{$qs['rdate_date']}에 포인트를 받은 회원이 없습니다.");
	}else{
		while($kpoint=db_array($rs_kpoint)) {
			new21Kpoint($kpoint['bid'], $kpoint['accountno'], $qs['deposit'], $qs['remark'], $qs['type'], $qs['branch'], $rdate, $qs['s_id'], $qs['s_name']) ;
		}
		
	}

	return $total;
} // end func


function winpoint_delete_ok(&$dbinfo)
{
	Global $qs_basic, $thisUrl;
	$qs=array(
			'rdate_date'	=> "request,trim",
			'type'	=> "post,tirm",
			'deposit'	=> "post,tirm",
			's_id'	=> "request,trim"
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT * FROM {$dbinfo['table_kpoint']} WHERE rdate_date='{$qs['rdate_date']}' and type='{$qs['type']}'  ";
	$rs_kpoint = db_query($sql) ;
	$total = db_count($rs_kpoint);
	if(!$total){
		back("{$qs['rdate_date']}에 승리포인트를 받은 회원이 없습니다.");
	}else{
		while($kpoint=db_array($rs_kpoint)) {
			$str_del .= $kpoint['bid'].",";
		}
		$len = strlen($str_del); 
		$str_del = substr($str_del, 0, $len-1);
	}
	// 삭제
	db_query("DELETE FROM {$dbinfo['table_kpoint']} WHERE rdate_date='{$qs['rdate_date']}'  and type='{$qs['type']}' ");

	db_query("update {$dbinfo['table_kpointinfo']} set `balance`= balance - {$qs['deposit']} where `bid` in ($str_del) and s_id = {$qs['s_id']} ");
	db_query("update {$dbinfo['table_kmember']} set `balance`= balance - {$qs['deposit']}  where `mid` in ($str_del) ");

	return $total;
} // end func memoDelete_ok


?>