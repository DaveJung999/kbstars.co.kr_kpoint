<?php
//=======================================================
// 설  명 : 템플릿 샘플
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

switch($_REQUEST['mode']) {
	case 'kpoint':
		
		$csn = db_escape($_GET['csn'] ?? ''); // $_GET['csn'] 이스케이프 및 널 병합 처리
		
		// season 선택시에
		if($csn) {
			$sql = "SELECT *
					FROM {$dbinfo['table_logon']}
					WHERE accountno='{$csn}'"; // 배열 키 수정 및 변수 이스케이프된 값 사용
			//if(!$klogon = db_arrayone($sql)) back('미 등록 회원입니다. 등록 후 다시 시도 해 주시기 바랍니다.', 'kmember.php?accountno='.$_GET['csn']);
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

		back($msg,$goto);
		break;
	case 'kpointadd':
		$uid = kpointadd_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'kpointmodify':
		$uid = kpointmodify_ok($dbinfo, $uid);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'kpoint_allreg':
		$msg = kpoint_allreg_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'klist.php?' . href_qs("s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'kpointdelete':
		$uid = kpointdelete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		elseif($dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'kread.php?'.href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back('',$goto);
		break;
	case 'kseason':
		$uid = kseason_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'memSeasonReg':
		$msg = memSeasonReg_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'klist.php?' . href_qs("s_id=$s_id",$qs_basic);
		back($msg,$goto);
		break;
	case 's_modify':
		$uid = s_modify_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 's_delete':
		$uid = s_delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		elseif($dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'kread.php?'.href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back('',$goto);
		break;
	case 'p_write':
		$uid = p_write_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'p_modify':
		$uid = p_modify_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		else $goto = $thisUrl.'kread.php?' . href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
		back($msg,$goto);
		break;
	case 'p_delete':
		$uid = p_delete_ok($dbinfo);
		
		// 어느 페이지로 이동할 것인지 결정
		if($_GET['goto']) $goto = $_GET['goto'];
		elseif($dbinfo['goto_delete']) $goto = $dbinfo['goto_delete'];
		else $goto = $thisUrl.'kread.php?'.href_qs("mode=inquiry&bid={$_REQUEST['bid']}&s_id={$_REQUEST['s_id']}&cur_sid={$_REQUEST['cur_sid']}",$qs_basic);
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
		if( isset($_GET['mode']) && preg_match('/^[a-z0-9\-\_]+$/i', $_GET['mode'])
			&& function_exists('mode_'.$_GET['mode']) ) { // 논리 연산자 수정 (and -> &&)
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
	
	$csn = db_escape($_GET['csn'] ?? ''); // $_GET['csn'] 이스케이프 및 널 병합 처리
	$kdate = db_escape($_GET['kdate'] ?? ''); // $_GET['kdate'] 이스케이프 및 널 병합 처리
	
	// 포인트 적립해줌
	if( $csn) {
		$rdate_date = $kdate;
		$h = substr($kdate, 8,2);
		$i = substr($kdate, 10,2);
		$s = substr($kdate, 12,2);
		$m = substr($kdate, 4,2);
		$d = substr($kdate, 6,2);
		$y = substr($kdate, 0,4);
					
		$rdate = mktime($h, $i, $s, $m, $d, $y);
		$rdate_date = "{$y}-{$m}-{$d}";
		
		//게임정보, 시즌정보 가져오기
		$sql_game = "SELECT *
						FROM savers_secret.game
					  WHERE from_unixtime(g_start,'%Y-%m-%d') ='{$rdate_date}'
						AND (g_home = '13' OR g_away = '13')"; // 변수 이스케이프된 값 사용
		$list_game = db_arrayone($sql_game);
		if ( !is_array($list_game) ) back("'{$rdate_date}' 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요."); // 변수 보간 수정
		
		$weekday = strftime ( "%a", $list_game['g_start'] );

		$sid = db_escape($list_game['sid']); // DB 결과값도 쿼리에 사용될 경우 이스케이프
		$sql_season = "SELECT *
						 FROM savers_secret.season
						WHERE sid = '{$sid}'"; // 변수 이스케이프된 값 사용
		$list_season = db_arrayone($sql_season);
		
		// 홈경기 중 어웨이  경기.........
		if ( $list_game['g_home'] == '13' && strpos($list_game['g_ground'], "청주") === false ){
			$list_game['g_home'] = "";
			$list_game['g_away'] = "13";
		}
		////////////////////////////
		
		if ($list_game['g_home'] == '13' ){
			if ($weekday =='Sat' || $weekday == 'Sun'){
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
		$remark_escaped = db_escape($remark); // remark도 쿼리에 사용되므로 이스케이프
		$sql = "SELECT pid
				  FROM {$dbinfo['table_kpoint']}
				 WHERE accountno='{$csn}'
					AND rdate_date='{$rdate_date}'
					AND remark='{$remark_escaped}'"; // 배열 키 수정 및 변수 이스케이프된 값 사용
					
		if(!db_resultone($sql,0,'pid'))  {
			new21Kpoint($klogon['uid'], $klogon['accountno'], $deposit, $remark, $type,'사이트', $rdate, $list_season['sid'], $list_season['s_name']) ;
		}else{
			back("'{$rdate_date}, {$remark}'으로 이미 등록 되었습니다. 확인 바랍니다."); // 변수 보간 수정
		}
	}	
	return $list_season['sid'];
} // end func

function kpointadd_ok($dbinfo)
{
	// $qs
	$qs=array(
				'bid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'accountno'	=> "post,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'	=> "post,trim",
				's_name'	=> "post,trim",
				'rdate_date'	=> "post,trim",
				'type'	=> "post,tirm",
				'remark'	=> "post,tirm",
				'deposit'	=> "post,tirm",
				'branch'	=> "post,tirm"
		);
	$qs=check_value($qs);
	
	// SQL 쿼리에 사용할 변수들을 이스케이프 처리
	$rdate_date = db_escape($qs['rdate_date']);
	$accountno = db_escape($qs['accountno']);
	$remark = db_escape($qs['remark']);
	$type = db_escape($qs['type']);
	
	$rdate = strtotime( $qs['rdate_date'] ) ;
	
	//게임정보, 시즌정보 가져오기
	if ($qs['type'] == '홈경기(주중)' || $qs['type'] == '홈경기(주말)' || $qs['type'] == '어웨이경기' ){
		$sql_game = "SELECT *
						FROM savers_secret.game
					  WHERE from_unixtime(g_start,'%Y-%m-%d') ='{$rdate_date}'
						AND (g_home = '13' OR g_away = '13')"; // 변수 이스케이프된 값 사용
		$list_game = db_arrayone($sql_game);
		if ( !is_array($list_game) ) back("{$qs['rdate_date']} 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요."); // 변수 보간 수정

		$weekday = strftime ( "%a", $list_game['g_start'] );
	
		$sid = db_escape($list_game['sid']);
		$sql_season = "SELECT *
						 FROM savers_secret.season
						WHERE sid = '{$sid}'"; // 변수 이스케이프된 값 사용
		$list_season = db_arrayone($sql_season);
		
		// 홈경기 중 어웨이  경기.........
		if ( $list_game['g_home'] == '13' && strpos($list_game['g_ground'], "청주") === false ){
			$list_game['g_home'] = "";
			$list_game['g_away'] = "13";
		}
		////////////////////////////
		
		if ($list_game['g_home'] == '13' ){
			if ($weekday =='Sat' || $weekday == 'Sun'){
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
	if( $accountno) {
		// 이미 적립했는지
		$sql = "SELECT pid
				  FROM {$dbinfo['table_kpoint']}
				 WHERE accountno='{$accountno}'
					AND rdate_date='{$rdate_date}'
					AND remark='{$remark}'"; // 배열 키 수정 및 변수 이스케이프된 값 사용
		if(!db_resultone($sql,0,'pid')) 
			// 함수 인자에서 불필요한 중괄호 제거 ({$qs['accountno']} -> $qs['accountno'])
			new21Kpoint($qs['bid'], $qs['accountno'], $qs['deposit'], $qs['remark'], $qs['type'], $qs['branch'], $rdate, $qs['s_id'], $qs['s_name']) ;
		else
			back("{$qs['rdate_date']}, {$qs['remark']}으로 이미 등록 되었습니다. 확인 바랍니다."); // 변수 보간 수정
		
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
				's_id'	=> "post,trim",
				's_name'	=> "post,trim",
				'rdate_date'	=> "post,trim",
				'type'	=> "post,tirm",
				'remark'	=> "post,tirm",
				'deposit'	=> "post,tirm",
				'branch'	=> "post,tirm"
		);
	$qs=check_value($qs);
	
	// SQL 쿼리에 사용할 변수들을 이스케이프 처리
	$rdate_date = db_escape($qs['rdate_date']);
	
	$rdate = strtotime( $qs['rdate_date'] ) ;
	
	//게임정보, 시즌정보 가져오기
	if ($qs['type'] == '홈경기(주중)' || $qs['type'] == '홈경기(주말)' || $qs['type'] == '어웨이경기' ){
		$sql_game = "SELECT *
		 				FROM savers_secret.game
					  WHERE from_unixtime(g_start,'%Y-%m-%d') ='{$rdate_date}'
						AND (g_home = '13' OR g_away = '13')"; // 변수 이스케이프된 값 사용
		$list_game = db_arrayone($sql_game);
		if ( !is_array($list_game) ) back("{$qs['rdate_date']} 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요."); // 변수 보간 수정

		$weekday = strftime ( "%a", $list_game['g_start'] );
	
		$sid = db_escape($list_game['sid']);
		$sql_season = "SELECT *
		 				 FROM savers_secret.season
						WHERE sid = '{$sid}'"; // 변수 이스케이프된 값 사용
		$list_season = db_arrayone($sql_season);
		
		// 홈경기 중 어웨이  경기.........
		if ( $list_game['g_home'] == '13' && strpos($list_game['g_ground'], "청주") === false ){
			$list_game['g_home'] = "";
			$list_game['g_away'] = "13";
		}
		////////////////////////////
		
		if ($list_game['g_home'] == '13' ){
			if ($weekday =='Sat' || $weekday == 'Sun'){
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
		// 함수 인자에서 불필요한 중괄호 제거 ({$qs['accountno']} -> $qs['accountno'])
		new21Kpoint_modify($qs['pid'], $qs['bid'], $qs['accountno'], $qs['deposit'], $qs['remark'], $qs['type'], $qs['branch'], $rdate, $qs['s_id'], $qs['s_name']) ;
	}	
	return $qs['bid'];
} // end func


function kpoint_allreg_ok($dbinfo)
{
	// $qs
	$qs=array(
				's_id'	=> "post,trim",
				's_name'	=> "post,trim",
				'rdate_date'	=> "post,trim",
				'type'	=> "post,tirm",
				'remark'	=> "post,tirm",
				'deposit'	=> "post,tirm",
				'branch'	=> "post,tirm"
		);
	$qs=check_value($qs);
	
	// SQL 쿼리에 사용할 변수들을 이스케이프 처리
	$s_id = db_escape($qs['s_id']);
	$rdate_date = db_escape($qs['rdate_date']);
	$type = db_escape($qs['type']);
	$deposit = db_escape($qs['deposit']);
		
	$rdate = strtotime( $qs['rdate_date'] ) ;
	
	//게임정보, 시즌정보 가져오기
	$sql_game = "SELECT *
					FROM savers_secret.game
					WHERE from_unixtime(g_start,'%Y-%m-%d') ='{$rdate_date}'
					AND (g_home = '13' OR g_away = '13')"; // 변수 이스케이프된 값 사용
	$list_game = db_arrayone($sql_game);
	if ( !is_array($list_game) ) back("{$qs['rdate_date']} 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요."); // 변수 보간 수정
		
	// 해당 게시물 읽어오기
 
	$sql = "SELECT A.*
			FROM {$dbinfo['table_kpointinfo']} A, {$dbinfo['table_logon']} C
			WHERE NOT EXISTS ( SELECT 'X'
							FROM {$dbinfo['table_kpoint']} B
							WHERE A.bid = B.bid
							AND B.rdate_date='{$rdate_date}'
							AND B.type='{$type}'
							AND A.s_id = B.s_id)
			AND A.bid = C.uid
			AND C.accountno != ''
			AND A.s_id = '{$s_id}'"; // 배열 키 수정 및 변수 이스케이프된 값 사용
	$rs_kpoint = db_query($sql) ;
	$total = db_count($rs_kpoint);
	if(!$total){
		back("{$qs['rdate_date']}에 포인트를 지급할 대상이 없습니다. 포인트가 이미 일괄 지급 된것 같습니다."); // 변수 보간 수정
	}else{
		while($kpoint=db_array($rs_kpoint)) {
			// new21Kpoint는 $qs의 이스케이프되지 않은 원본 값을 사용하는 것이 일반적이므로 $qs 값을 전달합니다.
			new21Kpoint($kpoint['bid'], $kpoint['accountno'], $qs['deposit'], $qs['remark'], $qs['type'], $qs['branch'], $rdate, $qs['s_id'], $qs['s_name']) ;
			
		}
		$msg = "{$qs['rdate_date']}에 {$total} 명에게 {$qs['deposit']}점의 포인트가 전부 일괄 지급 되었습니다."; // 변수 보간 수정
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
			's_id'	=> "request,trim",
		);
	$qs=check_value($qs);

	// SQL 쿼리에 사용할 변수들을 이스케이프 처리
	$pid = db_escape($qs['pid']);
	$bid = db_escape($qs['bid']);
	$s_id = db_escape($qs['s_id']);

	// 해당 게시물 읽어오기
	$sql = "SELECT *
			 FROM {$dbinfo['table_kpoint']}
			 WHERE pid='{$pid}'"; // pid를 이스케이프된 변수로 대체 및 문자열로 처리
	$kpoint = db_arrayone($sql) or back("이미 삭제되었거나 잘못된 요청입니다");

	// 회원의 적립통장 구함(여러 적립포인트 중에서 가장 처음에 생성되고 정상인 계좌로)
	$sql = "SELECT *
			 FROM {$dbinfo['table_kpointinfo']}
			 WHERE bid='{$bid}'
				AND s_id='{$s_id}'
				AND errorno='0'
			 ORDER BY uid LIMIT 1"; // bid, s_id를 이스케이프된 변수로 대체 및 문자열로 처리
	if(!$kpointinfo = db_arrayone($sql)) back("적립포인트 정보가 없거나 적립포인트 정보에 문제가 있습니다");

	// 삭제
	db_query("DELETE FROM {$dbinfo['table_kpoint']} WHERE pid='{$pid}'"); // pid를 이스케이프된 변수로 대체 및 문자열로 처리

	$sql = "SELECT sum(deposit) AS psum
			 FROM {$dbinfo['table_kpoint']}
			 WHERE bid='{$bid}'
				AND s_id='{$s_id}'"; // bid, s_id를 이스케이프된 변수로 대체 및 문자열로 처리
	$ksum = db_arrayone($sql) or back("잘못된 요청입니다");

	// kpointinfo 테이블의 잔액 업데이트
	db_query("UPDATE {$dbinfo['table_kpointinfo']}
				 SET `balance` = '{$ksum['psum']}'
				WHERE `uid` = '{$kpointinfo['uid']}'
				 AND s_id = '{$s_id}'"); // uid, s_id를 문자열로 처리

	// logon 테이블의 잔액 업데이트
	db_query("UPDATE {$dbinfo['table_logon']}
				 SET `balance` = '{$ksum['psum']}'
				WHERE `uid` = '{$bid}'"); // bid를 이스케이프된 변수로 대체 및 문자열로 처리

	return $qs['bid'];
} // end func memoDelete_ok


function kseason_ok($dbinfo)
{
	// $qs
	$qs=array(
				'bid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'name'	=> "post,trim",
				'accountno'	=> "post,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'	=> "post,trim",
				's_name'	=> "post,trim"
		);
	$qs=check_value($qs);
	
	// SQL 쿼리에 사용할 변수들을 이스케이프 처리
	$bid = db_escape($qs['bid']);
	$name = db_escape($qs['name']);
	$accountno = db_escape($qs['accountno']);
	$s_id = db_escape($qs['s_id']);
	$s_name = db_escape($qs['s_name']);

	//카드 정보 생성
	$rs_accountinfo=db_query("SELECT *
								FROM {$dbinfo['table_kpointinfo']}
								WHERE accountno='{$accountno}'
								AND s_id = '{$s_id}' LIMIT 1"); // 배열 키 수정 및 변수 이스케이프된 값 사용
	if(!db_count($rs_accountinfo)) {
		$tankyoupoint = 0;
		$sql_season = "SELECT *
						 FROM savers_secret.season
						WHERE sid = '{$s_id}'"; // 변수 이스케이프된 값 사용
		$list_season = db_arrayone($sql_season);
		
		
		// INSERT 쿼리 수정: 배열 키 수정, 문자열 보간 및 타입 오류 수정
		$account_type = db_escape($list_season['s_name'] . ' 적립포인트'); // 문자열 결합 후 이스케이프 처리
		$sql = "INSERT INTO {$dbinfo['table_kpointinfo']} (`bid`,`accountno`, `userid`, `name`, `s_id`, `s_name`, `accounttype`, `transfertype`, `balance`, `comment`, `errorno`, `errornotice`, `rdate`)
					VALUES ('{$bid}', '{$accountno}', '', '{$name}', '{$list_season['s_id']}', '{$list_season['s_name']}', '{$account_type}', '', '{$tankyoupoint}', '', '0', '', UNIX_TIMESTAMP())"; // $qs['userid']가 없으므로 공백 처리.
		db_query($sql);
		
		// mysql_insert_id() 대신 db_insert_id() 사용 (요청된 사용자 정의 함수 사용)
		if(!$accountno = db_insert_id()) {
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
				's_name'		=> "get,trim" 
		);
	$qs=check_value($qs);
	
	// SQL 쿼리에 사용할 변수들을 이스케이프 처리
	$s_id = db_escape($qs['s_id']);
	$s_name = db_escape($qs['s_name']);
	
	// 서포터즈 년도 2015-11-13
	$qs['spts_year'] = substr($qs['s_name'], 0, 4);
	$spts_year = db_escape($qs['spts_year']);
	
	$sql = "SELECT *
			FROM {$dbinfo['table_logon']} A
			WHERE (((A.priv LIKE '%홍보대사%' OR A.priv LIKE '%서포터즈%') AND A.spts_year = '{$spts_year}' )
			  OR A.spts_cate = '영구회원' OR A.priv LIKE '%운영자%' OR A.priv LIKE '%포인트관리자%')
			  AND A.accountno != ''
			  AND NOT EXISTS(
				SELECT 'X'
				FROM {$dbinfo['table_kpointinfo']} B
				WHERE B.s_id = '{$s_id}'
				  AND A.uid = B.bid)"; // 배열 키 수정 및 변수 이스케이프된 값 사용

	$rs_accountinfo=db_query($sql);
	
	if(!$total=db_count($rs_accountinfo)) { // 회원별 입력할 정보가 없을때
		$msg = "시즌별 정보가 다 생성 되었거나, 또는 생성 할 정보가 없습니다.";			
	}
	else{  // 회원정보 입력
		for($i = 0 ; $i < $total ; $i++)	{
			
			$list = db_array($rs_accountinfo);
			
			$tankyoupoint = 0;
			
			// INSERT 쿼리 수정: 배열 키 수정, 문자열 보간 및 타입 오류 수정
			$bid = db_escape($list['uid']);
			$accountno = db_escape($list['accountno']);
			$userid = db_escape($list['userid']);
			$name = db_escape($list['name']);
			$account_type = db_escape($qs['s_name'] . ' 적립포인트'); // 문자열 결합 후 이스케이프 처리
			
			$sql_ins = "INSERT INTO {$dbinfo['table_kpointinfo']} (`bid`,`accountno`, `userid`, `name`, `s_id`, `s_name`, `accounttype`, `transfertype`, `balance`, `comment`, `errorno`, `errornotice`, `rdate`)
						VALUES ('{$bid}', '{$accountno}', '{$userid}', '{$name}', '{$s_id}', '{$s_name}', '{$account_type}', '', '{$tankyoupoint}', '', '0', '', UNIX_TIMESTAMP())"; // $qs 값을 이스케이프된 $s_id, $s_name으로 대체
			db_query($sql_ins);
			
		}
		
		$msg ="{$total} 건의 회원별 카드 정보 생성을 완료 하였습니다."; // 변수 보간 수정
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

	// PHP 7+ 호환성 및 SQL 인젝션 방지: $qs 변수들을 이스케이프 처리
	$accountno = db_escape($qs['accountno']);
	$s_id = db_escape($qs['s_id']);
	$puid = db_escape($qs['puid']);
	$name = db_escape($qs['name']);
	$s_name = db_escape($qs['s_name']);
	
	$rs_accountinfo=db_query("SELECT *
								FROM {$dbinfo['table_kpointinfo']}
								WHERE accountno='{$accountno}'
								  AND s_id ='{$s_id}'
								  AND uid != '{$puid}' LIMIT 1"); // 배열 키 수정 및 변수 이스케이프된 값 사용
	if(!db_count($rs_accountinfo)) {
		$sql = "UPDATE {$dbinfo['table_kpointinfo']}
					SET `accountno` = '{$accountno}', `name` = '{$name}',
						`s_id` = '{$s_id}', `s_name` = '{$s_name}',
						`mdate` = UNIX_TIMESTAMP()
					WHERE uid = '{$puid}'"; // 배열 키 수정 및 변수 이스케이프된 값 사용
		db_query($sql);
	}else
		// 오류 메시지 내에서도 이스케이프된 변수를 사용하여 안전성을 높임
		back("{$s_name} 에 이미 등록 된 카드 정보가 있습니다. 다시 확인 하시기 바랍니다.");
	
	return $qs['uid'];
} // end func


function s_delete_ok(&$dbinfo)
{
	Global $qs_basic, $thisUrl;
	$qs=array(
				'puid'		=> "request,trim,notnull=" . urlencode("삭제 할 고유값이 넘어오지 않았습니다."),
				'bid'		=> "request,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'name'	=> "request,trim",
				'accountno'	=> "request,trim,notnull=" . urlencode("카드번호가 넘어오지 않았습니다."),
				's_id'	=> "request,trim",
				's_name'	=> "request,trim"
		);
	$qs=check_value($qs);
	
	// PHP 7+ 호환성 및 SQL 인젝션 방지를 위해 $qs 변수들을 이스케이프 처리합니다.
	$puid = db_escape($qs['puid']);
	$bid = db_escape($qs['bid']);
	$s_id = db_escape($qs['s_id']);

	// 해당 게시물 읽어오기
	$sql = "SELECT *
			 FROM {$dbinfo['table_kpointinfo']}
			 WHERE uid='{$puid}'"; // uid를 이스케이프 처리된 변수로 대체
	$kpointinfo = db_arrayone($sql) or back("이미 삭제되었거나 잘못된 요청입니다");

	// 시즌카드정보 삭제
	// 배열 키에 따옴표를 추가하고 중괄호로 감싸서 PHP 7+ 호환성을 확보합니다.
	db_query("DELETE FROM {$dbinfo['table_kpointinfo']}
				WHERE uid='{$puid}'"); // uid를 이스케이프 처리된 변수로 대체
	
	// 포인트정보삭제
	db_query("DELETE FROM {$dbinfo['table_kpoint']}
				WHERE bid='{$bid}'
				AND s_id = '{$s_id}'"); // bid, s_id를 이스케이프 처리된 변수로 대체
	
	// 경품정보삭제
	db_query("DELETE FROM {$dbinfo['table_kpresent']}
				WHERE bid='{$bid}'
				AND s_id = '{$s_id}'"); // bid, s_id를 이스케이프 처리된 변수로 대체
	
	//포인트정보 0으로
	db_query("UPDATE {$dbinfo['table_logon']}
				 SET `balance`= '0'
				WHERE `uid` = '{$bid}'"); // uid(bid)를 이스케이프 처리된 변수로 대체
	
	return $qs['puid'];
} // end func memoDelete_ok


function p_write_ok($dbinfo)
{
	// $qs
	$qs=array(
				'bid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'pdate'	=> "post,trim",
				's_id'	=> "post,trim",
				'present'	=> "post,trim",
				'memo'	=> "post,trim",
				'point'	=> "post,trim"
		);
	$qs=check_value($qs);
	
	$pdate = strtotime($qs['pdate']);
	
	// SQL 쿼리에 사용할 변수들을 이스케이프 처리
	$bid = db_escape($qs['bid']);
	$s_id = db_escape($qs['s_id']);
	$present = db_escape($qs['present']);
	
	//경품지급 등록
	$rs_kpresent=db_query("SELECT * FROM {$dbinfo['table_kpresent']} WHERE bid = '{$bid}' and present='{$present}' and s_id = '{$s_id}' LIMIT 1"); // 배열 키 수정 및 변수 이스케이프된 값 사용
	if(!db_count($rs_kpresent)) {
		// SQL 인젝션 방지: $qs 배열의 외부 입력 값들을 이스케이프 처리합니다.
		$point = db_escape($qs['point']);
		$memo = db_escape($qs['memo']);
		$pdate_escaped = db_escape($pdate);

		$sql = "INSERT INTO {$dbinfo['table_kpresent']} (`bid`, `s_id`, `point`, `pdate`, `present`, `memo`, `rdate`)
				VALUES ('{$bid}', '{$s_id}', '{$point}', '{$pdate_escaped}', '{$present}', '{$memo}', UNIX_TIMESTAMP())"; // 배열 키 수정 및 변수 이스케이프된 값 사용
		db_query($sql);
		
		// mysql_insert_id() 대신 db_insert_id() 사용 (요청된 사용자 정의 함수 사용)
		if(!$accountno = db_insert_id()) {
			back("경품지급 등록이 실패하였습니다.\\n다시 시도해 주시기 바랍니다.");
		}
	}else
		back("{$qs['pdate']}에 {$qs['present']} 경품이 이미 지급 되었습니다."); // 변수 보간 수정
	
	return $qs['bid'];
} // end func

function p_modify_ok($dbinfo)
{
	// $qs
	$qs=array(
				'uid'		=> "post,trim,notnull=" . urlencode("수정 할 고유값이 넘어오지 않았습니다."),
				'bid'		=> "post,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'pdate'	=> "post,trim",
				's_id'	=> "post,trim",
				'present'	=> "post,trim",
				'memo'	=> "post,trim",
				'point'	=> "post,trim"
		);
	$qs=check_value($qs);
		
	$pdate = strtotime($qs['pdate']);
	
	// SQL 쿼리에 사용할 변수들을 이스케이프 처리
	$uid = db_escape($qs['uid']);
	$bid = db_escape($qs['bid']);
	$s_id = db_escape($qs['s_id']);
	$present = db_escape($qs['present']);
	$memo = db_escape($qs['memo']);
	$point = db_escape($qs['point']);
	$pdate_escaped = db_escape($pdate);

	
	//경품 생성
	$sql_kpresent = "SELECT *
					  FROM {$dbinfo['table_kpresent']}
					  WHERE present='{$present}'
					  AND bid = '{$bid}'
					  AND s_id = '{$s_id}'
					  AND memo = '{$memo}'
					  AND uid != '{$uid}' LIMIT 1"; // 배열 키 수정 및 변수 이스케이프된 값 사용
	
	$rs_kpresent=db_query($sql_kpresent);
	if(!$kpresent = db_count($rs_kpresent)) {
		$sql = "UPDATE {$dbinfo['table_kpresent']}
				SET `s_id` = '{$s_id}', `point` = '{$point}',
				`pdate` = '{$pdate_escaped}', `present` = '{$present}', `memo` = '{$memo}',
				`rdate` = UNIX_TIMESTAMP()
				WHERE uid = '{$uid}'"; // 배열 키 수정 및 변수 이스케이프된 값 사용
		db_query($sql);
	}else
		back("{$qs['pdate']}에 {$qs['present']} 경품이 이미 지급 되었습니다."); // 변수 보간 수정
	
	return $qs['bid'];
} // end func


function p_delete_ok(&$dbinfo)
{
	Global $qs_basic, $thisUrl;
	$qs=array(
				'uid'		=> "request,trim,notnull=" . urlencode("삭제 할 고유값이 넘어오지 않았습니다."),
				'bid'		=> "request,trim,notnull=" . urlencode("회원의 고유값이 넘어오지 않았습니다."),
				'pdate'	=> "request,trim",
				's_id'	=> "request,trim",
				'present'	=> "request,trim",
				'memo'	=> "request,trim",
				'point'	=> "request,trim"
		);
	$qs=check_value($qs);

	// SQL 쿼리에 사용할 변수들을 이스케이프 처리
	$uid = db_escape($qs['uid']);

	// 해당 게시물 읽어오기
	$sql_kpresent="SELECT *
					FROM {$dbinfo['table_kpresent']}
					WHERE uid='{$uid}' LIMIT 1"; // 배열 키 수정 및 변수 이스케이프된 값 사용
	$kpresent = db_arrayone($sql_kpresent) or back("이미 삭제되었거나 잘못된 요청입니다");

	// 삭제
	db_query("DELETE FROM {$dbinfo['table_kpresent']} WHERE uid='{$uid}'"); // uid를 이스케이프 처리된 변수로 대체

	return $qs['bid'];
} // end func memoDelete_ok


function winpoint_add_ok($dbinfo)
{
	// $qs
	$qs=array(
				's_id'	=> "post,trim",
				's_name'	=> "post,trim",
				'rdate_date'	=> "post,trim",
				'type'	=> "post,tirm",
				'remark'	=> "post,tirm",
				'deposit'	=> "post,tirm",
				'branch'	=> "post,tirm"
		);
	$qs=check_value($qs);
	
	// SQL 쿼리에 사용할 변수들을 이스케이프 처리
	$rdate_date = db_escape($qs['rdate_date']);
	$type = db_escape($qs['type']);
	$deposit = db_escape($qs['deposit']);
	$s_id = db_escape($qs['s_id']);
	
	$rdate = strtotime( $qs['rdate_date'] ) ;
	
	//게임정보, 시즌정보 가져오기
	$sql_game = "SELECT *
					FROM savers_secret.game
					WHERE from_unixtime(g_start,'%Y-%m-%d') ='{$rdate_date}'
					AND (g_home = '13' OR g_away = '13')"; // 변수 이스케이프된 값 사용
	$list_game = db_arrayone($sql_game);
	if ( !is_array($list_game) ) back("{$qs['rdate_date']} 경기는 KB 국민은행 홈 경기 또는 어웨이 경기정보에 없는 날짜입니다. \\n\\n다시 확인 해 주세요."); // 변수 보간 수정
	if($list_game['g_home']==13) {
		$strWin = ($list_game['home_score'] > $list_game['away_score']) ? "승" : "패";
	}
	else {
		$strWin = ($list_game['home_score'] < $list_game['away_score']) ? "승" : "패";
	}
	if ($strWin == '패') back("{$qs['rdate_date']} 경기는 패한 경기입니다."); // 변수 보간 수정
		
	// 해당 게시물 읽어오기
	$sql = "SELECT *
			FROM {$dbinfo['table_kpoint']}
			WHERE rdate_date='{$rdate_date}'
			AND type='{$type}'"; // 배열 키 수정 및 변수 이스케이프된 값 사용
	$rs_kpoint = db_query($sql) ;
	$total = db_count($rs_kpoint);
	if($total > 0)
		back("{$qs['rdate_date']}에 승리포인트를 받은 회원이 {$total} 명 있습니다.\\n\\n포인트가 중복 될 수 있습니다.\\n\\n승리포인트일괄 삭제 후 다시 시도 해 주세요.", "klist.php"); // 변수 보간 수정

	// 해당 게시물 읽어오기
	$sql = "SELECT *
			FROM {$dbinfo['table_kpoint']}
			WHERE rdate_date='{$rdate_date}'
			AND (type='홈경기(주중)' OR type='홈경기(주말)' OR type='어웨이경기' )"; // 배열 키 수정 및 변수 이스케이프된 값 사용
	$rs_kpoint = db_query($sql) ;
	$total = db_count($rs_kpoint);
	if(!$total){
		back("{$qs['rdate_date']}에 포인트를 받은 회원이 없습니다."); // 변수 보간 수정
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

	// SQL 쿼리에 사용할 변수들을 이스케이프 처리
	$rdate_date = db_escape($qs['rdate_date']);
	$type = db_escape($qs['type']);
	$deposit = db_escape($qs['deposit']);
	$s_id = db_escape($qs['s_id']);

	// 해당 게시물 읽어오기
	$sql = "SELECT *
			FROM {$dbinfo['table_kpoint']}
			WHERE rdate_date='{$rdate_date}' AND type='{$type}'"; // 배열 키 수정 및 변수 이스케이프된 값 사용
	$rs_kpoint = db_query($sql) ;
	$total = db_count($rs_kpoint);
	if(!$total){
		back("{$qs['rdate_date']}에 승리포인트를 받은 회원이 없습니다."); // 변수 보간 수정
	}else{
		$str_del = '';
		while($kpoint=db_array($rs_kpoint)) {
			$str_del .= $kpoint['bid'].",";
		}
		$len = strlen($str_del); 
		$str_del = substr($str_del, 0, $len-1);
	}

	// 삭제
	db_query("DELETE FROM {$dbinfo['table_kpoint']}
					WHERE rdate_date='{$rdate_date}' AND type='{$type}'"); // 배열 키 수정 및 변수 이스케이프된 값 사용

	// $str_del은 이미 이스케이프된 정수 목록이므로 쿼리에서 따옴표 없이 사용
	db_query("UPDATE {$dbinfo['table_kpointinfo']}
				SET `balance`= balance - '{$deposit}'
				WHERE `bid` IN ({$str_del}) AND s_id = '{$s_id}'"); // 배열 키 수정 및 변수 이스케이프된 값 사용

	db_query("UPDATE {$dbinfo['table_logon']}
				SET `balance`= balance - '{$deposit}'
				WHERE `uid` IN ({$str_del})"); // 배열 키 수정 및 변수 이스케이프된 값 사용

	return $total;
} // end func memoDelete_ok


?>