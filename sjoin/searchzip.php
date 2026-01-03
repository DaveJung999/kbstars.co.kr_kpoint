<?
//=======================================================
// 설  명 : 우편번호 찾기(searchzip.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
//=======================================================
$HEADER = array();
$HEADER['priv']		= ''; // 인증유무 (비회원,회원,운영자,서버관리자)
$HEADER['usedb2']	= 1; // DB 커넥션 사용
$HEADER['useSkin']	= 1; // 템플릿 사용

require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once($thisPath.'config.php');	// $dbinfo 가져오기
	
	$dbinfo['table'] = $SITE['th'] . 'postcode';
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file('skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate('skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

if($_REQUEST['mode']=='check' and $region) {
	$result = db_query("SELECT * FROM {$dbinfo['table']} WHERE region2 like '%$region%'");
	if($total = db_count()) { 
		for($i=0; $i<$total; $i++) {
			$zip = explode('-', db_result($result, $i, 'postcode'));
			$tpl->set_var('zip1',$zip[0]);
			$tpl->set_var('zip2',$zip[1]);
			$tpl->set_var('add1', db_result($result, $i, 'region1'));
			$tpl->set_var('add2', db_result($result, $i, 'region2'));
			$tpl->set_var('add3', db_result($result, $i, 'region3'));

			$tpl->process('RESULT','resultzip',TPL_APPEND);
		}
	}
	else {
		$tpl->process('RESULT','nozip');
	} // end if
}

// 템플릿 마무리 할당
$tpl->set_var('formname'	,$_REQUEST['formname']?$_REQUEST['formname']:'join');
$tpl->set_var('inputzip'	,$_REQUEST['inputzip']?$_REQUEST['inputzip']:'zip');
$tpl->set_var('inputaddress',$_REQUEST['inputaddress']?$_REQUEST['inputaddress']:'address');
$form_default = " action='{$_SERVER['PHP_SELF']}' method='post'>";
$form_default .= substr(href_qs("mode=check&formname={$_REQUEST['formname']}&inputzip={$_REQUEST['inputzip']}&inputaddress={$_REQUEST['inputaddress']}",'mode=',1),0,-1);
$tpl->set_var('form_default'	,$form_default);

// 마무리
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));	
?>