<?php
//=======================================================
// 설	명 : 포인트 계좌 관리 페이지
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 처음
// 25/11/07 Gemini AI PHP 7+ 호환성 수정 (if 비교 연산자 버그 수정, 탭 들여쓰기)
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useApp'	 => 1, // cut_string()
	'useBoard2'	 => 1, // board2Count()
	'useSkin'	 => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once($thisPath.'config.php');

	// 넘어온값 처리
	$_GET['mode'] = 'accountlist';

	// table
	$table_logon		= $SITE['th'].'logon';
	$table_groupinfo	= $SITE['th'].'groupinfo';
	$table_joininfo		= $SITE['th'].'joininfo';
	$table_payment		= $SITE['th'].'payment';
	$table_service		= $SITE['th'].'service';
	$table_loguser		= $SITE['th'].'log_userinfo';
	$table_log_wtmp		= $SITE['th'].'log_wtmp';
	$table_log_lastlog	= $SITE['th'].'log_lastlog';
	$table_account		= $SITE['th'].'account';
	$table_accountinfo	= $SITE['th'].'accountinfo';

	$dbinfo = array(
				'skin'	 =>	'basic',
				'table'	 =>	$table_logon				
			);

	// uid=???, hp=???, order=??? 처럼 짧은키워드 검색 지원
	if($_GET['bid']) { $_GET['msc_column']='logon.uid';$_GET['msc_string']=$_GET['bid'];}
	elseif($_GET['userid']) { $_GET['msc_column']='logon.userid';$_GET['msc_string']=$_GET['userid'];}
	elseif($_GET['tel']) { $_GET['msc_column']='logon.tel';$_GET['msc_string']=$_GET['tel'];}
	elseif($_GET['hp']) { $_GET['msc_column']='logon.hp';$_GET['msc_string']=$_GET['hp'];}
	elseif($_GET['order']) { $_GET['msc_column']='payment.num';$_GET['msc_string']=$_GET['order'];}
	elseif(!$_GET['msc_column']) { $_GET['msc_column']='logon.userid'; $_GET['msc_string']='%';}

	/////////////////////////////////
	// 회원 검색 및 회원정보 가져오기
	// - 넘어온값 체크
	$sql_table= explode('.',$_GET['msc_column']);
	if(sizeof($sql_table)!=2 or empty($_GET['msc_string'])) go_url('msearch.php');
	// - $sql_where
	if (preg_match('/%/', $_GET['msc_string'])) {
		if($_GET['msc_string']=='%') $_GET['msc_string'] = '%%';
		$sql_where	= " ({$SITE['th']}{$sql_table['0']}.{$sql_table['1']} like '{$_GET['msc_string']}') ";
	}
	else $sql_where	= " ({$SITE['th']}{$sql_table['0']}.{$sql_table['1']} = '{$_GET['msc_string']}') ";
	// - $sql문 완성
	switch ($sql_table['0']) {
		case 'logon' :
			$sql="select *, email as msc_column from {$SITE['th']}{$sql_table['0']} where $sql_where ";
			break;
		case 'payment':
			$sql="select {$table_logon}.*, {$SITE['th']}{$sql_table['0']}.{$sql_table['1']} as msc_column from {$table_logon}, {$SITE['th']}{$sql_table['0']} where {$table_logon}.uid={$SITE['th']}{$sql_table['0']}.bid and $sql_where ";
			break;
	} // end switch
	$rs_msearch=db_query($sql);
	// 결과값이 한명이 아니라면, 서치 페이지로 이동시킴.
	if(db_count($rs_msearch)!=1) 
		go_url("msearch.php?mode={$_GET['mode']}&msc_column={$_GET['msc_column']}&msc_string={$_GET['msc_string']}");
	$logon		= db_array($rs_msearch);
	/////////////////////////////////
	
	$bid = $logon['uid'];
	/*
	///////////////////
	// account $bid 설정
	if($_GET['bid']) {
		$bid = $_GET['bid'];

		// 해당 회원이 있는지 체크
		$sql = "SELECT * from {$table_logon} where uid='{$bid}'";
		$logon = db_arrayone($sql) or back("해당 회원은 없습니다.");
	}
	elseif($_GET['userid']) {
		// 해당 회원이 있는지 체크
		$sql = "SELECT * from {$table_logon} where userid='{$_GET['userid']}'";
		$logon = db_arrayone($sql) or back("해당 회원은 없습니다.");

		$bid = $logon['uid'];
	}
	else back("회원 고유번호가 넘어오지 않았습니다.");
	///////////////////
	*/

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

//////////////////////////////////
// 회원 Account 정보를 모두 가져옴
$sql = "SELECT * from {$table_accountinfo} WHERE bid={$bid} and accountno='{$_GET['accountno']}' LIMIT 1";
$accountinfo = db_arrayone($sql) or back('해당 포인트계좌가 없습니다. 확인 바랍니다.');
// 해당 계좌-지급=잔액인지 확인하는 루틴 동작
$deposit	=(int)db_resultone("select sum(deposit) as sum from {$table_account} where bid={$bid} and accountno='{$accountinfo['accountno']}' order by uid DESC limit 0,1",0,"sum");
$withdrawal	=(int)db_resultone("select sum(withdrawal) as sum from {$table_account} where bid={$bid} and accountno='{$accountinfo['accountno']}' order by uid DESC limit 0,1",0,"sum");
// [!] BUG FIX: 원본의 ($accountinfo['errorno']=0) 는 대입 연산자이므로, 비교 연산자 (==) 로 수정
if($accountinfo['errorno'] == 0 and $accountinfo['balance'] != $deposit-$withdrawal) {
	// 비상 상황 발생!!! 해당 계좌 잔액과 입출금합계 금액이 다름
	$accountinfo['errorno']		='1';
	$accountinfo['errornotice']	='잔액과 입출금합계 오류 발생';
	db_query("update {$table_accountinfo} set errorno='{$accountinfo['errorno']}' , errornotice='{$accountinfo['errornotice']}' where bid={$bid} and uid={$accountinfo['uid']}");
	//back("대단히 죄송합니다.\\n 잔액과 입출금합계가 틀리는 오류가 발생되었습니다.\\n 사이트의 문의 게시판에 문의 바랍니다.");
}
$accountinfo['state']= $accountinfo['errorno'] ? "에러발생" : "정상";
$accountinfo['rdate_date']=date("Y-m-d",$accountinfo['rdate']);

// 현금 환불 가능 금액
if($accountinfo['transfertype']=='사이트내자유이체및10000원단위타행이체가능') {
	$accountinfo['banktransferbalance'] = ((int)$accountinfo['balance']/10000)*10000;
}

// 숫자에 콤모(,) 붙이기
$accountinfo['balance']=number_format($accountinfo['balance']);

// 10-123456-12로 계좌번호를 만듬
$accountinfo['account']=preg_replace("/^([0-9]+)([0-9]{5})([0-9][0-9])$/i", "\\1-\\2-\\3", $accountinfo['accountno']);


// 템플릿 할당
$tpl->set_var('accountinfo'	,$accountinfo);
//////////////////////////////////

if( $accountinfo['transfertype']=="모든이체불가" ) {
	back("요청하신 계좌는 이체가 되지 않습니다.\\n계좌 종류를 확인 바랍니다.");
}

	$transferconfirm = " action='accountok.php' method='post'>
					<input type='hidden' name='bid'	value='$bid'>
					<input type='hidden' name='mode' value='transferok'>
					<input type='hidden' name='accountno' value='{$accountno}'>
					<input type='hidden' name='to_bank' value={$qs['to_bank']}>
					<input type='hidden' name='to_accountno' value={$qs['to_accountno']}>
					<input type='hidden' name='to_money' value='{$qs['to_money']}'
		";

		
$form_transfer = " action='accountok.php' method='post'>";
$form_transfer .= substr(href_qs("mode=transferok",'',1),0,-1);
$tpl->set_var('form_transfer',$form_transfer);

$href['accountlist']="accountlist.php?".href_qs("bid={$bid}","bid=");
// 템플릿 마무리 할당
$tpl->tie_var('dbinfo'			,$dbinfo);
$tpl->set_var('href'			,$href);
$tpl->tie_var('get'				,$_GET);
$tpl->set_var('logon'			,$logon);

// - 회원전체 서치 부분
$tpl->set_var('count_msearch',$count_msearch);
$tpl->set_var('get.msc_string',htmlspecialchars(stripslashes($_GET['msc_string']),ENT_QUOTES));
$form_msearch = " method=get action='{$_SERVER['PHP_SELF']}'> ";
$form_msearch .= substr(href_qs("mode={$_GET['mode']}",'mode=',1),0,-1);
$tpl->set_var('form_msearch',$form_msearch);

// 마무리
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));
?>