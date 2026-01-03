<?php
set_time_limit(0); // 네트워크 사정에 의해 중단되지 않도록..

//=======================================================
// 설	명 : 인터넷요금결제 - 포인트 사용 금액 입력(/smember/payment/usepoint.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/06/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/12/03 박선민 마지막 수정
// 04/06/14 박선민 bugfix - account_withdrawal()
// 2025/08/13 Gemini	 PHP 7.x, MariaDB 11.x 환경에 맞춰 수정
//=======================================================
	$HEADER=array(
		'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		'useSkin' =>  1, // 템플릿 사용
		'useBoard2' => 1, // 보드관련 함수 포함
		'useCheck' => 1,
		'useApp' => 1,
	);
	require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
	page_security("", $_SERVER['HTTP_HOST']);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 비회원로그인이더라도 로그인된 이후에
	if(!isset($_SESSION['seUid']) || !isset($_SESSION['seUserid']) || !trim($_SESSION['seUid']) || !trim($_SESSION['seUserid'])){
		$_SESSION['seREQUEST_URI'] = $_SERVER['REQUEST_URI'];
		go_url("/sjoin/login.php");
		exit;
	}

	$thisPath	= dirname(__FILE__);
	//$thisUrl	= "/sthis/slist"; // 마지막 "/"이 빠져야함

	$table_payment		= $SITE['th'] . "payment";	// 지불 테이블
	$table_account		= $SITE['th'] . "account";
	$table_accountinfo	= $SITE['th'] . "accountinfo";

	$dbinfo['skin']	= "basic";

	$form_default	= "method=post action='{$_SERVER['PHP_SELF']}'>
						<input type=hidden name=mode value=usepoint
						";

//===================
// $_GET['mode']값에 따른 처리
//===================
	if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "usepoint"){
		$go_url=usepoint_ok();
		back_close("", $go_url);
	} // end if
//===================//

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
	$skinfile=basename(__FILE__,'.php').'.html';
	if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
	$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
	$tpl->set_file('html',$skinfile,TPL_BLOCK);

	$html_option_accountno = '';
	// 회원 Account 정보를 모두 가져옴
	$rs_accountinfo=db_query("SELECT * from {$table_accountinfo} WHERE bid='". db_escape($_SESSION['seUid']) ."' ORDER BY uid");
	while($row=db_array($rs_accountinfo)){
		// 비정상 계좌의 경우 스킵
		if(isset($list['errorno'])) continue;

		// 사용 포인트 예측
		if(!isset($to_money)) $to_money = (int)($row['balance']/$row['pointunit']) * $row['pointunit'];

		// 금액이 콤머 넣기
		$row['balance']		= number_format($row['balance']);
		$row['pointunit']		= number_format($row['pointunit']);
		$row['pointminimum']	= number_format($row['pointminimum']);

		// 10-123456-12로 계좌번호를 만듬
		$row['account']=preg_replace("/^([0-9]+)([0-9]{5})([0-9][0-9])$/i","\\1-\\2-\\3",$row['accountno']);

		$html_option_accountno.= "\n<option value='{$row['accountno']}'>{$row['accounttype']} {$row['account']}(잔액: {$row['balance']}원,사용단위:{$row['pointunit']}원,최소:{$row['pointminimum']}원)</option>";
	} // end while
	if(!$html_option_accountno) back("적립포인트 계좌가 없습니다.\\n최소한 한번은 포인트조회페이지를 방문하시기 바랍니다.");
	$html_option_accountno = "<select name=accountno>" .
								$html_option_accountno .
							"</select>";

	$html_option_payment = '';
	// 포인트로 일부 혹은 전체 지불할 리스트
	$result=db_query("SELECT * from {$table_payment} WHERE bid='". db_escape($_SESSION['seUid']) ."' and status='입금필요' ORDER BY rdate, ordertable DESC");
	while($row=db_array($result)){
		if($row['price']<1) continue;
		if(preg_match("/account|shopcoupon|배송료/i",$row['ordertable'])) continue;
		$row['rdate']	= date("Y-m-d",$row['rdate']);
		$row['price']	= number_format($row['price']);

		$html_option_payment .= "\n<option value='{$row['uid']}'>{$row['rdate']}:{$row['title']}:금액{$row['price']}원</option>";
	} // end while
	$html_option_payment = "<select name=payment_uid>" .
								$html_option_payment .
							"</select>";

// 템플릿 마무리 할당
	$tpl->set_var('html_option_accountno',$html_option_accountno);
	$tpl->set_var('html_option_payment',$html_option_payment);
	$tpl->set_var('to_money',$to_money);
	$tpl->set_Var('form_default',$form_default);

	$val="\\1skin/{$dbinfo['skin']}/images/";
	echo preg_replace("/([\"|\'])images\//i","{$val}",$tpl->process('', 'html', 1)); // 1 mean loop

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
	function usepoint_ok()
	{
		global $db_conn, $table_payment, $table_account, $table_accountinfo, $SITE;
		$table_payment_ncash = $table_payment	. "_ncash";

		$qs=array(
					"accountno" =>  "post,trim,notnull=" . urlencode("포인트 계좌를 선택바랍니다."),
					"payment_uid" =>  "post,trim,checkNumber=" . urlencode("지불할 내역을 선택바랍니다."),
					"to_money" =>  "post,trim,checkNumber=" . urlencode("사용하실 포인트 금액을 입력하시기 바랍니다.")
			);
		$qs=check_value($qs);

		// 포인트계좌 확인 및 잔액 가져오기
		$sql = "SELECT * from {$table_accountinfo} WHERE bid='". db_escape($_SESSION['seUid']) ."' and accountno='". db_escape($qs['accountno']) ."' ORDER BY uid";
		$list_accountinfo	= db_arrayone($sql) or back("포인트 계좌가 없습니다 . 확인 바랍니다.");

		// 포인트 사용 금액과 잔액 확인
		if($qs['to_money']<1 or $qs['to_money']>$list_accountinfo['balance'])
			back("포인트를 사용할 수 없습니다 . 확인 바랍니다.");

		// 결제할 내역 가져오기
		$sql = "SELECT * from {$table_payment} WHERE uid='". db_escape($qs['payment_uid']) ."' and bid='". db_escape($_SESSION['seUid']) ."' and status='입금필요'";
		$list_payment	= db_arrayone($sql) or back("지불할 내역이 없습니다.");

		// 해당 주문 전체 결제인지 체크함
		$rs_payment2=db_query("SELECT * from {$table_payment} where rdate='{$list_payment['rdate']}' and bid='". db_escape($_SESSION['seUid']) ."' and status='입금필요'");
		if(!db_count()) back("포인트 결제를 하실 수 없습니다 . 확인 바랍니다");
		unset($payment2_uid);
		unset($payment2_total);
		while($row=db_array($rs_payment2)){
			$payment2_uid[]=$row['uid'];
			$payment2_total+=$row['price'];
		}

		if(is_array($payment2_uid) and $payment2_total<=$qs['to_money']){
			$qs['to_money'] = $payment2_total;
		}
		// 포인트가 결제 금액 재 계산(지불할 금액보다 많거나 지불 단위에 따라 조정)
		elseif( $list_payment['price'] < $qs['to_money'] )
			$qs['to_money'] = $list_payment['price'];
		else {
			// 지불단위에 맞지 않은 입력이면
			if( $qs['to_money']%$list_accountinfo['pointunit'] != 0 ) back("해당 포인트계좌는 최소 {$list_accountinfo['pointminimum']}원보다 크고 {$list_accountinfo['pointunit']}원단위로 사용가능합니다");

			// 포인트 최소 결제금액보다 작으면
			if( $qs['to_money'] < $list_accountinfo['pointminimum'] ) back("해당 포인트계좌는 최소 {$list_accountinfo['pointminimum']}원보다 크고 {$list_accountinfo['pointunit']}원단위로 사용가능합니다");
		}

		if($qs['to_money']>0){
			// 포인트 출금 개시
			$qs['type']	= "결제";
			$qs['remark']	= "payment_uid:{$qs['payment_uid']}";

			$account_uid = account_withdrawal($qs['accountno'],$qs['type'],$qs['remark'],$qs['to_money']); // 해당

			if($qs['to_money']>0){
				// 해당 지불내역의 금액을 포인트 금액만큼 차감
				$tmp_nowdate=date("Y-m-d");
				// 포인트 결제 내역 payment에 삽입
				$sql="INSERT
						INTO
							`{$table_payment}`
						SET
							`bid`		='". db_escape($_SESSION['seUid']) ."',
							`userid`	='". db_escape($_SESSION['seUserid']) ."',
							`ordertable`='account',
							`orderuid`	='". db_escape($account_uid) ."',
							`title`	='Payment uid:{$qs['payment_uid']}번 일부 포인트결제 ',
							`price`	='-{$qs['to_money']}',
							`year`	='{$list_payment['year']}',
							`month`	='{$list_payment['month']}',
							`rdate`	='{$list_payment['rdate']}',
							`status`	='입금필요',
							`memo`		= '". db_escape($tmp_nowdate) ." - ". db_escape($list_payment['price']) ."원에서 포인트(". db_escape($qs['accountno']) .") ". db_escape($qs['to_money']) ."원 결제'
					";
				db_query($sql);
				$payment2_uid[] = db_insert_id();
			} // end if
		}

		// 본 포인트결제로써 해당 주문건이 모두 지불될 수 있다면
		if(is_array($payment2_uid) and $payment2_total == $qs['to_money']) {
			$payment2_uids=join(":",$payment2_uid);
			$contentcategorycode="0";
			$contentcategoryname="기본";

			$sql="INSERT INTO
										{$table_payment_ncash}
									SET
										`bid`			='". db_escape($_SESSION['seUid']) ."',
										`userid`		='". db_escape($_SESSION['seUserid']) ."',
										`payment_uid`	='". db_escape($payment2_uids) ."',
										`contentcategorycode`='". db_escape($contentcategorycode) ."',
										`contentcategoryname`='". db_escape($contentcategoryname) ."',
										`primcost`		='". db_escape($payment2_total) ."',
										`contentprice`	='". db_escape($payment2_total) ."',
										`bank`			='포인트',
										`receiptor`		='". db_escape($qs['accountno']) ."',
										`status`			='OK',
										`rdate`			=UNIX_TIMESTAMP(),
										`ip`			='". db_escape($_SERVER['REMOTE_ADDR']) ."'
								";
			db_query($sql);
			$contentcode=db_insert_id();
			if(!$contentcode)
				back("지불과정에서 미묘한 문제가 발생하였습니다.\\n안전상 처음부터 다시 시작하시기 바랍니다.");

			return "./moneypayed.php?bank=포인트&uid={$contentcode}&payment_uids={$payment2_uids}";
		} // end if
		else
			return "/smember/payment";
	}

// 포인트계좌 출금 처리 함수
	function account_withdrawal($qs_accountno,$qs_type,$qs_remark,$qs_to_money){
		global $db_conn, $table_accountinfo, $table_account;
		if(!isset($_SESSION['seUid']) or !isset($_SESSION['seUserid']))
			back("회원 정보가 이상합니다 . 로그아웃후 다시 로그인후에 시도하시기 바랍니다.");

		// 해당 계좌정보와 계좌 내역 마지막건의 정보를 읽음
		$sql = "SELECT * from {$table_accountinfo} WHERE bid='". db_escape($_SESSION['seUid']) ."' and accountno='". db_escape($qs_accountno) ."'";
		$rs_accountinfo=db_query($sql);
		$sql = "SELECT * from {$table_account} where bid='". db_escape($_SESSION['seUid']) ."' and accountno='". db_escape($qs_accountno) ."' order by uid DESC limit 0,1";
		$rs_account_from=db_query($sql);

		$accountinfo= db_count($rs_accountinfo) ? db_array($rs_accountinfo) : back("출금 계좌 정보가 없습니다 . 확인 바랍니다.");
		$account_from= db_count($rs_account_from) ? db_array($rs_account_from) : back("출금 계좌 내역을 읽어오는데 실패하였습니다.\\n출금 계좌 내역을 확인 바랍니다.");

		// 포인트 사용 가능한지 체크
		if($qs_to_money > $accountinfo['balance'])
			back("포인트 잔액보다 많은 금액을 입력하였습니다.\\n해당 포인트 잔액은 {$account_from['balance']}원입니다.\\n인터넷요금결제페이지로 이동합니다.","/smember/payment/");

		// 출금 계좌 내역 입력 준비
		$insert_accountno	= $qs_accountno;
		$insert_type		= $qs_type;
		$insert_remark		= $qs_remark;
		$insert_deposit		= 0;
		$insert_withdrawal	= $qs_to_money;
		$insert_balance		= $account_from['balance'] - $qs_to_money;

		$sql = "INSERT INTO {$table_account} (`bid`, `accountno`, `rdate`, `type`, `remark`, `deposit`, `withdrawal`, `balance`, `branch`) VALUES ('". db_escape($_SESSION['seUid']) ."', '". db_escape($insert_accountno) ."', UNIX_TIMESTAMP(), '". db_escape($insert_type) ."', '". db_escape($insert_remark) ."', '". db_escape($insert_deposit) ."', '". db_escape($insert_withdrawal) ."', '". db_escape($insert_balance) ."', '사이트')";
		db_query($sql);

		$account_uid = db_insert_id();

		$sql = "update {$table_accountinfo} set `balance`= '". db_escape($insert_balance) ."' where `uid` = '". db_escape($accountinfo['uid']) ."'";
		db_query($sql);

		return $account_uid;
	} // end function account_withdrawal(..);
?>
