<?php
//=======================================================
// 설	명 : 관리페이지 메인
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	  수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
//=======================================================

// $HEADER를 하나의 배열로 만듭니다.
$HEADER = array(
	'priv'		 =>	'운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'	 =>	1, // DB 커넥션 사용
	'useSkin'	 =>	1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 넘오온값 체크
	
	// table
	$table_dbinfo = $SITE['th'].'admininfo';

	// dbinfo
	$sql_dbinfo = "SELECT * from {$table_dbinfo} where bid='{$_SESSION['seUid']}'";
	if(!$dbinfo = db_arrayone($sql_dbinfo)) {
		// 관리자 설정 등록!!
		$sql_insert = "insert into $table_dbinfo set	
							bid='{$_SESSION['seUid']}',
							rdate=UNIX_TIMESTAMP(),
							fdate=UNIX_TIMESTAMP()
							";
		db_query($sql_insert);
		$dbinfo = db_arrayone($sql_dbinfo) or exit('관리자 설정을 가져오지 못하였습니다.');
	}

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
$tpl->tie_var('site'	,$SITE);
$tpl->tie_var('dbinfo'	,$dbinfo);

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
