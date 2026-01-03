<?php
//=======================================================
// 설  명 : 카테고리 관리리스트(cate.php)
// 책임자 : 박선민 (), 검수:05/01/27
// Project: sitePHPbasic
// ChangeLog
//   DATE   수정인			   수정 내용
// -------- ------ --------------------------------------
// 05/01/27 박선민 소스 개선
// 25/09/17 시스템 php 7, mariadb 10 환경으로 수정
// 25/10/15 Gemini AI	PHP 7.4+ 호환성 보강 및 코드 정리
//=======================================================
$HEADER=array(
	'usedb2'	=>1, // DB 커넥션 사용
	'useApp'	=>1, // cut_string() 		
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
$table_dbinfo = ($SITE['th'] ?? '') . $prefix . 'info';

// boardinfo 테이블 정보 가져와서 $dbinfo로 저장
$db_param_db = isset($_GET['db']) ? db_escape($_GET['db']) : '';
$sql = "SELECT * from {$table_dbinfo} WHERE db='" . $db_param_db . "' LIMIT 1";
$dbinfo = db_arrayone($sql) or back('사용하지 않는 카테고리입니다.');
if(($dbinfo['enable_cate'] ?? '') != 'Y') back('카테고리 기능을 지원하지 않습니다.');

// 인증 체크
if(!privAuth($dbinfo, 'priv_catemanage')) back('이용이 제한되었습니다.(레벨부족)');

// table	
$dbinfo['table_cate'] = ($SITE['th'] ?? '') . $prefix;

$sql_where_cate = " db='". $db_param_db ."' "; // init
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} ORDER BY num, re";
$rs_catelist = db_query($sql);
$total = db_count($rs_catelist);
for($i=0; $i<$total; $i++){
	$list = db_array($rs_catelist);
	$list['rede']=strlen($list['re']);
	
	if(($list['hide'] ?? '') == '1')
		$list['title']= "<font color='#78ABD8'>[감춤] {$list['title']}</font>";
	
	if($list['rede'])
		$list['title']= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $list['rede']) . ' ↘ ' . $list['title'];

	switch ($list['rede']) {
		case 0: $list['tr_color'] = "#D3EDFA"; break;
		case 1: $list['tr_color'] = "#FBE1EC"; break;
		case 2: $list['tr_color'] = "#FDFAC5"; break;
		case 3: $list['tr_color'] = "#fefefe"; break;
		default: $list['tr_color'] = "#ffffff";
	}
	
	// cut_url 만듬
	$list['cut_url'] = cut_string($list['url'],40,1);
	$list['bgcolor'] = "#A1FE71";
	
	// 해당 카테고리수의 db수 구하기
	if(!empty($dbinfo['table']))
		$list['dbcount']=db_resultone("select count(*) as count from {$dbinfo['table']} WHERE cateuid='".db_escape($list['uid'])."'",0,'count');
	
	// URL Link..
	$href['catewrite']	=$_SERVER['PHP_SELF'].'?db='.db_escape($dbinfo['db']);
	$href['catereply']	=$_SERVER['PHP_SELF']."?db=".db_escape($dbinfo['db'])."&cateuid=".db_escape($list['uid']);
	$href['catemodify']	=$_SERVER['PHP_SELF']."?db=".db_escape($dbinfo['db'])."&mode=catemodify&cateuid=".db_escape($list['uid']);
	$href['catesort']	=$thisUrl."catesort.php?db=".db_escape($dbinfo['db'])."&cateuid=".db_escape($list['uid']);
	$href['catedelete']	=$thisUrl."cateok.php?db=".db_escape($dbinfo['db'])."&mode=catedelete&cateuid=".db_escape($list['uid']);
	$href['list']		=$thisUrl."list.php?db=".db_escape($dbinfo['db'])."&cateuid=".db_escape($list['uid']);
	
	// 템플릿 입력
	$tpl->set_var('href' 		, $href);
	$tpl->set_var('list' 		, $list);
	
	$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
	$tpl->set_var('blockloop',true);
} // end for
// 할당템플릿 제거
$tpl->drop_var('list',$list);


/////////////////////////
// 템플릿할당 - 쓰기 부분
// - 해당 카테고리 네비케이션 구하기
$list = array();
$cate_nevi = '';
if(isset($_GET['cateuid'])) {
	$cate_nevi = "<a href='{$_SERVER['PHP_SELF']}?db=".db_escape($dbinfo['db'])."'>Top</a> > ";
	$cateuid = db_escape($_GET['cateuid']);
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE uid=". $cateuid ." and {$sql_where_cate} LIMIT 1";
	if( $cateinfo = db_arrayone($sql) ) {
		if(strlen($cateinfo['re'])) {
			// ( re='' or re='a' or re='ac' ) 만들기, re='aca"일때
			$sql_where_cate_tmp = ' (re="" ';
			for($i=0;$i<strlen($cateinfo['re'])-1;$i++) {
				$sql_where_cate_tmp .= ' or re="' . db_escape(substr($cateinfo['re'],0,$i+1)) .'" ';
			}
			$sql_where_cate_tmp .= ' ) ';
			// 	카테고리 네비게이션 만들기
			$rs = db_query("SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} and num={$cateinfo['num']} and {$sql_where_cate_tmp} order by re");
			while($row=db_array($rs)) {
				$cate_nevi .= $row['title'] . ' > ';
			}
		}
		if(($_GET['mode'] ?? '') == 'catemodify') {
			$list=$cateinfo;
		}else {
			$cate_nevi .= $cateinfo['title'].' > ';
		}
	}
}

if(!isset($_GET['mode']) || ($_GET['mode'] ?? '')!='catemodify') {
	$_GET['mode'] = 'catewrite';
	$list = userGetDefaultFromTable($dbinfo['table_cate']);
	if(!$list['target']) $list['target'] = "mainFrame";
}

$form_catewrite = " method='post' action='{$thisUrl}cateok.php'>";
$form_catewrite .= href_qs("mode={$_GET['mode']}&db=".db_escape($dbinfo['db'])."&cateuid=".db_escape($_GET['cateuid']),'mode=',1);
$form_catewrite = substr($form_catewrite,0,-1);
$tpl->set_var('form_catewrite',$form_catewrite);

// - 추가되어 있는 테이블 필드 포함
$skip_fields = array('uid','bid','cateuid','passwd' , 'db' , 'cateuid' , 'num' , 're' , 'upfiles' , 'upfiles_totalsize' , 'docu_type' , 'type' , 'priv_level' , 'ip' , 'hit' , 'hitip' , 'hitdownload', 'vote' , 'voteip' , 'rdate');
if($fieldlist = userGetAppendFields($dbinfo['table_cate'],$skip_fields)) {
	foreach($fieldlist as $value) {
		if(isset($list[$value])) {
			$list[$value] 	= htmlspecialchars($list[$value],ENT_QUOTES);
		}
	}
}
// 스킨 리스트 가져오기
if(isset($list['skin']))
	$list['skin_option'] = userGetSkinList($list['skin'],$thisPath.'skin');

// 템플릿 마무리 할당
$tpl->set_var('dbinfo' 			,$dbinfo);// boardinfo 정보 변수
$tpl->set_var('list' 			,$list);
$tpl->set_var('cate_nevi' 		,$cate_nevi);
$tpl->set_var('mode_'.($_GET['mode'] ?? '') ,true); // mode_write, mode_modify 값있게

// 마무리
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('/([="\'])images\//',$val,$tpl->process('', 'html',TPL_OPTIONAL));

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
// 추가 입력해야할 필드
function userGetAppendFields($table,$skip_fields='') { // 05/02/03 박선민
	global $SITE;

	if(!is_array($skip_fields) or sizeof($skip_fields)<1)
		$skip_fields = array();
	
	$fieldlist = array();
	$fields = db_query('SHOW COLUMNS FROM `'.db_escape($table).'`');
	if(!$fields) return false;
	
	while($row = db_array($fields)){
		if(!in_array($row['Field'],$skip_fields)) {
			$fieldlist[] = $row['Field'];
		}
	}

	if(sizeof($fieldlist)) return $fieldlist;
	else return false;
}

// 테이블에서 기본값들을 가져오는 함수
// $field값이 있을 경우, 해당 필드 기본값을 string으로 return
// $field값이 없을 경우, 모든 필드의 기본값을 array로 return
function userGetDefaultFromTable($table,$field='') { // 05/02/03 박선민
	$list = array();
	$table_def = db_query('SHOW COLUMNS FROM `'.db_escape($table).'`');
	if(!$table_def) return;

	while($row_table_def = db_array($table_def)) {
		$list[$row_table_def['Field']] = $row_table_def['Default'];
	}
	
	if($field) {
		return isset($list[$field]) ? $list[$field] : null;
	} else {
		return $list;
	}
} // end function

// 스킨 리스트 <option ..>..</option>으로 가져오기
function userGetSkinList($skin,$dir) { // 06/01/17 박선민
	if(!$dir) $dir = dirname(__FILE__) . '/skin';
	if(!is_dir($dir)) return false;
	
	$entries = scandir($dir);
	if($entries === false) return false;
	$rt_str = '';
	foreach ($entries as $entry) {
		if ($entry === '.' || $entry === '..') continue;
		if (substr($entry,0,1) == '.' || substr($entry,0,1) == '_') continue;
		if (is_dir($dir . '/' . $entry)) {
			if($entry == $skin)
				$rt_str .= '<option name="'.$entry.'" selected>'.$entry.'</option>';
			else $rt_str .= '<option name="'.$entry.'">'.$entry.'</option>';
		}
	}
	return $rt_str;
}
?>
