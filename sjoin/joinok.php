<?php
//=======================================================
// 설  명 : 회원 가입 처리(joinok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/26
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/26  박선민 마지막 수정
// 25/01/XX  PHP 7 업그레이드: 단축 태그 <?→ <?php, mysql_insert_id() → db_insert_id()
//=======================================================
$HEADER = array();
$HEADER['priv']		= ''; // 인증유무 (비회원,회원,운영자,서버관리자)
$HEADER['usedb2']	= 1; // DB 커넥션 사용
$HEADER['useSkin']	= 1; // 템플릿 사용
$HEADER['useApp']	= 1; // remote_addr()
$HEADER['useCheck']	= 1; // check_email()
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security('', $_SERVER['HTTP_HOST']);
 
//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// table
	$table_logon	= $SITE['th'] . 'logon';

	// 비밀번호가 두번 정확했는지...
	if($_POST['passwd']!=$_POST['passwd2']) back('비밀번호를 두번 정확히 입력바랍니다.');

	// 등록 제한 아이디 리스트
	$nouserid = array( 
		'root','bin','daemon','adm','lp','sync','shutdown','halt','mail','news',
		'uucp','operator','games','gopher','ftp','nobody','vcsa','mailnull','rpm',
		'rpc','xfs','rpcuser','nfsnobody','nscd','ident','radvd','named','pcap',
		'mysql','postgres','oracle','dba','sa','administrator','master','webmaster',
		'manager','operator','admin','sysadmin','test','guest','anonymous','sysop',
		'moderator','www','temp','tmp','null','cs');

	// 넘어온값 체크 조건
	$qs=array(	'priv'	=> 'post,trim,notnull=' . urlencode('가입자 유형 선택값이 넘어오지 않았습니다.'),
				'userid'	=> 'post,trim,notnull=' . urlencode('회원아이디를 입력하시기 바랍니다.'),
				'passwd'	=> 'post,trim,notnull=' . urlencode('패스워드를 입력하시기 바랍니다.'),
				'name'		=> 'post,trim,notnull=' . urlencode('회원님 이름을 입력하시기 바랍니다.'),
				'email'		=> 'post,checkEmail=' . urlencode('메일 주소가 잘못되었습니다.'),
				'hp'		=> 'post,trim',
				'address'	=> 'post,trim',
				'address2'	=> 'post,trim',
				'job'		=> 'post,trim',
				'recommender'	=> 'post,trim',
				'country'	=> 'post,trim',
				'nickname'	=> 'post,trim',
				'region'	=> 'post,trim',
				'business'	=> 'post,trim',
				'company'	=> 'post,trim',
				'position'	=> 'post,trim',
				'birth_date'	=> 'post,trim',
				'birth_lunar'	=> 'post,trim',
				'birth_hide'	=> 'post,trim',
				'wedding'		=> 'post,trim',
				'homepage'		=> 'post,trim',
				'intro'			=> 'post,trim',
				'yesmail'		=> 'post,trim',
				'skintype'			=> 'post,trim',
				'skinspecial'		=> 'post,trim',
				'p_time'		=> "post,trim",
				'p_bus'			=> "post,trim",
				'p_why'			=> "post,trim",
				'p_player'		=> "post,trim",
				'p_gamecount'		=> "post,trim"
		);
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
switch($_POST['priv']) {
	case '회원' :
		$qs=array_merge($qs,array( 
				'idnum1'	=> 'post,trim,notnull=' . urlencode('주민번호를 입력하시기 바랍니다.'),
				'idnum2'	=> 'post,trim,notnull=' . urlencode('주민번호를 입력하시기 바랍니다.'),
				'zip1'		=> 'post,trim,notnull=' . urlencode('우편번호를 입력하시기 바랍니다.'),
				'zip2'		=> 'post,trim,notnull=' . urlencode('우편번호를 입력하시기 바랍니다.'),
			));

		// 만 14세 미만의 경우 부모님 정보 체크
		$tempage=substr($idnum2,0,1)>2 ? substr($idnum1,0,2)+100 : substr($idnum1,0,2);
		if(date('y')+100-$tempage < 14) {
			if($check_ok =='') {
				back('14세 미만의 경우 부모님 동의가 있어야 합니다.');
			}
			else {
				$qs=array_merge($qs,array( 
						p_name		=> 'post,trim,notnull=' . urlencode('부모님 성함을 입력하시기 바랍니다.'),
						p_idnum1	=> 'post,trim,notnull=' . urlencode('부모님 주민번호를 정확히 입력하시기 바랍니다.'),
						p_idnum2	=> 'post,trim,notnull=' . urlencode('부모님 주민번호를 정확히 입력하시기 바랍니다.'),
						p_tel		=> 'post,trim,notnull=' . urlencode('부모님 연락가능한 연락처를 입력하시기 바랍니다.')
					));
			}
		} // end if
		break;
	case '회사' :
		$qs=array_merge($qs,array( 
					'idnum1'	=> 'post,trim,notnull=' . urlencode('주민번호를 입력하시기 바랍니다.'),
					'idnum2'	=> 'post,trim,notnull=' . urlencode('주민번호를 입력하시기 바랍니다.'),
					'zip1'		=> 'post,trim,notnull=' . urlencode('우편번호를 입력하시기 바랍니다.'),
					'zip2'		=> 'post,trim,notnull=' . urlencode('우편번호를 입력하시기 바랍니다.'),
				
					'c_num1'	=> 'post,trim,notnull=' . urlencode('회사사업자등록번호를 입력하시기 바랍니다.'),
					'c_num2'	=> 'post,trim,notnull=' . urlencode('회사사업자등록번호를 입력하시기 바랍니다.'),
					'c_num2'	=> 'post,trim,notnull=' . urlencode('회사사업자등록번호를 입력하시기 바랍니다.'),
					'c_owner'	=> 'post,trim,notnull=' . urlencode('회사 대표자 성함을 입력하시기 바랍니다.'),
					'c_address'	=> 'post,trim,notnull=' . urlencode('회사 주소를 입력하시기 바랍니다.'),
					'c_kind'	=> 'post,trim,notnull=' . urlencode('회사 업태를 입력하시기 바랍니다.'),
					'c_detail'	=> 'post,trim,notnull=' . urlencode('회사 종목을 입력하시기 바랍니다.')
			));
		break;
	case '외국인':
		$qs=array_merge($qs,array( 
					'country'	=> 'post,trim,notnull=' . urlencode('국가를 선택하시기 바랍니다.')
		));
		break;
	default:
		back('정상적인 요청이 아닙니다');
}
$qs=check_value($qs);

// 값 체크 마무리
if(!preg_match("/^[a-z][a-z0-9]+$/i", $qs['userid'])) // Userid 체크
	back('아이디는 2-10자까지 숫자, 영문자의 조합만 가능합니다. 첫문자는 영문자여야 합니다.');
if(!$qs['country']) // 국가선택을 하지 않았을때 기본적으로 한국으로
	$qs['country']='kr'; 
if($check_ok) // 14세미만 부모님 주민번호체크
	$qs['p_idnum'] = check_idnum($qs['p_idnum1'],$qs['p_idnum2']); 
if($qs['priv']=='회원') { // 개인의 경우 주민번호체크와 주민번호, 우편변호 값 설정
	$qs['idnum'] = check_idnum($qs['idnum1'],$qs['idnum2']);
	$qs['zip'] = $qs['zip1'] . '-' . $qs['zip2'];

	// DB에 등록된 주민번호인지 체크
	$sql = "SELECT * FROM $table_logon WHERE idnum='{$qs['idnum']}'";
	if(db_count(db_query($sql)))
		back('중복된 주민등록번호입니다.');	
}
elseif($qs['priv']=='회사'){ // 회사의 경우 주민번호 체크와 주민번호, 우편번호, 회사사업장번호 설정
	$qs['idnum'] = check_idnum($qs['idnum1'],$qs['idnum2']);
	$qs['zip'] = $qs['zip1'] . '-' . $qs['zip2'];
	$qs['c_num'] = $qs['c_num1'] . '-' . $qs['c_num2'] . '-' . $qs['c_num3'];
}
//-생일
if(!$qs['birth_hide']) {
	$aBirth = explode('-',$qs['birth_date']);
	if($qs['birth_lunar']) {
		$qs['birth'] = $aBirth[1].$aBirth[2].'-'.$aBirth[0];
	}
	else {
		$qs['birth'] = $aBirth[1].$aBirth[2].'+'.$aBirth[0];
	}
}
else $qs['birth'] ='';
//-결혼기념일
if($qs['wedding_hide']) $qs['wedding_hide']='';

// -nickname
if(!$qs['nickname']) $qs['nickname'] = $qs['name'];
// -yesmail 디폴트값
if(!isset($_POST['yesmail'])) $qs['yesmail'] = 1;

$qs['ip']		= remote_addr();
$qs['host']	= $_SERVER['HTTP_HOST'];

// 전화번호, 휴대폰이 나누어져 넘어왔다면
if(!$qs['tel'] and strlen($_POST['tel1'])>0) 
	$qs['tel'] = $_POST['tel1'] . '-' .  $_POST['tel2'] . '-' . $_POST['tel3'];
if(!$qs['hp'] and strlen($_POST['hp1'])>0) 
	$qs['hp'] = $_POST['hp1'] . '-' . $_POST['hp2'] . '-' . $_POST['hp3'];
	
// DB에 등록된 userid인지 체크
$sql =  "SELECT * FROM $table_logon WHERE userid = '{$qs['userid']}'";
if(db_count(db_query($sql)))
	back('이미등록되어 있는 아이디 입니다. ');

// 등록 제한된 아이디인지 체크
if( in_array($qs['userid'],$nouserid) ) {
	back('등록이 제한된 아이디입니다.\\n해당 아이디로 등록을 원하시면 종합질문게시판에 문의 바랍니다.');
}

##	---------------------------------------------------
##	logon 테이블에 회원 기본 정보 삽입
##	---------------------------------------------------
switch($qs['priv']) {
	case '회사': $qs['priv'] = '회원,회사'; break;
	case '외국인': $qs['priv'] = '회원,외국인'; break;	
}
$qs['level'] = 1;
$sql = "INSERT INTO $table_logon SET
				userid	= '{$qs['userid']}',
				passwd	= password('{$qs['passwd']}'),
				name	= '{$qs['name']}',
				nickname= '{$qs['nickname']}',
				email	= '{$qs['email']}',
				yesmail	= '{$qs['yesmail']}',
				priv	= '{$qs['priv']}',
				level	= '{$qs['level']}',
				zip		= '{$qs['zip']}',
				address	= '{$qs['address']} {$qs['address2']}',
				country	= '{$qs['country']}',
				hp		= '{$qs['hp']}',
				tel		= '{$qs['tel']}',
				idnum	= '{$qs['idnum']}',
				birth	= '{$qs['birth']}',
				wedding	= '{$qs['wedding']}',
				homepage= '{$qs['homepage']}',
				region	= '{$qs['region']}',
				business= '{$qs['business']}',
				company	= '{$qs['company']}',
				job		= '{$qs['job']}',
				position= '{$qs['position']}',
				p_name	= '{$qs['p_name']}',
				p_idnum	= '{$qs['p_idnum']}',
				p_tel	= '{$qs['p_tel']}',
				c_num	= '{$qs['c_num']}',
				c_name	= '{$qs['c_name']}',
				c_owner	= '{$qs['c_owner']}',
				c_address='{$qs['c_address']}',
				c_kind	= '{$qs['c_kind']}',
				c_detail= '{$qs['c_detail']}',
				rdate	= UNIX_TIMESTAMP(),
				ip		= '{$qs['ip']}',
				host	= '{$qs['host']}',
				recommender='{$qs['recommender']}',
				intro	= '{$qs['intro']}',
				skintype='{$qs['skintype']}',
				skinspecial	= '{$qs['skinspecial']}',
				`p_time`	='{$qs['p_time']}',
				`p_bus`		='{$qs['p_bus']}',
				`p_why`		='{$qs['p_why']}',
				`p_player`	='{$qs['p_player']}',
				`p_gamecount`	='{$qs['p_gamecount']}'
			
			";
db_query($sql);

##	---------------------------------------------------
##	회원 고유 번호 불러들임
##	---------------------------------------------------
$uid = db_insert_id();



##	---------------------------------------------------
##	가입 메일 발송
##	---------------------------------------------------
//@include("$_SERVER['DOCUMENT_ROOT']/sjoin/mail/join_thankyou.php");
//sfmail_join('join',$qs['email'],$qs['name']);

##	---------------------------------------------------
##	로그화 : log_userinfo 회원가입 로그
##	---------------------------------------------------
$tmp_date = date('Y-m-d [H:i]');

$sql="INSERT INTO `new21_log_userinfo` SET
			`host`		= '{$qs['host']}',
			`bid`		= 1,
			`userbid`	= $uid,
			`title`		= '회원가입',
			`content`	= '$tmp_date : {$qs['ip']}에서 가입',
			`ip`		= '{$qs['ip']}',
			`rdate`		= UNIX_TIMESTAMP()
		";
db_query($sql);


##	---------------------------------------------------
##	회원 자동 로그인
##	---------------------------------------------------
$_SESSION['seUid']		= $uid;
$_SESSION['seUserid']	= $qs['userid'];
$_SESSION['seName']		= $qs['name'];
$_SESSION['seNickname']	= $qs['nickname'];
$_SESSION['seEmail']	= $qs['email'];
$_SESSION['seLevel']	= $qs['level'];
$_SESSION['seClass']	= $qs['person'];
// priv 넣기
if($qs['priv']) {
	$aPriv = explode(',',$qs['priv']);
	foreach($aPriv as $v) $_SESSION['sePriv'][$v]=(int)$qs['level'];
	$_SESSION['sePriv']['level']=(int)$qs['level'];
}

##	가입 환영 페이지로 이동
//go_url('./jointhankyou.php');
back('회원가입이 완료되었습니다.\\n메인페이지로 이동합니다',"http://www.kbsavers.com/smember/sitebank/firstaccount.php?goto=/");
//back('회원가입이 완료되었습니다.\\n메인페이지로 이동합니다','/');


//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function sfmail_onlysend($db,$email,$name) {
	GLOBAL $SITE;

	$table_fmailinfo	= $SITE['th'] . 'fmailinfo';
	// 해당 db가 있는지 체크
	$sql = "select * from $table_fmailinfo where db='$db' and type='onlysend'";
	$rs = db_query($sql);
	if(!db_count($rs)) return false;
	db_free($rs);

	$dbinfo = db_arrayone($sql) or back('잘못된 요청입니다');

	$__POST	= $_POST;
	$_POST	= array(
				db		=> $db,
				mode	=> 'send',
				tomail	=> $email,
				toname	=> $name			
			);
	include($_SERVER['DOCUMENT_ROOT'].'/sfmail/ok.php');
	$_POST	= $__POST;

	return;
}
?>