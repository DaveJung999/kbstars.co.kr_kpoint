<?
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

	//////////////////////////////////////////////////
	// 쇼핑몰 주문에서 비회원주문을 위한 비회원 로그인
	
	/*if( $_POST['mode']=='loginguest' ) {
		if( !$_POST['name'] or !$_POST['tel'] or !$_POST['email'])
			back('이름, 전화번호를 모두를 입력하여주시기 바랍니다');
		
		$_POST['tel']=eregi_replace('[^0-9]','',$_POST['tel']);// 전화번호에 대해서 숫자로만
		if( strlen($_POST['tel']<10) )
			back('전화번호를 정확히 입력하여 주시기 바랍니다');

		$_POST['userid'] = 'g-' . $_POST['tel']; // 아이디를 g-전화번호 
		$_POST['passwd'] = addslashes($_POST['name'].$_POST['tel']);
		$_POST['passwdemail'] = addslashes($_POST['name'].$_POST['tel'].$_POST['email']); // mulkang 호환때문에...

		$sql = "select *,password('{$_POST['passwd']}') as userpass, password('{$_POST['passwdemail']}') as userpass2 from $table_logon where userid='{$_POST['userid']}'";
		if($list=db_arrayone($sql)) { // 이미 아이디가 있으면
			if($list['passwd']!=$list['userpass']) {
				if($list['passwd']==$list['userpass2'])
					$_POST['passwd'] = $_POST['passwdemail'];
				else
					back('같은 전화번호로 다른 이름을 사용한 적이 있습니다.\\n전화번호를 다르게하거나 이전에 사용한 이름을 입력해주십시오');
			}
		}
		else { // class guest인 비회원로그인 생성
			$qs['ip']		= remote_addr();
			$qs['host']	= $_SERVER['HTTP_HOST'];
			
			// 전화번호가 휴대폰번호이면, 휴대폰번호로 등록
			$_POST['hp'] = '';
			if (preg_match('/^(010|011|016|017|018|019)[0-9]{7,}$/', $_POST['tel'])) {
				$_POST['hp'] = $_POST['tel'];
			}
			
			$sql = "INSERT INTO $table_logon SET
						userid	= '$_POST['userid']',
						passwd	= password('$_POST['passwd']'),
						name	= '$_POST['name']',
						nickname= '$_POST['name']',
						email	= '$_POST['email']',
						yesmail	= '0',
						priv	= '비회원',
						tel		= '$_POST['tel']',
						hp		= '$_POST['hp']',
						rdate	= UNIX_TIMESTAMP(),
						ip		= '$qs['ip']',
						host	= '$qs['host']',
						open	= 'sms'
				";
			db_query($sql);
		}
		unset($list);
		$prev_mode = $_POST['mode'];
		$_POST['mode'] = 'login'; // 아래 로그인
	}*/
	
	
	/**
	////////////////
	// REMOTE 로그인
	function fetchURL( $url ) {
		$url_parsed = parse_url($url);
		$host = $url_parsed["host"];
		$port = $url_parsed["port"];
		if ($port==0) $port = 80;
		$path = $url_parsed["path"];
		if ($url_parsed["query"] != "") $path .= "?".$url_parsed["query"];
		$out = "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n";
		$fp = fsockopen($host, $port, $errno, $errstr, 30);
		fwrite($fp, $out);
		$body = false;
		while (!feof($fp)) {
			$s = fgets($fp, 1024);
			if ( $body ) $in .= $s;
			if ( $s == "\r\n" )	$body = true;
		}
		
		fclose($fp);
		
		return $in;
	}
	if($_POST['mode']=='remotelogin' && $_POST['userid'] && $_POST['passwd']) {
		$remote = fetchURL("http://new21.com/sjoin/loginRemote.php?userid={$_POST['userid']}&passwd={$_POST['passwd']}");
		$rlist = unserialize($remote);
		if(!is_array($rlist)) back('인증 서버 접속에 장애가 발생하였습니다. 잠시후 다시 시도하여 주세요');
		//print_r($rlist);exit;
		
		if($rlist['error']) back("다음의 이유로 로그인에 실패하였습니다.\\n에러메세지:".$rlist['error']);
		
		$rlist['addr1'] = str_replace("&nbsp;", " ", $rlist['addr1']);
		$rlist['rdate'] = strtotime($rlist['sdate']);
		
		// 회원 정보 업데이트
		$sql = "SELECT * FROM $table_logon WHERE userid='{$_POST['userid']}'";
		if($list=db_arrayone($sql)) {
			$sql = "update $table_logon set
						`passwd`=password('$rlist['user_passwd']'),
						`name` = '$rlist['user_name']',
						`email` = '$rlist['email']',
						`idnum` = '$rlist['junmin1']-$rlist['junmin2']',
						`gisu` = '$rlist['gisu']',
						`tel` = '$rlist['tel']',
						`hp` = '$rlist['handphone']',
						`zip` = '$rlist['zipcode']',
						`address` = '$rlist['addr1'] $rlist['addr2']',
						`homepage` = '$rlist['homepage']',
						`yesmail` = '$rlist['mail_yn']',
						`rdate` = '$rlist['rdate']'
					where uid=$list['uid']
					";
		}
		else {
			$sql = "insert into $table_logon set
						`priv`	= '회원',
						`userid` = '$rlist['user_id']',
						`passwd`=password('$rlist['user_passwd']'),
						`name` = '$rlist['user_name']',
						`email` = '$rlist['email']',
						`idnum` = '$rlist['junmin1']-$rlist['junmin2']',
						`gisu` = '$rlist['gisu']',
						`tel` = '$rlist['tel']',
						`hp` = '$rlist['handphone']',
						`zip` = '$rlist['zipcode']',
						`address` = '$rlist['addr1'] $rlist['addr2']',
						`homepage` = '$rlist['homepage']',
						`yesmail` = '$rlist['mail_yn']',
						`rdate` = '$rlist['rdate']'
					";	
		}
		db_query($sql);
		
		unset($list);
		unset($rlist);
		$prev_mode = $_POST['mode'];		
		$_POST['mode'] = 'login';
	}
	**/
	
	/////////
	// 로그인
	if($_POST['mode']=='login' && $_POST['userid'] && $_POST['passwd']) {
		// 0.1초간 쉬자.. 로그인에 0.1초 기다리게 할 수 있겠지.
		usleep(100); // 패스워드 무한루트 돌리는 해킹을 조금이라도 무력화 시키기 위해
		
		$sql	= "SELECT * FROM $table_logon WHERE userid='{$_POST['userid']}' and passwd=password('{$_POST['passwd']}')";
		if(!$list=db_arrayone($sql)) {
			sleep(1); // 패스워드 무한루트 돌리는 해킹을 조금이라도 무력화 시키기 위해
			back('회원 인증에 실패하였습니다. \\n아이디와 패스워드를 정확히 입력해 주십시요.'); 
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