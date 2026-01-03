<?php
//=======================================================
// 설	명 : 주문내역-세부조회
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
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 해당 게시물 불러들임
$sql_where = " bid='{$_SESSION['seUid']}' and num='{$_GET['num']}' and re='' "; // init
$sql = "SELECT rdate, bid, num, totalprice FROM {$table_payment} WHERE $sql_where ORDER BY num DESC";
$result=db_query($sql);
if(!$count_payment=db_count()){
	back("해당요청의 주문(청구) 내역이 없습니다");
}
else {
	$totalprice	= 0; // init
	for($i=0;$i<$count_payment;$i++){
		$list = db_array($result);

		/////////////////////////
		// 주문 세부 리스트 처리
		$sql = "SELECT * from {$table_payment} where bid='{$list['bid']}' and num='{$list['num']}' order by re ";
		$rs_cell = db_query($sql);
		while($cell = db_array($rs_cell)){
			// 업로드파일 처리
			userUnserializeUpfile($cell,"/smember/payment/paymentdownload.php");

			// 쇼핑몰이라면
			$tpl->drop_var('href.delete');
			$href['shop'] = '';		
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
				elseif($cell['orderdb'] == '배송료'){
					$dlist = $cell; // 배송비 데이터 $dlist로 저장
				}
				// 상품정보 가져오기
				elseif($cell['orderdb'] != ''){
					$sql = "select uid,brand,price,code,publiccode from {$SITE['th']}shop2_{$cell['orderdb']} where uid='{$cell['orderuid']}'";
					$cell['shop']=db_arrayone($sql);

					// URL Link..
					$href['shop'] = "/sshop2/read.php?db={$cell['orderdb']}&uid={$cell['orderuid']}";
				}
				else $href['shop'] = '';
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
			
			$tpl->set_var('href.shop',$href['shop']);
			$tpl->set_var('href.status',$href['status']);
			$tpl->set_var('list',$cell);
			$tpl->set_var('list.rdate_date',date("Y-m-d [H:i:s]",$cell['rdate']));
			$tpl->set_var('list.price',number_format($cell['price']));

			$tpl->process('CELL','cell',TPL_OPTIONAL|TPL_APPEND);
			$tpl->drop_var('list',$cell);
		}
		/////////////////////////
		// 주문 취소 가능한지
		if($sw_delete) $href['delete']	= "ok.php?mode=delete&num={$list['num']}";
		else $href['delete']='';
		$sw_delete	= 0; // 다시 초기화

		// 고객이 상태를 "OK"로 만들고 포인트 충전되도록
		if($sw_status_ok) $href['status_ok'] = "ok.php?mode=status_ok&num={$list['num']}";
		else $href['status_ok'] = '';
		$sw_status_ok	= 0; // 다시 초기화
		
		// 현금영수증 발생 가능한지
		if( (empty($list['taxcash_status']) or $list['taxcash_status'] == '발행요청')
				and !in_array($list['bank'],array('신용카드','계좌이체','휴대폰','포인트')) ){
			$form_taxcash =' action="ok.php"'.' method="post">';
			$form_taxcash .= href_qs('mode=taxcashmodify&num='.$list['num'],'mode=',1);
			$form_taxcash = substr($form_taxcash,0,-1);
		}
		else $form_taxcash = "";
		
		// URL Link...
		$href['delete']	= "ok.php?mode=delete&num={$list['num']}";
		$href['companytax'] = "comtax.php?num={$list['num']}&rdate={$list['rdate']}";

		$tpl->set_var('href.delete',$href['delete']);
		$tpl->set_var('href.companytax',$href['companytax']);
		$tpl->set_var('href.status_ok'	,$href['status_ok']);
		$tpl->set_var('form_taxcash'	,$form_taxcash);

		$tpl->set_var('list',$list);
		$tpl->set_var('list.totalprice',number_format($list['totalprice']));
		$tpl->set_var('list.rdate_date',date("Y-m-d [H:i:s]",$list['rdate']));
		$tpl->set_var('list.idate_date',date("Y-m-d [H:i:s]",$list['idate']));		
		//$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);

		// 총 가격
		$totalprice += $list['totalprice'];
	} // end for
} // end if . . else ..
// 템플릿 마무리 할당
$tpl->set_var('href'		,$href);
$tpl->set_var('totalprice'	,number_format($totalprice));
$tpl->set_var('dlist'		,$dlist); // 배송관련 정보
$tpl->set_var('dlist.idate_date',date("Y-m-d [H:i:s]",$dlist['idate']));

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
