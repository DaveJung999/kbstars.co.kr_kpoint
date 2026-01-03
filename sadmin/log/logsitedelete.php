<?php
//=======================================================
// 설	명 : log_site 특정 IP 삭제
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/03/16
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/03/16 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'	 => 2, // 인증유무 (0:모두에게 허용)
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
		usedb2	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		useSkin	 =>	1, // 템플릿 사용
		useBoard	 => 1, // 보드관련 함수 포함
	'useApp'	 => 1
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

	$table_log_site = $SITE['th'] . "log_site";	// 지불 테이블

	$dbinfo	= array(
					skin				 =>	"basic",
					html_type	 =>	"no"
				);

	// URL Link..

	// 넘오온값 체크
	// - startdate와 enddate가 없다면
	if($_GET['startdate']=="") {
		$_GET['startdate']=date("Y-m-d",time()-3600*24); // 하루전
	}
	$starttime = strtotime($_GET['startdate']);

	if($_GET['enddate']=="") {
		$_GET['enddate']=date("Y-m-d");
	}
	$endtime = strtotime($_GET['enddate'])+3600*24-1;

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 해당 게시물 불러들임
$sql_where = " rdate>={$starttime} and rdate <={$endtime} "; // init

// 해당 IP 삭제
$count_delete = 0; // init
if($_GET['submit']=="삭제") {
	if(preg_match("/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$/",$_GET['ip']) and $sql_where) { // 해당 ip 삭제
		$sql = "delete from {$table_log_site} where $sql_where and ip='{$_GET['ip']}' ";
		db_query($sql);
		$count_delete=db_count();
	}
}
$tpl->set_var('count_delete',$count_delete);
$tpl->set_var('sql',$sql);

// 해당 기간동안의 IP별 count수
$sql = "SELECT ip, count(*) as count FROM {$table_log_site} WHERE $sql_where group by ip ORDER BY count DESC";
$result=db_query($sql);

if(!$count_payment=db_count()) {
	$tpl->process('LIST','nolist');
}
else {
	for($i=0;$i<$count_payment;$i++) {
		$list = db_array($result);

		$tpl->set_var('list',$list);

		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
	} // end for
} // end if.. else..



// 템플릿 마무리 할당


$tpl->set_var('ip',$_GET['ip']);
$tpl->set_var('myip',$_SERVER['REMOTE_ADDR']); // 자기 ip


$tpl->set_var('startdate', $_GET['startdate']);
$tpl->set_var('enddate', $_GET['enddate']);

// 마무리
$val="\\1{$thisUrl}/skin/{$dbinfo['skin']}/images/";
echo preg_replace("~([\"'])images/~", "\1{$val}", $tpl->process('', 'html', TPL_OPTIONAL));	
?>