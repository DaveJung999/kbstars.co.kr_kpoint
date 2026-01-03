<?php
//=======================================================
// 설	명 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/12/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/12/03 박선민 마지막 수정
//=======================================================
$HEADER=array(
		priv	 => '운영자', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2'	 =>	1,
	'useCheck' => 1,	// check_value()
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table_account		= $SITE['th'] . "account";
	$table_accountinfo	= $SITE['th'] . "accountinfo";
	$table_logon		= $SITE['th'] . "logon";
	$table_payment		= $SITE['th'] . "payment";

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
	switch($_REQUEST['mode']) {
		case "deposit" :
			if(deposit($_REQUEST['bid']))
				go_url("accountlist.php?bid={$_REQUEST['bid']}");
			else back("요청을 실패하였습니다.");
			break;
		case "withdrawal" :
			if(withdrawal($_REQUEST['bid']))
				go_url("accountlist.php?bid={$_REQUEST['bid']}");
			else back("요청을 실패하였습니다.");
			break;
		case "transferok" :
			transferok($_REQUEST['bid']);
			go_url("accountlist.php?bid={$_REQUEST['bid']}");
			break;
		default :
			back("지원하지 않는 모드입니다.");
	}
exit;
//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function transferok($bid) {
	Global $table_account, $table_accountinfo, $table_logon;

	// 회원 정보 가져오기
	$sql = "SELECT * from {$table_logon} where uid='{$bid}'";
	$logon = db_arrayone($sql) or back("해당 회원은 없습니다.");

	$qs=array(	accountno	 =>	"post,trim,notnull=" . urlencode("출금계좌번호가 넘오오지 않았습니다. 확인 바랍니다."),
				to_bank		 =>	"post,trim,notnull=" . urlencode("이체 은행을 입력하시기 바랍니다."),
				to_accountno =>	"post,trim,notnull=" . urlencode("계좌번호를 입력하시기 바랍니다."),
				to_money	 =>	"post,trim,notnull=" . urlencode("이체 금액을 입력하시기바랍니다."),
				passwd		 =>	"post,trim,notnull=" . urlencode("웹로그인 패스워드를 입력하시기바랍니다."),
		);
	$qs=check_value($qs);
	$qs['to_accountno']=preg_replace("/[^0-9]/i", "", $qs['to_accountno']);

	// 해당 계좌정보와 계좌 내역 마지막건의 정보를 읽음
	$rs_accountinfo=db_query("SELECT * from {$table_accountinfo} WHERE bid={$logon['uid']} and accountno='{$qs['accountno']}'");
	$rs_account_from=db_query("SELECT * from {$table_account} where bid={$logon['uid']} and accountno='{$qs['accountno']}' order by uid DESC limit 0,1");

	$accountinfo= db_count($rs_accountinfo) ? db_array($rs_accountinfo) : back("출금 계좌 정보가 없습니다. 확인 바랍니다.","/smember/sitebank/");
	$account_from= db_count($rs_account_from) ? db_array($rs_account_from) : back("출금 계좌 내역을 읽어오는데 실패하였습니다.\\n출금 계좌 내역을 확인 바랍니다.","/smember/sitebank/");

	// 해당 계좌내역의 총 입금과 총 출금이 잔액과 동일한지 체크
	$sum_deposit	=(int)db_result(db_query("select sum(deposit) as sum from {$table_account} where bid={$logon['uid']} and accountno='{$qs['accountno']}' order by uid DESC limit 0,1"),0,"sum");
	$sum_withdrawal	=(int)db_result(db_query("select sum(withdrawal) as sum from {$table_account} where bid={$logon['uid']} and accountno='{$qs['accountno']}' order by uid DESC limit 0,1"),0,"sum");
	if($account_from['balance'] != $sum_deposit-$sum_withdrawal) {
		// 비상 상황 발생!!! 해당 계좌 잔액과 입출금합계 금액이 다름
		db_query("update {$table_accountinfo} set errorno='1' , errornotice='잔액과 입출금합계 오류 발생' where bid={$logon['uid']} and uid={$list['uid']}");
		back("대단히 죄송합니다.\\n 잔액과 입출금합계가 틀리는 오류가 발생되었습니다.\\n 사이트의 문의 게시판에 문의 바랍니다.","/smember/sitebank/");
	}

	// 이체 가능한지 체크
	if($qs['accountno']==$qs['to_accountno']) {
		back("동일 계좌로 이체는 당연히 되지 않습니다","/smember/sitebank/");
	}
	elseif( $accountinfo['transfertype']=="모든이체불가" ) {
		back("요청하신 계좌는 이체가 되지 않습니다.\\n계좌 종류를 확인 바랍니다.","/smember/sitebank/");
	}
	elseif($accountinfo['errorno']) {
		back("해당 계좌에 문제가 있어 일지 사용 중지되었습니다.\\n사이트의 문의 게시판에 문의 바랍니다.","/smember/sitebank/");
	}

	// 웹로그인비밀번호가 맞은지 확인
	if( !db_count(db_query("SELECT * from {$table_logon} where uid='{$logon['uid']}' and passwd=password('{$qs['passwd']}')")) ) {
		back("회원님의 웹인증 패스워드가 틀립니다.\\n정말 회원님이시길 바라며 다시 입력바랍니다.");
	}


	if($qs['to_bank']=="사이트") {
		$qs['commission']	= 0;	// 이체수수료 없음

		// 입금 계좌 정보와 내역 마지막건 정보를 읽음
		$rs_accountinfo_to=db_query("SELECT * from {$table_accountinfo} WHERE bid={$logon['uid']} and accountno='{$qs['to_accountno']}'");
		$rs_account_to=db_query("SELECT * from {$table_account} where bid={$logon['uid']} and accountno='{$qs['to_accountno']}' order by uid DESC limit 0,1");

		$accountinfo_to= db_count($rs_accountinfo_to) ? db_array($rs_accountinfo_to) : back("입금 계좌 정보가 없습니다. 확인 바랍니다.","/smember/sitebank/");
		if(db_count($rs_account_to)) {
			$account_to= db_array($rs_account_to);
		}
		else {
			// 계좌 정보가 없으니 없는 것으로 초기화
			$account_to['balance']=0;
		}
		//적립포인트에 한하여 이체 허용
		if($accountinfo_to['transfertype']!='모든이체불가') 
			back("본 계좌는 적립포인트 계좌가 아닙니다.","/smember/sitebank/");


		// 출금 계좌 내역 입력 준비
		$insert_accountno	= $qs['accountno'];
		$insert_type		= "이체";
		$insert_remark		= "{$qs['to_accountno']}로 이체완료";
		$insert_deposit		= 0;
		$insert_withdrawal	= $qs['to_money'];
		$insert_balance		= $account_from['balance'] - $qs['to_money'];
		

		// 마지막 계좌 잔액이 부족하지 않은지 체크
		if($accountinfo['to_balance'] < 0) 
			back("계좌 잔액보다 더 많은 금액을 이체하시고자 하였습니다.\\n계좌 잔액을 확인바랍니다.","/smember/sitebank/");

		db_query("INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) 
				VALUES ('{$logon['uid']}', '{$insert_accountno}', UNIX_TIMESTAMP(), '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
		db_query("update {$table_accountinfo} set `balance`= '{$insert_balance}' where `uid` = '{$accountinfo['uid']}'");

		// 입금 계좌 내역 입력 준비
		$insert_accountno	= $qs['to_accountno'];
		$insert_type		= "이체";
		$insert_remark		= "{$qs['accountno']}에서 이체됨";
		$insert_deposit		= $qs['to_money'];
		$insert_withdrawal	= 0;
		$insert_balance		= $account_to['balance'] + $qs['to_money'];

		db_query("INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) 
				VALUES ('{$logon['uid']}', '{$insert_accountno}', UNIX_TIMESTAMP(), '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
		db_query("update {$table_accountinfo} set `balance`= '{$insert_balance}' where `uid` = '{$accountinfo_to['uid']}'");
	}
	else {
		//출금계좌 내역 입력 준비
		$qs['to_money']	= ((int)$qs['to_money']/10000)* 10000;
		$qs['commission']	= 500;

		// 출금 계좌 내역 입력 준비
		$insert_accountno	= $qs['accountno'];
		$insert_type		= "환급중";
		$insert_remark		= "{$qs['to_bank']}({$qs['to_accountno']})이체접수";
		$insert_deposit		= 0;
		$insert_withdrawal	= $qs['to_money'];
		$insert_balance		= $account_from['balance'] - $qs['to_money'] ;
		
		// 마지막 계좌 잔액이 부족하지 않은지 체크
		if( 0 > $insert_balance-$qs['commission']) 
			back("계좌 잔액보다 더 많은 금액을 이체하시고자 하였습니다.\\n계좌 잔액을 확인바랍니다.","/smember/sitebank/");

		db_query("INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) 
			VALUES ('{$logon['uid']}', '{$insert_accountno}', UNIX_TIMESTAMP(), '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
		db_query("update {$table_accountinfo} set `balance`= '{$insert_balance}' where `uid` = '{$accountinfo['uid']}'");

		// 출금 계좌 내역 입력 준비 (이체 수수료)
		/* 입금 수수료를 제하고 입금함으로써 수수료 청구 안함
		$insert_accountno	= $qs['accountno'];
		$insert_type		= "수수료";
		$insert_remark		= "{$qs['to_bank']}({$qs['to_accountno']})이체건";
		$insert_deposit		= 0;
		$insert_withdrawal	= $qs['commission'];
		$insert_balance		= $account_from['balance'] - ($qs['to_money'] + $qs['commission']);
		
		db_query("INSERT INTO $table_account (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) 
				VALUES ('{$seUid}', '{$insert_accountno}', UNIX_TIMESTAMP(), '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
		db_query("update {$table_accountinfo} set `balance`= '{$insert_balance}' where `uid` = '{$accountinfo['uid']}'");
		*/

	} // end if.. else..
} // end func.


function deposit($bid) {
	Global $table_payment,$table_logon,$table_accountinfo,$table_account;

	// 회원 정보 가져오기
	$sql = "SELECT * from {$table_logon} where uid='$bid'";
	$logon = db_arrayone($sql) or back("해당 회원은 없습니다.");

	$qs=array(	accountno	 =>	"post,trim,notnull=" . urlencode("계좌번호가 유효하지 않습니다."),
				money		 =>	"post,trim,notnull=" . urlencode("금액을 선택하시기 바랍니다."),
				remark		 =>	"post,trim,notnull=" . urlencode("적요를 입력하시기 바랍니다")
		);
	$qs=check_value($qs);

	if($qs['money']<1) back("금액은 1원 이상이어야 합니다");

	// 회원의 적립통장정보 가져오기
	$sql = "SELECT * from {$table_accountinfo} where bid='{$bid}' and accountno='{$qs['accountno']}' and errorno='0'";
	if(!$accountinfo = db_arrayone($sql)) return false;

	// SQL문 완성
	$insert_accountno	= $accountinfo['accountno'];
	$insert_type		= "적립";
	$insert_remark		= $qs['remark'];
	$insert_deposit		= $qs['money'];
	$insert_withdrawal	= 0;
	$insert_balance		= $accountinfo['balance'] + $insert_deposit - $insert_withdrawal;

	db_query("INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) 
			VALUES ('{$bid}', '{$insert_accountno}', UNIX_TIMESTAMP(), '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
	db_query("update {$table_accountinfo} set `balance`= '{$insert_balance}' where `uid` = '{$accountinfo['uid']}'");

	return true;
}

function withdrawal($bid) {
	Global $table_payment,$table_logon,$table_accountinfo,$table_account;

	// 회원 정보 가져오기
	$sql = "SELECT * from {$table_logon} where uid='{$bid}'";
	$logon = db_arrayone($sql) or back("해당 회원은 없습니다.");

	$qs=array(	accountno	 =>	"post,trim,notnull=" . urlencode("계좌번호가 유효하지 않습니다."),
				money		 =>	"post,trim,notnull=" . urlencode("금액을 선택하시기 바랍니다."),
				remark		 =>	"post,trim,notnull=" . urlencode("적요를 입력하시기 바랍니다")
		);
	$qs=check_value($qs);

	if($qs['money']<1) back("금액은 1원 이상이어야 합니다");

	// 회원의 적립통장정보 가져오기
	$sql = "SELECT * from {$table_accountinfo} where bid='{$bid}' and accountno='{$qs['accountno']}' and errorno='0'";
	if(!$accountinfo = db_arrayone($sql)) return false;

	// SQL문 완성
	$insert_accountno	= $accountinfo['accountno'];
	$insert_type		= "적립";
	$insert_remark		= $qs['remark'];
	$insert_deposit		= 0;
	$insert_withdrawal	= $qs['money'];
	$insert_balance		= $accountinfo['balance'] + $insert_deposit - $insert_withdrawal;

	db_query("INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) 
			VALUES ('{$bid}', '{$insert_accountno}', UNIX_TIMESTAMP(), '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
	db_query("update {$table_accountinfo} set `balance`= '{$insert_balance}' where `uid` = '{$accountinfo['uid']}'");

	return true;

}
?>