<?php
//=======================================================
// 설	명 : 처리(ok.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/09/10 
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/09/10 박선민 처음제작
// 04/09/10 박선민 마지막수정
//=======================================================
$HEADER=array(
		'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		'useCheck' => 1, // check_value()
		'useApp' => 1, // remote_addr()
	);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $_SERVER['HTTP_HOST']);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$urlprefix	= ""; // ???list.php ???write.ephp ???ok.php
	$prefix		= ""; // board? album? 등의 접두사
	$thisPath	= dirname(__FILE__);
	$thisUrl	= "."; // 마지막 "/"이 빠져야함
	
	// table
	$table_companyinfo = $SITE['th'] . 'companyinfo';
	
	// 기본 URL QueryString
	$qs_basic = "";

	// $dbinfo
	include_once("{$thisPath}/config.php");	// $dbinfo 가져오기
	$dbinfo['table'] = $SITE['th'] . "comptaxbuy";

	// 공통적으로 사용할 $qs
	$qs=array(
			"from_cpid" =>  "post,trim,notnull=".urlencode('공급자 선택이 잘못되었습니다.') . ",checkNumber=".urlencode('공급자 선택이 잘못되었습니다.'),
			"to_cpid" =>  "post,trim,notnull,checkNumber=".urlencode('공급받는자 선택이 잘못되었습니다.') . ",checkNumber=".urlencode('공급받는자 선택이 잘못되었습니다.'),
			//"bid" =>  "post,trim",
			//"paymentrdate" =>  "post,trim",
			//"type" =>  "post,trim",
			"from_c_num" =>  "post,trim,notnull=".urlencode('공급자번호를 입력하십시요') . ",checkNumber=".urlencode('공급자번호를 입력하십시요'),
			"from_c_name" =>  "post,trim,notnull=".urlencode('공급자 회사명을 입력하십시요'),
			"from_c_owner" =>  "post,trim,notnull=".urlencode('공급자 성명을 입력하십시요'),
			"from_c_address" =>  "post,trim,notnull=".urlencode('공급자 사업장주소를 입력하십시요'),
			"from_c_kind" =>  "post,trim,notnull=".urlencode('공급자 업태를 입력하십시요'),
			"from_c_detail" =>  "post,trim,notnull=".urlencode('공급자 종목을 입력하십시요'),
			"to_c_num" =>  "post,trim,notnull=".urlencode('공급받는자 번호를 입력하십시요') . ",checkNumber=".urlencode('공급받는자 번호를 입력하십시요'),
			"to_c_name" =>  "post,trim,notnull=".urlencode('공급받는자 회사명을 입력하십시요'),
			"to_c_owner" =>  "post,trim,notnull=".urlencode('공급받는자 성명을 입력하십시요'),
			"to_c_address" =>  "post,trim,notnull=".urlencode('공급받는자 사업장주소를 입력하십시요'),
			"to_c_kind" =>  "post,trim,notnull=".urlencode('공급받는자 업태를 입력하십시요'),
			"to_c_detail" =>  "post,trim,notnull=".urlencode('공급받는자 종목을 입력하십시요'),
			"date1" =>  "post,trim",
			"item1" =>  "post,trim,notnull=".urlencode('첫번째 품목은 꼭 입력되어야 합니다.'),
			"standard1" =>  "post,trim",
			"quantity1" =>  "post,trim",
			"price1" =>  "post,trim,checkNumber=".urlencode('품목 첫번째 단가를 숫자로 입력하십시요'),
			"supply_money1" =>  "post,trim,checkNumber=".urlencode('품목 첫번째 공급가액을 숫자로 입력하십시요'),
			"tax_money1" =>  "post,trim,checkNumber=".urlencode('품목 첫번째 세액을 숫자로 입력하십시요'),
			"etc1" =>  "post,trim",
			"date2" =>  "post,trim",
			"item2" =>  "post,trim",
			"standard2" =>  "post,trim",
			"quantity2" =>  "post,trim",
			"price2" =>  "post,trim,checkNumber=".urlencode('품목 두번째 단가를 숫자로 입력하십시요'),
			"supply_money2" =>  "post,trim,checkNumber=".urlencode('품목 두번째 공급가액을 숫자로 입력하십시요'),
			"tax_money2" =>  "post,trim,checkNumber=".urlencode('품목 두번째 세액을 숫자로 입력하십시요'),
			"etc2" =>  "post,trim",
			"date3" =>  "post,trim",
			"item3" =>  "post,trim",
			"standard3" =>  "post,trim",
			"quantity3" =>  "post,trim",
			"price3" =>  "post,trim,checkNumber=".urlencode('품목 세번째 단가를 숫자로 입력하십시요'),
			"supply_money3" =>  "post,trim,checkNumber=".urlencode('품목 세번째 공급가액을 숫자로 입력하십시요'),
			"tax_money3" =>  "post,trim,checkNumber=".urlencode('품목 세번째 세액을 숫자로 입력하십시요'),
			"etc3" =>  "post,trim",
			"date4" =>  "post,trim",
			"item4" =>  "post,trim",
			"standard4" =>  "post,trim",
			"quantity4" =>  "post,trim",
			"price4" =>  "post,trim,checkNumber=".urlencode('품목 네번째 단가를 숫자로 입력하십시요'),
			"supply_money4" =>  "post,trim,checkNumber=".urlencode('품목 네번째 공급가액을 숫자로 입력하십시요'),
			"tax_money4" =>  "post,trim,checkNumber=".urlencode('품목 네번째 세액을 숫자로 입력하십시요'),
			"etc4" =>  "post,trim",
			//"total_supply" =>  "post,trim",
			//"total_tax" =>  "post,trim",
			"total_money" =>  "post,trim,checkNumber=".urlencode('합계금액을 숫자로 입력하십시요'),
			"total_cash" =>  "post,trim,checkNumber=".urlencode('현금을 숫자로 입력하십시요'),
			"total_check" =>  "post,trim,checkNumber=".urlencode('수표를 숫자로 입력하십시요'),
			"total_bill" =>  "post,trim,checkNumber=".urlencode('어음을 숫자로 입력하십시요'),
			"total_credit" =>  "post,trim,checkNumber=".urlencode('외상미수금을 숫자로 입력하십시요'),
			//"total_space" =>  "post,trim",
			"total_etc" =>  "post,trim",
			"total_gubun" =>  "post,trim",
			"rdate" =>  "post,trim",
			//"rdate_send" =>  "post,trim",
			//"rdate_receive" =>  "post,trim",
			//"rdate_reject" =>  "post,trim",
			//"rdate_resend" =>  "post,trim",
			//"rdate_cancle" =>  "post,trim",
			//"status" =>  "post,trim"
		);
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']){
	case 'write':
		$uid = write_ok($dbinfo, $qs);
		back_close('',$_REQUEST['goto'] ? $_REQUEST['goto'] : $dbinfo['goto_write'] ? $dbinfo['goto_write'] : "{$thisUrl}/{$urlprefix}read.php?" . href_qs("uid={$uid}",$qs_basic));
		break;
	case 'modify':
		modify_ok($dbinfo,$qs,'uid');
		back_close('',$_REQUEST['goto'] ? $_REQUEST['goto'] : $dbinfo['goto_modify'] ? $dbinfo['goto_modify'] : "{$thisUrl}/{$urlprefix}read.php?" . href_qs("uid={$uid}",$qs_basic));
		break;
	case 'delete':
		$goto = $_REQUEST['goto'] ? $_REQUEST['goto'] : $dbinfo['goto_delete'] ? $dbinfo['goto_delete'] : "{$thisUrl}/{$urlprefix}list.php?{$qs_basic}";
		delete_ok($dbinfo,'uid',$goto);
		go_url($goto);
		break;
	default :
		back("잘못된 웹 페이지에 접근하였습니다");
} // end switch

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function write_ok(&$dbinfo, $qs){
	global $table_companyinfo;

	// 권한체크
	if(!siteAuth($dbinfo, "priv_write")) back("추가 권한이 없습니다");

	// 넘어온값 체크
	//$qs['userid']	= "post,trim";
	$qs=check_value($qs);
	
	//-from_cpid
	if(10 == strlen($qs['from_c_num'])) { // 사업자등록번호이면
		if(!check_compnum($qs['from_c_num'])) back('공급자번호로 입력한 사업자등록번호에 오류가 있습니다.');
	}
	elseif(13 == strlen($qs['from_c_num'])) {// 주민등록번호이면
		if(!check_idnum($qs['from_c_num'])) back('공급자번호로 입력한 주민등록번호에 오류가 있습니다.');
	}
	else back('공급자번호를 정확히 입력하여주시기 바랍니다.');	
	$sql="SELECT * from {$table_companyinfo} where uid='{$qs['from_cpid']}' and c_num='{$qs['from_c_num']}'";
	$from_cp = db_arrayone($sql) or back('공급자번호 선택이 잘못되었습니다.');
	if($from_cp['status'] != 'OK') back("회사정보 상태가 '{$from_cp['status']}'입니다.\\n'OK'상태인 경우에만 세금계산서 발행이 가능합니다.");
	//-to_cpid
	if(10 == strlen($qs['to_c_num'])) { // 사업자등록번호이면
		if(!check_compnum($qs['to_c_num'])) back('공급자번호로 입력한 사업자등록번호에 오류가 있습니다.');
	}
	elseif(13 == strlen($qs['to_c_num'])) {// 주민등록번호이면
		if(!check_idnum($qs['to_c_num'])) back('공급자번호로 입력한 주민등록번호에 오류가 있습니다.');
	}
	else back('공급자번호를 정확히 입력하여주시기 바랍니다.');		
	$sql="SELECT * from {$table_companyinfo} where uid='{$qs['to_cpid']}' and c_num='{$qs['to_c_num']}'";
	$to_cp = db_arrayone($sql) or back('공급받는자번호 선택이 잘못되었습니다.');
	if($to_cp['status'] != 'OK') back("회사정보 상태가 '{$from_cp['status']}'입니다.\\n'OK'상태인 경우에만 세금계산서 발행이 가능합니다.");
	//-비교 from_cpid, to_cpid
	if($qs['from_c_num'] == $qs['to_c_num']) back('공급자번호와 공급받는자번호는 동일할 수 없습니다.');

	// 값 추가
	$qs['bid']	= $_SESSION['seUid'];
	$qs['type']	= '직접입력';
	// - total_supply, total_tax
	$qs['total_supply']	= 0;
	$qs['total_tax']		= 0;
	for($i=1;$i<=4;$i++){
		if($qs["item{$i}"] and $qs["supply_money{$i}"]) { // 품목에 이름이 있는 경우에만 인정
			$qs['total_supply']	+= $qs["supply_money{$i}"];
			$qs['total_tax']		+= $qs["tax_money{$i}"];
		}
		else break;
	}
	if($qs['total_money'] != $qs['total_supply']+$qs['total_tax']) 
		back('세부내역의 공급가액과 세금이 합계와 다름니다 . \\n\\n다시 확인하십시요');
	// - total_space
	$tax['total_space']		= 11 - strlen((string)$tax['total_supply']);
	// - rdate
	$qs['rdate'] = strtotime($qs['rdate']);
	if($qs['rdate']<946652400) back('작성일자를 2000년 1월 1일 이후로 입력하시기 바랍니다.');
	// - status
	$qs['status'] = '발행';
	
	// $sql 완성
	$sql="INSERT INTO {$dbinfo['table']} SET
				`from_cpid`	='{$qs['from_cpid']}',
				`to_cpid`	='{$qs['to_cpid']}',
				`bid`		='{$qs['bid']}',
				`type`		='{$qs['type']}',
				`from_c_num`	='{$qs['from_c_num']}',
				`from_c_name`	='{$qs['from_c_name']}',
				`from_c_owner`	='{$qs['from_c_owner']}',
				`from_c_address`='{$qs['from_c_address']}',
				`from_c_kind`	='{$qs['from_c_kind']}',
				`from_c_detail`	='{$qs['from_c_detail']}',
				`to_c_num`	='{$qs['to_c_num']}',
				`to_c_name`	='{$qs['to_c_name']}',
				`to_c_owner`	='{$qs['to_c_owner']}',
				`to_c_address`	='{$qs['to_c_address']}',
				`to_c_kind`		='{$qs['to_c_kind']}',
				`to_c_detail`	='{$qs['to_c_detail']}',
				`date1`		='{$qs['date1']}',
				`item1`		='{$qs['item1']}',
				`standard1`	='{$qs['standard1']}',
				`quantity1`	='{$qs['quantity1']}',
				`price1`	='{$qs['price1']}',
				`supply_money1`	='{$qs['supply_money1']}',
				`tax_money1`	='{$qs['tax_money1']}',
				`etc1`		='{$qs['etc1']}',
				`date2`		='{$qs['date2']}',
				`item2`		='{$qs['item2']}',
				`standard2`	='{$qs['standard2']}',
				`quantity2`	='{$qs['quantity2']}',
				`price2`	='{$qs['price2']}',
				`supply_money2`	='{$qs['supply_money2']}',
				`tax_money2`	='{$qs['tax_money2']}',
				`etc2`		='{$qs['etc2']}',
				`date3`		='{$qs['date3']}',
				`item3`		='{$qs['item3']}',
				`standard3`	='{$qs['standard3']}',
				`quantity3`	='{$qs['quantity3']}',
				`price3`	='{$qs['price3']}',
				`supply_money3`	='{$qs['supply_money3']}',
				`tax_money3`	='{$qs['tax_money3']}',
				`etc3`		='{$qs['etc3']}',
				`date4`		='{$qs['date4']}',
				`item4`		='{$qs['item4']}',
				`standard4`	='{$qs['standard4']}',
				`quantity4`	='{$qs['quantity4']}',
				`price4`	='{$qs['price4']}',
				`supply_money4`	='{$qs['supply_money4']}',
				`tax_money4`	='{$qs['tax_money4']}',
				`etc4`		='{$qs['etc4']}',
				`total_supply`	='{$qs['total_supply']}',
				`total_tax`	='{$qs['total_tax']}',
				`total_money`	='{$qs['total_money']}',
				`total_cash`	='{$qs['total_cash']}',
				`total_check`	='{$qs['total_check']}',
				`total_bill`	='{$qs['total_bill']}',
				`total_credit`	='{$qs['total_credit']}',
				`total_space`	='{$qs['total_space']}',
				`total_etc`	='{$qs['total_etc']}',
				`total_gubun`	='{$qs['total_gubun']}',
				`rdate`		='{$qs['rdate']}',
				`status`	='{$qs['status']}'
		";
	db_query($sql);

	return db_insert_id();
} // end func write_ok

function modify_ok(&$dbinfo,$qs,$field){
	// 넘어온값 체크
	$qs["{$field}"]	= "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다");
	$qs=check_value($qs);

	// 값 추가

	// 해당 데이터 읽기
	$sql_where	= " 1 "; // $sql_where 시작
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}' and  $sql_where ";
	if( !$list=db_arrayone($sql) )
		back("해당 데이터가 없습니다");

	// 권한체크
	if(!siteAuth($dbinfo, "priv_delete")){
		if($list['bid'] != $_SESSION['seUid']) back("수정 권한이 없습니다");
	}

	// $sql 완성
	$sql="UPDATE {$dbinfo['table']} SET
				`from_cpid`		='{$qs['from_cpid']}',
				`to_cpid`		='{$qs['to_cpid']}',
				`bid`			='{$qs['bid']}',
				`type`			='{$qs['type']}',
				`from_c_num`	='{$qs['from_c_num']}',
				`from_c_name`	='{$qs['from_c_name']}',
				`from_c_owner`	='{$qs['from_c_owner']}',
				`from_c_address`='{$qs['from_c_address']}',
				`from_c_kind`	='{$qs['from_c_kind']}',
				`from_c_detail`	='{$qs['from_c_detail']}',
				`to_c_num`		='{$qs['to_c_num']}',
				`to_c_name`		='{$qs['to_c_name']}',
				`to_c_owner`	='{$qs['to_c_owner']}',
				`to_c_address`	='{$qs['to_c_address']}',
				`to_c_kind`		='{$qs['to_c_kind']}',
				`to_c_detail`	='{$qs['to_c_detail']}',
				`date1`			='{$qs['date1']}',
				`item1`			='{$qs['item1']}',
				`standard1`		='{$qs['standard1']}',
				`quantity1`		='{$qs['quantity1']}',
				`price1`		='{$qs['price1']}',
				`supply_money1`	='{$qs['supply_money1']}',
				`tax_money1`	='{$qs['tax_money1']}',
				`etc1`			='{$qs['etc1']}',
				`date2`			='{$qs['date2']}',
				`item2`			='{$qs['item2']}',
				`standard2`		='{$qs['standard2']}',
				`quantity2`		='{$qs['quantity2']}',
				`price2`		='{$qs['price2']}',
				`supply_money2`	='{$qs['supply_money2']}',
				`tax_money2`	='{$qs['tax_money2']}',
				`etc2`			='{$qs['etc2']}',
				`date3`			='{$qs['date3']}',
				`item3`			='{$qs['item3']}',
				`standard3`		='{$qs['standard3']}',
				`quantity3`		='{$qs['quantity3']}',
				`price3`		='{$qs['price3']}',
				`supply_money3`	='{$qs['supply_money3']}',
				`tax_money3`	='{$qs['tax_money3']}',
				`etc3`			='{$qs['etc3']}',
				`date4`			='{$qs['date4']}',
				`item4`			='{$qs['item4']}',
				`standard4`		='{$qs['standard4']}',
				`quantity4`		='{$qs['quantity4']}',
				`price4`		='{$qs['price4']}',
				`supply_money4`	='{$qs['supply_money4']}',
				`tax_money4`	='{$qs['tax_money4']}',
				`etc4`			='{$qs['etc4']}',
				`total_supply`	='{$qs['total_supply']}',
				`total_tax`		='{$qs['total_tax']}',
				`total_money`	='{$qs['total_money']}',
				`total_cash`	='{$qs['total_cash']}',
				`total_check`	='{$qs['total_check']}',
				`total_bill`	='{$qs['total_bill']}',
				`total_credit`	='{$qs['total_credit']}',
				`total_space`	='{$qs['total_space']}',
				`total_etc`		='{$qs['total_etc']}',
				`total_gubun`	='{$qs['total_gubun']}',
				`rdate`			='{$qs['rdate']}',
				`status`		='{$qs['status']}'
			WHERE
				{$field}='{$qs[$field]}'
			AND
				 $sql_where 
		";
	db_query($sql);

	return db_count();
} // end func modify_ok

function delete_ok(&$dbinfo,$field,$goto){
	$qs=array(
			"{$field}" =>  "request,trim,notnull=" . urlencode("고유넘버가 넘어오지 않았습니다.")
		);
	// 넘오온값 체크
	$qs=check_value($qs);

	// 해당 데이터 읽기
	$sql_where	= " 1 "; // $sql_where 시작
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE {$field}='{$qs[$field]}' and  $sql_where ";
	if( !$list=db_arrayone($sql) )
		back("해당 데이터가 없습니다");

	// 권한체크
	if(!siteAuth($dbinfo, "priv_delete")){
		if($list['bid'] != $_SESSION['seUid']) back("삭제 권한이 없습니다");
	}

	db_query("DELETE FROM {$table} WHERE {$field}='{$qs[$field]}' AND  $sql_where ");

	return db_count();
} // end func delete_ok; ?>
