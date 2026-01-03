<?php
//=======================================================
// 설	명 : 주문내역-세부 정보 수정
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/03/17
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/03/17 박선민 마지막 수정
// 24/08/12 Gemini	PHP 7.x, MariaDB 호환성 업데이트 
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
	$num_get = $_GET['num'] ?? 0;

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 해당 게시물 불러들임
$num_safe = (int)$num_get;
$sql = "SELECT * FROM `{$table}` WHERE num='{$num_safe}' ORDER BY re";
$result = db_query($sql);
$count_payment = $result ? db_count($result) : 0;

if(!$count_payment) {
	back("해당요청의 주문(청구) 내역이 없습니다");
}
else {
	$totalprice = 0; // init
	$re_info = [];
	$list = null; // 마지막 루프의 list를 저장하기 위해 초기화

	while($list_item = db_array($result)) {
		// 업로드파일 처리
		userUnserializeUpfile($list_item,"/smember/payment/paymentdownload.php");

		// 쇼핑몰이라면
		$href['shopread'] = ''; // init
		$tpl->drop_var('href.delete');		
		
		if(empty($list_item['re'])){
			$re_info = [
				'memo'		 =>	$list_item['memo'] ?? '',
				're_invoice' => $list_item['re_invoice'] ?? '',
				're_name'	 =>	$list_item['re_name'] ?? '',
				're_tel'	 => $list_item['re_tel'] ?? '',
				're_zip'	 => $list_item['re_zip'] ?? '',
				're_address' => $list_item['re_address'] ?? '',
				're_email'	 =>	$list_item['re_email'] ?? '',
				're_memo'	 =>	$list_item['re_memo'] ?? '',
				'uid'		 =>	$list_item['uid'] ?? 0
			];
		}
		
		$totalprice += $list_item['price'] ?? 0;
		
		//현금영수증 발급 관련 자료 받아오기
		if(isset($list_item['taxcash_name']) && (isset($list_item['taxcash_hp']) || isset($list_item['taxcash_num']))){
			$list_item['taxcash_regi'] = "regi";
		}
		
		$bid_safe = (int)($list_item['bid'] ?? 0);
		$sql_logon = "SELECT * FROM {$table_logon} WHERE uid='{$bid_safe}'";
		$result_logon = db_query($sql_logon);
		$logon = $result_logon ? db_array($result_logon) : null;
		
		$tpl->set_var('logon'		,$logon);

		// URL Link..
		$href['uidmodify'] = "inquirymodify_write.php?mode=uidmodify&uid=".($list_item['uid'] ?? 0);
		$href['uiddelete']	= "paymentok.php?mode=uiddelete&uid=".($list_item['uid'] ?? 0)."&rdate=".($list_item['rdate'] ?? 0)."&bid=".($list_item['bid'] ?? 0);

		if(($list_item['paid'] ?? 'N')=='N') $list_item['paid']='';
		$list_item['idate'] = isset($list_item['idate']) ? $list_item['idate'] : time();
		
		$tpl->set_var('href.uidmodify',$href['uidmodify']);
		$tpl->set_var('href.uiddelete',$href['uiddelete']);

		$tpl->set_var('list',$list_item);
		$tpl->set_var('re_info',$re_info);
		
		$tpl->set_var('list.rdate_date',date("y-m-d H:i:s",($list_item['rdate'] ?? time())));
		$tpl->set_var('list.idate_date',date("y-m-d H:i:s",($list_item['idate'] ?? time())));
		$tpl->set_var('list.price',number_format($list_item['price'] ?? 0));

		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);

		if(is_array($list_item['shop'] ?? null)) {
			foreach($list_item['shop'] as $key => $value) {
				$tpl->drop_var("list.shop.{$key}");
			}
			$tpl->drop_var("href.shop");
		}
		$list = $list_item; // 마지막 루프의 데이터를 저장
	} // end while
	db_free($result);
	
	// 할당된 것 삭제
	if(is_array($list)) {
		foreach($list as $key => $value) {
			$tpl->drop_var('list.'.$key);
		}
	}

	$paid_option = userOptionEnumByTable($table,"paid", $list['paid'] ?? '');
	$status_option = userOptionEnumByTable($table,"status", $list['status'] ?? '');
	$taxcash_status_option = userOptionEnumByTable($table,"taxcash_status", $list['taxcash_status'] ?? '');
	$form_rdatemodify = " method='post' action='{$thisUrl}paymentok.php'>";
	$form_rdatemodify .= substr(href_qs("mode=rdatemodify&num={$num_get}","mode=",1),0,-1);

	$form_uidmodify = " method='post' action='{$thisUrl}paymentok.php'>";
	$form_uidmodify .= substr(href_qs("mode=uidmodify&uid=" . ($re_info['uid'] ?? ''),"mode=",1),0,-1);

	$tpl->set_var('paid_option',$paid_option);	
	$tpl->set_var('status_option',$status_option);	
	$tpl->set_var('taxcash_status_option',$taxcash_status_option);	
	$tpl->set_var('list.rdate_date',date("y-m-d H:i:s",($list['rdate'] ?? time())));
	$tpl->set_var('list.totalprice',number_format($totalprice));
} // end if .. else ..

/////////
// 템플릿 할당
$tpl->set_var("mode_" . ($_GET['mode'] ?? ''),true); // For use <opt name="mode_???">

$tpl->set_var('href',$href ?? []);
$tpl->set_var('form_rdatemodify',$form_rdatemodify ?? '');
$tpl->set_var('form_uidmodify',$form_uidmodify ?? '');

// 마무리
$replacement = '$1' . $thisUrl . 'skin/' . ($dbinfo['skin'] ?? 'basic') . '/images/';
$pattern = '/([\'"])images\//';
echo preg_replace($pattern, $replacement, $tpl->process('', 'html', TPL_OPTIONAL));

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function userOptionEnumByTable($table, $argField, $data) {
	
	$safe_table = db_escape($table);
	$table_def = db_query("SHOW FIELDS FROM {$safe_table}");
	if(!$table_def) return "";
	
	$return = "";

	while($row_table_def = db_array($table_def)) {
		$field = $row_table_def['Field'];

		if($field != $argField) continue;

		$row_table_def['True_Type'] = preg_replace('/\(.*/', '', $row_table_def['Type']);
		if($row_table_def['True_Type']!='enum') break;

		// The value column (depends on type)
		// ----------------
		$enum		= str_replace('enum(', '', $row_table_def['Type']);
		$enum		= preg_replace('/\)$/', '', $enum);
		$enum		= explode('\',\'', substr($enum, 1, -1));
		
		// show dropdown or radio depend on length
		foreach ($enum as $enum_atom) {
			// Removes automatic MySQL escape format
			$enum_atom = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $enum_atom));
			$return .= '<option value="' . htmlspecialchars($enum_atom,ENT_QUOTES) . '"';
			if ($data == $enum_atom
				|| ($data == '' && ($row_table_def['Null'] ?? 'YES') != 'YES'
					&& isset($row_table_def['Default']) && $enum_atom == ($row_table_def['Default'] ?? ''))) {
				$return .= ' selected="selected"';
			}
			$return .= '>' . htmlspecialchars($enum_atom) . '</option>' . "\n";
		} // end for
	} // end while

	db_free($table_def);

	return $return;
} // end function

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