<?php
//=======================================================
// 설  명 : 쇼핑몰 카테고리 소트(catesort.php)
// 책임자 : 박선민 (), 검수: 05/01/27
// Project: sitePHPbasic
// ChangeLog
//   DATE   수정인			   수정 내용
// -------- ------ --------------------------------------
// 05/01/27 박선민 마지막 수정
// 25/09/17 시스템 php 7, mariadb 10 환경으로 수정
//=======================================================
$HEADER=array(
	'usedb2'	=>1, // DB 커넥션 사용
	'useSkin'	=>1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$prefix 	= 'cate';
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
// table
$table_dbinfo = $SITE['th'].$prefix.'info';

// boardinfo 테이블 정보 가져와서 $dbinfo로 저장
$sql = "SELECT * from {$table_dbinfo} WHERE db='".db_escape($_GET['db'])."' LIMIT 1";
$dbinfo = db_arrayone($sql) or back('사용하지 않는 즐겨찾기입니다.');
if($dbinfo['enable_cate']!='Y') back('즐겨찾기를 지원하지 않습니다.');

// 인증 체크
if(!privAuth($dbinfo, 'priv_catemanage')) back('이용이 제한되었습니다.(레벨부족)');

// table	
$dbinfo['table_cate'] = $SITE['th'].$prefix;

$sql_where_cate = " db='".db_escape($dbinfo['db'])."' "; // init

$sql		= "SELECT * FROM {$dbinfo['table_cate']} WHERE uid='".db_escape($_GET['cateuid'])."' and {$sql_where_cate}";
$cateinfo	= db_arrayone($sql) or back_close('카테고리가 선택되지 않았습니다.');
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

/////////////////////////
// $dstuid_options 구하기
if(strlen($cateinfo['re'])) {
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} and num='".db_escape($cateinfo['num'])."' and length(re) = length('".db_escape($cateinfo['re'])."') and locate('".db_escape(substr($cateinfo['re'],0,-1)).'\',re)=1 order by re';
} else {
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} and re='' order by num";
}
$rs_menus = db_query($sql);
$count=db_count($rs_menus);
if($count <=1) back_close('순서변경이 필요없습니다.');

// 처음으로, ??다음으로 출력
$dstuid_options = '';
$html_option='<option value="first">처음으로</option>';
while($list_menus = db_array($rs_menus)) {
	if($list_menus['uid']==$cateinfo['uid']) {
		$html_option='';
	} else {
		$dstuid_options .= $html_option;
		$html_option="<option value='{$list_menus['uid']}'>{$list_menus['title']} 다음으로</option>";
	}
} // end while
$dstuid_options .= $html_option;
/////////////////////////
$tpl->set_var('dstuid_options',$dstuid_options);

$form_default = " method='post' action='{$thisUrl}cateok.php'>";
$form_default .= href_qs("mode=catesort&db=".db_escape($dbinfo['db'])."&srcuid=".db_escape($cateinfo['uid']),'mode=',1);
$form_default = substr($form_default,0,-1);
$tpl->set_var('form_default',$form_default);
	
// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			,$dbinfo);// boardinfo 정보 변수
$tpl->set_var('cateinfo'		,$cateinfo);

// 오픈창으로 뜨니깐, 사이트 헤더테일 넣지 않고 바로
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('/([="\'])images\//',$val,$tpl->process('', 'html',TPL_OPTIONAL));
?>