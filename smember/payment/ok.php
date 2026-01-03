<?php
//=======================================================
// 설	명 : payment 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/04/05
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/04/05 박선민 마지막 수정
// 24/05/18 Gemini PHP 7 마이그레이션
//=======================================================
$HEADER=array(
	'priv' => '비회원,회원', // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useCheck' => 1
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $_SERVER['HTTP_HOST']);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table_payment		= $SITE['th'] . 'payment';
	$table_coupon		= $SITE['th'] . 'shopcoupon';
	$table_account		= $SITE['th'] . 'account';
	$table_accountinfo	= $SITE['th'] . 'accountinfo';

	// 기본 URL QueryString
	$qs_basic = "db=" . ($_REQUEST['db'] ?? $table) .			//table 이름
				"&mode=" . ($_REQUEST['mode'] ?? '') .		// mode값은 list.php에서는 당연히 빈값
				"&cateuid=" . ($_REQUEST['cateuid'] ?? '') .		//cateuid
				"&team=" . ($_REQUEST['team'] ?? '') .				// 페이지당 표시될 게시물 수
				"&pern=" . ($_REQUEST['pern'] ?? '') .				// 페이지당 표시될 게시물 수
				"&sc_column=" . ($_REQUEST['sc_column'] ?? '') .	//search column
				"&sc_string=" . urlencode(stripslashes(isset($sc_string) ? $sc_string : '')) . //search string
				"&team=" . ($_REQUEST['team'] ?? '').
				"&html_headtpl=" . (isset($html_headtpl) ? $html_headtpl : '').
				"&pid=" . ($_REQUEST['pid'] ?? '').
				"&pname=" . ($_REQUEST['pname'] ?? '').
				"&goto=" . ($_REQUEST['goto'] ?? '').
				"&page=" . ($_REQUEST['page'] ?? '');

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
$bid = (int)($_SESSION['seUid'] ?? 0);
$mode = $_REQUEST['mode'] ?? '';

switch ($mode){
	case 'taxcashmodify' :
		taxcashmodify();
		back();
		break;
	case "status_ok" :
		$num = (int)($_GET['num'] ?? 0);
		$return = payment_status_change($num, $bid, "배송중", "OK");
		if($return)	back("성공적으로 변경되었습니다.");
		else back("일부 혹은 전체가 변경되지 못했습니다.");
		break;
	case "userpay" :
		userpay_ok($table_payment);
		go_url("./",0,"입력하신 요금이 청구되었습니다 . 다음 페이지에서 결제하시기 바랍니다");
		break;
	case "cancle_point" :
		$uid = (int)($_GET['uid'] ?? 0);
		$sql = "SELECT * from {$table_payment} where uid='{$uid}' and bid='{$bid}' and status='입금필요' and orderdb='account'";
		$list_payment = db_arrayone($sql) or back("해당 포인트 사용 정보가 없습니다.");

		// 해당 계좌 내역 읽음
		$orderuid = (int)$list_payment['orderuid'];
		$sql = "select * from {$SITE['th']}account where bid='{$bid}' and uid='{$orderuid}'";
		$account_from = db_arrayone($sql) or back("해당 포인트 사용 정보가 없습니다.");

		// accountinfo 구하기
		$accountno = db_escape($account_from['accountno']);
		$sql = "SELECT * from {$table_accountinfo} where bid='{$bid}' and accountno='{$accountno}'";
		if($accountinfo=db_arrayone($sql)){
			// 포인트계좌에 포인트 추가
			$insert_accountno	= $accountinfo['accountno'];
			$insert_type		= "환원";
			$insert_remark		= "포인트사용 취소(" . (int)$account_from['uid'] . "-" . db_escape($account_from['remark']) . ")";
			$insert_deposit		= abs($list_payment['price']);
			$insert_withdrawal	= 0;
			$insert_balance		= $accountinfo['balance'] + abs($list_payment['price']);

			db_query("INSERT INTO {$SITE['th']}account (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) VALUES ('{$bid}', '" . db_escape($insert_accountno) . "', UNIX_TIMESTAMP(), '" . db_escape($insert_type) . "', '" . db_escape($insert_remark) . "', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', 'UCARI')");
			db_query("update {$table_accountinfo} set `balance`= '{$insert_balance}' where `uid` = '" . (int)$accountinfo['uid'] . "'");
			if($accountinfo['accounttype'] == '적립포인트')
			$_SESSION['sePoint']=$insert_balance; // 세션값 변경

			// 주문건 요금 조정
			db_query("delete from {$table_payment} where bid='{$bid}' and status='입금필요' and uid='" . (int)$list_payment['uid'] . "'");
			$sql = "update {$table_payment} set totalprice=totalprice+{$insert_deposit} where bid='{$bid}' and num='" . (int)$list_payment['num'] . "' and re=''";
			db_query($sql);

			go_url("./",0,"포인트 사용이 취소되었습니다.\\n포인트계좌에 다시 적립하였습니다.");
		}
		else back("포인트 계좌에 문제가 있습니다 . \\n계속 같은 에러가 발생한다면 종합질문페이지에 문의 주시기 바랍니다.");
		break;
	/*
	case "cancle_coupon" :
		$uid = (int)($_GET['uid'] ?? 0);
		$sql = "SELECT * from {$table_payment} where uid='{$uid}' and bid='{$bid}' and status='입금필요' and ordertable='shopcoupon'";
		$list_payment = db_arrayone($sql) or back("해당 쿠폰사용 정보가 없습니다.");

		$orderuid = (int)$list_payment['orderuid'];
		$sql = "SELECT * from {$table_coupon} where uid='{$orderuid}'";
		$couponlist = db_arrayone($sql) or back("쿠폰 정보가 이상합니다 . 취소하실 수 없습니다");

		// 해당 상품 쿠폰 사용 취소
		$sql = "update {$table_payment} set coupon_uid='0' where uid='" . (int)$couponlist['payment_uid'] . "'";
		db_query($sql);

		// 할인쿠폰 사용 취소
		$sql = "update {$table_coupon} set usedate='0', payment_uid='0' where uid='" . (int)$couponlist['uid'] . "'";
		db_query($sql);

		// 쿠폰 사용 삭제
		db_query("delete from {$table_payment} where bid='{$bid}' and status='입금필요' and uid='" . (int)$list_payment['uid'] . "'");
		go_url("./",0,"할인쿠폰 사용이 취소되었습니다.\\n해당 쿠폰은 다시 사용하실 수 있습니다.");
		break;
	*/
	case "delete" :
		$num = (int)($_GET['num'] ?? 0);
		$rs=db_query("SELECT * from {$table_payment} where num={$num} and bid='{$bid}' and status='입금필요'");
		$sw_receipt=0;
		while($row=db_array($rs)){
			$row_uid = (int)$row['uid'];
			$orderuid = (int)$row['orderuid'];

			if($row['ordertype'] == 'shop2'){
				// orderdb에 따른 처리
				if($row['orderdb'] == 'account') { // 적립금 다시 환원
					// 해당 계좌 내역 읽음
					$sql = "select * from {$SITE['th']}account where bid='{$bid}' and uid='{$orderuid}'";
					if(!$account_from = db_arrayone($sql)){
						db_query("update {$table_payment} SET status='삭제접수' where bid='{$bid}' and status='입금필요' and uid={$row_uid}");
						$sw_receipt=1;
						continue; // 에러가 발생되면 지우지 않고 나둬버림
					}

					// accountinfo 구하기
					$accountno = db_escape($account_from['accountno']);
					$sql = "select * from {$SITE['th']}accountinfo where bid='{$bid}' and accountno='{$accountno}'";
					if(!$accountinfo = db_arrayone($sql)){
						//db_query("update {$table_payment} SET status='삭제접수' where bid='{$bid}' and status='입금필요' and uid={$row_uid}");
						$sw_receipt=1;
						continue; // 에러가 발생되면 지우지 않고 나둬버림
					}

					// 포인트계좌에 포인트 추가
					$insert_accountno	= $accountinfo['accountno'];
					$insert_type		= "환원";
					$insert_remark		= "포인트사용 취소(" . db_escape($account_from['remark']) . ")";
					$insert_deposit		= abs($row['price']);
					$insert_withdrawal	= 0;
					$insert_balance		= $accountinfo['balance'] +	abs($row['price']);
					
					db_query("INSERT INTO {$SITE['th']}account (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) VALUES ('{$bid}', '" . db_escape($insert_accountno) . "', UNIX_TIMESTAMP(), '" . db_escape($insert_type) . "', '" . db_escape($insert_remark) . "', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
					db_query("update {$SITE['th']}accountinfo set `balance`= '{$insert_balance}' where `uid` = '" . (int)$accountinfo['uid'] . "'");
					if($accountinfo['accounttype'] == '적립포인트')
						$_SESSION['sePoint']=$insert_balance; // 세션값 변경					
					
					db_query("delete from {$table_payment} where uid={$row_uid}");
				}
				elseif($row['orderdb'] == 'coupon') { // 쿠폰 다시 환원
					// 할인쿠폰 사용 취소
					$sql = "update {$table_coupon} set usedate='0', payment_uid='0' where uid='{$orderuid}'";
					db_query($sql);

					// 쿠폰 사용 삭제
					db_query("delete from {$table_payment} where uid={$row_uid}");
				} else {
					// 해당 청구 DB 삭제면 모두 끝
					db_query("delete from {$table_payment} where uid={$row_uid}");
				}
			} elseif($row['ordertype'] == 'userpay'){
				db_query("delete from {$table_payment} where uid={$row_uid}");
			}			
			else {
					db_query("update {$table_payment} SET status='삭제접수' where bid='{$bid}' and status='입금필요' and uid={$row_uid}");
					$sw_receipt=1;
			}
		} // end while

		if($sw_receipt)
			back("일부 청구 내역이 자동삭제되지 않고 삭제 접수되었습니다.\\n24시간이내에 확인을 거쳐 삭제처리될 것입니다.\\n[알림]모든 문의는 종합질문페이지에서 해결하여 드리고 있습니다.");
		else
			back("요청하신 청구 내역이 모두 삭제처리되었습니다.\\n[알림]모든 문의는 종합질문페이지에서 해결하여 드리고 있습니다.");
		break;
	default :
		back("지원되지 않은 모드입니다.");
} // end switch
//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function taxcashmodify(){
	global $SITE;

	$table_payment	= $SITE['th'] . "payment";
	
	$qs=array(
			'num' => 	"post",
			'taxcash_check' => 	"post",
			'taxcash_name' => 	"post,trim",
			'taxcash_num' => 	"post,trim",
			'taxcash_hp' => 	"post,trim",
			'taxcash_email' => 	"post,trim",
		);
	// 넘어온값 체크
	$qs=check_value($qs);
	$qs['taxcash_num'] = preg_replace('/[^0-9]/','',$qs['taxcash_num']);
	$qs['taxcash_hp']	= preg_replace('/[^0-9]/','',$qs['taxcash_hp']);
	if(!$qs['taxcash_name'] or !$qs['taxcash_num'] or !$qs['taxcash_hp'] or !$qs['taxcash_email'])
		back('현금 영수증 신청을 위해서 이름,주민(사업자)등록번호,휴대폰,메일주소를 모두 입력하셔야 합니다.');

	$num = (int)$qs['num'];
	$sql = "select * from `{$table_payment}` where uid = {$num}";
	$list = db_arrayone($sql);
		// 현금영수증 발생 가능한지
		if( (empty($list['taxcash_status']) or $list['taxcash_status'] == '발행요청')
				and !in_array($list['bank'],array('신용카드','계좌이체','휴대폰','포인트')) ){
			$taxcash_num = db_escape($qs['taxcash_num']);
			$taxcash_hp = db_escape($qs['taxcash_hp']);
			$taxcash_email = db_escape($qs['taxcash_email']);
			$sql = "update `{$table_payment}` set taxcash_num = '{$taxcash_num}' , taxcash_hp = '{$taxcash_hp}', taxcash_email = '{$taxcash_email}' , taxcash_status = '발행요청' where uid = {$num}";
			db_query($sql);
		} else {
			back("수정할수 없습니다!");
		}
}

function userpay_ok($table_payment){
	// 공통적으로 사용할 $qs
	$qs=array(
			"name" =>  "post,trim,notnull=".urlencode("이름을 입력하여주세요"),
			"tel" =>  "post,trim,notnull=".urlencode("전화번호를 입력하여주세요"),
			"price" =>  "post,trim,notnull=".urlencode("결제할 금액을 입력하여주세요"),
			"memo" =>  "post,trim",
		);
	// 넘어온값 체크
	$qs=check_value($qs);

	$bid = (int)($_SESSION['seUid'] ?? 0);
	$userid = db_escape($_SESSION['seUserid'] ?? '');
	$price = db_escape($qs['price']);
	$memo = db_escape($qs['memo']);
	$tel = db_escape($qs['tel']);

	// $sql 완성
	$sql= "INSERT INTO `{$table_payment}`	SET
					`bid`		='{$bid}',
					`userid`	='{$userid}',
					`ordertype`	='userpay',
					`orderdb`	='userpay',
					`orderuid`	='',
					`title`		='고객입력결제: {$price} 원',
					`options`	='',
					`quantity`	='1',
					`period`	='1',
					`price`		='{$price}',
					`point`		='0',
					`year`		=year(now()),
					`month`		=month(now()),
					`rdate`		=UNIX_TIMESTAMP(),
					`memo`		='{$memo}',
					`re_tel`	='{$tel}',	
					`status`	='입금필요'
			";
	db_query($sql);
	$uid = db_insert_id();
	
	// num값 생성
	if ($uid > 0) {
		$sql = "update {$table_payment} set num=uid where uid='{$uid}'";
		db_query($sql);
	}

	return $uid;
} // end func write_ok

// payment table 상태 변경
// 03/11/24 By Sunmin Park
function payment_status_change($num,$bid,$status,$newstatus){
	global $SITE;

	if(!$num or !$bid or !$status or !$newstatus) return false;

	$table_payment	= $SITE['th'] . "payment";

	if(strtoupper($newstatus) == 'OK'){
		// 해당 상품이 적립금이 있고, 적립이 되지 않았을 경우 적립시킴
		$return = payment_point_deposit($num,$bid,$status);
		if(!$return) back("포인트 적립 과정에서 문제가 발생되었습니다.");
	}

	$num_int = (int)$num;
	$bid_int = (int)$bid;
	$status_esc = db_escape($status);
	$newstatus_esc = db_escape($newstatus);

	$sql = "update {$table_payment} set status='{$newstatus_esc}' where num='$num_int' and bid='{$bid_int}' and status='{$status_esc}'";
	db_query($sql);
	return db_count();
}
// payment 포인트 적립
// 03/11/24 By Sunmin Park
function payment_point_deposit($num,$bid,$status){
	global $SITE;

	$table_logon	= $SITE['th'] . "logon";
	$table_payment	= $SITE['th'] . "payment";
	$table_account	= $SITE['th'] . "account";
	$table_accountinfo=$SITE['th'] . "accountinfo";

	if(!$num or !$bid or !$status) return false;

	$bid_int = (int)$bid;
	// 해당 회원이 존재하는지 체크(level이 0이하라면 포인트 지급하지 않음)
	$sql = "select priv,level from {$table_logon} where uid='{$bid_int}'";
	if($logon = db_arrayone($sql)){
		if($logon['level'] == '비회원') return true; // 적립할 필요 없으니 성공한 것으로 처리
	}
	else back("회원이 아닙니다 . 회원만이 가능합니다.");

	// 회원의 적립통장 구함(여러 적립포인트 중에서 가장 처음에 생성되고 정상인 계좌로)
	$sql = "SELECT * from {$table_accountinfo} where bid='{$bid_int}' and accounttype='적립포인트' and errorno='0' order by uid limit 1";
	if(!$accountinfo = db_arrayone($sql)) back("적립포인트 계좌가 없거나 적립포인트 계좌에 문제가 있습니다");

	// 적립금 넣음
	$num_int = (int)$num;
	$status_esc = db_escape($status);
	$rs=db_query("SELECT * from {$table_payment} where num={$num_int} and bid='{$bid_int}' and status='{$status_esc}' and pointdepositdate=0");
	$sw_change = 0;
	while($list_payment=db_array($rs)){
		$insert_accountno	= $accountinfo['accountno'];
		$insert_type		= "적립";
		$insert_remark		= "상품구매에따른적립(payment_uid:" . (int)$list_payment['uid'] . ")";
		$insert_deposit		= $list_payment['point'];
		$insert_withdrawal	= 0;
		$insert_balance		= $accountinfo['balance'] + $insert_deposit - $insert_withdrawal;

		if($accountinfo['balance']<>$insert_balance){
			db_query("INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) VALUES ('{$bid_int}', '" . db_escape($insert_accountno) . "', UNIX_TIMESTAMP(), '" . db_escape($insert_type) . "', '" . db_escape($insert_remark) . "', '" . db_escape($insert_deposit) . "', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
			
			$accountinfo['balance']	= $insert_balance; // while동안에 잔액 증액
			// 해당상품 적립금 적립시간 넣음
			$sql = "update {$table_payment} set pointdepositdate=UNIX_TIMESTAMP() where uid='" . (int)$list_payment['uid'] . "'";
			db_query($sql);

			$sw_change = 1;
		}
	}
	if($sw_change) db_query("update {$table_accountinfo} set `balance`= '" . db_escape($accountinfo['balance']) . "' where `uid` = '" . (int)$accountinfo['uid'] . "'");

	return true;
}

?>
