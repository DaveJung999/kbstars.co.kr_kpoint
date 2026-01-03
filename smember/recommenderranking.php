<?php
//=======================================================
// 설	명 : 추천인 랭킹 리스트(recommenderranking.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/09/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 02/09/03 박선민 마지막 수정
//=======================================================
$HEADER=array(
		'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
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

// Limit로 필요한 게시물만 읽음.
$_GET['limitno'] = $_GET['limitno'] ? $_GET['limitno'] : 0;
$_GET['limitrows'] = $_GET['limitrows'] ? $_GET['limitrows'] : 50;
$rs_recmder	= db_query("SELECT recommender, count(*) as total FROM {$table_userinfo} GROUP BY recommender HAVING recommender<>'' ORDER BY total DESC, rdate DESC LIMIT {$_GET['limitno']}, {$_GET['limitrows']} ");
if(!$total=db_count()) { // 게시물이 없다면
		$tpl->process('LIST', 'nolist');
}
else {
	for($i=0;$i<$total;$i++){
		$list=db_array($rs_recmder);

		$list_logon		= db_array(db_query("SELECT * from {$table_logon} where userid='{$list['recommender']}'"));

		$list['num']		= $_GET['limitno'] + $i + 1;
		$list['userid']	= $list_logon['userid'];
		$list['name']		= $list_logon['name'];

		$tpl->set_var('list', $list);

		$tpl->process('LIST', 'list', TPL_APPEND);
	} // end for
} // end if

// 마무리 할당
$tpl->set_var('site', $SITE);

// 마무리
$val="\\1skin/{$dbinfo['skin']}/images/";
echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); ?>
