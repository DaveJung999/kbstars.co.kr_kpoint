<?php
//=======================================================
// 설 명 : sitePHPbasic 해더 파일(header.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 06/03/24
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인		수정 내용
// -------- ------ ------------------------------------
// 06/03/24 박선민 modeok 옵션 추가
// 25/08/08 AI 수정 (PHP 7.4.33 호환)
//=======================================================

// 이 파일의 경로를 저장
$thisPath = dirname(__FILE__) . '/';
$db_conn = null; // db_conn 변수 초기화

// 기본 함수 읽기
require_once($thisPath . 'lib/function_default.php'); //back(), go_url() 함수 등

// modeok에 따른 처리
if (isset($HEADER['modeok']) && $HEADER['modeok'] && isset($_REQUEST['modeok'])){
	$modeok_value = $_REQUEST['modeok'];
	// preg_match()를 사용하여 입력값 검증을 강화
	if (preg_match("/^[a-z0-9_]+$/", $modeok_value)){
		$modefile = basename($_SERVER['PHP_SELF'], '.php') . '_ok_' . $modeok_value . '.php';
		if (is_file($modefile)){
			unset($HEADER);
			unset($thisPath);
			include($modefile);
			exit; // 모드 파일 실행 후 스크립트 종료
		}
	}
	back('잘못된 요청입니다.');
}

// HTTP cache 여부
if (isset($HEADER['private']) && $HEADER['private']){
	@session_cache_limiter('private');
	header('Cache-Control: private, must-revalidate');

	// 페이지 캐쉬로 time값이 1분이상 차이나면 새로고침
	if (isset($_GET['time']) && 60 < abs((int)$_GET['time'] - time())){
		go_url($_SERVER['PHP_SELF'] . '?' . href_qs('time=' . time()));
	}
}

// 서버 session 변수를 언제나 사용
@session_start();

// 기본 환경 파일 불러드림
$HEADER['usedb2'] = '1';
$HEADER['useApp'] = '1';
require($thisPath . 'config.php');

// MySQL DB를 사용
if ( !is_object($db_conn) ) { // is_resource() 대신 is_object() 사용
	if ( (isset($HEADER['usedb2']) && $HEADER['usedb2']) || (isset($HEADER['usedb2']) && $HEADER['usedb2']) || (isset($HEADER['priv']) && $HEADER['priv']) || (isset($HEADER['log']) && $HEADER['log']) ){
		include_once($thisPath . 'lib/function_mysql2.php');
		$db_conn = db_connect($SECURITY['db_server'], $SECURITY['db_user'], $SECURITY['db_pass']);
		db_select($SECURITY['db_name'], $db_conn);
	}
}

// 로그인 인증
if (isset($HEADER['priv']) && $HEADER['priv']){
	// 세션변수를 가능하면 seVar로 이용하길 권함(cookie 변수는 ckVar 등으로)
	if (!privAuth($HEADER, 'priv', 1)){
		if ($HEADER['priv'] === '판매자'){
			back('판매자 전용사이트입니다. 가입 페이지로 이동합니다.', '/sjoin/join_seller.php');
		} else {
			// $HEADER['goto_nopriv'] 변수가 정의되지 않았을 경우를 대비
			$goto_url = isset($HEADER['goto_nopriv']) ? $HEADER['goto_nopriv'] : '/';
			back('페이지를 볼 수 있는 권한이 없습니다.', $goto_url);
		}
	}
	// 해킹감지 1 - php 임시세션파일을 수정하여 운영자 권한 획득
	if (isset($_SESSION['sePriv']['운영자']) && preg_match('/(^|,)운영자(,|$)/', $HEADER['priv'])){
		// 매번 logon에서 uid,userid 일치 여부 체크
		$sql = "SELECT uid FROM {$SITE['th']}logon WHERE uid='{$_SESSION['seUid']}' and userid='{$_SESSION['seUserid']}' and find_in_set('운영자',priv)";
		if (db_resultone($sql, 0, 'uid') < 1){
			// 세션변수 이상 발생을 로그화
			$log_msg = "비관리자 접근: {$_SERVER['REQUEST_URI']}\n" . __FILE__ . ':' . __LINE__ . "에서 발생";
			db_query("INSERT INTO {$SITE['th']}log_secure (bid, rdate, log, host, ref_uri, ip) VALUES ('{$_SESSION['seUid']}', UNIX_TIMESTAMP(), '{$log_msg}', '{$_SERVER['REQUEST_URI']}', '{$_SERVER['HTTP_REFERER']}', '{$_SERVER['REMOTE_ADDR']}')");
			back('회원님은 관리자가 아닙니다. 방문 로그가 기록되었습니다.');
			go_url('/');
		}
	}

	// 해킹감지 2 - php 임시세션파일을 수정하여 root 권한 획득
	if (isset($_SESSION['sePriv']['root']) && preg_match('/(^|,)root(,|$)/', $HEADER['priv'])){
		// 매번 logon에서 uid,userid 일치 여부 체크
		$sql = "SELECT uid FROM {$SITE['th']}logon WHERE uid='{$_SESSION['seUid']}' and userid='{$_SESSION['seUserid']}' and find_in_set('root',priv)";
		if (db_resultone($sql, 0, 'uid') < 1){
			// 세션변수 이상 발생을 로그화
			$log_msg = "비관리자 접근: {$_SERVER['REQUEST_URI']}\n" . __FILE__ . ':' . __LINE__ . "에서 발생";
			db_query("INSERT INTO {$SITE['th']}log_secure (bid, rdate, log, host, ref_uri, ip) VALUES ('{$_SESSION['seUid']}', UNIX_TIMESTAMP(), '{$log_msg}', '{$_SERVER['REQUEST_URI']}', '{$_SERVER['HTTP_REFERER']}', '{$_SERVER['REMOTE_ADDR']}')");
			back('회원님은 root 권한이 없습니다. 방문 로그가 기록되었습니다.');
			go_url('/');
		}

		// MyAdmin(MySQL 관리 페이지) 동작시 DB아이디 패스워드 특별변수 저장
		if (preg_match('/^\/sadmin\/myadmin/', $_SERVER['PHP_SELF'])){
			$cfgServers_user = $SECURITY['db_user'];
			$cfgServers_pass = $SECURITY['db_pass'];
		}
	}
}

// log_site 로그화(쿠키를 이용하여, 한시간이내 재입장은 무시!!)
if (isset($HEADER['log']) && !isset($_COOKIE["ck_{$HEADER['log']}"])){
	// $seHTTP_REFERER는 어디서 링크하여 왔는지 저장하고, 로그인하면서 로그에 남기고 삭제된다.
	$seHTTP_REFERER = '';
	if (!isset($_SESSION['seUserid']) && !isset($_SESSION['seHTTP_REFERER']) && isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) === false){
		$_SESSION['seHTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
	}
	// $_SESSION 변수를 직접 사용하여 $seHTTP_REFERER 변수 할당
	if (isset($_SESSION['seHTTP_REFERER'])){
		$seHTTP_REFERER = $_SESSION['seHTTP_REFERER'];
	}
	db_query("INSERT INTO {$SITE['th']}log_site (bid, log, host, ip, ref_uri, rdate) values ('{$_SESSION['seUid']}', '{$HEADER['log']}', '{$_SERVER['HTTP_HOST']}','{$_SERVER['REMOTE_ADDR']}', '{$seHTTP_REFERER}', UNIX_TIMESTAMP())");

	setcookie("ck_{$HEADER['log']}", 'log', time() + 3600);
}

// 각종 함수 include
if (isset($HEADER['useSkin']))			include_once($thisPath . 'lib/class_phemplate.php');
if (isset($HEADER['useCheck']))			include_once($thisPath . 'lib/function_check.php');
if (isset($HEADER['useApp']))			include_once($thisPath . 'lib/function_app.php');
if (isset($HEADER['useImage']))			include_once($thisPath . 'lib/function_image.php');
if (isset($HEADER['useClassSendmail']))	include_once($thisPath . 'lib/class_sendmail.php');
if (isset($HEADER['useBoard']))			include_once($thisPath . 'lib/function_board.php');
if (isset($HEADER['useBoard2']))		include_once($thisPath . 'lib/function_board2.php');
if (isset($HEADER['usePoint']))			include_once($thisPath . 'lib/function_point.php');


// html header부분 출력과 tail부분 $SITE['tail']에 할당
$SITE['html_path'] = $thisPath . 'skin/'; // site skin 디렉토리를 저장
if (isset($HEADER['html_echo']) && $HEADER['html_echo']){
$HEADERfile = $SITE['html_path'] . 'index_' . $HEADER['html_skin'] . '.php';
	if (!is_file($headerfile)){
	$HEADERfile = $SITE['html_path'] . '/index_basic.php';
	}
	@include_once($headerfile); // 해더파일 읽기
	unset($headerfile);
}

// lib 디렉토리를 저장
$SITE['lib_path'] = $thisPath . 'lib/';

// 보안을 위해 환경파일 변수값 삭제
if (!isset($HEADER['usedbLong']) || !$HEADER['usedbLong']){
	unset($SECURITY);
}

// 웹 보안 도구 캐슬 셋팅........davej.........
define("__CASTLE_PHP_VERSION_BASE_DIR__", "/home/kbstars/www.kbstars.co.kr/_CASTLE_");
include_once(__CASTLE_PHP_VERSION_BASE_DIR__ . "/castle_referee.php");


$_DEBUG = TRUE;
	
// 디버깅용 davej.............
$_DEBUG = isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '125.141.56.249' ? TRUE : FALSE;

// DEBUG
if ((isset($HEADER['debug']) && $HEADER['debug'] == '1') || $_DEBUG) {
		
	$myString = $_SERVER['REQUEST_URI'];
	$searchWord = '/Admin/';

	// E_NOTICE, E_WARNING을 포함한 모든 오류를 보고
	error_reporting(E_ALL & ~E_NOTICE);
	// 오류를 웹 페이지 화면에 출력
	ini_set('display_errors', '1');

	//echo strpos($myString, $searchWord)."---";
	if ( strpos($myString, $searchWord) === false ) {
		print_r("//===================================================\n<br>");
		print_r("<b>- [_REQUEST] : </b>");
		print_r("\n<br>---------------------------------------------\n<br>");
		print_r($_REQUEST);
		print_r("\n<br>");
		print_r("//===================================================\n<br>\n<br>");
	}
}



unset($HEADER);
unset($thisPath);

// E_NOTICE, E_WARNING을 포함한 모든 오류를 보고
error_reporting(E_ALL);

// 오류를 웹 페이지 화면에 출력
ini_set('display_errors', '1');

//===================================================
/*
if ($_SERVER['HTTP_HOST'] == 'kbstars.co.kr') $_SERVER['HTTP_HOST'] = 'www.kbstars.co.kr';
if($_SERVER['SERVER_PORT'] == "80"){
	$Action_domain = "https://".$_SERVER['HTTP_HOST'];
} else if($_SERVER['SERVER_PORT'] == "443"){
	$Action_domain = "http://".$_SERVER['HTTP_HOST'];
}
*/
?>
