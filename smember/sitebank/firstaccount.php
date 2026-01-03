<?php
//=======================================================
// 설	명 : 처음 포인트 계좌 개설
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/04/22
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/04/07 박선민 마지막 수정
// 05/04/22 박선민 47L 회원 priv 저장에서 버그 수정
//=======================================================
$HEADER=array(
		'priv' => '회원', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용
		'useCheck' => 1, // check_value()
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table_account		= $SITE['th'] . "account";
	$table_accountinfo	= $SITE['th'] . "accountinfo";
	$table_logon		= $SITE['th'] . "logon";

	// 관리자가 bid값을 넘겨서 생성시키고자 하였다면
	if($_GET['bid']){
		$priv = array('priv' => '운영자');
		if(privAuth($priv,'priv'))
			$bid = $_GET['bid'];
		else back("잘못된 요청입니다.");
	}
	else $bid = $_SESSION['seUid'];

	// 해당 회원 정보 가져오기
	$sql = "SELECT * from {$table_logon} where uid='{$bid}'";
	if(!$logon = db_arrayone($sql)){
		echo "해당 회원은 가입되어 있지 않습니다.";
		exit;
	}
	if($logon['level']<1){
		echo "해당 회원은 레벨이 부족합니다.";
		exit;
	}
	if($logon['priv']){
		$aPriv = explode(',',$logon['priv']);
		foreach($aPriv as $v) $logon['sePriv'][$v]=(int)$logon['level'];
	}
	
	// 추가 포인트 적립여부
	$bonuspoint = 500;
	if($_POST['p_time'] and $_POST['p_bus'] and $_POST['p_why'] 
		and $_POST['p_player'] and $_POST['p_gamecount']){
		$bonuspoint = 600;
	}

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 적립 포인트 계좌 생성
$rs_accountinfo=db_query("SELECT * from {$table_accountinfo} WHERE bid='{$logon['uid']}' LIMIT 1");
if(!db_count($rs_accountinfo)){
	//$accountno=($logon['uid']+10000000) . substr(date("d"),1,1) . substr(microtime(),2,1);
	if(isset($logon['sePriv']['회원'])) $tankyoupoint = $bonuspoint; 
	else $tankyoupoint = 0;
	$sql = "INSERT INTO $table_accountinfo (`uid`,`bid`,`accountno`, `userid`, `name`, `accounttype`, `transfertype`,	`balance`, `comment`, `errorno`, `errornotice`, `rdate`) 
			VALUES ('{$logon['uid']}','{$logon['uid']}', '{$logon['uid']}','{$logon['userid']}', '{$logon['name']}', '적립포인트', '모든이체불가', '{$tankyoupoint}', '현금 환불이 되지 않는 계좌입니다.', '0', '', UNIX_TIMESTAMP())";
	db_query($sql);
	if($accountno = db_insert_id()){
		db_query("INSERT INTO $table_account (`bid`, `userid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) V
				ALUES ('{$logon['uid']}', '{$logon['userid']}', '{$logon['uid']}', UNIX_TIMESTAMP(), '알림', '계좌개설을환영합니다', '{$tankyoupoint}', '0', '{$tankyoupoint}', '사이트')");
	} else {
		back("계좌 생성이 실패하였습니다.\\n운영자에게 문의 바랍니다.");
	}
}

/*
// SMS포인트 계좌 생성
// 적립 포인트 계좌 생성
$rs_accountinfo=db_query("SELECT * from {$table_accountinfo} WHERE bid='{$logon['uid']}' and accounttype='SMS포인트' LIMIT 1");
if(!db_count($rs_accountinfo)){
	//$accountno=($logon['uid']+30000000) . substr(date("d"),1,1) . substr(microtime(),2,1);

	$sql = "INSERT INTO {$table_accountinfo} (`bid`, `userid`, `name`, `accounttype`, `transfertype`, `accountno`,	`balance`, `comment`, `errorno`, `errornotice`, `rdate`) VALUES ('{$logon['uid']}', '{$logon['userid']}', '{$logon['name']}', 'SMS포인트', '모든이체불가', '{$accountno}', '0', '현금 환불이 되지 않는 계좌입니다.', '0', '', UNIX_TIMESTAMP())";
	db_query($sql);
	if($accountno = db_insert_id()){
		db_query("update {$table_accountinfo} set accountno=uid where uid='{$accountno}'");
		db_query("INSERT INTO {$table_account} (`bid`, `userid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) VALUES ('{$logon['uid']}', '{$logon['userid']}', '{$accountno}', UNIX_TIMESTAMP(), '알림', '계좌개설을환영합니다', '0', '0', '0', '사이트')");
	} else {
		back("계좌 생성이 실패하였습니다.\\n운영자에게 문의 바랍니다.");
	}

	$_SESSION['sePoint'] = (int)$tankyoupoint;
}
else back("이미 포인트계좌가 생성되어 있습니다");
*/

	/* (주의-그대로 쓰지말것) 여러가지 수정해야됨...
	if(!is_array($accounttype) or !in_array("캐쉬포인트",$accounttype)){
		$accountno=($seUid+20000000) . substr(date("d"),1,1) . substr(microtime(),2,1);
		
		if(db_query("INSERT INTO {$table}info (`bid`, `userid`, `name`, `accounttype`, `transfertype`, `accountno`, `comment`, `errorno`, `errornotice`, `rdate`) VALUES ('{$seUid}', '{$seUserid}', '$seName', '캐쉬포인트', '사이트내자유이체및10000원단위타행이체가능', '{$accountno}', '10000원단위로 현금 환불 신청을 하실 수 있습니다.', '0', '', UNIX_TIMESTAMP())")){
			db_query("INSERT into {$table} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) VALUES ('{$seUid}', '{$accountno}', UNIX_TIMESTAMP(), '알림', '계좌개설을환영합니다.', '0', '0', '0', '사이트')");
		} else {
			back("계좌 생성이 실패하였습니다.\\n운영자에게 문의 바랍니다.");
		}
	} // end if
	*/

// 마무리
go_url($_REQUEST['goto'] ? $_REQUEST['goto'] : "/smember/sitebank/"); ?>
