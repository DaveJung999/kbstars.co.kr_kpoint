<?php
//=======================================================
// 설  명 : 로그인 페이지 및 로그인 처리 페이지(sjoin/login.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/11/20
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ ------------------------------------
// 03/11/13 박선민 비회원 임시아이디 생성과 로그인추가
// 04/12/22 박선민 인증알고리즘 변경
// 05/01/25 박선민 마지막 수정
// 05/03/20 박선민 비회원로그인수정, $_SESSION['sePriv']['priv'] 추가
// 05/11/19 박선민 정복인, 이동언 프로그래머 참여로 큰 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <? → <?php, eregi_replace() → preg_replace() 변환
//=======================================================
$HEADER=array(
		'priv'		=>'', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2'		=>1, // DB 커넥션 사용
		'useApp'	=>1, // remote_addr()
		'useCheck'	=>1, // check_email()
		'useSkin'	=>1, // 템플릿 사용
		'usePoint'	=>1
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once($thisPath.'config.php');	// $dbinfo 가져오기

	// table
	$table_logon		= $SITE['th'].'logon';
	$table_joininfo		= $SITE['th'].'joininfo';
	$table_groupinfo	= $SITE['th'].'groupinfo';
	$table_log_wtmp		= $SITE['th'].'log_wtmp';
	$table_log_lastlog	= $SITE['th'].'log_lastlog';
	$table_accountinfo	= $SITE['th'].'accountinfo';
	$table_coupon		= $SITE['th'].'shop2coupon';

	
	// MySQL 4.x의 OLD_PASSWORD() 함수를 DB에서 직접 가져오는 함수
	// 이 함수는 마이그레이션 목적으로만 사용하고, 마이그레이션 완료 후에는 삭제하는 것이 좋습니다.
	function old_mysql_password($password) {
		global $db;
		$sql = "SELECT OLD_PASSWORD('".db_escape($password) . "') as old_pw";
		$result = db_arrayone($sql);
		return $result['old_pw'];
	}
	
	/////////
	// 로그인
	if($_POST['mode']=='login' && $_POST['userid'] && $_POST['passwd']) {
		// 0.1초간 쉬자.. 로그인에 0.1초 기다리게 할 수 있겠지.
		usleep(100); // 패스워드 무한루트 돌리는 해킹을 조금이라도 무력화 시키기 위해
		
		$userid = $_POST['userid'];
		$passwd = $_POST['passwd']; // 비밀번호는 해싱 전 상태로 유지

		// 아이디로 사용자 정보를 먼저 가져옴
		$sql = "SELECT * from {$table_logon} WHERE userid='{$userid}'";
		//echo $sql;exit;
		if(!$list = db_arrayone($sql)){
			sleep(1); // 패스워드 무한루트 돌리는 해킹을 조금이라도 무력화 시키기 위해
			back('등록되지 않은 아이디입니다.');
			exit;
		}

		// PHP의 password_verify 함수로 비밀번호를 검증
		// 이 함수는 MariaDB 11과 PHP 7에서 권장되는 안전한 해싱 방식입니다.
		$is_authenticated = false;
		if(password_verify($passwd, $list['passwd'])){
			$is_authenticated = true;
		} else {
			// 기존 password() 함수는 OLD_PASSWORD() 함수를 사용하여 16자리 문자열을 반환하는 방식과 유사하게 작동했습니다.
			// password_verify()에 실패했을 경우, 위에서 정의한 old_mysql_password 함수를 사용하여 재확인합니다.
			// **주의: 이 코드는 임시 마이그레이션용이며, 모든 사용자가 로그인 후에는 삭제하는 것이 좋습니다.**
			$old_hash_check = old_mysql_password($passwd);
			
			if ($list['passwd'] === $old_hash_check) {
				$is_authenticated = true;
				// 로그인 성공 후 새로운 해시로 업데이트
				$new_hash = password_hash($passwd, PASSWORD_DEFAULT);
				$update_sql = "update {$table_logon} SET passwd = '{$new_hash}' WHERE uid = '{$list['uid']}'";
				db_query($update_sql);
			}
		}

		if(!$is_authenticated){
			sleep(1); // 패스워드 무한루트 돌리는 해킹을 조금이라도 무력화 시키기 위해
			back('회원 인증에 실패하였습니다 . \\n아이디와 패스워드를 정확히 입력해 주십시요.');
			exit;
		}

		if($list['level']<0) back('회원탈퇴를 한 아이디입니다.\\n이 아이디로는 로그인이 불가합니다.');

		$seHTTP_REFERER = $_SESSION['seHTTP_REFERER'];
		$seREQUEST_URI	= $_SESSION['seREQUEST_URI'];
		@session_destroy();
		@session_start();

		$_SESSION['seUid']		= $list['uid'];
		$_SESSION['seUserid']	= $list['userid'];
		$_SESSION['seName']		= $list['name'];
		$_SESSION['seNickname']	= $list['name'];
		$_SESSION['seEmail']	= $list['email'];		
		$_SESSION['seHTTP_REFERER'] = $seHTTP_REFERER;
		
		// priv 넣기
		if($list['priv']) {
			$aPriv = explode(',',$list['priv']);
			foreach($aPriv as $v) $_SESSION['sePriv'][$v]=(int)$list['level'];
			$_SESSION['sePriv']['level']=(int)$list['level'];
			$_SESSION['sePriv']['priv']=$list['priv'];
		}

		// 그룹 권한 읽어오기
		$seGroup=array();
		$sql = "SELECT * FROM $table_joininfo WHERE bid='{$list['uid']}'";
		$rs_joininfo=db_query($sql);
		while($row=db_array($rs_joininfo)) {
			if($row['priv']) {
				$aPriv = explode(',',$row['priv']);
				foreach($aPriv as $v) $_SESSION['seGroup'][$row['gid']][$v] = (int)$row['level'];
				$_SESSION['seGroup'][$row['gid']]['level']=(int)$row['level'];
			}
		} // end while

		// 그룹개설자 권한 'root'로 재설정하기
		$sql = "SELECT * FROM $table_groupinfo WHERE bid='{$list['uid']}'";
		$rs_joininfo=db_query($sql);
		while($row=db_array($rs_joininfo)) {
			$_SESSION['seGroup'][$row['uid']]['운영자']=$_SESSION['seGroup'][$row['uid']]['level'];
		} // end while

		//접속 로그화 - log_wtmp(접속로그), log_lastlog(마지막 접속로그)
		if( !$_SESSION['seHTTP_REFERER'] && $_SERVER['HTTP_REFERER'] 
			&& strpos($_SERVER['HTTP_REFERER'],$_SERVER['HTTP_HOST'])==false ) {
			$_SESSION['seHTTP_REFERER']	= $_SERVER['HTTP_REFERER'];
		}
		$remote_addr = remote_addr();
		$sql = "INSERT INTO $table_log_wtmp SET bid='{$list['uid']}', ip='$remote_addr', ref_uri='{$_SESSION['seHTTP_REFERER']}', rdate=UNIX_TIMESTAMP()";
		db_query($sql);
		$sql = "DELETE FROM $table_log_lastlog WHERE bid='{$list['uid']}' AND gid='0'";
		db_query($sql);
		$sql = "INSERT INTO $table_log_lastlog SET bid='{$list['uid']}', gid='0', port='www', ip='$remote_addr', ref_uri='{$_SESSION['seHTTP_REFERER']}', rdate=UNIX_TIMESTAMP()";
		db_query($sql);
		
		// 쿠폰개수 세션으로 저장
		$sql = "select count(*) as count from $table_coupon where bid='{$_SESSION['seUid']}' and payment_uid=0";
		$_SESSION['seCoupon'] = db_resultone($sql,0,'count');


		// 로그인 후 이동할 페이지
		$goto = $seREQUEST_URI ? $seREQUEST_URI : '/kpoint/klist.php'; 
		$goto = $_REQUEST['goto'] ? $_REQUEST['goto'] : $goto; // 넘어온 goto가 최우선

		// 적립포인트 잔액 세션으로 저장
/*		if(!$prev_mode){
			$sql = "select balance from $table_accountinfo where bid='{$_SESSION['seUid']}' and accounttype='적립포인트' and errorno='0' LIMIT 1";
			if(!$accountinfo=db_arrayone($sql)) { // 적립포인트 생성
				go_url('/smember/sitebank/firstaccount.php?goto='.urlencode($goto));
			}
		}
		$_SESSION['sePoint'] = (int)$accountinfo['balance'];
	
		// 포인트적립 - 오늘 처음 로그인했다면
		$remark = '1일 1회 로그인포인트';
		// 이미 적립했는지
		$sql = "select uid from new21_account where bid='{$_SESSION['seUid']}' and rdate_date=curdate() and remark='$remark'";
		if(!db_resultone($sql,0,'uid')) 
			new21PointDeposit($_SESSION['seUid'] , 30, $remark ,'적립');
*/			
		// 운영자 출석체크
		/*if($_SESSION['sePriv']['운영자'] and is_file('../sadmin/intranet/check_attendance.php')) {
			include($_SERVER['DOCUMENT_ROOT'].'/sadmin/intranet/check_attendance.php');
		}
		if ( strpos($goto, "https://") === true )
			$goto = str_replace("https://", "http://", $goto);
		else if ( strpos($goto, "http://") === false )
			$goto = "".$goto;*/

		go_url($goto);
		exit;
	} // end if

	// 넘어온 값에 따라 $dbinfo값 변경
	if($dbinfo['enable_getinfo']=='Y') {
		// skin관련
		if($_GET['html_type'])	$dbinfo['html_type'] = $_GET['html_type'];
		if( isset($_GET['html_skin']) && preg_match('/^[_a-z0-9]+$/i', $_GET['html_skin']) )
			and is_file($SITE['html_path'].'index_'.$_GET['html_skin'].'.php') )	
			$dbinfo['html_skin'] = $_GET['html_skin'];
		if( isset($_GET['skin']) && preg_match('/^[_a-z0-9]+$/i', $_GET['skin']) )
			and is_dir($thisPath.'skin/'.$_GET['skin']) )
			$dbinfo['skin']	= $_GET['skin'];
	}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file('skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate('skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$_REQUEST['goto'] = htmlspecialchars($_REQUEST['goto'],ENT_QUOTES);
$form_loginpage=" method='post' action='{$_SERVER['PHP_SELF']}'>
				<input type='hidden' name='goto' value='{$_REQUEST['goto']}'>
				<input type='hidden' name='mode' value='login'";
$form_loginguest=" method='post' action='{$_SERVER['PHP_SELF']}'>
				<input type='hidden' name='goto' value='{$_REQUEST['goto']}'>
				<input type='hidden' name='mode' value='loginguest'";
$form_remotelogin=" method='post' action='{$_SERVER['PHP_SELF']}'>
				<input type='hidden' name='goto' value='{$_REQUEST['goto']}'>
				<input type='hidden' name='mode' value='remotelogin'";

$tpl->set_var('form_loginpage', $form_loginpage);
$tpl->set_var('form_loginguest', $form_loginguest);
$tpl->set_var('form_remotelogin', $form_remotelogin);
// 마무리
$tpl->echoHtml($dbinfo, $SITE);

?>