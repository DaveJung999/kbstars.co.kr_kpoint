<?php
//=======================================================
// 설	명 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/13
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/08/13 박선민 마지막 수정
//=======================================================
$HEADER=array(
		'usedb2' =>  1,
		'useSkin' =>  1, // 템플릿 사용
	);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$thisPath		= dirname(__FILE__);
	$thisUrl		= "."; // 마지막 "/"이 빠져야함
	include_once("{$thisPath}/config.php");	// $dbinfo 가져오기
	
	$table_payment		= $SITE['th'] . "payment";	// 지불 테이블
	$table_companyinfo	= $SITE['th'] . "companyinfo";	// 회사정보테이블
	$table_companytax	= $SITE['th'] . "companytax";

	// 넘어온값 체크
	if(!$_GET['c_num']) back_close('중요한 값이 넘어오지 않았습니다.');
		
	// 세금계산서가 발행되어 있는지 체크
	$sql = "SELECT * from {$table_companytax} where bid='{$_SESSION['seUid']}' and paymentrdate='{$_GET['rdate']}'";
	if(db_arrayone($sql)) back('세금계산서가 이미 발행되어있습니다.','comtax.php');
	
	// 회사 정보가 등록되어 있는지 체크
	$sql = "SELECT * from {$table_companyinfo} where c_num ='{$_GET['c_num']}'";
	$tocomp = db_arrayone($sql) or back('등록되어 있는 회사 정보가 없습니다.\\n먼저 회사정보를 입력하여야 합니다.');
	
	// - 회사 정보 상태가 OK이여야, 세금계산서 발행이 가능함.
	if($tocomp['status'] != 'OK') back("회사정보 상태가 '{$tocomp['status']}'이여서 세금계산서 발행이 되지 않습니다.\\n");

	// 세금계산서 정보 확정
	$sql = "SELECT * from {$table_payment} where bid='{$_SESSION['seUid']}' and rdate='{$_GET['rdate']}' order by ordertable";
	$rs_payment = db_query($sql);
	if(!$total = db_count($rs_payment)) back_close('세금계산서 발행할 정보가 없습니다 . 확인 바랍니다.');
	
	// 초기화
	$tax			= array();
	$total_money	= 0; // 총 공급가액
	$total_tax		= 0; // 총 세금
	$lasttax_supply	= 0; // 5건이상 공급가액 합계
	$lasttax_tax	= 0; // 5건이상 세금 합계
	
	for($i=1;$i<=$total;$i++) { // $i를 1부터 시작함
		$list = db_array($rs_payment);

		// 세금계산서는 내역일 5건만 넣기에, 
		// 5건이 넘으면 "상품명 외"로 포함하여 합계액을 넣음
		if($total>4 and $i>=4){
			if($total == $i) { // 마지막건이니
				// $tax 함수에 넣음
				$i = 4;
				$tax["date{$i}"]		= date('m/d',$list['rdate']);
				$tax["item{$i}"]		= $list['title'] . '외'.($total-4).'건';
				$tax["standard{$i}"]	= '';
				$tax["quantity{$i}"]	= '';
				$tax["price{$i}"]		= ''; // 단가인데 넣지 않음
				$tax["supply_money{$i}"]	= $lasttax_supply;
				$tax["tax_money{$i}"]	= $lasttax_tax;
				$tax["etc{$i}"]		= $list['bank'];
				
				$total_supply	+= $tax["supply_money{$i}"]; // 총 공급가액
				$total_tax		+= $tax["tax_money{$i}"]; // 총 세금				
				$tax["supply_money{$i}"]	= number_format($tax["supply_money{$i}"]);
				$tax["tax_money{$i}"]		= number_format($tax["tax_money{$i}"]);
				$i = $total;
			} else {
				$lasttax_supply += round($list['price']*10/11); // 반올림
				$lasttax_tax	+= $list['price'] - round($list['price']*10/11);
			}
			continue;
		}
		
		// $tax 함수에 넣음
		$tax["date{$i}"]		= date('m/d',$list['rdate']);
		$tax["item{$i}"]		= $list['title'];
		$tax["standard{$i}"]	= $list['options'];
		$tax["quantity{$i}"]	= $list['quantity'] ?	$list['quantity']:1;
		$tax["price{$i}"]		= ''; // 단가인데 넣지 않음
		$tax["supply_money{$i}"]	= round($list['price']*10/11); // 반올림
		$tax["tax_money{$i}"]	= $list['price'] - $tax["supply_money{$i}"];
		$tax["etc{$i}"]		= $list['bank'];
		
		$total_supply	+= $tax["supply_money{$i}"]; // 총 공급가액
		$total_tax		+= $tax["tax_money{$i}"]; // 총 세금
		$tax["supply_money{$i}"]	= number_format($tax["supply_money{$i}"]);
		$tax["tax_money{$i}"]		= number_format($tax["tax_money{$i}"]);
	} // end for
	// $tax 마무리
	$tax['total_supply']	= number_format($total_supply);
	$tax['total_tax']		= number_format($total_tax);
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// URL Link..
$href['confirm'] = "comok.php?mode=comtax_confirm&c_num={$_GET['c_num']}&rdate={$_GET['rdate']}";

// 템플릿 마무리 할당
$tpl->set_var('href'	,$href);
$tpl->set_var('tax'		,$tax); // 세금계산서 정보
$tpl->set_var('to'		,$tocomp);
$form_default = " method='get' action='{$thisUrl}/comok.php'>";
$form_default .= href_qs("mode=comtax_confirm","mode=",1);
$form_default = substr($form_default,0,-1);
$tpl->set_var("form_default",	$form_default);

// 마무리
$val="\\1{$thisUrl}/skin/{$dbinfo['skin']}/images/";
echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html', TPL_OPTIONAL)); 
?>
