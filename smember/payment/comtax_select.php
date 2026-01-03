<?php
//=======================================================
// 설	명 : 회원 가입 첫페이지 - 약관동의 (/sjoin/index.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/16
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/08/16 박선민 마지막 수정
// 25/09/17 시스템 php 7, mariadb 10 환경으로 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
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

// table
$table_companyinfo = $SITE['th'] . "companyinfo";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$sql = "SELECT * from {$table_companyinfo} where bid='".db_escape($_SESSION['seUid'])."'";
$rs_list = db_query($sql);
if(!($total=db_count($rs_list))) {	// 게시물이 하나도 없다면...
	$tpl->process('LIST', 'nolist');
}
else{
	while($list = db_array($rs_list)){

		// 사업자등록번호에 "-" 넣기
		if(10 == strlen($list['c_num'])) { // 사업자등록번호이면
			// 사업자등록번호가 정확한지
			$list['c_num_format'] = preg_replace("/^([0-9]{3})([0-9]{2})(.*)$/",'\\1-\\2-\\3',$list['c_num']);
		}
		elseif(13 == strlen($list['c_num'])) { // 주민등록번호이면
			$list['c_num_format'] = preg_replace("/^([0-9]{6})(.*)$/",'\\1-\\2',$list['c_num']);
		} else {
			// 등록번호 오류
		}

		$tpl->set_var('list'			, $list);

		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
	} // end while
}

// URL Link..
$href['write'] = "cominfo_write.php?rdate=".($_GET['rdate'] ?? '');

// 템플릿 마무리 할당
$tpl->tie_var('href'	,$href);
$form_default = " method='get' action='{$thisUrl}/comtax_confirm.php'>";
$form_default .= href_qs("mode=comtax_select&rdate=".($_GET['rdate'] ?? ''),"mode=",1);
$form_default = substr($form_default,0,-1);
$tpl->set_var("form_default",	$form_default);

// 마무리
$val="\\1{$thisUrl}/skin/".($dbinfo['skin'] ?? 'basic')."/images/";
echo preg_replace("/([\"|'])images\//", "{$val}", $tpl->process('', 'html', TPL_OPTIONAL));

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
// 사업자등록번호가 정확한지 체크
// 04/08/20 박선민
function userCheckCompanyNumber($c_num){
	if(!preg_match('/^[0-9]{10}$/',$c_num)) return false;

	$IDtot = 0;
	$IDAdd = "137137135";
	for ($i=0;$i < 9 ; $i++)
	{
		$IDtot = $IDtot + (intval(substr($c_num,$i,1)) * intval(substr($IDAdd,$i,1)));
	}

	$IDtot = $IDtot + ((intval(substr($c_num,8,1))*5)/10);
	$IDtot = 10 - ($IDtot % 10);

	if (substr($c_num,-1) != substr($IDtot,-1))
		return false;
	else return true;
}

?>