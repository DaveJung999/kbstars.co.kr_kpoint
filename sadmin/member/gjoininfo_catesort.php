<?php
//=======================================================
// 설	명 : 쇼핑몰 카테고리 소트(catesort.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/07
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/07/07 박선민 마지막 수정
//=======================================================
$HEADER=array(
		usedb2	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		useSkin =>	1, // 템플릿 사용
		useBoard => 1, // boardAuth
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
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
	$prefix 	= 'joininfo';
	$prefixurl 	= 'gjoininfo_';

	// 기본 URL QueryString
	$qs_basic		= href_qs("gid={$_REQUEST['gid']}&gsc_column={$_REQUEST['gsc_column']}&gsc_string={$_REQUEST['gsc_string']}",'gsc_column=');

	// table	
	$table_groupinfo = $SITE['th'] . "groupinfo";
	
	// 넘어온값 처리
	$sql= "SELECT * from {$table_groupinfo} where uid='{$_REQUEST['gid']}'";
	$groupinfo = db_arrayone($sql) or back('해당 그룹이 없습니다. 잘못된 요청이십니다.');
		
	// $dbinfo값정의 - 기본 where절
	$dbinfo['table'] = "{$SITE['th']}{$prefix}"; // 테이블이름 가져오기
	$dbinfo['table_cate'] = {$dbinfo['table']} . '_cate';

	$dbinfo['sql_where'] 		= " gid='{$_REQUEST['gid']}' ";
	$dbinfo['sql_where_cate']	= " gid='{$_REQUEST['gid']}' "; 	
	// - ','로 시작하고, case '???' : continue 2; 해야함
	$dbinfo['sql_set']		= ", gid='{$_REQUEST['gid']}' "; 
	$dbinfo['sql_set_cate']	= ", gid='{$_REQUEST['gid']}' "; // ','로 시작해야함
	
	$sql		= "SELECT * FROM {$dbinfo['table_cate']} WHERE uid='{$cateuid}' and {$dbinfo['sql_where_cate']}";
	$cateinfo	= db_arrayone($sql) or back_close("카테고리가 선택되지 않았습니다.");
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

/////////////////////////
// $dstuid_options 구하기
if(strlen($cateinfo['re']))
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$dbinfo['sql_where_cate']} and num='{$cateinfo['num']}' and length(re) = length('{$cateinfo['re']}') and locate('" . substr($cateinfo['re'],0,-1) . "',re)=1 order by re";
else 
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$dbinfo['sql_where_cate']} and re='' order by num";
$rs_menus = db_query($sql);
$count=db_count($rs_menus);
if($count <=1) back_close("순서변경이 필요없습니다.");

// 처음으로, ??다음으로 출력
$dstuid_options = "";
$html_option="<option value='first'>처음으로</option>";
for($i=0; $i<$count; $i++){
	$list_menus=db_array($rs_menus);
	if($list_menus['uid']==$cateinfo['uid'])
		$html_option="";
	elseif($i==$count-1) { // 마지막이면
		$dstuid_options .= $html_option;
		$dstuid_options .= "<option value='{$list_menus['uid']}'>마지막으로</option>";
	}
	else {
		$dstuid_options .= $html_option;
		$html_option="<option value='{$list_menus['uid']}'>{$list_menus['title']} 다음으로</option>";
	}
} // end for
/////////////////////////
$tpl->set_var('dstuid_options',$dstuid_options);

$form_default = " method='post' action='{$prefixurl}cateok.php'> ";
$form_default .= substr(href_qs("mode=catesort&srcuid={$cateinfo['uid']}",$qs_basic,1),0,-1);
$tpl->set_var('form_default',$form_default);
	
// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			,$dbinfo);// boardinfo 정보 변수
$tpl->set_var('cateinfo'		,$cateinfo);

// 오픈창으로 뜨니깐, 사이트 헤더테일 넣지 않고 바로
// 마무리
$val="\\1{$thisUrl}/skin/{$dbinfo['skin']}/images/";
echo preg_replace("~([\"'])images/~", "\1{$val}", $tpl->process('', 'html', TPL_OPTIONAL));
?>