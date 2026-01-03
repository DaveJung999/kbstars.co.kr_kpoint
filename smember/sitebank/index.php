<?php
//=======================================================
// 설	명 : 포인트 계좌 관리 페이지
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/12/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/11/19 박선민 계좌개설시 3000포인트 자동 적립
// 03/11/19 박선민 이체,충전 버튼 없앰
// 03/12/03 박선민 신규계좌개설은 firstaccount.php에서
//=======================================================
$HEADER=array(
		'private' => 1, // 브라우저 캐쉬
		'priv' => '회원', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용
		'useCheck' => 1, // cut_string()
		'useSkin' => 1, // 템플릿 사용
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once("{$thisPath}/config.php");	// $dbinfo 가져오기

	$table_account		= $SITE['th'] . "account";
	$table_accountinfo	= $SITE['th'] . "accountinfo";

	$bid = $_SESSION['seUid'];

	// PHP 7 호환성: register_globals=off 대응
	$mode = $_REQUEST['mode'] ?? '';
	$accountno = $_REQUEST['accountno'] ?? '';
	$from_year = $_GET['from_year'] ?? '';
	$from_month = $_GET['from_month'] ?? '';
	$from_day = $_GET['from_day'] ?? '';
	$to_year = $_GET['to_year'] ?? '';
	$to_month = $_GET['to_month'] ?? '';
	$to_day = $_GET['to_day'] ?? '';
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 회원 Account 정보를 모두 가져옴
$rs_accountinfo=db_query("SELECT * from {$table_accountinfo} WHERE bid='{$bid}' ORDER BY uid");
$accountinfo = null;
$accounttype = [];
while($list=db_array($rs_accountinfo)){
	$accounttype[]=$list['accounttype'];
	
	$list['state']= $list['errorno'] ? "에러발생" : "정상";

	// 해당 계좌-지급=잔액인지 확인하는 루틴 동작
	if(!$_GET['mode']){
		$deposit	=(int)db_resultone("select sum(deposit) as sum from {$table_account} where bid='{$bid}' and accountno='{$list['accountno']}' order by uid DESC limit 0,1",0,"sum");
		$withdrawal	=(int)db_resultone("select sum(withdrawal) as sum from {$table_account} where bid='{$bid}' and accountno='{$list['accountno']}' order by uid DESC limit 0,1",0,"sum");
		if($list['balance'] != $deposit-$withdrawal){
			// 비상 상황 발생!!! 해당 계좌 잔액과 입출금합계 금액이 다름
			db_query("update {$table_accountinfo} set errorno='1' , errornotice='잔액과 입출금합계 오류 발생' where bid='{$bid}' and uid='{$list['uid']}'");
			back("대단히 죄송합니다.\\n 잔액과 입출금합계가 틀리는 오류가 발생되었습니다.\\n 사이트의 문의 게시판에 문의 바랍니다.");
		}
	} // end if

	// 현금 환불 가능 금액
	if($list['transfertype'] == '사이트내자유이체및10000원단위타행이체가능'){
		$list['banktransferbalance'] = ((int)($list['balance']/10000))*10000;
	}

	// 숫자에 콤모(,) 붙이기
	$list['balance']=number_format($list['balance']);

	// 10-123456-12로 계좌번호를 만듬
	$list['account']=preg_replace("/^([0-9]+)([0-9]{5})([0-9]{2})$/","\\1-\\2-\\3",$list['accountno']);

	// URL link..
	$href['inquiry']="{$_SERVER['PHP_SELF']}?mode=inquiry&accountno={$list['accountno']}";
	$href['deposit']="{$_SERVER['PHP_SELF']}?mode=deposit&accountno={$list['accountno']}";
	if($list['transfertype'] == "모든이체불가"){
		$href['transfer']="javascript: window.alert(\"본 계좌는 이체나 환불이 되지 않습니다.\\n계좌 종류를 확인 바랍니다.\");";
	} else {
		$href['transfer']="{$_SERVER['PHP_SELF']}?mode=transfer&accountno={$list['accountno']}";
	}
		//		<a href='{$href['deposit']}'><img src='images/deposit.gif' width='35' height='15' border='0'></a>
		//		<a href='{$href['transfer']}'><img src='images/transfer.gif' width='35' height='15' border='0'></a></font></div>

	// mode가 조회등 해당 계좌정보를 필요로할때
	if($accountno == $list['accountno']){
		$accountinfo=$list;
		$accountinfo['state'] = $accountinfo['state'] == "정상" ? $accountinfo['state'] : $accountinfo['state'] . $accountinfo['errornotice'];
		$accountinfo['comment'] = nl2br($accountinfo['comment']);
		$accountinfo['rdate'] =date("Y-m-d",$accountinfo['rdate']);
	}
	$tpl->set_var('href.inquiry',$href['inquiry']);
	$tpl->set_var('href.deposit',$href['deposit']);
	$tpl->set_var('href.transfer',$href['transfer']);

	$tpl->set_var('list',$list);
	if ($accountinfo) {
		$tpl->set_var('accountinfo',$accountinfo);
	}
	
	$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
} // end while

if(!$_GET['mode'] and !is_array($accounttype)) {	// 계좌가 하나도 개설되어 있지 않으면
	go_url("/smember/sitebank/firstaccount.php?&goto=".urlencode("{$_SERVER['PHP_SELF']}"));
} // end if

/*
계좌 내역 조회
*/

if($mode == "inquiry"){
	if(!is_array($accountinfo)){
		back("계좌 정보 불려오기에 실패하였습니다.\\n계속 발생한다면 사이트 종합질문페이지에 문의 바랍니다.");
	}
	if($from_year){
		// 기간 조회에 따른 where절 만들기
		$sql_where = "rdate > " . mktime(0,0,0,$from_month, $from_day,$from_year);
		$sql_where .= " and rdate < " . mktime(23,59,59,$to_month, $to_day,$to_year);
	} else {
		$sql_where = "rdate > " . mktime(0, 0, 0, date('m')-1, date('d'), date('Y'));
		$sql_where .= " and rdate < " . time();
	}
	
	$rs_account=db_query("SELECT * from {$table_account} where bid='{$bid}' and accountno='{$accountno}' and $sql_where order by uid");
	$total = db_count($rs_account);
	if(!$total){
		$tpl->process('ACCOUNT_LIST','noaccount_list');
	} else {
		while($account_list=db_array($rs_account)){
			$account_list['rdate']=date("Y-m-d",$account_list['rdate']);
			
			// 숫자에 콤모(,) 붙이기
			$account_list['deposit']=number_format($account_list['deposit'],0,"",",");
			$account_list['withdrawal']=number_format($account_list['withdrawal'],0,"",",");
			$account_list['balance']=number_format($account_list['balance'],0,"",",");

			$tpl->set_var('account_list',$account_list);

			$tpl->process('ACCOUNT_LIST','account_list',TPL_OPTIONAL|TPL_APPEND);

		}
	}

	//form 내용 입략하기
	$form_inquiry = " action='{$_SERVER['PHP_SELF']}' method='get'>
						<input type='hidden' name='accountno' value='{$accountno}'>
						<input type='hidden' name='mode' value='inquiry'";
	$from_year = $from_year ? $from_year : date('Y');
	$from_month = $from_month ? $from_month : date('m')-1;
	$from_day = $from_day ? $from_day : date('d');
	$to_year = $to_year ? $to_year : date('Y');
	$to_month = $to_month ? $to_month : date('m');
	$to_day = $to_day ? $to_day : date('d');

	$tpl->set_var('from_year'		,$from_year);	
	$tpl->set_var('from_month'		,$from_month);	
	$tpl->set_var('from_day'		,$from_day);	
	$tpl->set_var('to_year'		,$to_year);	
	$tpl->set_var('to_month'		,$to_month);	
	$tpl->set_var('to_day'		,$to_day);	

	$tpl->set_var('form_inquiry'		,$form_inquiry);	
	$tpl->process('INQUIRY','inquiry');

} // end if($mode == "inquiry")

/////////////////////////////////////////////////////////////////
//	계좌 이체
if($mode == "deposit"){
	if(!is_array($accountinfo)){
		back("계좌 정보 불려오기에 실패하였습니다.\\n계좌번호를 확인 바랍니다.");
	}
	$form_deposit = " action='./bankok.php' method='post'>
						<input type='hidden' name='accountno' value='{$accountno}'>
						<input type='hidden' name='mode' value='deposit'
		";

	$tpl->set_var('form_deposit'		,$form_deposit);	
	$tpl->process('DEPOSIT','deposit');
} // end if($mode == "deposit")

/////////////////////////////////////////////////////////////////
//	계좌 이체
if($mode == "transfer"){
	if(!is_array($accountinfo)){
		back("계좌 정보 불려오기에 실패하였습니다.\\n계좌번호를 확인 바랍니다.");
	}
	elseif( $accountinfo['transfertype'] == "모든이체불가" ){
		back("요청하신 계좌는 이체가 되지 않습니다.\\n계좌 종류를 확인 바랍니다.");
	}
	$form_transfer = " action='{$_SERVER['PHP_SELF']}' method='post'>
						<input type='hidden' name='accountno' value='{$accountno}'>
						<input type='hidden' name='mode' value='transferconfirm'
		";
	$tpl->set_var('form_transfer'		,$form_transfer);	
	$tpl->process('TRANSFER','transfer');
} // end if($mode == "transfer")

/*
	계좌 이체
*/

if($mode == "transferconfirm"){
	// 넘어온 값 체크
	$qs=array(	'to_bank' =>	"post,trim,notnull=" . urlencode("이체 은행을 입력하시기 바랍니다."),
				'to_accountno' =>	"post,trim,notnull=" . urlencode("계좌번호를 입력하시기 바랍니다."),
				'to_money' =>	"post,trim,notnull=" . urlencode("이체 금액을 입력하시기바랍니다."),
		);
	$qs=check_value($qs);
	$qs['to_accountno']=preg_replace("/[^0-9]/","",$qs['to_accountno']);

	if(!is_array($accountinfo)){
		back("계좌 정보 불려오기에 실패하였습니다.\\n계좌번호를 확인 바랍니다.");
	}
	elseif( $accountinfo['transfertype'] == "모든이체불가" ){
		back("요청하신 계좌는 이체가 되지 않습니다.\\n계좌 종류를 확인 바랍니다.");
	}

	if($qs['to_bank'] == "사이트"){
		if(!db_count(db_query("SELECT * from {$table_accountinfo} where bid='{$bid}' and accountno='{$qs['to_accountno']}'"))){
			back("계좌번호가 틀립니다.\\n계좌번호를 확인하시고 숫자로만 입력바랍니다.");
		}
		$qs['commission']	= 0;
	} else {
		if($qs['to_money'] < 10000)
			back("실제 은행으로 이체(환급)은 1만원단위 만원 이상입니다.");
		$qs['to_money']	= (int)($qs['to_money']/10000)* 10000;
		$qs['commission']	= 500;

	}

	$transferconfirm = " action='bankok.php' method='post'>
					<input type='hidden' name='mode' value='transferok'>
					<input type='hidden' name='accountno' value='{$accountno}'>
					<input type='hidden' name='to_bank' value='{$qs['to_bank']}'>
					<input type='hidden' name='to_accountno' value='{$qs['to_accountno']}'>
					<input type='hidden' name='to_money' value='{$qs['to_money']}'
		";
	
	$tpl->set_var('qs'		,$qs);	
	$tpl->set_var('transferconfirm'		,$transferconfirm);	
	$tpl->process('TRANSFERCONFIRM','transferconfirm');

} // end if($mode == "transferconfirm")

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
