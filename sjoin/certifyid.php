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

	// 넘어온 값 체크
	if(!$_GET['userid']) back_close('userid값이 넘어오지 않았습니다.');

	// 등록을 제한하는 아이디 리스트
	$nouserid = array( 
		'root','bin','daemon','adm','lp','sync','shutdown','halt','mail','news',
		'uucp','operator','games','gopher','ftp','nobody','vcsa','mailnull','rpm',
		'rpc','xfs','rpcuser','nfsnobody','nscd','ident','radvd','named','pcap',
		'mysql','postgres','oracle','dba','sa','administrator','master','webmaster',
		'manager','operator','admin','sysadmin','test','guest','anonymous','sysop',
		'moderator','www','temp','tmp','null','cs');

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================

// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file('skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate('skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$rs_logon = db_query("SELECT uid FROM $table_logon WHERE userid='{$_GET['userid']}'");
if(db_count()){
	$tpl->set_var('MSG','닫기');
	$tpl->process('RESULT','userid_use');
}
else { // DB에 등록되지 않았다면
	if( in_array($userid,$nouserid) ) { // 등록을 제한하는 아이디라면
		$tpl->set_var('MSG','닫기');
		$tpl->process('RESULT','userid_use');
	}
	else {
		$tpl->set_var('MSG','신청');
		$tpl->process('RESULT','userid_nouse');
	} // end if
} // end if

// 마무리
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));	
?>