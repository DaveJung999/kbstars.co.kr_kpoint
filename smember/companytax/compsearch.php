<?php
//=======================================================
// 설	명 :
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/21
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/08/21 박선민 마지막 수정
// 25/09/17 시스템 php 7, mariadb 10 환경으로 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>  1, // 템플릿 사용
	'useBoard2' => 1, // board2CateInfo(), board2Count()
	'useApp' => 1, // cut_string()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
$urlprefix	= "comp"; // ???list.php ???write.ephp ???ok.php
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

// $dbinfo
include_once("{$thisPath}/config.php");	// $dbinfo 가져오기
$dbinfo['table'] = $SITE['th'] . "companyinfo";

// 넘어온값 체크
if(isset($_GET['sc_c_num'])) $_GET['sc_c_num'] = preg_replace('/[^0-9]/','',$_GET['sc_c_num']);

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

if(($_REQUEST['mode'] ?? null) == "check"){
	$sql = "SELECT * FROM {$dbinfo['table']} WHERE bid='".db_escape($_SESSION['seUid'])."' and c_num like '%".db_escape($_GET['sc_c_num'])."%'";
}
else $sql = "SELECT * FROM {$dbinfo['table']} WHERE bid='".db_escape($_SESSION['seUid'])."'";

$result = db_query($sql);
if($total = db_count($result)) {
	while($list = db_array($result)){
		$tpl->set_var('list',$list);
		$tpl->process("LIST",'list',TPL_APPEND);
	}
}
else {
	$tpl->process("LIST",'nolist');
} // end if

// 템플릿 마무리 할당
$tpl->tie_var('get'	, $_GET);

$form_default = " action='".$_SERVER['PHP_SELF']."' method='post'>";
$form_default .= substr(href_qs("mode=check","",1),0,-1);
$tpl->set_var('form_default'	,$form_default);

// 마무리
$val="\\1{$thisUrl}/skin/".($dbinfo['skin'] ?? 'basic')."/images/";
echo preg_replace("/([\"|'])images\//", "{$val}", $tpl->process('', 'html', TPL_OPTIONAL));
?>