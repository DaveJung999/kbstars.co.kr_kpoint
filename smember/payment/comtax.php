<?php
//=======================================================
// 설	명 : 세금계산서 조회 혹은 인터넷 발급으로 이동
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/20
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/08/20 박선민 마지막 수정
//=======================================================
$HEADER=array(
		'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		'useSkin' =>	1, // 템플릿 사용
		'useBoard2' => 1, // 보드관련 함수 포함
		'useApp' => 1,
		'useCheck' => 1, // check_idnum, check_compnum
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$thisPath	= dirname(__FILE__);
	$thisUrl	= "."; // 마지막 "/"이 빠져야함
	include_once("{$thisPath}/config.php");	// $dbinfo 가져오기

	// 지금 세금계산서 발행서비스 제공하는지
	if($dbinfo['enable_tax'] != 'Y')
		back("지금 세금계산서 조회, 발행을 제공하고 있지 않습니다.");

	// table
	$table_payment		= $SITE['th'] . "payment";	// 지불 테이블
	$table_companyinfo	= $SITE['th'] . "companyinfo";	// 회사정보테이블
	$table_companytax	= $SITE['th'] . "companytax";	// 회사정보테이블

	// 넘어온값 체크
	if(!$_GET['rdate']) back_close('필요한 값이 넘어오지 않았습니다.');

	// 세금계산서가 발행되어 있는지 체크
	$sql = "SELECT * from {$table_companytax} where to_cid ='{$_SESSION['seUid']}' and rdate='{$_GET['rdate']}'";
	$rs_list = db_query($sql);
	$count_list = db_count($rs_list);
	if(!$count_list) go_url("comtax_select.php?rdate={$_GET['rdate']}");
	elseif($count_list>1) { // 세금계산서 중복 발행
		// 관리자에게 알림
		$mail = array(
					'from' => 	$SITE['webmaster'],
					'to' =>	$SITE['webmaster'],
					'subject' =>	"[중요-사이트] 세금계산서 중복발행",
					'content' =>	"
다음 주문(청구)건에 대한 세금계산서가 중복 발행되었습니다.
회원아이디 : {$_SESSION['seUid']}
입금날자(timestamp) : {$_GET['rdate']}
							"
				);
		mail($mail['to'],$mail['subject'],$mail['content'],"From: {$mail['from']}");

		back_close('세금계산서가 중복 발행되었습니다 . 관리자에게 문의바랍니다 . err44');
	}
	$list = db_array($rs_list);

	// payment 합계액과 발행된 세금계산서 합계액이 일치 여부
	$sql = "select sum(price) as sumprice from {$table_payment} where bid='{$_SESSION['seUid']}' and rdate='{$_GET['rdate']}'";
	$total_money = db_resultone($sql,0,'sumprice');
	if($list['total_money'] != $total_money){
		// 관리자에게 알림
		$mail = array(
					'from' => 	$SITE['webmaster'],
					'to' =>	$SITE['webmaster'],
					'subject' =>	"[중요-사이트] 세금계산서 발행 금액 오류",
					'content' =>	"
다음 주문(청구)건에 대한 세금계산서가 발행 금액이 일치하지 않습니다.
회원아이디 : {$_SESSION['seUid']}
입금날자(timestamp) : {$_GET['rdate']}
발행한 세금계산서 금액 : {$list['total_money']}
주문(청구)건의 금액 : {$total_money}
							"
				);
		mail($mail['to'],$mail['subject'],$mail['content'],"From: {$mail['from']}");

		back_close('세금계산서에 해당하는 입금내역이 없습니다 . 관리자에게 문의 바랍니다 . err56');
	}

	// 공급자번호와 공급받는자 사업자등록번호가 동일하면 '등로번호오류'로 변경
	if($list['from_c_num'] == $list['to_c_num']){
		$sql = "update {$table_companytax} set status='등록번호오류' where uid='{$list['uid']}'";
		db_query($sql);
		back_close('세금계산서상 사업자등록번호가 오류가 있습니다 . 관리자에게 문의 바랍니다 . err90');
	}

	// 공급자 사업자등록번호 체크와 하이픈(-) 넣기
	if(10 == strlen($list['from_c_num'])) { // 사업자등록번호이면
		if(!check_compnum($list['from_c_num']) and $list['status'] != '등록번호오류'){
			// 사업자등록번호가 잘못되었으면, 등록번호 오류 상태로 정보 변경
			$sql = "update {$table_companytax} set status='등록번호오류' where uid='{$list['uid']}'";
			db_query($sql);
			back_close('세금계산서상 사업자등록번호가 오류가 있습니다 . 관리자에게 문의 바랍니다 . err99');
		}
		$list['from_c_num'] = preg_replace("/^([0-9]{3})([0-9]{2})(.*)$/",'\\1-\\2-\\3',$list['from_c_num']);
	}
	elseif(13 == strlen($list['from_c_num'])) {// 주민등록번호이면
		if(!check_idnum($list['from_c_num']) and $list['status'] != '등록번호오류'){
			// 사업자등록번호가 잘못되었으면, 등록번호 오류 상태로 정보 변경
			$sql = "update {$table_companytax} set status='등록번호오류' where uid='{$list['uid']}'";
			db_query($sql);
			back_close('세금계산서상 주민등록번호가 오류가 있습니다 . 관리자에게 문의 바랍니다 . err108');
		}
		$list['from_c_num'] = preg_replace("/^([0-9]{6})(.*)$/",'\\1-\\2',$list['from_c_num']);
	}

	// 공급받는자 사업자등록번호 체크와 하이픈(-) 넣기
	if(10 == strlen($list['to_c_num'])) { // 사업자등록번호이면
		if(!check_compnum($list['to_c_num']) and $list['status'] != '등록번호오류'){
			// 사업자등록번호가 잘못되었으면, 등록번호 오류 상태로 정보 변경
			$sql = "update {$table_companytax} set status='등록번호오류' where uid='{$list['uid']}'";
			db_query($sql);
			echo $list['to_c_num'];
			back_close('세금계산서상 사업자등록번호가 오류가 있습니다 . 관리자에게 문의 바랍니다 . err119');
		}
		$list['to_c_num'] = preg_replace("/^([0-9]{3})([0-9]{2})(.*)$/",'\\1-\\2-\\3',$list['to_c_num']);
	}
	elseif(13 == strlen($list['to_c_num'])) {// 주민등록번호이면
		if(!check_idnum($list['to_c_num']) and $list['status'] != '등록번호오류'){
			// 사업자등록번호가 잘못되었으면, 등록번호 오류 상태로 정보 변경
			$sql = "update {$table_companytax} set status='등록번호오류' where uid='{$list['uid']}'";
			db_query($sql);
			back_close('세금계산서상 주민등록번호가 오류가 있습니다 . 관리자에게 문의 바랍니다 . err128');
		}
		$list['to_c_num'] = preg_replace("/^([0-9]{6})(.*)$/",'\\1-\\2',$list['to_c_num']);
	}

	// 공급가총액 한글자씩 자르기
	$supply = (string)$list['total_supply'];
	$len_tmp = strlen($supply);
	for($i=1;$i<=$len_tmp;$i++){
		$list["total_supply{$i}"]=substr($supply,$len_tmp-$i,1);
	}
	// 세금총액 한글자씩 자르기
	$tax = (string)$list['total_tax'];
	$len_tmp = strlen($tax);
	for($i=1;$i<=$len_tmp;$i++){
		$list["total_tax{$i}"]=substr($tax,$len_tmp-$i,1);
	}

	// 금액에 콤머 붙임
	for($i=1;$i<=4;$i++){
		$list["price{$i}"]		= number_format($list["price{$i}"]);
		$list["supply_money{$i}"] = number_format($list["supply_money{$i}"]);
		$list["tax_money{$i}"]	= number_format($list["tax_money{$i}"]);
	}
	$list['total_money']	= number_format($list['total_money']);
	$list['total_cash']	= number_format($list['total_cash']);
	$list['total_check']	= number_format($list['total_check']);
	$list['total_credit']	= number_format($list['total_credit']);

	// 세금계산서 상태에 따른 처리
	switch($list['status']){
		case '승인':
			// nothing..
			break;
		case '등록번호오류' :
			back_close('세금계산서에 사업자등록번호 오류가 있습니다 . 관리자에게 문의하시기 바랍니다 . err164');
			break;
		case '발행':
		case '통지':
		case '반송':
		case '재통지': // 승인상태로 변경
			$sql="update {$table_companytax} set status='승인', rdate_receive=UNIX_TIMESTAMP() where uid='{$list['uid']}'";
			db_query($sql);
			$list['status']	= '승인';
			$list['rdate_receive']=time();
			break;
		default :
			back_close("세금계산서 상태가 {$list['status']}단계입니다 . 승인단계에서 조회가 가능합니다");
	}
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
$href['fromstamp'] = "/smember/companytax/download.php?uid=1";
$tpl->set_var('list',$list);
//$tpl->process('FROM','from'); // 공급자 블럭
$tpl->process('TO','to'); // 공급받는자 불럭

// 마무리
$val="\\1{$thisUrl}/skin/{$dbinfo['skin']}/images/";
echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html', TPL_OPTIONAL));
?>