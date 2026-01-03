<?
//=======================================================
// 설  명 : 회원 아이디에 이름 출력(/sjoin/certify_namedperuserid.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
//=======================================================
$HEADER=array(
		'priv'		=>'', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2'		=>1, // DB 커넥션 사용
		'useSkin'	=>1, // 템플릿 사용
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once($thisPath.'config.php');	// $dbinfo 가져오기

	// table
	$table_logon	= $SITE['th'].'logon';

	// 넘어온 값 체크
	if(!$_GET['userid']) back_close('userid값이 넘어오지 않았습니다.');

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$tpl->set_var('get.userid'	, $_GET['userid']);

$rs_logon = db_query("SELECT * FROM $table_logon WHERE userid='{$_GET['userid']}'" );
if(db_count($rs_logon)) {
	$tpl->set_var('logon.name',db_result($rs_logon,0,'name'));
	$tpl->process('RESULT','userid_use');
}
else $tpl->process('RESULT','userid_nouse');

// 마무리
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));	
?>