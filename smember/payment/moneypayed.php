
<?php
set_time_limit(0); // 회선때문에 중단되지 않도록..

//=======================================================
// 설	명 : 인터넷결제성공 이후(KCP 기준)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/19
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/12/03 박선민 마지막 수정
// 04/02/06 박선민 추가 수정
// 04/07/19 박선민 정리
// 05/04/12 채혜진 138 // 상품값 업데이트 뺐음 
//=======================================================
$HEADER=array(
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// Debug By Sunmin Park)
	ob_start(); // 버퍼링 시작(Debug By Sunmin Park)
	//phpinfo();
	print_r(get_defined_vars());
	$body=ob_get_contents(); //버퍼링된 내용을 변수화
	ob_end_clean();//버퍼링비움
	mail("sponsor@new21.com",$_SERVER['PHP_SELF'],$body);

	$table				= $SITE['th'] . "payment";
	$table_ncash		= $SITE['th'] . "payment_ncash";
	$table_account		= $SITE['th'] . "account";
	$table_accountinfo	= $SITE['th'] . "accountinfo";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
/*
if($_GET['Name5'][0] == 'T') { // KCP-telec 카드 승인이라면
	if($uid=telec_payed()){
		if(dbpayed($uid)) go_url("./moneypayok.php?bank=CARD&price={$_GET['Name3']}");
		else {
			go_url("./moneypayfail.php");
		}
	} else {
		go_url("./moneypayfail.php");
	}
}
elseif($_GET['Name5'][0] == 'B') { // KCP-telec 뱅크 승인이라면
	if($uid=telec_bank_payed()){
		if(dbpayed($uid)) go_url("./moneypayok.php?bank=BANK&price={$_GET['Name3']}");
		else {
			go_url("./moneypayfail.php");
		}
	} else {
		go_url("./moneypayfail.php");
	}
}
elseif($_POST['Name5'][0] == 'M') { // KCP-telec 휴대폰 승인이라면
	if($uid=telec_hp_payed()){
		if(dbpayed($uid)) go_url("./moneypayok.php?bank=HP&price={$_GET['Name3']}");
		else {
			go_url("./moneypayfail.php");
		}
	} else {
		go_url("./moneypayfail.php");
	}
}
*/
if($_POST['respCode']) { // KCP Card
	if($uid=kcp_payed()){
		if(dbpayed($uid)) go_url("./moneypayok.php?bank=CARD&price={$_POST['amount']}");
		else {
			go_url("./moneypayfail.php");
		}
	} else {
		go_url("./moneypayfail.php");
	}
}
elseif($_REQUEST['respcode']) { // KCP Bank
	if($uid=kcp_bankpayed()){
		if(dbpayed($uid)) go_url("./moneypayok.php?bank=BANK&price={$_REQUEST['Amount']}");
		else {
			go_url("./moneypayfail.php");
		}
	} else {
		go_url("./moneypayfail.php");
	}
}
elseif($_GET['bank'] == "포인트"){
	if(dbpayed($_GET['uid'],$_GET['bank'])) go_url("./moneypayok.php?bank=POINT&price=");
	else go_url("./moneypayfail.php");
}
else back("정상적인 요청이 아님니다.\\n인터넷요금결제페이지로 이동합니다.","/smember/payment/");

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
// 지불확인된 payment_ncash의 uid값을 받아서 payment 테이블의 bank, receiptor, status 변경과 해당 지불 확인에 따른 해당 테이블 값 변경
function dbpayed($uid, $bank="") 
{
	global $table, $table_ncash, $table_account, $table_accountinfo, $SITE;

	$rs=db_query("SELECT * from {$table_ncash} where uid='{$uid}' and status='OK'");
	if(db_count()) 
		$payment_ncash= db_array($rs);
	else
		return false;

	$payment_uid=explode(":",$payment_ncash['payment_uid']);

	$error=0; // 중간 에러 발생 유무
	$sw_proccess_point=0;
	$nowtime=time();
	foreach($payment_uid as $value){
		$rs_payment=db_query("SELECT * from {$table} where num ='{$value}' and status='입금필요'");
		if(!db_count()){
			$error=1;
			continue;
		}
		else
			$list=db_array($rs_payment);

		$status_now = "입금완료";

		if($list['ordertype'] == 'shop2') { // 쇼핑몰 주문이라면
			db_query("update {$table} set status='{$status_now}', idate='{$nowtime}', bank='{$payment_ncash['bank']}', receiptor='{$payment_ncash['receiptor']}' where num ='{$value}' and bid = {$list['bid']}");
		}
		/*
		elseif($list['ordertype'] == 'auction') { // 경매 주문이라면
			db_query("update {$table} set status='경매진행중', idate=UNIX_TIMESTAMP(), bank='{$payment_ncash['bank']}', receiptor='{$payment_ncash['receiptor']}', price='{$list['price']}' where uid='{$value}'");
			if(!db_count()) $error=1;

			@db_query("update {$SITE['th']}{$list['ordertable']} set status='OK' where uid='{$list['orderuid']}'");
			if(!db_count()) $error=1;
		}
		elseif($list['ordertype'] == "포인트충전") { // 포인트 충전이라면
			// accountinfo 구하기
			$sql = "SELECT * from {$table_accountinfo} where bid='{$_SESSION['seUid']}' and accountno='{$list['orderuid']}'";
			if($accountinfo=db_arrayone($sql)){
				// 포인트계좌에 포인트 추가
				$insert_accountno	= $accountinfo['accountno'];
				$insert_type		= "충전";
				$insert_remark		= "현금 충전(고유번호: {$list['uid']})";
				$insert_deposit		= $list['price'];
				$insert_withdrawal	= 0;
				$insert_balance		= $accountinfo['balance'] + $list['price'];

				db_query("INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) 
						VALUES ('{$_SESSION['seUid']}', '{$insert_accountno}', '{$nowtime}', '{$insert_type}', '{$insert_remark}', '{$insert_deposit}', '{$insert_withdrawal}', '{$insert_balance}', '사이트')");
				db_query("update {$table_accountinfo} set `balance`= '{$insert_balance}' where `uid` = '{$accountinfo['uid']}'");

				if(db_count()){
					db_query("update {$table} set status='OK', idate='{$nowtime}', bank='{$payment_ncash['bank']}', receiptor='{$payment_ncash['receiptor']}' where uid='{$value}'");

					if(!db_count()) $error=1;
				}
				else 
					$error=1;
			}
			else // 마지막 계좌 내역이 없으면 에러처리
				$error=1;
		}
		*/
		else {
			db_query("update {$table} set status='{$status_now}', idate='{$nowtime}', bank='{$payment_ncash['bank']}', receiptor='{$payment_ncash['receiptor']}', price='{$list['price']}' where num='{$value}'");
			if(!db_count()) $error=1;
		}
	} // end foreach
	
	// payment_ncash의 status값 변경
	if($error){
		db_query("update {$table_ncash} set status='에러발생' where uid='{$uid}' and status='OK'");
		return false;
	} else {
		db_query("delete from {$table_ncash} where uid='{$uid}' and status='OK'");
		return true;
	}
} // end func

// KCP-telecbank 인터넷계좌이체
function telec_bank_payed(){
	global $table, $table_ncash;

	//String order_id	= request.getParameter ("Name0");	/* ORDER_ID */
	//String shop_id	= request.getParameter ("Name2");	/* SHOP_ID */
	//String amount	= request.getParameter ("Name3");	/* AMOUNT */
	//String app_no	= request.getParameter ("Name4");	/* APP_NO */
	//String app_rt	= request.getParameter ("Name5");	/* APP_RT */
	//String trade_tmd= request.getParameter ("Name9");	/* TRADE_TMD */
	//String trade_hms= request.getParameter ("Name10");	/* TRADE_HMS */
	//String quota= request.getParameter ("Name17");	/* TRADE_HMS */

	
	// 넘오온 값들(신용카드)
	$orderid	= $_GET['Name0'];	//oderid
	$amount		= $_GET['Name3'];
	$respCode	= $_GET['Name5'];	//결과 코드	성공(B000)
	$respMsg	= $_GET['Name4'];	//텔렉이 생성한 TX 번호
	$txntime	= $_GET['Name9'] . " " . $_GET['Name10']; //YYYY-MM-DD HH:MM:SS

	// 성공 체크
	if($respCode != "B000") { // 결제를 실패하였다면
		back("결제가 취소(실패)되었습니다 . 다시 시도하여주세요\\n","/smember/payment");
		exit;
	}

	$sql="update {$table_ncash} set status='OK', bank='계좌이체', receiptor='{$respMsg}' where uid='{$orderid}' and contentprice='{$amount}' and status=''";
	$rs=db_query($sql);
	if(!db_count())
		return false;
	else 
		return $orderid;
}

// KCP-telecmcash 휴대폰결제
function telec_hp_payed(){
	global $table, $table_ncash;

	//String order_id = request.getParameter ("Name0");	/* ORDER_ID */
	//String shop_id	= request.getParameter ("Name2");	/* SHOP_ID */
	//String amount	= request.getParameter ("Name3");	/* AMOUNT */
	//String app_no	= request.getParameter ("Name4");	/* APP_NO */
	//String app_rt	= request.getParameter ("Name5");	/* APP_RT */
	//String trade_tmd= request.getParameter ("Name9");	/* TRADE_TMD */
	//String trade_hms= request.getParameter ("Name10");	/* TRADE_HMS */
	
	// 넘오온 값들(신용카드)
	$orderid	= $_POST['Name0'];	//oderid
	$amount		= $_POST['Name3'];
	$respCode	= $_POST['Name5'];	//휴대폰 결과 성공 : M000
	$respMsg	= $_POST['Name4'];	//텔렉이 생성한 TX 번호
	$txntime	= $_POST['Name9'] . " " . $_POST['Name10']; //YYYY-MM-DD HH:MM:SS

	// 성공 체크
	if($respCode != "M000") { // 결제를 실패하였다면
		back("결제가 취소(실패)되었습니다 . 다시 시도하여주세요\\n","/smember/payment");
		exit;
	}

	$sql="update {$table_ncash} set status='OK', bank='휴대폰', receiptor='{$respMsg}' where uid='{$orderid}' and contentprice='{$amount}' and status=''";
	$rs=db_query($sql);
	if(!db_count())
		return false;
	else 
		return $orderid;
}

// KCP-telec(신용카드) 지불 처리
function telec_payed(){
	global $table, $table_ncash;

	//String order_id	= request.getParameter ("Name0");	/* ORDER_ID */
	//String shop_id	= request.getParameter ("Name2");	/* SHOP_ID */
	//String amount	= request.getParameter ("Name3");	/* AMOUNT */
	//String app_no	= request.getParameter ("Name4");	/* APP_NO */
	//String app_rt	= request.getParameter ("Name5");	/* APP_RT */
	//String trade_tmd= request.getParameter ("Name9");	/* TRADE_TMD */
	//String trade_hms= request.getParameter ("Name10");	/* TRADE_HMS */
	//String quota= request.getParameter ("Name17");	/* TRADE_HMS */
	//String Opt01= request.getParameter (“Opt01");	/* Opt01 */
	//String Opt02= request.getParameter (“Opt02");	/* Opt02 */
	//String card_id = request.getParameter (“Name20");	/* CARD_ID */
	//String pid_rtn = request.getParameter (“Name21");	/* PidRtn */
	/*
		[_GET] =>  Array
		(
			[Name0] =>  6
			[Name2] =>  CE93
			[Name3] =>  25012
			[Name4] =>  98163816
			[Name5] =>  T000
			[Name9] =>  2004-02-04
			[Name10] =>  16:33:04
			[Name17] =>  00
			[Name20] =>  KM
			[Opt01] =>  null
			[Opt02] =>  null
		)
	*/
	
	// 넘오온 값들(신용카드)
	$orderid	= $_GET['Name0'];	//oderid
	$amount		= $_GET['Name3'];
	$respCode	= $_GET['Name5'];	//카드 승인 결과 코드	카드승인 실패(T002) 성공(T000)
	$respMsg	= $_GET['Name4'];		//카드 승인 번호	만약 승인이 나지 않으면 “XXXXXXXX”
	$authNumber	= $_GET['Name20'];	//카드ID(ex . SS 신한, OH 외환, KM 국민 등)
	$txntime	= $_GET['Name9'] . " " . $_GET['Name10'];	//YYYY-MM-DD HH:MM:SS
	$ccinstallment = $_GET['Name17']; //할부기간

	// 성공 체크
	if($respCode != "T000") { // 결제를 실패하였다면
		back("결제가 취소(실패)되었습니다 . 다시 시도하여주세요\\n","/smember/payment");
		exit;
	}

	$sql="update {$table_ncash} set	paid='Y', status='OK', bank='신용카드', receiptor='{$authNumber}-{$respMsg}' where uid='{$orderid}' and contentprice='{$amount}' and status=''";
	$rs=db_query($sql);
	if(!db_count())
		return false;
	else 
		return $orderid;
}
// KCP(신용카드) 지불 처리
function kcp_payed(){
	global $table, $table_ncash;

	$kcp_url = "https://secure.kcp.co.kr";
	$kcp_url2 = "http://secure.kcp.co.kr";
	$addr = $_SERVER['HTTP_REFERER'];
	
	if (!isset($addr)){
		echo "<br><br><font size=8 color=red>정상적인 접근이 아닙니다.</font>";
		exit;
	} else {
		$dn = substr($addr,0,strlen($kcp_url));
		$dn2 = substr($addr,0,strlen($kcp_url2));
	
		if ( $dn != $kcp_url && $dn2 != $kcp_url2 ){
			echo "<br><br><font size=8 color=red>본 트랜젝션의 Origin이 일치하지 않습니다.</font>";
			exit;
		}
	}

	if($_POST['respCode'] != "00") { // 결제를 실패하였다면
		back("결제가 다음의 이유로 취소(실패)되었습니다 . 다시 시도하여주세요\\n에러메시지:{$_POST['errorMsg']}","/smember/payment");
		exit;
	}

	// 넘오온 값들(신용카드)
	$orderid = $_POST['orderid'];						//oderid
	$customerName = $_POST['customerName'];				
	$customerTel = $_POST['customerTel'];
	$userkey = $_POST['userkey'];
	$amount = $_POST['amount'];
	$respCode = $_POST['respCode'];						//응답코드
	$respMsg = $_POST['respMsg'];						//응답메세지
	$authNumber = $_POST['authNumber'];			// 승인번호
	$KCPTxnID = $_POST['KCPTxnID'];
	$txntime = $_POST['txntime'];
	$Issuer = $_POST['Issuer'];
	$ccinstallment = $_POST['ccinstallment'];			//할부기간
	$errorMsg = $_POST['errorMsg'];					//에러메세지	

	$sql="update {$table_ncash} set status='OK', bank='신용카드', receiptor='{$Issuer}-{$authNumber}' where uid='{$orderid}' and contentprice='{$amount}' and status=''";
	$rs=db_query($sql);
	if(!db_count())
		back('관리자에게 문의주세요\\n결제는 성공적으로 됐지만, 결제과정에서 문제가 발생하였습니다.','/');
	else 
		return $orderid;
}
// KCP(계좌이체) 지불 처리
function kcp_bankpayed(){
	global $table, $table_ncash;

	if($_REQUEST['respcode'] != "00") { // 결제를 실패하였다면
		back("결제가 취소(실패)되었습니다 . 다시 시도하여주세요","/smember/payment");
		exit;
	}
	/*
		echo "KcpTxnId		:{$KcpTxnId} <BR>";
		echo "지불일시		:{$Pay_Date} <BR>";
		echo "은행종류		:{$BankName} <BR>";
		echo "지불자		:{$PayrName} <BR>";
		echo "거래금액		:{$Amount} <BR>";
		echo "주문번호		:{$OrderId} <BR>";
		echo "주문자 성명	:{$customerName} <BR>";
		echo "주문자 전화번호:{$customerTel} <BR>";
		echo "사용자 정의	:{$UserKey} <BR>"; 
	*/

	// 넘오온 값들(신용카드)
	$KcpTxnId	= $_REQUEST['KcpTxnId'];	//oderid
	$Pay_Date	= $_REQUEST['Pay_Date'];				
	$BankName	= $_REQUEST['BankName'];
	$PayrName	= $_REQUEST['PayrName'];
	$Amount		= $_REQUEST['Amount'];
	$orderid	= $_REQUEST['OrderId'];	//응답코드

	$sql="update {$table_ncash} set status='OK', bank='계좌이체', receiptor='{$BankName}-{$PayrName}-{$KcpTxnId}' where uid='{$orderid}' and contentprice='{$Amount}' and status=''";
	$rs=db_query($sql);
	if(!db_count())
		return false;
	else 
		return $orderid;
} 

?>
