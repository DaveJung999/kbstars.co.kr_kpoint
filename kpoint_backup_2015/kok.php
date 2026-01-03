<?php
//=======================================================
// 설  명 : 템플릿 샘플
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/11/20
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/11/20 박선민 마지막 수정
// 25/11/10 Gemini PHP7/MariaDB 환경 맞춤 및 DB 함수 변경
//=======================================================
$HEADER = array(
	'priv'		=>'운영자,포인트관리자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'	=>1, // DB 커넥션 사용
	'useCheck'	=>1, // DB 커넥션 사용
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
				"&bid=$bid".
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

switch(isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '') { // $_REQUEST['mode'] isset 체크
	case 'kpoint':

		// season 선택시에
		if(isset($_GET['csn']) && $_GET['csn']) { // $_GET['csn'] isset 체크 및 빈값 체크
			$sql = "select *
					from {$dbinfo['table_logon']}
					where accountno='{$_GET['csn']}'"; // 문자열 변수 작은 따옴표로 감싸기
			
			if(!$klogon = db_arrayone($sql)) back('미 등록 회원입니다. \\n\\nKB스타즈 홈페이지의 서포터즈 메뉴에서 카드번호를 등록 후 다시 시도 해 주시기 바랍니다.');
		}
		else {
			back('카드번호 정보가 없습니다. 잘못된 요청입니다');	
		}	
		//exit;
		$s_id = kpoint_ok($dbinfo, $klogon);
		
		// 어느 페이지로 이동할 것인지 결정
/*		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$klogon['uid']}&s_id=$s_id&cur_sid=$s_id",$qs_basic);*/
		$goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$klogon['uid']}&s_id=$s_id&cur_sid=$s_id",$qs_basic);

		$msg = isset($msg) ? $msg : ''; // $msg 변수 초기화
		back($msg,$goto);
		break;
	case 'kpointadd':
		$uid = kpointadd_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		$msg = isset($msg) ? $msg : ''; // $msg 변수 초기화
		back($msg,$goto);
		break;
	case 'kpointmodify':
		// $uid 변수가 정의되지 않아 사용하지 않음
		$uid = kpointmodify_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		$msg = isset($msg) ? $msg : ''; // $msg 변수 초기화
		back($msg,$goto);
		break;
	case 'kpoint_allreg':
		$msg = kpoint_allreg_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'klist.php?' . href_qs("s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'kpointdelete':
		$uid = kpointdelete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		elseif(isset($dbinfo['goto_delete']) && $dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'kread.php?'.href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back('',$goto);
		break;
	case 'kseason':
		$uid = kseason_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		$msg = isset($msg) ? $msg : ''; // $msg 변수 초기화
		back($msg,$goto);
		break;
	case 'memSeasonReg':
		$msg = memSeasonReg_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		$s_id = isset($s_id) ? $s_id : '';
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'klist.php?' . href_qs("s_id=$s_id",$qs_basic);
		back($msg,$goto);
		break;
	case 's_modify':
		$uid = s_modify_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		$msg = isset($msg) ? $msg : ''; // $msg 변수 초기화
		back($msg,$goto);
		break;
	case 's_delete':
		$uid = s_delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		elseif(isset($dbinfo['goto_delete']) && $dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'kread.php?'.href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back('',$goto);
		break;
	case 'p_write':
		$uid = p_write_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		$msg = isset($msg) ? $msg : ''; // $msg 변수 초기화
		back($msg,$goto);
		break;
	case 'p_modify':
		$uid = p_modify_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		$msg = isset($msg) ? $msg : ''; // $msg 변수 초기화
		back($msg,$goto);
		break;
	case 'p_delete':
		$uid = p_delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		elseif(isset($dbinfo['goto_delete']) && $dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'kread.php?'.href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back('',$goto);
		break;
	case 'winpoint_add':
		$total = winpoint_add_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'klist.php?' . href_qs("",$qs_basic);
		$msg = "$total 명의 회원에게 승리포인트가 지급되었습니다.";
		back($msg,$goto);
		break;
	case 'winpoint_delete':
		$total = winpoint_delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if(isset($_GET['goto']) && $_GET['goto']) $goto = $_GET['goto'];
		elseif(isset($dbinfo['goto_delete']) && $dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'klist.php?'.href_qs("",$qs_basic);
		$msg = "$total 명의 회원에게 지급된 승리포인트가 삭제되었습니다.";
		back($msg,$goto);
		break;
	default :
		// mode_??? 함수가 있으면 해당 함수 실행
		if( isset($_GET['mode']) && preg_match('/^[a-z0-9\-\_]+$/i', $_GET['mode']) && function_exists('mode_'.$_GET['mode']) ) {
			$func = 'mode_'.$_GET['mode'];
			$func();			
		}
		else
			back('잘못된 요청입니다.');
} // end switch	
	
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================

function kpoint_ok($dbinfo, $klogon)
{

	// 포인트 적립해줌
	if(isset($_GET['csn']) && $_GET['csn']) {
		$rdate_date = $_GET['kdate'];
		$h = substr($_GET['kdate'], 8,2);
		$i = substr($_GET['kdate'], 10,2);
		$s = substr($_GET['kdate'], 12,2);
		$m = substr($_GET['kdate'], 4,2);
		$d = substr($_GET['kdate'], 6,2);
		$y = substr($_GET['kdate'], 0,4);
					
		$rdate = mktime((int)$h, (int)$i, (int)$s, (int)$m, (int)$d, (int)$y); // int 캐스팅
		$rdate_date = "$y-$m-$d";
		
		//게임정보, 시즌정보 가져오기
		$sql_game = "select *
						from savers_secret.game
					  where from_unixtime(g_start,'%Y-%m-%d') ='{$rdate_date}'
						and (g_home = '13' || g_away = '13') "; // and -> && 로 변경
		$list_game = db_arrayone($sql_game);
		if ( !is_array($list_game) ) back("'{$rdate_date}' 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요.");
		
		$weekday = strftime ( "%a", (int)$list_game['g_start'] ); // int 캐스팅

		$sql_season = "select *
						 from savers_secret.season
						where sid = {$list_game['sid']} ";
		$list_season = db_arrayone($sql_season);
		
		// 홈경기 중 어웨이  경기.........
		if ( $list_game['g_home'] == '13' && strpos($list_game['g_ground'], "청주") === false ){
			$list_game['g_home'] = "";
			$list_game['g_away'] = "13";
		}
		////////////////////////////
		
		if ($list_game['g_home'] == '13' ){
			if ($weekday =='Sat' || $weekday == 'Sun'){ // or -> || 로 변경
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
		$sql = "select pid
				  from {$dbinfo['table_kpoint']}
				 where accountno='{$_GET['csn']}'
					and rdate_date='{$rdate_date}'
					and remark='{$remark}'";
					
		if(!db_resultone($sql,0,'pid'))  {
			new21Kpoint($klogon['uid'], $klogon['accountno'], $deposit, $remark, $type,'사이트', $rdate, $list_season['sid'], $list_season['s_name']) ;
		}else{
			back("'{$rdate_date}, {$remark}'으로 이미 등록 되었습니다. 확인 바랍니다.");
		}
	}	
	return isset($list_season['sid']) ? $list_season['sid'] : 0; // 변수 isset 체크
} // end func

function kpointadd_ok($dbinfo)
{
	// $qs
	$qs=array(
				'bid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'accountno'	=> "post,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'		=> "post,trim",
				's_name'	=> "post,trim",
				'rdate_date'=> "post,trim",
				'type'		=> "post,tirm",
				'remark'	=> "post,tirm",
				'deposit'	=> "post,tirm",
				'branch'	=> "post,tirm"
		);
	$qs=check_value($qs);
		
	$rdate = strtotime( $qs['rdate_date'] ) ;
	
	//게임정보, 시즌정보 가져오기
	if ($qs['type'] == '홈경기(주중)' || $qs['type'] == '홈경기(주말)' || $qs['type'] == '어웨이경기' ){
		$sql_game = "select *
						from savers_secret.game
					  where from_unixtime(g_start,'%Y-%m-%d') = '{$qs['rdate_date']}'
						and (g_home = '13' || g_away = '13') "; // and -> || 로 변경, 문자열은 따옴표로 감싸기
		$list_game = db_arrayone($sql_game);
		if ( !is_array($list_game) ) back("{$qs['rdate_date']} 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요.");

		$weekday = strftime ( "%a", (int)$list_game['g_start'] ); // int 캐스팅
	
		$sql_season = "select *
						 from savers_secret.season
						where sid = {$list_game['sid']} ";
		$list_season = db_arrayone($sql_season);
		
		// 홈경기 중 어웨이  경기.........
		if ( $list_game['g_home'] == '13' && strpos($list_game['g_ground'], "청주") === false ){
			$list_game['g_home'] = "";
			$list_game['g_away'] = "13";
		}
		////////////////////////////
		
		if ($list_game['g_home'] == '13' ){
			if ($weekday =='Sat' || $weekday == 'Sun'){ // or -> || 로 변경
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
		$sql = "select pid
				  from {$dbinfo['table_kpoint']}
				 where accountno='{$qs['accountno']}'
					and rdate_date='{$qs['rdate_date']}'
					and remark='{$qs['remark']}'"; // 문자열은 따옴표로 감싸기
		if(!db_resultone($sql,0,'pid')) 
			new21Kpoint($qs['bid'], $qs['accountno'], $qs['deposit'], $qs['remark'], $qs['type'], $qs['branch'], $rdate, $qs['s_id'], $qs['s_name']) ;
		else
			back("{$qs['rdate_date']}, {$qs['remark']}으로 이미 등록 되었습니다. 확인 바랍니다.");
		
	}	
	return $qs['bid'];
} // end func

function kpointmodify_ok($dbinfo)
{
	// $qs
	$qs=array(
				'pid'		=> "post,trim,notnull=" . urlencode("수정 할 고유값이 넘어오지 않았습니다."),
				'bid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'accountno'	=> "post,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'		=> "post,trim",
				's_name'	=> "post,trim",
				'rdate_date'=> "post,trim",
				'type'		=> "post,tirm",
				'remark'	=> "post,tirm",
				'deposit'	=> "post,tirm",
				'branch'	=> "post,tirm"
		);
	$qs=check_value($qs);
		
	$rdate = strtotime( $qs['rdate_date'] ) ;
	
	//게임정보, 시즌정보 가져오기
	if ($qs['type'] == '홈경기(주중)' || $qs['type'] == '홈경기(주말)' || $qs['type'] == '어웨이경기' ){
		$sql_game = "select *
		 				from savers_secret.game
					  where from_unixtime(g_start,'%Y-%m-%d') = '{$qs['rdate_date']}'
						and (g_home = '13' || g_away = '13') "; // and -> || 로 변경, 문자열은 따옴표로 감싸기
		$list_game = db_arrayone($sql_game);
		if ( !is_array($list_game) ) back("{$qs['rdate_date']} 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요.");

		$weekday = strftime ( "%a", (int)$list_game['g_start'] ); // int 캐스팅
	
		$sql_season = "select *
		 				 from savers_secret.season
						where sid = {$list_game['sid']} ";
		$list_season = db_arrayone($sql_season);
		
		// 홈경기 중 어웨이  경기.........
		if ( $list_game['g_home'] == '13' && strpos($list_game['g_ground'], "청주") === false ){
			$list_game['g_home'] = "";
			$list_game['g_away'] = "13";
		}
		////////////////////////////
		
		if ($list_game['g_home'] == '13' ){
			if ($weekday =='Sat' || $weekday == 'Sun'){ // or -> || 로 변경
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
		new21Kpoint_modify($qs['pid'], $qs['bid'], $qs['accountno'], $qs['deposit'], $qs['remark'], $qs['type'], $qs['branch'], $rdate, $qs['s_id'], $qs['s_name']) ;
	}	
	return $qs['bid'];
} // end func


function kpoint_allreg_ok($dbinfo)
{
	// $qs
	$qs=array(
				's_id'		=> "post,trim",
				's_name'	=> "post,trim",
				'rdate_date'=> "post,trim",
				'type'		=> "post,tirm",
				'remark'	=> "post,tirm",
				'deposit'	=> "post,tirm",
				'branch'	=> "post,tirm"
		);
	$qs=check_value($qs);
		
	$rdate = strtotime( $qs['rdate_date'] ) ;
	
	//게임정보, 시즌정보 가져오기
	$sql_game = "select *
					from savers_secret.game
					where from_unixtime(g_start,'%Y-%m-%d') = '{$qs['rdate_date']}'
					and (g_home = '13' || g_away = '13') "; // and -> || 로 변경, 문자열은 따옴표로 감싸기
	$list_game = db_arrayone($sql_game);
	if ( !is_array($list_game) ) back("{$qs['rdate_date']} 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요.");
		
	// 해당 게시물 읽어오기
 
	$sql = "select A.*
			from {$dbinfo['table_kpointinfo']} A, {$dbinfo['table_logon']} C
			where not exists ( SELECT 'X'
							from {$dbinfo['table_kpoint']} B
							WHERE A.bid = B.bid
							and B.rdate_date='{$qs['rdate_date']}'
							and B.type='{$qs['type']}'
							and A.s_id = B.s_id)
			and A.bid = C.uid
			and C.accountno != ''
			and A.s_id = {$qs['s_id']}"; // 문자열은 따옴표로 감싸기
	$rs_kpoint = db_query($sql) ;
	$total = db_count($rs_kpoint);
	if(!$total){
		back("{$qs['rdate_date']}에 포인트를 지급할 대상이 없습니다. 포인트가 이미 일괄 지급 된것 같습니다.");
	}else{
		while($kpoint=db_array($rs_kpoint)) {
			new21Kpoint($kpoint['bid'], $kpoint['accountno'], $qs['deposit'], $qs['remark'], $qs['type'], $qs['branch'], $rdate, $qs['s_id'], $qs['s_name']) ;
			
		}
		$msg = "{$qs['rdate_date']}에 $total 명에게 {$qs['deposit']}점의 포인트가 전부 일괄 지급 되었습니다.";
	}

	return $msg;
} // end func



function kpointdelete_ok(&$dbinfo)
{
	Global $qs_basic, $thisUrl;
	$qs=array(
			'pid'		=> "request,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다."),
			'bid'		=> "request,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
			'accountno'	=> "request,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
			's_id'		=> "request,trim",
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT *
			  from {$dbinfo['table_kpoint']}
			 WHERE pid={$qs['pid']} ";
	$kpoint = db_arrayone($sql) or back("이미 삭제되었거나 잘못된 요청입니다");

	// 회원의 적립통장 구함(여러 적립포인트 중에서 가장 처음에 생성되고 정상인 계좌로)
	$sql = "select *
			  from {$dbinfo['table_kpointinfo']}
			 where bid={$qs['bid']}
				and s_id={$qs['s_id']}
				and errorno='0'
			 order by uid limit 1";
	if(!$kpointinfo = db_arrayone($sql)) back("적립포인트 정보가 없거나 적립포인트 정보에 문제가 있습니다");

	// 삭제
	db_query("DELETE FROM {$dbinfo['table_kpoint']} WHERE pid={$qs['pid']} ");

	$sql = "SELECT sum(deposit) as psum
			  from {$dbinfo['table_kpoint']}
			 where bid={$qs['bid']}
				and s_id={$qs['s_id']} ";
	$ksum = db_arrayone($sql) or back("잘못된 요청입니다");

	db_query("update {$dbinfo['table_kpointinfo']}
				 set `balance`= {$ksum['psum']}
				where `uid` = {$kpointinfo['uid']}
				 and s_id = {$qs['s_id']}");
	db_query("update {$dbinfo['table_logon']}
				 set `balance`= {$ksum['psum']}
				where `uid` = {$qs['bid']}");

	return $qs['bid'];
} // end func memoDelete_ok


function kseason_ok($dbinfo)
{
	// $qs
	$qs=array(
				'bid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'name'		=> "post,trim",
				'accountno'	=> "post,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'		=> "post,trim",
				's_name'	=> "post,trim"
		);
	$qs=check_value($qs);
		
	//카드 정보 생성
	$rs_accountinfo=db_query("SELECT *
								from {$dbinfo['table_kpointinfo']}
								where accountno='{$qs['accountno']}'
								and s_id = {$qs['s_id']} LIMIT 1"); // 문자열은 따옴표로 감싸기
	if(!db_count($rs_accountinfo)) {
		$tankyoupoint = 0;
		$sql_season = "select *
						 from savers_secret.season
						where sid = {$qs['s_id']} ";
		$list_season = db_arrayone($sql_season);
		
		
		$sql = "INSERT INTO {$dbinfo['table_kpointinfo']} (`bid`,`accountno`, `userid`, `name`, `s_id`, `s_name`, `accounttype`, `transfertype`,  `balance`, `comment`, `errorno`, `errornotice`, `rdate`)
					VALUES ({$qs['bid']}, '{$qs['accountno']}','{$qs['userid']}', '{$qs['name']}', {$list_season['sid']}, '{$list_season['s_name']}', '{$list_season['s_name']} 적립포인트', '', '{$tankyoupoint}', '', '0', '', UNIX_TIMESTAMP())";
		db_query($sql);
		if(!($accountno = db_insert_id())) { // mysql_insert_id() -> db_insert_id()로 변경
			back("카드 정보 생성이 실패하였습니다.\\n다시 시도해 주시기 바랍니다.");
		}
	}else
		back("이미 생성 된 카드 정보가 있습니다.");
	
	return $qs['bid'];
} // end func

// 회원별 시즌 전체 생성................davej...........2008-10-08
function memSeasonReg_ok($dbinfo)
{
	// $qs
	$qs=array(
				's_id'		=> "get,trim,notnull=" . urlencode("시즌정보의 고유값이 넘어오지 않았습니다."),
				's_name'	=> "get,trim" 
		);
	$qs=check_value($qs);
	
	// 서포터즈 년도 2015-11-13
	$qs['spts_year'] = substr($qs['s_name'], 0, 4);

	$sql = "select *
			 from {$dbinfo['table_logon']} A
			where (A.priv like '%서포터즈%' || A.priv like '%운영자%' || A.priv like '%포인트관리자%')
			  and (A.spts_year = '{$qs['spts_year']}' || A.spts_cate = '영구회원')
			  and A.accountno != ''
			  and not exists(
				SELECT 'X'
				from {$dbinfo['table_kpointinfo']} B
				WHERE B.s_id = {$qs['s_id']}
				  AND A.uid = B.bid)"; // and -> || 로 변경
	
	$rs_accountinfo=db_query($sql);
	
	if(!($total=db_count($rs_accountinfo))) { // db_count 호출 방식 보완
		$msg = "시즌별 정보가 다 생성 되었거나, 또는 생성 할 정보가 없습니다.";			
	}
	else{  // 회원정보 입력
		for($i = 0 ; $i < $total ; $i++)	{
			
			$list = db_array($rs_accountinfo);
			
			$tankyoupoint = 0;
			$sql_ins = "INSERT INTO {$dbinfo['table_kpointinfo']} (`bid`,`accountno`, `userid`, `name`, `s_id`, `s_name`, `accounttype`, `transfertype`,  `balance`, `comment`, `errorno`, `errornotice`, `rdate`)
						VALUES ({$list['uid']}, '{$list['accountno']}','{$list['userid']}', '{$list['name']}', {$qs['s_id']}, '{$qs['s_name']}', '{$qs['s_name']} 적립포인트', '', '{$tankyoupoint}', '', '0', '', UNIX_TIMESTAMP())"; // 문자열은 따옴표로 감싸기
			db_query($sql_ins);
			
		}
		
		$msg ="$total 건의 회원별 카드 정보 생성을 완료 하였습니다.";
	}

	return $msg;
} // end func


function s_modify_ok($dbinfo)
{
	// $qs
	$qs=array(
				'puid'		=> "post,trim,notnull=" . urlencode("수정 할 고유값이 넘어오지 않았습니다."),
				'bid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'name'		=> "post,trim",
				'accountno'	=> "post,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'		=> "post,trim",
				's_name'	=> "post,trim"
		);
	$qs=check_value($qs);
		
	//카드 정보 생성
	$rs_accountinfo=db_query("SELECT *
								from {$dbinfo['table_kpointinfo']}
								where accountno='{$qs['accountno']}'
								  and s_id ={$qs['s_id']}
								  and uid != {$qs['puid']} LIMIT 1"); // 문자열은 따옴표로 감싸기
	if(!db_count($rs_accountinfo)) {
		$sql = "update {$dbinfo['table_kpointinfo']}
					set `accountno` = '{$qs['accountno']}', `name` = '{$qs['name']}',
						`s_id` = {$qs['s_id']}, `s_name` = '{$qs['s_name']}',
						`mdate` =  UNIX_TIMESTAMP()
				  where uid = {$qs['puid']} "; // 문자열은 따옴표로 감싸기
		db_query($sql);
	}else
		back("{$qs['s_name']} 에 이미 등록 된 카드 정보가 있습니다. 다시 확인 하시기 바랍니다.");
	
	// $qs['uid']가 정의되어 있지 않음. $qs['puid'] 반환으로 추정되나, 함수명 변경 불가 요청에 따라 기존 $qs['uid'] 유지
	return isset($qs['uid']) ? $qs['uid'] : $qs['puid']; 
} // end func


function s_delete_ok(&$dbinfo)
{
	Global $qs_basic, $thisUrl;
	$qs=array(
				'puid'		=> "request,trim,notnull=" . urlencode("삭제 할 고유값이 넘어오지 않았습니다."),
				'bid'		=> "request,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'name'		=> "request,trim",
				'accountno'	=> "request,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'		=> "request,trim",
				's_name'	=> "request,trim"
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql = "SELECT *
			  from {$dbinfo['table_kpointinfo']}
			 WHERE uid={$qs['puid']} ";
	$kpointinfo = db_arrayone($sql) or back("이미 삭제되었거나 잘못된 요청입니다");

	// 시즌카드정보 삭제
	db_query("DELETE from {$dbinfo['table_kpointinfo']}
				WHERE uid={$qs['puid']} ");
	// 포인트정보삭제
	db_query("DELETE from {$dbinfo['table_kpoint']}
				where bid={$qs['bid']}
				and s_id = {$qs['s_id']} ");
	// 경품정보삭제
	db_query("DELETE FROM {$dbinfo['table_kpresent']}
				where bid={$qs['bid']}
				and s_id = {$qs['s_id']} ");
	//포인트정보 0으로
	db_query("update {$dbinfo['table_logon']}
				 set `balance`= '0'
				where `uid` = {$qs['bid']}");
	return $qs['puid'];
} // end func memoDelete_ok


function p_write_ok($dbinfo)
{
	// $qs
	$qs=array(
				'bid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'pdate'		=> "post,trim",
				's_id'		=> "post,trim",
				'present'	=> "post,trim",
				'memo'		=> "post,trim",
				'point'		=> "post,trim"
		);
	$qs=check_value($qs);
	
	$pdate = strtotime($qs['pdate']);
	
	//경품지급 등록
	$rs_kpresent=db_query("SELECT * FROM {$dbinfo['table_kpresent']} WHERE bid = {$qs['bid']} and present='{$qs['present']}' and s_id = {$qs['s_id']} LIMIT 1"); // 문자열은 따옴표로 감싸기
	if(!($kpresent = db_count($rs_kpresent))) { // db_count 호출 방식 보완
		$sql = "INSERT INTO {$dbinfo['table_kpresent']} (`bid`, `s_id`, `point`, `pdate`, `present`,  `memo`, `rdate`)
				VALUES ({$qs['bid']}, {$qs['s_id']},{$qs['point']}, '{$pdate}', '{$qs['present']}', '{$qs['memo']}',  UNIX_TIMESTAMP())"; // 문자열은 따옴표로 감싸기
		db_query($sql);
		if(!($accountno = db_insert_id())) { // mysql_insert_id() -> db_insert_id()로 변경
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
				'uid'		=> "post,trim,notnull=" . urlencode("수정 할 고유값이 넘어오지 않았습니다."),
				'bid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'pdate'		=> "post,trim",
				's_id'		=> "post,trim",
				'present'	=> "post,trim",
				'memo'		=> "post,trim",
				'point'		=> "post,trim"
		);
	$qs=check_value($qs);
		
	$pdate = strtotime($qs['pdate']);
	
	//경품 생성
	$sql_kpresent = "SELECT *
					  FROM {$dbinfo['table_kpresent']}
					  WHERE present='{$qs['present']}'
					  and bid = {$qs['bid']}
					  and s_id = {$qs['s_id']}
					  and memo = '{$qs['memo']}'
					  and uid != {$qs['uid']} LIMIT 1"; // 문자열은 따옴표로 감싸기
	
	$rs_kpresent=db_query($sql_kpresent);
	if(!($kpresent = db_count($rs_kpresent))) { // db_count 호출 방식 보완
		$sql = "update {$dbinfo['table_kpresent']}
				set `s_id` = {$qs['s_id']}, `point` = {$qs['point']},
				`pdate` = '{$pdate}', `present` = '{$qs['present']}', `memo` = '{$qs['memo']}',
				`rdate` =  UNIX_TIMESTAMP()
				where uid = {$qs['uid']} "; // 문자열은 따옴표로 감싸기
		db_query($sql);
	}else
		back("{$qs['pdate']}에 {$qs['present']} 경품이 이미 지급 되었습니다.");
	
	return $qs['bid'];
} // end func


function p_delete_ok(&$dbinfo)
{
	Global $qs_basic, $thisUrl;
	$qs=array(
				'uid'		=> "request,trim,notnull=" . urlencode("삭제 할 고유값이 넘어오지 않았습니다."),
				'bid'		=> "request,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'pdate'		=> "request,trim",
				's_id'		=> "request,trim",
				'present'	=> "request,trim",
				'memo'		=> "request,trim",
				'point'		=> "request,trim"
		);
	$qs=check_value($qs);

	// 해당 게시물 읽어오기
	$sql_kpresent="SELECT *
					FROM {$dbinfo['table_kpresent']}
					WHERE uid={$qs['uid']} LIMIT 1";
	$kpresent = db_arrayone($sql_kpresent) or back("이미 삭제되었거나 잘못된 요청입니다");

	// 삭제
	db_query("DELETE FROM {$dbinfo['table_kpresent']} WHERE uid={$qs['uid']} ");

	return $qs['bid'];
} // end func memoDelete_ok


function winpoint_add_ok($dbinfo)
{
	// $qs
	$qs=array(
				's_id'		=> "post,trim",
				's_name'	=> "post,trim",
				'rdate_date'=> "post,trim",
				'type'		=> "post,tirm",
				'remark'	=> "post,tirm",
				'deposit'	=> "post,tirm",
				'branch'	=> "post,tirm"
		);
	$qs=check_value($qs);
		
	$rdate = strtotime( $qs['rdate_date'] ) ;
	
	//게임정보, 시즌정보 가져오기
	$sql_game = "select *
					from savers_secret.game
					where from_unixtime(g_start,'%Y-%m-%d') = '{$qs['rdate_date']}'
					and (g_home = '13' || g_away = '13') "; // and -> || 로 변경, 문자열은 따옴표로 감싸기
	$list_game = db_arrayone($sql_game);
	if ( !is_array($list_game) ) back("{$qs['rdate_date']} 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요.");
	if($list_game['g_home']==13) {
		$strWin = ((int)$list_game['home_score'] > (int)$list_game['away_score']) ? "승" : "패"; // int 캐스팅
	}
	else {
		$strWin = ((int)$list_game['home_score'] < (int)$list_game['away_score']) ? "승" : "패"; // int 캐스팅
	}
	if ($strWin == '패') back("{$qs['rdate_date']} 경기는 패한 경기입니다.");
		
	// 해당 게시물 읽어오기
	$sql = "SELECT *
			from {$dbinfo['table_kpoint']}
			WHERE rdate_date='{$qs['rdate_date']}'
			and type='{$qs['type']}' "; // 문자열은 따옴표로 감싸기
	$rs_kpoint = db_query($sql) ;
	$total = db_count($rs_kpoint);
	if($total > 0)
		back("{$qs['rdate_date']}에 승리포인트를 받은 회원이 $total 명 있습니다.\\n\\n포인트가 중복 될 수 있습니다.\\n\\n승리포인트일괄 삭제 후 다시 시도 해 주세요.", "klist.php");

	// 해당 게시물 읽어오기
	$sql = "SELECT *
			from {$dbinfo['table_kpoint']}
			WHERE rdate_date='{$qs['rdate_date']}'
			and (type='홈경기(주중)' || type='홈경기(주말)' || type='어웨이경기' )"; // or -> || 로 변경, 문자열은 따옴표로 감싸기
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
			'rdate_date'=> "request,trim",
			'type'		=> "post,tirm",
			'deposit'	=> "post,tirm",
			's_id'		=> "request,trim"
		);
	$qs=check_value($qs);

	$str_del = ''; // 변수 초기화
	// 해당 게시물 읽어오기
	$sql = "SELECT *
			from {$dbinfo['table_kpoint']}
			WHERE rdate_date='{$qs['rdate_date']}' and type='{$qs['type']}' "; // 문자열은 따옴표로 감싸기
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
	db_query("DELETE from {$dbinfo['table_kpoint']}
					WHERE rdate_date='{$qs['rdate_date']}' and type='{$qs['type']}' "); // 문자열은 따옴표로 감싸기

	if(!empty($str_del) && is_numeric($qs['deposit'])) { // $str_del 체크 및 $qs['deposit']가 숫자인지 확인
		db_query("update {$dbinfo['table_kpointinfo']}
					set `balance`= balance - {$qs['deposit']}
					where `bid` in ($str_del) and s_id = {$qs['s_id']} ");
		db_query("update {$dbinfo['table_logon']}
					set `balance`= balance - {$qs['deposit']}
					where `uid` in ($str_del) ");
	}
	return $total;
} // end func memoDelete_ok


?>