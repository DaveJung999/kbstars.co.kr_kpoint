<?
//=======================================================
// 설  명 : 리스트(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/12/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/12/03 박선민 마지막 수정
//=======================================================
$HEADER=array(
		'priv'		=>'회원', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2'		=>1, // DB 커넥션 사용
		'useCheck'	=>1, // cut_string()
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table				= $SITE['th'] . "account";
	$table_accountinfo	= $SITE['th'] . "accountinfo";
	$table_logon		= $SITE['th'] . "logon";
	$table_payment		= $SITE['th'] . "payment";

	switch($_REQUEST['mode']) {
		case "deposit" :
			deposit();
			go_url("/smember/payment/",0,"{$money}원이 청구되었으며, \\n다음 페이지에서 인터넷요금결제하시거나 무통장입금하시면 충천됩니다.");
			exit;
			break;
		case "transferok" :
			transferok();
			go_url("./");
			break;
		default :
			back("지원하지 않는 모드입니다.");
			exit;
	}

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function transferok() {
	Global $table, $table_accountinfo, $table_logon;

	$qs=array(	accountno	=> "post,trim,notnull=" . urlencode("출금계좌번호가 넘오오지 않았습니다. 확인 바랍니다."),
				to_bank		=> "post,trim,notnull=" . urlencode("이체 은행을 입력하시기 바랍니다."),
				to_accountno=> "post,trim,notnull=" . urlencode("계좌번호를 입력하시기 바랍니다."),
				to_money	=> "post,trim,notnull=" . urlencode("이체 금액을 입력하시기바랍니다."),
				passwd		=> "post,trim,notnull=" . urlencode("웹로그인 패스워드를 입력하시기바랍니다."),
		);
	$qs=check_value($qs);
	$qs['to_accountno']=eregi_replace("[^0-9]","",$qs['to_accountno']);

	// 해당 계좌정보와 계좌 내역 마지막건의 정보를 읽음
	$rs_accountinfo=db_query("SELECT * FROM {$table_accountinfo} WHERE bid={$_SESSION['seUid']} and accountno='{$qs['accountno']}'");
	$rs_account_from=db_query("select * from $table where bid={$_SESSION['seUid']} and accountno='{$qs['accountno']}' order by uid DESC limit 0,1");

	$accountinfo= db_count($rs_accountinfo) ? db_array($rs_accountinfo) : back("출금 계좌 정보가 없습니다. 확인 바랍니다.","/smember/sitebank/");
	$account_from= db_count($rs_account_from) ? db_array($rs_account_from) : back("출금 계좌 내역을 읽어오는데 실패하였습니다.\\n출금 계좌 내역을 확인 바랍니다.","/smember/sitebank/");

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
	if( !db_count(db_query("select * from {$table_logon} where uid='{$_SESSION['seUid']}' and passwd=password('{$qs['passwd']}')")) ) {
		back("회원님의 웹인증 패스워드가 틀립니다.\\n정말 회원님이시길 바라며 다시 입력바랍니다.");
	}


	if($qs['to_bank']=="사이트") {
		$qs['commission']	= 0;	// 이체수수료 없음

		// 입금 계좌 정보와 내역 마지막건 정보를 읽음
		$rs_accountinfo_to=db_query("SELECT * FROM {$table_accountinfo} WHERE bid={$_SESSION['seUid']} and accountno='{$qs['to_accountno']}'");
		$rs_account_to=db_query("select * from {$table} where bid={$_SESSION['seUid']} and accountno='{$qs['to_accountno']}' order by uid DESC limit 0,1");

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

		db_query("INSERT INTO $table (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) VALUES ('{$_SESSION['seUid']}', '{$insert_accountno}', UNIX_TIMESTAMP(), '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
		db_query("update {$table_accountinfo} set `balance`= '{$insert_balance}' where `uid` = '{$accountinfo['uid']}'");

		// 입금 계좌 내역 입력 준비
		$insert_accountno	= $qs['to_accountno'];
		$insert_type		= "이체";
		$insert_remark		= "{$qs['accountno']}에서 이체됨";
		$insert_deposit		= $qs['to_money'];
		$insert_withdrawal	= 0;
		$insert_balance		= $account_to['balance'] + $qs['to_money'];

		db_query("INSERT INTO $table (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) VALUES ('{$_SESSION['seUid']}', '{$insert_accountno}', UNIX_TIMESTAMP(), '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
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

		db_query("INSERT INTO $table (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) VALUES ('{$_SESSION['seUid']}', '{$insert_accountno}', UNIX_TIMESTAMP(), '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
		db_query("update {$table_accountinfo} set `balance`= '{$insert_balance}' where `uid` = '{$accountinfo['uid']}'");

		// 출금 계좌 내역 입력 준비 (이체 수수료)
		/* 입금 수수료를 제하고 입금함으로써 수수료 청구 안함
		$insert_accountno	= $qs['accountno'];
		$insert_type		= "수수료";
		$insert_remark		= "{$qs['to_bank']}({$qs['to_accountno']})이체건";
		$insert_deposit		= 0;
		$insert_withdrawal	= $qs['commission'];
		$insert_balance		= $account_from['balance'] - ($qs['to_money'] + $qs['commission']);
		
		db_query("INSERT INTO $table (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) VALUES ('{$seUid}', '{$insert_accountno}', UNIX_TIMESTAMP(), '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
		db_query("update {$table_accountinfo} set `balance`= '{$insert_balance}' where `uid` = '{$accountinfo['uid']}'");

		*/

	} // end if.. else..
} // end func.


function deposit() {
	Global $table_payment;

	$qs=array(	accountno	=> "post,trim,notnull=" . urlencode("계좌번호가 유효하지 않습니다."),
				money		=> "post,trim,notnull=" . urlencode("금액을 선택하시기 바랍니다.")
		);
	$qs=check_value($qs);

	// 인터넷요금결제 DB에 요금 청구
	db_query("INSERT INTO {$table_payment} (`bid`, `userid`, `orderdb`, `orderuid`, `title`, `price`, `year`, `month`, `rdate`, `status`) VALUES ('{$_SESSION['seUid']}', '{$_SESSION['seUserid']}', '포인트충전', '{$qs['accountno']}', '포인트충전({$qs['accountno']})', '{$qs['money']}', year(now()), month(now()), UNIX_TIMESTAMP(), '입금필요')");
}
?>