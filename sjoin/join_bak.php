<?php
//=======================================================
// 설  명 : 회원 가입 폼(join.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <? → <?php 변환
//=======================================================
$HEADER=array(
		'private'	=>1, // 브라우저 캐쉬
		'priv'		=>'', // 인증유무 (비회원,회원,운영자,서버관리자)
		'useSkin'	=>1, // 템플릿 사용
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once($thisPath.'config.php');	// $dbinfo 가져오기

	// 보안체크
	if($_REQUEST['priv']=='root') {
		back('요청하신 회원가입은 거절되었습니다. 허락되지 않은 요청입니다');
	}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$tpl = new phemplate('','remove_nonjs');
// 가입 종류에 따른 다른 템플릿 파일 읽음
$list = array();
switch($_REQUEST['priv']) {
	case '회원' :
	case 'person' :
		$tplfile = $thisPath.'skin/'.$dbinfo['skin'].'/join_person.html';
		break;
	case '회사' :
	case 'company':	
		$tplfile = $thisPath.'skin/'.$dbinfo['skin'].'/join_company.html';
		break;
	case '외국인' :
	case 'foreign' :
		$_REQUEST['priv'] = '외국인';
		$list['country_option'] = userGetCountryOption();
		$tplfile = $thisPath.'skin/'.$dbinfo['skin'].'/join_foreign.html';
		break;
	default :
		$tplfile = '';
}
if( is_file($tplfile) ) $tpl->set_file('html',$tplfile);
else back("$_REQUEST['priv'] 회원가입을 받고 있지 않습니다. 확인하여주시기 바랍니다");

// 템플릿 마무리 할당
$form_default = " method='post' action='{$thisUrl}joinok.php'>";
$form_default .= href_qs("priv={$_REQUEST['priv']}",'priv=',1);
$form_default = substr($form_default,0,-1);
$tpl->set_var('form_default',	$form_default);

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);


//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function userGetCountryOption() {
	global $SITE;
	$table = $SITE['th'].'countrycode';
	$strOption = '';
	
	$sql = "select * from $table ";
	$rs = db_query($sql);
	while($list = db_array($rs)) {
		$strOption .= '<option value='.$list['code'].'>'.$list['country'].'</option>\n';
	}
	return $strOption;
}
?>