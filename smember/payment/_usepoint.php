<?php
set_time_limit(0); // 회선때문에 중단되지 않도록..

//=======================================================
// 설	명 : 인터넷요금결제 - 포인트 사용 금액 입력(/smember/payment/usepoint.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/09/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 02/09/03 박선민 마지막 수정
// 25/09/17 시스템 php 7, mariadb 10 환경으로 수정
//=======================================================
$HEADER=array(
	'auth' => 2, // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>  1, // 템플릿 사용
	'useBoard2' => 1, // 보드관련 함수 포함
	'useCheck' => 1,
	'useApp' => 1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
$thisPath	= dirname(__FILE__);
//$thisUrl	= "/sthis/slist"; // 마지막 "/"이 빠져야함

$table_payment		= $SITE['th'] . "payment";	// 지불 테이블
$table_account		= $SITE['th'] . "account";
$table_accountinfo	= $SITE['th'] . "accountinfo";

$dbinfo['skin']	= "basic";
$dbinfo['pointunit']	= 1; // 포인트 최소 사용 단위 100이면, 100원단위 사용

$form_default	= "method=post action='{$_SERVER['PHP_SELF']}'>
	<input type=hidden name=mode value=usepoint
	";

//===================
// $_GET['mode']값에 따른 처리
//===================
if(($_REQUEST['mode'] ?? null) == "usepoint"){
	$go_url=usepoint_ok();
	back_close("", $go_url);
} // end if
//===================//

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 회원 Account 정보를 모두 가져옴
$rs_accountinfo=db_query("SELECT * from {$table_accountinfo} WHERE bid={$_SESSION['seUid']} ORDER BY uid");
$html_option_accountno = "";
$html_option_payment = "";
$to_money = 0;

while($row=db_array($rs_accountinfo)){
	// 비정상 계좌의 경우 스킵
	if(isset($list['errorno']) && $list['errorno']) continue;

	$row['balance']=(int)db_resultone(db_query("select balance from {$table_account} where bid={$_SESSION['seUid']} and accountno='".db_escape($row['accountno'])."' order by uid DESC limit 0,1"),0,"balance");
	// 사용 포인트 예측
	if(!$to_money) $to_money = (int)($row['balance']/$dbinfo['pointunit']) * $dbinfo['pointunit'];

	$row['balance']=number_format($row['balance']); // 금액이 콤머 넣기

	// 10-123456-12로 계좌번호를 만듬
	$row['account']=preg_replace("/^([0-9]+)([0-9]{5})([0-9][0-9])$/","\\1-\\2-\\3",$row['accountno']);

	$html_option_accountno.= "\n<option value='{$row['accountno']}'>{$row['accounttype']} {$row['account']}(잔액: {$row['balance']}원)</option>";
} // end while
$html_option_accountno = "<select name=accountno>" .
	$html_option_accountno .
"</select>";

// 포인트로 일부 혹은 전체 지불할 리스트
$result=db_query("SELECT * from {$table_payment} WHERE bid={$_SESSION['seUid']} and status='입금필요' ORDER BY idate, ordertable DESC");
while($row=db_array($result)){
	$row['idate']	= date("Y-m-d",$row['idate']);
	$row['price']	= number_format($row['price']);

	$html_option_payment .= "\n<option value='{$row['uid']}'>{$row['idate']} : {$row['title']} : 금액 {$row['price']}원</option>";
} // end while
$html_option_payment = "<select name=payment_uid>" .
	$html_option_payment .
"</select>";

// 템플릿 마무리 할당
$tpl->set_var('html_option_accountno',$html_option_accountno);
$tpl->set_var('html_option_payment',$html_option_payment);
$tpl->set_var('to_money',$to_money);
$tpl->set_var('pointunit',$dbinfo['pointunit']);
$tpl->set_Var('form_default',$form_default);

$val="\\1skin/{$dbinfo['skin']}/images/";
echo preg_replace("/([\"|'])images\//","{$val}",$tpl->process('', 'html', 1)); // 1 mean loop

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function usepoint_ok()
{
	global $table_payment, $table_account, $table_accountinfo;
	global $dbinfo; // $dbinfo['pointunit']를 위해서
	global $SITE;

	$table_payment_ncash = $table_payment	. "_ncash";

	$qs=array(
		"accountno" =>  "post,trim,notnull=" . urlencode("포인트 계좌를 선택바랍니다."),
		"payment_uid" =>  "post,trim,checkNumber=" . urlencode("지불할 내역을 선택바랍니다."),
		"to_money" =>  "post,trim,checkNumber=" . urlencode("사용하실 포인트 금액을 입력하시기 바랍니다.")
	);
	$qs=check_value($qs);

	// 포인트계좌 확인 및 잔액 가져오기
	$rs_accountinfo=db_query("SELECT * from {$table_accountinfo} WHERE bid={$_SESSION['seUid']} and accountno='".db_escape($qs['accountno'])."' ORDER BY uid");
	$list_accountinfo	= db_count($rs_accountinfo) ? db_array($rs_accountinfo) : back("포인트 계좌가 없습니다 . 확인 바랍니다.");
	$list_accountinfo['balance'] = (int)db_resultone(db_query("select balance from {$table_account} where accountno='".db_escape($qs['accountno'])."' and bid={$_SESSION['seUid']} order by uid DESC limit 0,1"),0,"balance");

	// 포인트 사용 금액과 잔액 확인
	if($qs['to_money']<1 or $qs['to_money'] > $list_accountinfo['balance'])
		back("포인트를 사용할 수 없습니다 . 확인 바랍니다.");

	// 결제할 내역 가져오기
	$rs_payment=db_query("SELECT * from {$table_payment} WHERE uid=".db_escape($qs['payment_uid'])." and bid={$_SESSION['seUid']} and status='입금필요'");
	$list_payment	= db_count($rs_payment) ? db_array($rs_payment) : back("지불할 내역이 없습니다.");

	// 포인트가 결제 금액 재 계산(지불할 금액보다 많거나 지불 단위에 따라 조정)
	if( ($list_payment['price'] ?? 0) < $qs['to_money'] ) 
		$qs['to_money'] = $list_payment['price'] ?? 0;
	else $qs['to_money'] = (int)($qs['to_money']/$dbinfo['pointunit']) * $dbinfo['pointunit'];

	// 포인트 출금 개시
	$qs['type']	= "결제";
	$qs['remark']	= "paycode:{$qs['payment_uid']}";

	$account_uid = account_withdrawal($qs['accountno'],$qs['type'],$qs['remark'],$qs['to_money']); // 해당 

	if($qs['to_money']>0){
		// 해당 지불내역의 금액을 포인트 금액만큼 차감
		$tmp_nowdate=date("Y-m-d");
		$sql="UPDATE 
			{$table_payment} 
		SET 
			price=price - {$qs['to_money']},
			memo=CONCAT(memo, '\n{$tmp_nowdate} - ".db_escape($list_payment['price'])."원에서 포인트(".db_escape($qs['accountno']).":{$qs['to_money']}원 결제')
		WHERE
			uid=".db_escape($qs['payment_uid']);
		db_query($sql);

		// 포인트 결제 내역 payment에 삽입
		$sql="INSERT 
			INTO 
				`{$table_payment}`
			SET
				`bid`		='{$_SESSION['seUid']}', 
				`userid`	='{$_SESSION['seUserid']}',
				`ordertable`='account', 
				`orderuid`	='".db_escape($account_uid)."',
				`title`	='Payment uid:".db_escape($qs['payment_uid'])."번 일부 포인트결제 ', 
				`price`	='".db_escape($qs['to_money'])."', 
				`year`	='".db_escape($list_payment['year'])."', 
				`month`	='".db_escape($list_payment['month'])."', 
				`idate`	='".db_escape($list_payment['idate'])."',
				`rdate`	=UNIX_TIMESTAMP(),
				`bank`		='포인트',
				`receiptor`	='".db_escape($qs['accountno'])."',
				`status`	='OK',
				`memo`		= '{$tmp_nowdate} - ".db_escape($list_payment['price'])."원에서 포인트(".db_escape($qs['accountno']).") ".db_escape($qs['to_money'])."원 결제'
		";
		db_query($sql);
	} // end if

	// 포인트 전체 결제가 해당 주문 전체 결제인지 체크함
	$rs_payment2=db_query("SELECT * from {$table_payment} where bid={$_SESSION['seUid']} and idate='".db_escape($list_payment['idate'])."' and status='입금필요'");
	if(db_count($rs_payment2)){
		$payment2_uid = array();
		$payment2_total = 0;
		while($row=db_array($rs_payment2)){
			$payment2_uid[]=$row['uid'];
			$payment2_total+=$row['price'];
		}
		if(!$payment2_total) { // 본 포인트결제로써 해당 주문건이 모두 지불되었다면
			$payment2_uids=join(":",$payment2_uid);
			$contentcategorycode="0";
			$contentcategoryname="기본";

			$rs_insert=db_query("INSERT INTO
				{$table_payment_ncash}
			SET
				`bid`			='{$_SESSION['seUid']}',
				`userid`		='{$_SESSION['seUserid']}',
				`payment_uid`	='".db_escape($payment2_uids)."',
				`contentcategorycode`='{$contentcategorycode}',
				`contentcategoryname`='{$contentcategoryname}',
				`primcost`		='{$payment2_total}',
				`contentprice`	='{$payment2_total}',
				`bank`			='포인트',
				`receiptor`		='".db_escape($qs['accountno'])."',
				`status`		='OK',
				`rdate`			=UNIX_TIMESTAMP(),
				`ip`			='{$_SERVER['REMOTE_ADDR']}'
			");
			if(!($contentcode=db_insert_id()))
				back("지불과정에서 미묘한 문제가 발생하였습니다.\\n안전상 처음부터 다시 시작하시기 바랍니다.");

			return "./payed.php?bank=포인트&uid={$contentcode}&payment_uids={$payment2_uids}";
		} // end if
	} // end if
	return "/smember/payment";
}

// 포인트계좌 출금 처리 함수
function account_withdrawal($qs_accountno,$qs_type,$qs_remark,$qs_to_money){
	global $table_accountinfo, $table_account;
	if(!isset($_SESSION['seUid']) || !isset($_SESSION['seUserid']))
		back("회원 정보가 이상합니다 . 로그아웃후 다시 로그인후에 시도하시기 바랍니다.");

	// 해당 계좌정보와 계좌 내역 마지막건의 정보를 읽음
	$rs_accountinfo=db_query("SELECT * from {$table_accountinfo} WHERE bid={$_SESSION['seUid']} and accountno='".db_escape($qs_accountno)."'");
	$rs_account_from=db_query("SELECT * from {$table_account} where bid={$_SESSION['seUid']} and accountno='".db_escape($qs_accountno)."' order by uid DESC limit 0,1");

	$accountinfo= db_count($rs_accountinfo) ? db_array($rs_accountinfo) : back("출금 계좌 정보가 없습니다 . 확인 바랍니다.");
	$account_from= db_count($rs_account_from) ? db_array($rs_account_from) : back("출금 계좌 내역을 읽어오는데 실패하였습니다.\\n출금 계좌 내역을 확인 바랍니다.");

	// 해당 계좌내역의 총 입금과 총 출금이 잔액과 동일한지 체크
	$sum_deposit	=(int)db_resultone(db_query("select sum(deposit) as sum from {$table_account} where bid={$_SESSION['seUid']} and accountno='".db_escape($qs_accountno)."'"),0,"sum");
	$sum_withdrawal	=(int)db_resultone(db_query("select sum(withdrawal) as sum from {$table_account} where bid={$_SESSION['seUid']} and accountno='".db_escape($qs_accountno)."'"),0,"sum");
	if(($account_from['balance'] ?? 0) != $sum_deposit - $sum_withdrawal){
		// 비상 상황 발생!!! 해당 계좌 잔액과 입출금합계 금액이 다름
		db_query("update {$table_accountinfo} set errorno='1' , errornotice='잔액과 입출금합계 오류 발생' where bid={$_SESSION['seUid']} and uid=".($list['uid'] ?? 0));
		back("대단히 죄송합니다.\\n 잔액과 입출금합계가 틀리는 오류가 발생되었습니다.\\n 사이트의 문의 게시판에 문의 바랍니다.");
	}

	// 포인트 사용 가능한지 체크
	if($qs_to_money > ($account_from['balance'] ?? 0))
		back("포인트 잔액보다 많은 금액을 입력하였습니다.\\n해당 포인트 잔액은 ".($account_from['balance'] ?? 0)."원입니다.\\n인터넷요금결제페이지로 이동합니다.","/smember/payment/");

	// 출금 계좌 내역 입력 준비
	$insert_accountno	= $qs_accountno;
	$insert_type		= $qs_type;
	$insert_remark		= $qs_remark;
	$insert_deposit		= 0;
	$insert_withdrawal	= $qs_to_money;
	$insert_balance		= ($account_from['balance'] ?? 0) - $qs_to_money;
	db_query("INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`)
	VALUES ('{$_SESSION['seUid']}', '".db_escape($insert_accountno)."', UNIX_TIMESTAMP(), '".db_escape($insert_type)."', '".db_escape($insert_remark)."', '".db_escape($insert_deposit)."', '".db_escape($insert_withdrawal)."', '".db_escape($insert_balance)."', 'UCARI')");

	return db_insert_id();
} // end function account_withdrawal(..); ?>