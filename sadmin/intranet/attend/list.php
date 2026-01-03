<?php
//=======================================================
// 설	명 : 심플리스트
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
//
// 25/08/12 Gemini (PHP 7, MariaDB 11 호환성 개선)
//=======================================================
$HEADER = array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useSkin' => 1,
	'useApp' => 1
);
	
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1. 넘어온값 체크
	if(!isset($_GET['year']) or strlen($_GET['year'])!=4 or $_GET['year']<2000) $_GET['year']=date("Y");
	if((int)$_GET['month']<1 or (int)$_GET['month']>12) $_GET['month']=date("m");
	if(strlen($_GET['month'])==1) $_GET['month']="0".$_GET['month'];
	$workmonth=$_GET['year'] . $_GET['month'];

	// 2. 기본 URL QueryString
	$qs_basic	= 'goto='.$_SERVER['PHP_SELF'];

	// 3. $dbinfo 가져오기
	include_once('config.php');
	//$dbinfo = array('skin' => 'basic','priv_list' => '');
	
	// 4. 권한 체크
	if(!privAuth($dbinfo, 'priv_list',1)) back('페이지를 보실 권한이 없습니다.');

	// 5. URL Link...
	$href['list']	= $_SERVER['PHP_SELF'].'?'.href_qs('page=',$qs_basic);
	$href['write']	= 'write.php?' . href_qs('mode=write',$qs_basic);	// 글쓰기

	// 해당 월 데이터 변수에 일괄 저장
	$rs_attend	= db_query("select * from {$dbinfo['table']} where bid='{$_SESSION['seUid']}' and workday>'{$workmonth}00' and workday<{$workmonth}00+100");
	while($row=db_array($rs_attend)) {
		$total['dayhours']	+= $row['dayhours'];
		$total['overhours']	+= $row['overhours'];
		$total['nighthours']	+= $row['nighthours'];
	
		$data_attend[$row['workday']]=$row;
	}
	db_free($rs_attend);
	$total['totalhours'] = $total['dayhours'] + $total['overhours'] + $total['nighthours'];

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';

if( !is_file('skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate('skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);
// 템플릿 기본 할당
$tpl->tie_var('get'				,$_GET); 	// get값으로 넘어온것들
$tpl->tie_var('dbinfo'			,$dbinfo);	// dbinfo 정보 변수
$tpl->tie_var('href'			,$href);	// 게시판 각종 링크
$tpl->set_var('total'			,$total);
// 템플릿 list 블럭
if($workmonth == date("Y") . date("m")) $lastday=date("j"); // 1-31
else $lastday=date("j",mktime (0,0,0,$_GET['month']+1,0,$_GET['year']));
for($i=1;$i<=$lastday;$i++) {
	if(strlen($i)==1) $tmp_day="0".$i;
	else $tmp_day=$i;

	$list = $data_attend["{$workmonth}{$tmp_day}"];
	$list['begintime'] = isset($list['begintime']) ? date("d H:i",$list['begintime']) : "-";
	$list['finishtime'] = isset($list['finishtime']) ? date("d H:i",$list['finishtime']) : "-";
	
	// list.date
	switch( date("w",mktime(0,0,0,$_GET['month'],$i,$_GET['year'])) ) {
		case 0 :
			$tmp = "<font color=red>" . $i . "일 (" . "일" . ")</font>";
			break;
		case 1 :
			$tmp = $i . "일 (" . "월" . ")";
			break;		
		case 2 :		
			$tmp = $i . "일 (" . "화" . ")";
			break;		
		case 3 :		
			$tmp = $i . "일 (" . "수" . ")";
			break;		
		case 4 :		
			$tmp = $i . "일 (" . "목" . ")";
			break;		
		case 5 :		
			$tmp = $i . "일 (" . "금" . ")";
			break;		
		case 6 :		
			$tmp = "<font color=blue>" . $i . "일 (" . "토" . ")</font>";
			break;		
	} // end switch
	$list['date'] = $tmp;
	$list['href']['modify']="finishwork.php?mode=modify&uid={$list['uid']}";

	// 템플릿 할당
	$tpl->set_var('list'		,$list);
	$tpl->set_var('blockloop'	,true);
	$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);	
	$tpl->drop_var('list',$list);	
} // end for
//	템플릿내장값 지우기
$tpl->drop_var('blockloop');
//$tpl->drop_var('list',$list);

// 블럭 : 글쓰기
if(privAuth($dbinfo, 'priv_write')) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$tpl->echoHtml($dbinfo, $SITE);
?>
