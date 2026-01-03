<?php
//=======================================================
// 설	명 : 인터넷요금결제페이지(/smember/payment/index.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/04/05
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/04/05 박선민 마지막 수정
// 25/09/17 시스템 php 7, mariadb 10 환경으로 수정
//=======================================================
$HEADER=array(
	'priv' => '비회원,회원', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useApp' => 1, // cut_string()
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
$table_logon	= $SITE['th'] . "logon";
$table_ncash	= $SITE['th'] . "payment_ncash";

// URL Link..
$href['inquiry']="inquiry.php";

if(isset($_GET['num'])) { // 주문번호가 넘어왔다면, 입금필요가 아니면 세부 내역으로 이동
	$sql = "select status from {$table_payment} where num='".db_escape($_GET['num'])."' and re='' LIMIT 1";
	$status = db_resultone($sql,0,'status');
	if(isset($status) and $status != '입금필요') go_url('inqurydetail.php?num='.db_escape($_GET['num']));
}

// logon 읽기
$sql = "SELECT * from {$table_logon} where uid='".db_escape($_SESSION['seUid'])."'";
$logon = db_arrayone($sql);
$logon['idnum'] = preg_replace('/[^0-9]/','',$logon['idnum'] ?? '');
$logon['hp'] = preg_replace('/[^0-9]/','',$logon['hp'] ?? '');
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 해당 게시물 불러들임
$sql_where = " bid='".db_escape($_SESSION['seUid'])."' and status='입금필요' and re='' "; // init
$sql = "SELECT rdate, bid, num, totalprice FROM {$table_payment} WHERE $sql_where ORDER BY num DESC";
$result=db_query($sql);

$payment_uid = array();
if(!($count_payment=db_count($result))){
	$tpl->process('LIST','nolist');
}
else {
	$totalprice	= 0; // init
	while($list = db_array($result)){
		
		/////////////////////////
		// 주문 세부 리스트 처리
		$sql = "SELECT * from {$table_payment} where bid='".db_escape($list['bid'])."' and num='".db_escape($list['num'])."' order by re ";
		$rs_cell = db_query($sql);
		while($cell = db_array($rs_cell)){
			// 업로드파일 처리
			//userUnserializeUpfile($cell,"/smember/payment/paymentdownload.php");
			
			
			// 쇼핑몰이라면
			$tpl->drop_var('href.delete');
			if(isset($cell['ordertype']) && $cell['ordertype'] == 'shop2'){
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
				elseif($cell['orderdb'] == "배송료"){
					
				}
				// 상품정보 가져오기
				elseif($cell['orderdb'] != ''){
					$sql = "select uid,brand,price,code,publiccode from {$SITE['th']}shop2_".db_escape($cell['orderdb'])." where uid='".db_escape($cell['orderuid'])."'";
					if(db_istable("{$SITE['th']}shop2_".db_escape($cell['orderdb'])))
						$cell['shop']=db_arrayone($sql);
					// URL Link..
					$href['shop'] = "/sshop2/read.php?db=".db_escape($cell['orderdb'])."&uid=".db_escape($cell['orderuid']);
				}
				else $href['shop'] = '';
			}
			else $href['shop'] = '';
			
			// 배송료 없다면
			if(($cell['re'] ?? '') == '' and ($cell['price'] ?? 0) == 0) continue;
			
			$tpl->set_var('href.shop', $href['shop'] ?? '');
			$tpl->set_var('list',$cell);
			$tpl->set_var('list.rdate_date',date("Y-m-d [H:i:s]",$cell['rdate'] ?? 0));
			$tpl->set_var('list.price',number_format($cell['price'] ?? 0));
			
			$tpl->process('CELL','cell',TPL_OPTIONAL|TPL_APPEND);
			$tpl->drop_var('list',$cell);
		}
		/////////////////////////
		
		// checkbox
		//$list['check']="<input type=checkbox name='payment[{$list['num']}]' value=1 checked>";
		$list['check']="<input type=hidden name='payment[{$list['num']}]' value=1>";
		
		// URL Link...
		$href['delete']	= "ok.php?mode=delete&num={$list['num']}";
		$href['inquirydetail'] = "inquirydetail.php?num={$list['num']}";
		
		$tpl->set_var('href.delete',$href['delete']);
		$tpl->set_var('href.inquirydetail',$href['inquirydetail']);
		$tpl->set_var('list',$list);
		$tpl->set_var('list.totalprice',number_format($list['totalprice'] ?? 0));
		$tpl->set_var('list.rdate_date',date("Y-m-d [H:i:s]",$list['rdate'] ?? 0));
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
		
		// 총 가격
		$totalprice += $list['totalprice'];
		
		$payment_uid[]	= $list['num'];
		$tpl->drop_var('CELL');
	} // end for
} // end if . . else ..

// 카드 결제 준비
if(is_array($payment_uid) and $totalprice){
	$payment_uids=join(":",$payment_uid);
	$contentcategorycode="0";
	$contentcategoryname="기본";
	
	$rs_insert=db_query("insert into {$table_ncash} (`bid`, `userid`, `payment_uid`, `contentcategorycode`, `contentcategoryname`, `primcost`, `contentprice`, `status`, `rdate`, `ip`)
		VALUES ('".db_escape($_SESSION['seUid'])."', '".db_escape($_SESSION['seUserid'])."', '".db_escape($payment_uids)."', '{$contentcategorycode}', '{$contentcategoryname}', '{$totalprice}', '{$totalprice}', '', UNIX_TIMESTAMP(), '{$_SERVER['REMOTE_ADDR']}')");
	if(!($contentcode=db_insert_id()))
		back("지불과정에서 미묘한 문제가 발생하였습니다.\\n안전상 처음부터 다시 시작하시기 바랍니다.");
	
	// URL Link..
	$href['cardreturnurl'] = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER["PHP_SELF"]) . "/moneypayed.php";
	$href['cardsubmit'] = "javascript: makeWin('{$contentcode}', '{$totalprice}', 'ID{$_SESSION['seUserid']}', '{$logon['hp']}', '{$_SESSION['seUid']}','{$_SESSION['seEmail']}','{$href['cardreturnurl']}');";
}
// 템플릿 마무리 할당
$tpl->tie_var('logon'		,$logon);
$tpl->tie_var('href'		,$href);
$tpl->set_var('totalprice'	,number_format($totalprice));

// 무통장입금관련
$tpl->set_var('remitname'	,$_SESSION['seName'] ?? '');
$tpl->set_var('remitdate'	,date('Y-m-d'));

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
			if($value['name'] ?? null)
				$upfiles[$key]['href']=$href.'uid='.$list['uid'].'&upfile='.$key;
		} // end foreach
	}
	$list['upfiles']=$upfiles;
} 

?>