<?php
//=======================================================
// 설	명 : 주문 내역 조회(/smember/payment/inquiry.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/04/05
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/04/05 박선민 마지막 수정
//=======================================================
$HEADER=array(
		'priv' => '비회원,회원', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용
		'useSkin' => 1, // 템플릿 사용
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once("{$thisPath}/config.php");	// $dbinfo 가져오기
	$table_payment	= $SITE['th'] . "payment";	// 지불 테이블

	// URL Link..
	$href['inquiry']	= "inquiry.php";
	$href['payment']	= "payment.php";

	// startdate와 enddate가 없다면
	if($_GET['startdate'] == ""){
		$_GET['beforeday'] = intval($_GET['beforeday']);
		if(!$_GET['beforeday']) $_GET['beforeday']=30;
		$_GET['startdate']=date("Y-m-d",time()-3600*24*$_GET['beforeday']); // 한달전
	}
	$starttime = strtotime($_GET['startdate']);

	if($_GET['enddate'] == ""){
		$_GET['enddate']=date("Y-m-d");
	}
	$endtime = strtotime($_GET['enddate'])+3600*24-1;
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 해당 게시물 불러들임
$sql_where = " bid='{$_SESSION['seUid']}' and rdate>={$starttime} and rdate <={$endtime} and re=''"; // init
if($_GET['status']) $sql_where .= " and status='{$_GET['status']}' ";
$sql = "SELECT rdate, bid, num, totalprice FROM {$table_payment} WHERE $sql_where ORDER BY num DESC";
$result=db_query($sql);
if(!$count_payment=db_count()){
	$tpl->process('LIST','nolist');
}
else {
	$sw_delete	= 0; // init
	$sw_bonus	= 0; // init	
	$sw_status_ok = 0; // init
	for($i=0;$i<$count_payment;$i++){
		$list = db_array($result);

		/////////////////////////
		// 주문 세부 리스트 처리
		$sql = "SELECT * from {$table_payment} where num='{$list['num']}' and bid='{$list['bid']}' order by re ";
		$rs_cell = db_query($sql);
		while($cell = db_array($rs_cell)){
			// 업로드파일 처리
			userUnserializeUpfile($cell,"/smember/payment/paymentdownload.php");

			// 쇼핑몰이라면
			$href['shopread'] = '';
			$tpl->drop_var('href.delete');			
			if($cell['ordertype'] == 'shop2'){
				// 만약 쿠폰과 적립금 사용한 것이라면, 취소 넣음
				if($cell['orderdb'] == "coupon"){
					// URL Link...
					$href['delete']	= "ok.php?mode=cancle_coupon&uid={$cell['uid']}";
					$tpl->set_var('href.delete',$href['delete']);
				}
				elseif($cell['orderdb'] == "account"){
					// URL Link...
					$href['delete']	= "ok.php?mode=cancle_point&uid={$cell['uid']}";
					$tpl->set_var('href.delete',$href['delete']);
				}
				// 상품정보 가져오기
				elseif($cell['orderdb'] != '' and $cell['orderdb'] != '배송료'){
					$sql = "select uid,brand,price,code,publiccode from {$SITE['th']}shop2_{$cell['orderdb']} where uid='{$cell['orderuid']}'";
					//if(db_istable("{$SITE['th']}shop2_{$cell['orderdb']}")) // 테이블이 없는 버그 발생시
						$cell['shop']=db_arrayone($sql);

					// URL Link..
					$href['shopread'] = "/sshop2/read.php?db={$cell['orderdb']}&uid={$cell['orderuid']}";
				}
			}

			// URL Link..
			switch($cell['status']){
				case "입금필요":
					$href['status']	= "./index.php";
					$sw_delete		= 1;
					break;
				case "배송중": // 고객이 상태를 "OK"로 만들고 포인트 충전되도록
					$href['status']	= "";
					$sw_status_ok	= 1;
					break;
				default :
					$href['status']	= "";
			}

			// 배송료 없다면
			if($cell['re'] == '' and $cell['price'] == 0) continue;
			
			$tpl->set_var('href.status',$href['status']);
			$tpl->set_var('href.shopread',$href['shopread']);
			$tpl->set_var('list',$cell);
			$tpl->set_var('list.rdate_date',date("Y-m-d [H:i:s]",$cell['rdate']));
			$tpl->set_var('list.price',number_format($cell['price']));

			$tpl->process('CELL','cell',TPL_OPTIONAL|TPL_APPEND);
			$tpl->drop_var('list',$cell);
		}
		/////////////////////////

		// checkbox
		$list['check']="<input type=checkbox name='payment[{$list['num']}]' value=1 checked>";

		// 주문 취소 가능한지
		if($sw_delete) $href['delete']	= "ok.php?mode=delete&num={$list['num']}";
		else $href['delete']='';
		$sw_delete	= 0; // 다시 초기화

		// 고객이 상태를 "OK"로 만들고 포인트 충전되도록
		if($sw_status_ok) $href['status_ok'] = "ok.php?mode=status_ok&num={$list['num']}";
		else $href['status_ok'] = '';
		$sw_status_ok	= 0; // 다시 초기화
		
		// 현금영수증 발생 가능한지
		if( (empty($list['cr_status']) or $list['cr_status'] == '발행요청')
			and !in_array($list['bank'],array('신용카드','계좌이체','휴대폰','포인트')) ){
			$href['taxcash'] = 'taxcash.php';
		}
		else $href['taxcash'] = '';

		$href['inquirydetail'] = "inquirydetail.php?num={$list['num']}";
		
		$tpl->set_var('href.inquirydetail',$href['inquirydetail']);
		$tpl->set_var('href.delete'		,$href['delete']);
		$tpl->set_var('href.status_ok'	,$href['status_ok']);
		$tpl->set_var('href.taxcash'	,$href['taxcash']);

		$tpl->set_var('list',$list);
		$tpl->set_var('list.totalprice',number_format($list['totalprice']));
		$tpl->set_var('list.rdate_date',date("Y-m-d",$list['rdate']));
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);

		// 총 가격
		$totalprice += $list['totalprice'];
		
		$tpl->drop_var('CELL');
	} // end for
} // end if . . else ..

// 템플릿 마무리 할당
$tpl->set_var('get',$_GET);
$tpl->set_var('href',$href);
$tpl->set_var('totalprice'	,number_format($totalprice));
$tpl->set_var('startdate', $_GET['startdate']);
$tpl->set_var('enddate', $_GET['enddate']);

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
function userUnserializeUpfile(&$list,$href) { // 05/03/28
	if(empty($list['upfiles'])) return;
	
	$upfiles=unserialize($list['upfiles']);
	if(!is_array($upfiles)){
		// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
		$upfiles['upfile']['name']=$list['upfiles'];
		$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
	}
	if($href){
		$href .= (strpos($href,'?')) ? '&' : '?';
		foreach($upfiles as $key =>  $value){
			if($value['name'])
				$upfiles[$key]['href']=$href.'uid='.$list['uid'].'&upfile='.$key;
		} // end foreach
	}
	$list['upfiles']=$upfiles;
} 

?>
