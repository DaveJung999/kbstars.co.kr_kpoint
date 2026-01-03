<?php
//=======================================================
// 설	명 : 주문 처리
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/01/29
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/12/03 박선민 마지막 수정
// 03/12/15 박선민 추가수정
// 04/01/29 박선민 배송장 입력되도록
// 24/08/12 Gemini	PHP 7.x, MariaDB 호환성 업데이트 
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck'	 => 1, // check_value()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함
//page_security("", $_SERVER['HTTP_HOST']);

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
	
	global $conn, $SITE;

	// table
	$table_payment	= $SITE['th'] . "payment";
	$table_coupon	= $SITE['th'] . "shopcoupon";
	$table_logon	= $SITE['th'] . "logon";

	// GET/REQUEST 파라미터 안전하게 받기
	$mode = $_REQUEST['mode'] ?? '';
	$num_get = $_GET['num'] ?? 0;
	$status_get = $_GET['status'] ?? '';
	$newstatus_get = $_GET['newstatus'] ?? '';
	$invoice_get = $_GET['invoice'] ?? '';
	$rdate_get = $_GET['rdate'] ?? 0;
	$bid_get = $_GET['bid'] ?? 0;
	$uid_get = $_GET['uid'] ?? 0;

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
switch ($mode) {
	case "delete" :
		if(!$num_get || !$status_get)
			back("삭제를 위한 정확한 값이 넘어오지 않았습니다.");

		$return = payment_status_delete($num_get ,$status_get);
		if($return)	back("성공적으로 삭제 처리되었습니다.");
		else back("일부 청구 내역이 자동삭제되지 않고 삭제 접수되었습니다.");
		break;
	case "newstatus" :
		$return = payment_status_change($num_get,$status_get,$newstatus_get, $invoice_get);
		if($return)	back("성공적으로 변경되었습니다.");
		else back("일부 혹은 전체가 변경되지 못했습니다.");
		break;
	case "rdatemodify" :
		$return = rdatemodify($table_payment);
		if($return)	back("성공적으로 변경되었습니다.");
		else back("에러 - 변경내용이 없거나 변경되지 못하였습니다.");
		break;
	case "uidmodify" :
		$return = uidmodify($table_payment);
		if($return)	back("성공적으로 변경되었습니다.");
		else back("에러 - 변경내용이 없거나 변경되지 못하였습니다.");
		break;
	case "uidwrite" :
		$goto = uidWrite($table_payment);
		if($goto) go_url($goto);
		else back("에러 - 변경내용이 없거나 변경되지 못하였습니다.");
		break;
	case "uidappend" :
		$goto = uidAppend($table_payment);
		if($goto) go_url($goto);
		else back("에러 - 변경내용이 없거나 변경되지 못하였습니다.");
		break;
	case "uiddelete" :
		if(!$rdate_get || !$bid_get || !$uid_get)
			back("삭제를 위한 정확한 값이 넘어오지 않았습니다.");

		$return = payment_uid_delete($rdate_get, $bid_get, $uid_get);
		if($return)	back("성공적으로 삭제 처리되었습니다.");
		else back("삭제할 수 없는 건입니다. 확인하여 보시기 바랍니다.");
		break;
	default :
		back("잘못된 요청입니다.");
} // end switch

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
// payment table 상태 변경
// 03/11/24 By Sunmin Park
function payment_status_change($num, $status, $newstatus, $invoice = '') {
	GLOBAL $conn, $SITE;

	if(!$num || !$status || !$newstatus) return false;

	$table_payment	= $SITE['th'] . "payment";

	// SQL Injection 방지
	$num_safe = (int)$num;
	$status_safe = db_escape($status);
	$newstatus_safe = db_escape($newstatus);
	$invoice_safe = db_escape($invoice);
	
	$sql = "SELECT * FROM {$table_payment} WHERE uid = {$num_safe}";
	$result = db_query($sql);
	$list = $result ? db_array($result) : null;
	if (!$list) return false;

	$payment_uid = $list['uid'] ?? 0;
	$bid = $list['bid'] ?? 0;
	
	if(strtoupper($newstatus)=='OK') {
		// 해당 상품이 적립금이 있고, 적립이 되지 않았을 경우 적립시킴
		$return = payment_point_deposit($payment_uid , $bid, $num, $status);
		if(!$return) back("포인트 적립 과정에서 문제가 발생되었습니다.");
	}
	elseif($newstatus=='배송중' && $invoice_safe) {
		$sql = "UPDATE {$table_payment} SET re_invoice='{$invoice_safe}' WHERE uid = '{$payment_uid}' AND status='{$status_safe}' AND orderdb='배송료'";
		db_query($sql);
	}
	//배송료의 uid 와 num 값은 같고 , 같이 주문된 제품의 num 은 모두 같다. 따라서 num 이 uid 인것의 상태를 모두 변경
	$sql = "UPDATE {$table_payment} SET status='{$newstatus_safe}' WHERE num='{$payment_uid}' AND status='{$status_safe}'";
	db_query($sql);
	return db_count();
}
// payment 포인트 적립
// 03/11/24 By Sunmin Park
function payment_point_deposit($payment_uid, $bid, $num, $status) {
	GLOBAL $conn, $SITE;

	$table_logon	= $SITE['th'] . "logon";
	$table_payment	= $SITE['th'] . "payment";
	$table_account	= $SITE['th'] . "account";
	$table_accountinfo = $SITE['th'] . "accountinfo";

	if(!$payment_uid || !$bid || !$status) return false;

	// SQL Injection 방지
	$bid_safe = (int)$bid;
	$payment_uid_safe = (int)$payment_uid;
	$num_safe = (int)$num;
	$status_safe = db_escape($status);

	// 해당 회원이 존재하는지 체크(level이 0이하라면 포인트 지급하지 않음)
	$sql = "SELECT priv, level FROM {$table_logon} WHERE uid='{$bid_safe}'";
	$result = db_query($sql);
	if($logon = db_array($result)) {
		if(($logon['level'] ?? 0) < 1) return true; // 적립할 필요 없으니 성공한 것으로 처리
	}
	else {
		back("회원이 아닙니다. 회원만이 가능합니다.");
	}
	db_free($result);

	// 회원의 적립통장 구함(여러 적립포인트 중에서 가장 처음에 생성되고 정상인 계좌로)
	$sql = "SELECT * FROM {$table_accountinfo} WHERE bid='{$bid_safe}' AND accounttype='적립포인트' AND errorno='0' ORDER BY uid LIMIT 1";
	$result = db_query($sql);
	$accountinfo = $result ? db_array($result) : null;
	if (!$accountinfo) {
		// back("적립포인트 계좌가 없거나 적립포인트 계좌에 문제가 있습니다");
		// 계좌가 없는 경우에도 오류를 발생시키지 않고 넘어갈 수 있도록 처리
		return true;
	}
	db_free($result);

	// 적립금 넣음
	$rs = db_query("SELECT * FROM {$table_payment} WHERE num = '{$num_safe}' AND status='{$status_safe}' AND pointdepositdate=0");
	$sw_change = 0;
	if ($rs) {
		while($list_payment = db_array($rs)) {
			$insert_accountno = $accountinfo['accountno'] ?? '';
			$insert_type = "적립";
			$payment_uid_in_list = $list_payment['uid'] ?? 0;
			$insert_remark = "상품구매에따른적립(payment_uid:{$payment_uid_in_list}건)";
			$insert_deposit = $list_payment['point'] ?? 0;
			$insert_withdrawal = 0;
			$insert_balance = ($accountinfo['balance'] ?? 0) + $insert_deposit - $insert_withdrawal;

			if(($accountinfo['balance'] ?? 0) != $insert_balance) {
				$remark_safe = db_escape($insert_remark);
				db_query("INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) 
			VALUES ('{$bid_safe}', '{$insert_accountno}', UNIX_TIMESTAMP(), '{$insert_type}', '{$remark_safe}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
				
				$accountinfo['balance'] = $insert_balance; // while동안에 잔액 증액
				// 해당상품 적립금 적립시간 넣음
				$sql = "UPDATE {$table_payment} SET pointdepositdate=UNIX_TIMESTAMP() WHERE uid='{$payment_uid_in_list}'";
				db_query($sql);

				$sw_change = 1;
			}
		}
		db_free($result);
	}

	if($sw_change) {
		$accountinfo_uid = $accountinfo['uid'] ?? 0;
		db_query("UPDATE {$table_accountinfo} SET `balance`= '{$accountinfo['balance']}' WHERE `uid` = '{$accountinfo_uid}'");
	}

	return true;
}

// payment table 상태에 따른 삭제 함수
// 03/11/24 By Sunmin Park
function payment_status_delete($num, $status) {
	GLOBAL $conn, $SITE;

	if(!$num || !$status) return false;

	// SQL Injection 방지
	$num_safe = (int)$num;
	$status_safe = db_escape($status);

	$table_payment	= $SITE['th'] . "payment";
	$table_account	= $SITE['th'] . "account";
	$table_accountinfo = $SITE['th'] . "accountinfo";
	$table_coupon	= $SITE['th'] . "sponcoupon";

	$rs = db_query("SELECT * FROM {$table_payment} WHERE num = '{$num_safe}' AND status='{$status_safe}'");
	$sw_receipt=0;
	if ($rs) {
		while($row = db_array($rs)) {
			$tmp = explode("_", $row['orderdb'] ?? '');
			$bid_safe = (int)($row['bid'] ?? 0);
			$uid_safe = (int)($row['uid'] ?? 0);
			$orderuid_safe = (int)($row['orderuid'] ?? 0);

			switch($tmp['0'] ?? '') {
				case "":
				case "shop":
				case "구매금액사은품":
				case "누적금액사은품":
				case "배송료": // 해당 청구 DB 삭제면 모두 끝
					db_query("DELETE FROM {$table_payment} WHERE uid='{$uid_safe}' AND status='{$status_safe}'");
					break;
				case "account" : // 적립금 다시 환원
					if(($row['status'] ?? '') == "입금필요") {
						// 해당 계좌 내역 읽음
						$sql = "SELECT * FROM {$table_account} WHERE uid='{$orderuid_safe}'";
						$result_ac = db_query($sql);
						if($account_from = db_array($result_ac)) {
							// accountinfo 구하기
							$sql = "SELECT * FROM {$table_accountinfo} WHERE bid='{$bid_safe}' AND accountno='".db_escape($account_from['accountno'] ?? '')."'";
							$result_aci = db_query($sql);
							if($accountinfo = db_array($result_aci)) {
								// 포인트계좌에 포인트 추가
								$insert_balance = ($accountinfo['balance'] ?? 0) + ($row['price'] ?? 0);
								$insert_remark = "포인트사용 취소(".($account_from['remark'] ?? '')."건)";
								$remark_safe = db_escape($insert_remark);
								$accountno_safe = db_escape($accountinfo['accountno'] ?? '');
								db_query("INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) 
										VALUES ('{$bid_safe}', '{$accountno_safe}', UNIX_TIMESTAMP(), '환원', '{$remark_safe}', '".db_escape($row['price'] ?? 0)."', '0', '{$insert_balance}', '사이트')");
								db_query("UPDATE {$table_accountinfo} SET `balance`= '{$insert_balance}' WHERE `uid` = '".db_escape($accountinfo['uid'] ?? 0)."'");
							}
							db_free($result_aci);
						}
						db_free($result_ac);
					}
					db_query("DELETE FROM {$table_payment} WHERE uid='{$uid_safe}'");
					break;
				case "shopcoupon":
					if(($row['status'] ?? '') == "입금필요") {
						// 할인쿠폰 사용 취소
						$sql = "UPDATE {$table_coupon} SET usedate='0', payment_uid='0' WHERE uid='{$orderuid_safe}'";
						db_query($sql);
					}
					db_query("DELETE FROM {$table_payment} WHERE uid='{$uid_safe}'");
					break;
				default :
					db_query("UPDATE {$table_payment} SET status='삭제접수' WHERE uid='{$uid_safe}'");
					$sw_receipt=1;
			} // end switch
		} //end while
		db_free($rs);
	}

	return !$sw_receipt;
}

// 해당 주문건 정보 일괄 변경
// 04/03/18 By Sunmin Park
function rdatemodify($table) {
	global $conn;
	$qs=array(
			"mode"		 =>	"post,trim,notnull",
			"num"		 =>	"post,trim,notnull",
			"paid"		 =>	"post,trim",
			"bank"		 =>	"post,trim",
			"idate"		 =>	"post,trim",
			"receiptor"	 =>	"post,trim",
			"newstatus"	 =>	"post,trim,notnull",
			"taxcashnewstatus"	 =>	"post,trim"
		);
	$qs=check_value($qs);

	$qs['paid'] = $qs['paid'] ?? "N";
	if(in_array($qs['newstatus'],array('입금완료','재고준비','배송준비','배송중','배송요청','OK')) && ($qs['paid'] ?? 'N') == 'N') {
		back("{$qs['newstatus']} 상태는 입금이 확인된 경우에만 변경가능합니다.\\n정확히 입력하시기 바랍니다.");
	}

	$qs['idate'] = strtotime($qs['idate'] ?? '');
	if($qs['idate'] < strtotime('2000-01-01')) {
		back("입금날자가 2000년 이전입니다. 정확히 입력하여 주시기 바랍니다");
	}

	// SQL Injection 방지
	$num_safe = (int)$qs['num'];
	$paid_safe = db_escape($qs['paid']);
	$bank_safe = db_escape($qs['bank']);
	$idate_safe = (int)$qs['idate'];
	$receiptor_safe = db_escape($qs['receiptor']);
	$newstatus_safe = db_escape($qs['newstatus']);
	$taxcashnewstatus_safe = db_escape($qs['taxcashnewstatus']);

	$sql="UPDATE {$table} SET
				`paid`		='{$paid_safe}',
				`bank`		='{$bank_safe}',
				`idate`		='{$idate_safe}',
				`receiptor`	='{$receiptor_safe}',
				`status`	='{$newstatus_safe}',
				`taxcash_status` ='{$taxcashnewstatus_safe}'
			WHERE num='{$num_safe}'";
	db_query($sql);

	return db_count();
}

// 해당 주문건 세부주문 하나 변경
// 04/03/18 By Sunmin Park
function uidmodify($table) {
	global $conn;
	$qs=array(
			"mode" => "post,trim,notnull", "uid" => "post,trim,notnull", "options" => "post,trim",
			"quantity" => "post,trim", "period" => "post,trim", "price" => "post,trim",
			"point" => "post,trim", "re_name" => "post,trim", "re_tel" => "post,trim",
			"re_email" => "post,trim", "re_address" => "post,trim", "re_zip" => "post,trim",
			"re_invoice" => "post,trim", "re_memo" => "post,trim", "memo" => "post,trim",
			"comment" => "post,trim", "submit" => "post,trim"
		);
	$qs=check_value($qs);

	$sql_set = '';
	foreach ($qs as $key => $value) {
		if ($key == 'mode' || $key == 'uid' || $key == 'submit') continue;
		$safe_value = db_escape($value);
		$sql_set .= "`".db_escape($key)."` = '{$safe_value}', ";
	}
	$sql_set = rtrim($sql_set, ', ');

	$uid_safe = (int)($qs['uid'] ?? 0);
	$sql="UPDATE {$table} SET {$sql_set} WHERE uid='{$uid_safe}'";
	db_query($sql);

	return db_count();
}

// 해당 주문건 신규입력
// 04/06/24 By Sunmin Park
function uidWrite($table) {
	GLOBAL $conn, $table_logon;
	$qs=array(
			"bid"		 =>	"post,trim,notnull", "rdate_date"	 =>	"post,trim,notnull",
			"year"		 =>	"post,trim,notnull", "month"		 =>	"post,trim,notnull",
			"ordertype"	 =>	"post,trim", "orderdb"	 =>	"post,trim",
			"orderuid"	 =>	"post,trim", "title"		 =>	"post,trim,notnull",
			"paid"		 =>	"post,trim", "bank"		 =>	"post,trim",
			"idate_date" =>	"post,trim", "receiptor"	 =>	"post,trim",
			"options"	 =>	"post,trim", "quantity"	 =>	"post,trim,checkNumber",
			"period"	 =>	"post,trim,checkNumber", "price"		 =>	"post,trim,checkNumber",
			"point"		 =>	"post,trim,checkNumber", "re_name"	 =>	"post,trim",
			"re_tel"	 =>	"post,trim", "re_email"	 =>	"post,trim",
			"re_address"	 =>	"post,trim", "re_zip"	 =>	"post,trim",
			"re_invoice"	 =>	"post,trim", "re_memo"	 =>	"post,trim",
			"memo"		 =>	"post,trim", "comment"	 =>	"post,trim"
		);
	$qs=check_value($qs);
	if(empty($qs['quantity']) || ($qs['quantity'] ?? 0) < 1) $qs['quantity'] = 1;
	if(isset($qs['idate_date'])) $qs['idate'] = strtotime($qs['idate_date']);

	$bid_safe = (int)($qs['bid'] ?? 0);
	$sql="SELECT userid FROM {$table_logon} WHERE uid='{$bid_safe}'";
	$result = db_query($sql);
	$row = $result ? db_array($result) : null;
	if (!$row) back('해당 회원이 존재하지 않습니다');
	$qs['userid'] = $row['userid'] ?? '';
	db_free($result);
		
	$qs['rdate'] = strtotime($qs['rdate_date'] ?? '');
	if(($qs['rdate'] ?? 0) < strtotime('2000-01-01')) back('주문일자가 잘못되었습니다.err373');
	
	// 유일한 rdate값 구함
	do {
		$sql = "SELECT uid FROM {$table} WHERE bid='{$bid_safe}' AND rdate='".($qs['rdate'] ?? 0)."'";
		$result = db_query($sql);
		if ($result && db_count($result) > 0) {
			$qs['rdate']++;
			db_free($result);
		} else {
			db_free($result);
			break;
		}
	} while(true);

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$sql_set = '';
	$sql_set_file = '';
	$skip_fields = array('uid', 'passwd', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip');
	if($fieldlist = userGetAppendFields($table, $skip_fields)) {
		foreach($fieldlist as $value) {
			// sql_set 만듦
			if(isset($qs[$value])) {
				$safe_value = db_escape($qs[$value]);
				$sql_set .= ", `".db_escape($value)."` = '{$safe_value}' ";
			}
			elseif(isset($_POST[$value])) {
				$safe_value = db_escape($_POST[$value]);
				$sql_set .= ", `".db_escape($value)."` = '{$safe_value}' ";
			}
		}
	}
	////////////////////////////////
	if($sql_set) $sql_set = substr($sql_set,1);
	$sql="INSERT INTO {$table} SET {$sql_set} {$sql_set_file}";

	db_query($sql);
	
	$goto = "inquirymodify.php?bid=".($bid_safe ?? 0)."&rdate=".($qs['rdate'] ?? 0)."&mode=append";
	return $goto;
}
// 해당 주문건 추가신규입력
// 04/06/24 By Sunmin Park
function uidAppend($table) {
	GLOBAL $conn;
	$qs=array(
			"bid"		 =>	"post,trim,notnull", "rdate"		 =>	"post,trim,notnull",
			"year"		 =>	"post,trim,notnull", "month"		 =>	"post,trim,notnull",
			"ordertype"	 =>	"post,trim", "orderdb"	 =>	"post,trim",
			"orderuid"	 =>	"post,trim", "title"		 =>	"post,trim,notnull",
			"options"	 =>	"post,trim", "quantity"	 =>	"post,trim,checkNumber",
			"period"	 =>	"post,trim,checkNumber", "price"		 =>	"post,trim,checkNumber",
			"point"		 =>	"post,trim,checkNumber", "re_name"	 =>	"post,trim",
			"re_tel"	 =>	"post,trim", "re_email"	 =>	"post,trim",
			"re_address"	 =>	"post,trim", "re_zip"	 =>	"post,trim",
			"re_invoice"	 =>	"post,trim", "re_memo"	 =>	"post,trim",
			"memo"		 =>	"post,trim", "comment"	 =>	"post,trim"
		);
	$qs=check_value($qs);
	if(empty($qs['quantity']) || ($qs['quantity'] ?? 0)<1) $qs['quantity'] = 1;
	if(isset($qs['idate_date'])) $qs['idate'] = strtotime($qs['idate_date']);

	// 기존 주문건에서 $qs['userid'], 기타 등등 구함
	$bid_safe = (int)($qs['bid'] ?? 0);
	$rdate_safe = (int)($qs['rdate'] ?? 0);
	$sql="SELECT * FROM {$table} WHERE bid='{$bid_safe}' AND rdate='{$rdate_safe}'";
	$result = db_query($sql);
	if (!$list = db_array($result)) back('해당 주문건이 존재하지 않습니다.');
	$qs['userid'] = $list['userid'] ?? '';
	$qs['paid']	= $list['paid'] ?? '';
	$qs['bank']	= $list['bank'] ?? '';
	$qs['idate']	= $list['idate'] ?? 0;
	$qs['receiptor']=$list['receiptor'] ?? '';
	db_free($result);

	/////////////////////////////////
	// 추가되어 있는 테이블 필드 포함
	$sql_set = '';
	$sql_set_file = '';
	$skip_fields = array('uid', 'passwd', 'upfiles', 'upfiles_totalsize', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip');
	if($fieldlist = userGetAppendFields($table, $skip_fields)) {
		foreach($fieldlist as $value) {
			// sql_set 만듦
			if(isset($qs[$value])) {
				$safe_value = db_escape($qs[$value]);
				$sql_set .= ", `".db_escape($value)."` = '{$safe_value}' ";
			}
			elseif(isset($_POST[$value])) {
				$safe_value = db_escape($_POST[$value]);
				$sql_set .= ", `".db_escape($value)."` = '{$safe_value}' ";
			}
		}
	}
	////////////////////////////////
	if($sql_set) $sql_set = substr($sql_set,1);
	$sql="INSERT INTO {$table} SET {$sql_set} {$sql_set_file}";

	db_query($sql);
	
	$goto = "inquirymodify.php?bid=".($bid_safe ?? 0)."&rdate=".($rdate_safe ?? 0)."&mode=append";
	return $goto;
}
// payment table에서 특정 uid 삭제 함수
// 03/11/24 By Sunmin Park
function payment_uid_delete($rdate, $bid, $uid) {
	GLOBAL $conn, $SITE;

	if(!$rdate || !$bid || !$uid) return false;

	$rdate_safe = (int)$rdate;
	$bid_safe = (int)$bid;
	$uid_safe = (int)$uid;

	$table_payment	= $SITE['th'] . "payment";
	$table_account	= $SITE['th'] . "account";
	$table_accountinfo = $SITE['th'] . "accountinfo";
	$table_coupon	= $SITE['th'] . "sponcoupon";

	$sql = "SELECT * FROM {$table_payment} WHERE uid='{$uid_safe}' AND rdate='{$rdate_safe}' AND bid='{$bid_safe}'";
	$result = db_query($sql);
	if(!$row = db_array($result)) return false;
	db_free($result);

	$sw_receipt=0;
	$tmp=explode("_", $row['orderdb'] ?? '');
	$orderuid_safe = (int)($row['orderuid'] ?? 0);

	switch($tmp['0'] ?? '') {
		case "":
		case "shop":
		case "구매금액사은품":
		case "누적금액사은품":
		case "배송료":
			db_query("DELETE FROM {$table_payment} WHERE uid='{$uid_safe}'");
			break;
		case "account" :
			if(($row['status'] ?? '') == "입금필요") {
				// 해당 계좌 내역 읽음
				$sql = "SELECT * FROM {$table_account} WHERE uid='{$orderuid_safe}' AND bid='{$bid_safe}'";
				$result_ac = db_query($sql);
				if($account_from = db_array($result_ac)) {
					// accountinfo 구하기
					$sql = "SELECT * FROM {$table_accountinfo} WHERE bid='{$bid_safe}' AND accountno='".db_escape($account_from['accountno'] ?? '')."'";
					$result_aci = db_query($sql);
					if($accountinfo = db_array($result_aci)) {
						// 포인트계좌에 포인트 추가
						$insert_balance = ($accountinfo['balance'] ?? 0) + ($row['price'] ?? 0);
						$insert_remark = "포인트사용 취소(".($account_from['remark'] ?? '')."건)";
						$remark_safe = db_escape($insert_remark);
						$accountno_safe = db_escape($accountinfo['accountno'] ?? '');
						db_query("INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) 
								VALUES ('{$bid_safe}', '{$accountno_safe}', UNIX_TIMESTAMP(), '환원', '{$remark_safe}', '".db_escape($row['price'] ?? 0)."', '0', '{$insert_balance}', '사이트')");
						db_query("UPDATE {$table_accountinfo} SET `balance`= '{$insert_balance}' WHERE `uid` = '".db_escape($accountinfo['uid'] ?? 0)."'");
					}
					db_free($result_aci);
				}
				db_free($result_ac);
			}
			db_query("DELETE FROM {$table_payment} WHERE uid='{$uid_safe}'");
			break;
		case "shopcoupon":
			if(($row['status'] ?? '') == "입금필요") {
				// 할인쿠폰 사용 취소
				$sql = "UPDATE {$table_coupon} SET usedate='0', payment_uid='0' WHERE uid='{$orderuid_safe}'";
				db_query($sql);
			}
			db_query("DELETE FROM {$table_payment} WHERE uid='{$uid_safe}'");
			break;
		default :
			$sw_receipt=1;
	} // end switch

	return !$sw_receipt;
}

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
/**
 * 추가 입력해야할 필드를 가져옵니다. (Modernized version)
 * @param string $table The table name.
 * @param array $skip_fields Fields to exclude.
 * @return array|false List of additional fields or false on failure.
 */
function userGetAppendFields(string $table, array $skip_fields = [])
{
	if (empty($table)) {
		return false;
	}

	$result = db_query("SHOW COLUMNS FROM {$table}");

	if (!$result) {
		return false;
	}

	$fieldlist = [];
	while($row = db_array($result)) {
		if(!in_array($row['Field'], $skip_fields)){
			$fieldlist[] = $row['Field'];
		}
	}
	db_free($result); 

	return isset($fieldlist) ? $fieldlist : false;
}
?>