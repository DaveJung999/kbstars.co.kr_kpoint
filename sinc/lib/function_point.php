<?php
// 포인트 적립
// new21PointDeposit($_SESSION['seUid'] , 1000, '포인트적립','적립');
function new21PointDeposit($bid, $point, $remark='포인트적립', $type='적립'){
	global $SITE, $db_conn;

	$table_logon		= $SITE['th'] . "logon";
	$table_account		= $SITE['th'] . "account";
	$table_accountinfo	= $SITE['th'] . "accountinfo";

	if($bid < 1 || $point < 1) return false;
	
	// 해당 회원이 존재하는지 체크(level이 0이하라면 포인트 지급하지 않음)
	$sql = "select priv, level, userid from `{$table_logon}` where uid='{$bid}'";

	if($logon = db_arrayone($sql, "", $db_conn)){
		$aPriv = explode(',',$logon['priv']);
		if(!in_array('회원',$aPriv)) return true; // 적립할 필요 없으니 성공한 것으로 처리
	}
	else back("회원이 아닙니다. 회원만이 가능합니다.");
	
	// 회원의 적립통장 구함(여러 적립포인트 중에서 가장 처음에 생성되고 정상인 계좌로)
	$sql = "select * from `{$table_accountinfo}` where bid='{$bid}' and accounttype='적립포인트' and errorno='0' order by uid limit 1";
	if(!$accountinfo = db_arrayone($sql, "", $db_conn)) back("적립포인트 계좌가 없거나 적립포인트 계좌에 문제가 있습니다");

	// 적립금 넣음
	$insert_accountno	= $accountinfo['accountno'];
	$insert_type		= $type;
	$insert_remark		= $remark;
	$insert_deposit	 = $point;
	$insert_withdrawal	= 0;
	$insert_balance	 = $accountinfo['balance'] + $insert_deposit - $insert_withdrawal;

	if($accountinfo['balance'] != $insert_balance){
		$sql = "INSERT INTO `{$table_account}` (`bid`, `userid`, `accountno`, `rdate`, `rdate_date`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) VALUES ('{$bid}', '{$logon['userid']}', '{$insert_accountno}', UNIX_TIMESTAMP(), CURDATE(), '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')";
		db_query($sql, "", $db_conn);
		
		$accountinfo['balance'] = $insert_balance; // while동안에 잔액 증액
		
		if($bid == (isset($_SESSION['seUid']) ? $_SESSION['seUid'] : '')){
				$_SESSION['sePoint'] = $insert_balance; // 세션값 변경
		}

		db_query("update `{$table_accountinfo}` set `balance`='{$accountinfo['balance']}' where `uid`='{$accountinfo['uid']}'", "", $db_conn);
	}

	return true;
}
?>
