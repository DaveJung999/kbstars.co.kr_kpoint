<?
//=======================================================
// 설  명 : 회원 아이디 존재 유무 체크(/sjoin/certifyid.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
//=======================================================
$HEADER = array();
$HEADER['priv']		= ''; // 인증유무 (비회원,회원,운영자,서버관리자)
$HEADER['usedb2']	= 1; // DB 커넥션 사용
$HEADER['useCheck']	= 1; // check_idnum
$HEADER['useSkin']	= 1; // 템플릿 사용
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once($thisPath.'config.php');	// $dbinfo 가져오기

	// table
	$table_logon	= $SITE['th'].'logon';
	
	// 주민등록번호 규칙
	$aIdnum = explode('-',$_GET['idnum']);
	if(!check_idnum($aIdnum[0],$aIdnum[1]))
		back_close('잘못된 주민등록번호입니다. 정확하게 입력하여주세요');
	
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================

// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file('skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate('skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$rs_logon = db_query("SELECT uid FROM $table_logon WHERE idnum='{$_GET['idnum']}'");
if(db_count()){
	back_close('주민번호가 이미 사용되고 있습니다.\\n가입 여부를 확인하세요.');
	$tpl->set_var('MSG','닫기');
	$tpl->process('RESULT','idnum_use');
}
else { // DB에 등록되지 않았다면
	back_close('사용 가능한 주민번호입니다.');
	
	$tpl->set_var('MSG','신청');
	$tpl->process('RESULT','idnum_nouse');
} // end if

// 마무리
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));	
?>