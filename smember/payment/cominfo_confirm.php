<?php
//=======================================================
// 설	명 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/08/19
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/08/19 박선민 마지막 수정
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
	
	// 공급받는자 정보 가져옮
	$sql = "SELECT * from {$table_companyinfo} where uid='{$_GET['cpid']}'";
	$tocomp = db_arrayone($sql) or back_close('요청한 회사 정보가 없습니다.');
	if($tocomp['status'] == 'OK')
		back_close('회사 정보가 이상없습니다.\\n세금계산서 발행이 가능합니다.');
	if($tocomp['status'] != '사업자등록증확인중')
		back_close("회사정보 상태가 {$to_comp['status']}입니다.");
	
	// 공급자정보가져옮
	$sql = "SELECT * from {$table_companyinfo} where uid='1'";
	$tocomp = db_arrayone($sql) or back('세금계산서 발행을 하고 있지 않습니다.');

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
$tpl->set_var('tocomp'	,$tocomp);
$tpl->set_var('fromcomp',$fromcomp);

// 마무리
$val="\\1{$thisUrl}/skin/{$dbinfo['skin']}/images/";
echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html', TPL_OPTIONAL)); 
?>
