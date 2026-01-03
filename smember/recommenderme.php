<?php
//=======================================================
// 설	명 : 추천인 리스트(recommenderme.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/09/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 02/09/03 박선민 마지막 수정
//=======================================================
$HEADER=array(
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		'useSkin' =>  1, // 템플릿 사용
		'html_echo' => 2, // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])

	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table_logon	= $SITE['th'] . "logon";
	$table_userinfo	= $SITE['th'] . "userinfo";

	$dbinfo['skin']	= "2015_d12";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 관리자라면 $_SERVER['PHP_SELF']?userid=?? 즉, 해당 유저아이디의 추천 리스트를 볼 수 있게함
$_GET['userid'] = ($_SESSION["class"] == "root" and $_GET['userid']) ? $_GET['userid'] : $_SESSION['seUserid'];

$rs_recmder		= db_query("SELECT * from {$table_userinfo} WHERE recommender='{$_GET['userid']}' ORDER BY rdate DESC");

if(!$total=db_count()) { // 게시물이 없다면
		$tpl->process('LIST', 'nolist');
}
else {
	for($i=0;$i<$total;$i++){
		$list=db_array($rs_recmder);

		$list_logon		= db_array(db_query("SELECT * from {$table_logon} where userid='{$list['recommender']}'"));

		$list['num']		= $i+1;
		$list['rdate']	= date('Y-m-d',$list['rdate']);
		$list['userid']	= $list_logon['userid'];
		$list['name']		= $list_logon['name'];
		$list['email']	= $list_logon['email'];

		$tpl->set_var('list', $list);

		$tpl->process('LIST', 'list', TPL_APPEND);
	} // end for
} // end if

// 마무리 할당
$tpl->set_var('site', $SITE);
$tpl->set_var('seUserid',$_SESSION['seUserid']);
$tpl->set_var('seName',$_SESSION['seName']);

// 마무리
$val="\\1skin/{$dbinfo['skin']}/images/";
echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); ?>
