<?php
//=======================================================
// 설	명 : 주문조회
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/04/07
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/04/07 박선민 마지막 수정
// 05/04/12 채혜진 109 // 배송료 출력 부분 처리
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useSkin'	 => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table_payment	= $SITE['th'] . "payment";	// 지불 테이블
	$table_logon	= $SITE['th'] . "logon";

	$dbinfo	= array(
					'skin'				 =>	"basic",
				'html_type'	 =>	"no"
				);

	// URL Link..

	// 넘오온값 체크
	// - startdate와 enddate가 없다면
	if($_GET['startdate']=="") {
		$_GET['startdate']=date("Y-m-d",time()-3600*24); // 하루전
	}
	$starttime = strtotime($_GET['startdate']);

	if($_GET['enddate']=="") {
		$_GET['enddate']=date("Y-m-d");
	}
	$endtime = strtotime($_GET['enddate'])+3600*24-1;

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 해당 게시물 불러들임
$sql_where = " rdate>={$starttime} and rdate <={$endtime} and re=''"; // init
if($_GET['status']) $sql_where .= " and status='{$_GET['status']}' ";
$sql = "SELECT * from {$table_payment} WHERE $sql_where ORDER BY num DESC";
$result=db_query($sql);

$list_email	= array(); // email 리스트 모음
$list_hp	= array(); // 휴대폰 리스트 모음
$list_present = array();
if(!$count_payment=db_count($result)) {
	$tpl->process('LIST','nolist');
}
else {
	for($i=0;$i<$count_payment;$i++) {
		$list = db_array($result);

		/////////////////////////
		// 주문 세부 리스트 처리
		$sql = "SELECT * from {$table_payment} where num='{$list['num']}' and bid='{$list['bid']}' order by re ";
		$rs_cell = db_query($sql);
		while($cell = db_array($rs_cell)) {
			// 업로드파일 처리
			userUnserializeUpfile($cell,"/smember/payment/paymentdownload.php");
			$href['shop']	= "/sshop2/read.php?db={$cell['orderdb']}&uid={$cell['uid']}&cateuid={$cell['cateuid']}";
			// 쇼핑몰이라면
			$href['shopread'] = ''; // init
			$tpl->drop_var('href.delete');			
			if($cell['ordertype']=='shop2') {
				// 만약 쿠폰과 적립금 사용한 것이라면, 취소 넣음
				if($cell['orderdb']=="coupon") {
					// URL Link...
					$href['delete']	= "ok.php?mode=cancle_coupon&uid={$cell['uid']}";
					$tpl->set_var('href.delete',$href['delete']);
				}
				elseif($cell['orderdb']=="account") {
					// URL Link...
					$href['delete']	= "ok.php?mode=cancle_point&uid={$cell['uid']}";
					$tpl->set_var('href.delete',$href['delete']);
				}
				// 상품정보 가져오기
				elseif($cell['orderdb']!='' and $cell['orderdb']!='배송료') {
					$sql = "select uid,brand,price,code,publiccode from {$SITE['th']}shop2_{$cell['orderdb']} where uid='{$cell['orderuid']}'";
					//if(db_istable("{$SITE['th']}shop2_{$cell['orderdb']}")) 
						$cell['shop']=db_arrayone($sql);

					// URL Link..
					$cell['shopread'] = "/sshop2/read.php?db={$cell['orderdb']}&uid={$cell['orderuid']}";
				}
			}
			
			// 입금필요이면 결제페이지로 링크 되도록
			/*
			if($cell['status']=='입금필요') $tpl->set_var('href.status','./index.php');
			else $tpl->drop_var('href.status');
			*/
			if($cell['orderdb'] == '배송료'){
				$cell['price'] = $cell['totalprice'];
			}
			
			//관리자가 확인을 유용하게 하기 위해 상품 리스트 입력 
			if( !($cell['orderdb']== "배송료" || $cell['orderdb']== "account")) $list_present['title'] = array_push($list_present ,$cell['title']);	
									
			$tpl->set_var('list',$cell);
			$tpl->set_var('list.rdate_date',date("Y-m-d [H:i:s]",$cell['rdate']));
			$tpl->set_var('list.price',number_format($cell['price']));

			$tpl->process('CELL','cell',TPL_OPTIONAL|TPL_APPEND);
			$tpl->drop_var('list',$cell);
		}
		/////////////////////////
		
		
		$list['check']="<input type=checkbox name='payment[{$list['num']}]' value=1 checked>";
		
		// URL Link..
		$href['inquirydetail']	= "inquirydetail.php?num={$list['num']}";
		$href['rdatemodify'] 		= "inquirymodify.php?num={$list['num']}";
		$href['delete']		= '';
		$href['status_ok']	= '';		
		$href['bonus']		= '';
		switch($cell['status']) {
			case "입금필요":
				$href['delete']	= "payment_ok.php?mode=delete&num={$list['num']}";
				$href['bonus']	= '/sthis/coolbonus/list.php';
				break;
			case "배송중": // 고객이 상태를 "OK"로 만들고 포인트 충전되도록
				$href['status_ok'] = "ok.php?mode=status_ok&num={$list['num']}";
				break;
		}
		//echo $href['shop'];
		$tpl->set_var('href.shop'		,$href['shop']);
		$tpl->set_var('href.delete'		,$href['delete']);
		$tpl->set_var('href.status_ok'	,$href['status_ok']);
		$tpl->set_var('href.bonus'		,$href['bonus']);

		// 해당 회원 정보 가져오기
		$sql = "SELECT * from {$table_logon} where uid='{$list['bid']}'";
		$list['logon']=db_arrayone($sql);

		// 검색된 회원의 메일리스트, 휴대폰리스트 모음
		if($list['logon']['email'] and !in_array($list['logon']['email'],$list_email))
			$list_email[] = $list['logon']['email'];
		$list['logon'][hp]= trim(preg_replace("/[^0-9]/","",$list['logon'][hp]));
		if($list['logon'][hp] and preg_match("/^(010|011|016|017|018|019)[0-9]{7,}$/",$list['logon'][hp]) and !in_array($list['logon'][hp],$list_hp)) 
			$list_hp[]	= $list['logon'][hp];

		//현금영수증 발급 관련 자료 받아오기
		if($list['taxcash_name'] && ($list['taxcash_hp'] || $list['taxcash_num'])){
			$list['taxcash_regi'] = "regi";
		}
		/*
		switch($list['status']) {
			case "입금필요":
				$href['delete']	= "payment_ok.php?mode=delete&num={$list['num']}";
				$href['bonus']	= '/sthis/coolbonus/list.php';
				break;
			case "배송중": // 고객이 상태를 "OK"로 만들고 포인트 충전되도록
				$href['status_ok'] = "ok.php?mode=status_ok&num={$list['num']}";
				break;
		}
		*/
		// 상태변경부분
		// enum('OK', '입금필요', '입금완료', '재고준비', '배송준비', '배송중', '삭제접수', '사은품신청')
		$href['newstatus'] = "paymentok.php?mode=newstatus&num={$list['num']}&status={$list['status']}&newstatus=입금필요";
		$list['status_change'] = ($list['status']=="입금필요") ? " <B>입금필요</B> > " : " <a href='{$href['newstatus']}'>입금필요</a> > ";
		
		$href['newstatus'] = "inquirymodify.php?num={$list['num']}";
		$list['status_change'] .=	($list['status']=="입금완료") ? " <B>입금완료</B> > " : " <a href=\"javascript: window.open( '{$href['newstatus']}', 'openname', 'width=700,height=650,location=no,resizable=1,scrollbars=1,menubars=no,toolbars=no');void(0);\">입금완료</a> > ";
		//$href['newstatus'] = "paymentok.php?mode=newstatus&num={$list['num']}&status={$list['status']}&newstatus=배송요청";
		//$list['status_change'] .=	($list['status']=="배송요청") ? " <B>배송요청</B> > " : " <a href='{$href['newstatus']}'>배송요청</a> > ";
		
		$href['newstatus'] = "inquirymodify.php?num={$list['num']}#uidmodify";
		$list['status_change'] .=	($list['status']=="배송요청") ? " <B>배송요청</B> > " : " <a href=\"javascript: window.open( '{$href['newstatus']}', 'openname', 'width=700,height=650,location=no,resizable=1,scrollbars=1,menubars=no,toolbars=no');void(0);\">배송요청</a> > ";
		
		$href['newstatus'] = "paymentok.php?mode=newstatus&num={$list['num']}&status={$list['status']}&newstatus=재고준비";
		$list['status_change'] .=	($list['status']=="재고준비") ? " <B>재고준비</B> > " : " <a href='{$href['newstatus']}'>재고준비</a> > ";
		
		$href['newstatus'] = "paymentok.php?mode=newstatus&num={$list['num']}&status={$list['status']}&newstatus=배송준비";
		$list['status_change'] .=	($list['status']=="배송준비") ? " <B>배송준비</B> > " : " <a href='{$href['newstatus']}'>배송준비</a> > ";
		
		$href['newstatus'] = "paymentok.php?mode=newstatus&num={$list['num']}&status={$list['status']}&newstatus=배송중";
		$list['status_change'] .=	($list['status']=="배송중") ? " <B>배송중</B> > " : " <a href=\"javascript: window.location='{$href['newstatus']}&invoice='+prompt('배송장번호를 입력하여주세요','');\">배송중</a> > ";
		
		$href['newstatus'] = "paymentok.php?mode=newstatus&num={$list['num']}&status={$list['status']}&newstatus=OK";
		$list['status_change'] .=	($list['status']=="OK") ? " <B>OK</B> " : " <a href='{$href['newstatus']}' onClick=\"javascript: return confirm('적립할 포인트가 있다면 해당 포인트가 적립됩니다.\\n상태를 정말로 OK로 바꾸시겠습니까?');\">OK</a> ";
		
		$href['newstatus'] = "paymentok.php?mode=newstatus&num={$list['num']}&status={$list['status']}&newstatus=삭제접수";
		$list['status_change'] .=	($list['status']=="삭제접수") ? " | <B>삭제접수</B> " : " | <a href='{$href['newstatus']}'>삭제접수</a>";
		
		$href['newstatus'] = "paymentok.php?mode=delete&num={$list['num']}&status={$list['status']}";
		$list['status_change'] .=	($list['status']=="삭제접수") ? " | <a href='{$href['newstatus']}' onClick=\"javascript: return confirm('삭제하시면 복구하실 수 없습니다.\\n정말로 삭제하시겠습니까?');\">완전삭제</a>" : "";
		
		$href['newstatus'] = "inquirydetail.php?num={$list['num']}&bid={$list['bid']}";
		$list['status_change'] .= " | <a href=\"javascript: window.open( '{$href['newstatus']}', 'openname', 'width=700,height=650,location=no,resizable=1,scrollbars=1,menubars=no,toolbars=no');void(0);\">인쇄하기</a>";
		$list['status_change'] .= " | <a href=\"javascript: window.open( '{$href['newstatus']}&skin=young', 'openname', 'width=700,height=650,location=no,resizable=1,scrollbars=1,menubars=no,toolbars=no');void(0);\">인쇄new</a>";		
		unset($href['newstatus']);

		$tpl->set_var('href.inquirydetail'	,$href['inquirydetail']);
		$tpl->set_var('href.rdatemodify'	,$href['rdatemodify']);
		$tpl->set_var('list'				,$list);
		$tpl->set_var('list.check'			,$list_check);
		$tpl->set_var('list.rdate_date'		,date("Y-m-d [H:i:s]",$list['rdate']));
		$tpl->set_var('list.totalprice'		,number_format($list['totalprice']));
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);

		$tpl->drop_var('href.delete');
		$tpl->drop_var('href.bonus');
		$tpl->drop_var('href.inquirydetail');
		$tpl->drop_var('list'	,$list);
	
		$tpl->drop_var('CELL');
	} // end for
} // end if .. else ..

// 템플릿 마무리 할당
$tpl->set_var('href',$href);
$tpl->set_var('startdate', $_GET['startdate']);
$tpl->set_var('enddate', $_GET['enddate']);
$tpl->set_var('status', $_GET['status']);
$tpl->set_var('list_email',implode(",",$list_email));
$tpl->set_var('list_hp',implode(",",$list_hp));
//$tpl->set_var('list_present',implode(",",$list_present));

// 마무리
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));
$list_present = array_count_values($list_present);

foreach($list_present as $key => $value){
	echo "<span class ='menu_title'> {$key} -	{$value} </span><br>"; 
}

//print_r($list_present);

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function userUnserializeUpfile(&$list,$href) { // 05/03/28
	if(empty($list['upfiles'])) return;
	
	$upfiles=unserialize($list['upfiles']);
	if(!is_array($upfiles)) {
		// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
		$upfiles['upfile']['name']=$list['upfiles'];
		$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
	}
	if($href) {
		$href .= (strpos($href,'?')) ? '&' : '?';
		foreach($upfiles as $key => $value) {
			if($value['name'])
				$upfiles[$key]['href']=$href.'uid='.$list['uid'].'&upfile='.$key;
		} // end foreach
	}
	$list['upfiles']=$upfiles;
}
?>