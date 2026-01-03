<?php
//=======================================================
// 설	명 : 주문내역-세부 정보 수정
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/03/17
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/03/17 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin'	 =>	1, // 템플릿 사용
	'useBoard'	 => 1, // 보드관련 함수 포함
	'useApp'	 => 1
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	
	global $conn, $SITE;

	$table			= $SITE['th'] . "payment";	 // 지불 테이블
	$table_logon	= $SITE['th'] . "logon";
	$table_userinfo	= $SITE['th'] . "userinfo";

	$dbinfo	= array(
				'skin'				 =>	"basic",
			'html_type'	 =>	"no"
			);

	//넘어온값 체크
	$mode_get = $_GET['mode'] ?? '';
	$uid_get = $_GET['uid'] ?? 0;

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);
// 해당 게시물 불러들임

$form_uidmodify = '';
$logon = [];

if($mode_get == 'uidmodify'){
	$uid_safe = (int)$uid_get;
	$sql = "SELECT * FROM `{$table}` WHERE uid='{$uid_safe}'";
	
	$result = db_query($sql);
	$list = $result ? db_array($result) : null;

	if(!$list) {
		back("해당요청의 주문(청구) 내역이 없습니다");
	}
	else {
		// 업로드파일 처리
		userUnserializeUpfile($list,"/smember/payment/paymentdownload.php");
		
		//주문자 회원 정보 불러 오기
		$bid_safe = (int)($list['bid'] ?? 0);
		$sql_logon = "SELECT * FROM {$table_logon} WHERE uid = '{$bid_safe}'";
		$result_logon = db_query($sql_logon);
		$logon = $result_logon ? db_array($result_logon) : null;
		
		// URL Link..
		$tpl->set_var('list',$list);
		$tpl->set_var('list.rdate_date',date("y-m-d H:i:s",($list['rdate'] ?? time())));
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);

		if(is_array($list['shop'] ?? null)) {
			foreach($list['shop'] as $key => $value) {
				$tpl->drop_var("list.shop.{$key}");
			}
			$tpl->drop_var("href.shop");
		}

	// 할당된 것 삭제
		if(is_array($list)) {
			foreach($list as $key => $value) {
				$tpl->drop_var('list.'.$key);
			}
		}
	} // end if .. else ..
	if(isset($list)) {
		$form_uidmodify = " method='post' action='{$thisUrl}paymentok.php'>";
		$form_uidmodify .= substr(href_qs("mode=uidmodify&rdate=".($list['rdate'] ?? 0)."&uid=".($list['uid'] ?? 0),"mode=",1),0,-1);
	}
}

// 템플릿 할당
$tpl->set_var('logon', $logon);
$tpl->set_var('form_uidmodify', $form_uidmodify);

// 마무리
$replacement = '$1' . $thisUrl . 'skin/' . ($dbinfo['skin'] ?? 'basic') . '/images/';
$pattern = '/([\'"])images\//';
echo preg_replace($pattern, $replacement, $tpl->process('', 'html', TPL_OPTIONAL));

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function userUnserializeUpfile(&$list, $href) { // 05/03/28
	if(empty($list['upfiles'])) return;
	
	$upfiles = @unserialize($list['upfiles']);
	if(!is_array($upfiles)) {
		// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
		$upfiles = [];
		$upfiles['upfile']['name']=$list['upfiles'];
		$upfiles['upfile']['size']=(int)($list['upfiles_totalsize'] ?? 0);
	}
	if($href) {
		$href .= (strpos($href,'?') === false) ? '?' : '&';
		foreach($upfiles as $key => $value) {
			if(isset($value['name']))
				$upfiles[$key]['href']=$href.'uid='.$list['uid'].'&upfile='.$key;
		} // end foreach
	}
	$list['upfiles']=$upfiles;
}
?>