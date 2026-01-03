<?php
//=======================================================
// 설	명 : 카테고리 관리리스트(cate.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/05/31
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/05/31 박선민 소스 개선
//=======================================================
$HEADER=array(
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용		
	'useApp'	 => 1,
	'useBoard' => 1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST']);

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
	
	global $conn, $SITE;

$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함
	$prefix 	= 'joininfo';
	$prefixurl 	= 'gjoininfo_';
	
	// table
	$table_logon	= $SITE['th'] . 'logon';
	$table_userinfo	= $SITE['th'] . 'userinfo';
	$table_groupinfo= $SITE['th'] . 'groupinfo';
	$table_joininfo	= $SITE['th'] . 'joininfo';
	$table_joininfo_cate= $SITE['th'].'joininfo_cate';
	$table_payment	= $SITE['th'] . 'payment';
	$table_service	= $SITE['th'] . 'service';
	$table_loguser	= $SITE['th'] . 'log_userinfo';
	$table_log_wtmp	= $SITE['th'] . 'log_wtmp';
	$table_log_lastlog= $SITE['th'] . 'log_lastlog';
	$table_boardinfo= $SITE['th'] . "boardinfo";
	
	// GET 파라미터 안전하게 받기
	$gid_get = $_GET['gid'] ?? '';
	$groupid_get = $_GET['groupid'] ?? '';
	$gsc_column_get = $_GET['gsc_column'] ?? '';
	$gsc_string_get = $_GET['gsc_string'] ?? '';
	$mode = $_GET['mode'] ?? '';
	$cateuid = $_GET['cateuid'] ?? 0;

	// uid=???, hp=???, order=??? 처럼 짧은키워드 검색 지원
	if($gid_get) { $gsc_column_get='groupinfo.uid'; $gsc_string_get=$gid_get; }
	elseif($groupid_get) { $gsc_column_get='groupinfo.groupid'; $gsc_string_get=$groupid_get; }
	else {
		if(!$gsc_column_get) $gsc_column_get='groupinfo.uid';
		if(!$gsc_string_get) $gsc_string_get='%%';
	}
	
	// 기본 URL QueryString
	$qs_basic = href_qs("gsc_column={$gsc_column_get}&gsc_string={$gsc_string_get}",'gsc_column=');
	
	/////////////////////////////////
	// $sql문 결정 (Limit ?,? 부분 제외)
	$sql_table= explode(".", $gsc_column_get);
	if(count($sql_table)!=2) go_url($_SERVER['PHP_SELF']);

	$gsc_string_safe = db_escape($gsc_string_get);
	$sql_where = '';
	if( strpos($gsc_string_get, "%") !== false ) {
		if($gsc_string_get=="%") $gsc_string_safe = "%%";
		$sql_where	= " (`{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` LIKE '{$gsc_string_safe}') ";
	}
	else $sql_where	= " (`{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` = '{$gsc_string_safe}') ";

	$sql = '';
	switch ($sql_table['0']) {
		case "groupinfo" :
			$sql="SELECT *, `{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` as sc_column FROM {$table_groupinfo} WHERE  $sql_where ";
			break;
		case "logon" :
			$sql="SELECT {$table_groupinfo}.*, `{$SITE['th']}{$sql_table['0']}`.`{$sql_table['1']}` as sc_column FROM {$table_logon}, {$table_groupinfo} WHERE $sql_where AND {$table_groupinfo}.uid=`{$SITE['th']}{$sql_table['0']}`.uid";
			break;
		default :
			back("지원하지 않는 서치 옵션을 선택하였습니다. 관리자에게 문의 바랍니다");
	} // end switch
	$rs_gsearch = db_query($sql);
	$count_gsearch = db_count($rs_gsearch);

	// - 결과값이 한명이 아니라면, 서치 페이지로 이동시킴
	if($count_gsearch!=1) go_url("gsearch.php?mode=gjoininfo&gsc_column=".urlencode($gsc_column_get) . "&gsc_string=".urlencode($gsc_string_get));
	
	$groupinfo	= db_array($rs_gsearch);
	$bid_safe = (int)($groupinfo['bid'] ?? 0);
	$sql_logon = "SELECT * FROM {$table_logon} WHERE uid='{$bid_safe}'";
	$result_logon = db_query($sql_logon);
	$groupinfo['logon'] = $result_logon ? db_array($result_logon) : null;
	/////////////////////////////////
	
	include_once("{$thisPath}/config.php"); // $dbinfo 정의
	$dbinfo['table']		= $table_joininfo;
	$dbinfo['table_cate'] = $table_joininfo_cate;
		
	$groupinfo_uid_safe = (int)($groupinfo['uid'] ?? 0);
	$dbinfo['sql_where']	= " gid='{$groupinfo_uid_safe}' "; // init
	$dbinfo['sql_where_cate'] = " gid='{$groupinfo_uid_safe}' "; // init

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$rs_catelist = db_query("SELECT * FROM {$dbinfo['table_cate']} WHERE {$dbinfo['sql_where_cate']} ORDER BY num, re");
$total = db_count($rs_catelist);
$list = null;
for($i=0; $i<$total; $i++){
	$list = db_array($rs_catelist);
	$list['rede']=strlen($list['re'] ?? '');
	if($list['rede'])
		$list['title']= str_repeat("&nbsp;&nbsp;&nbsp;", $list['rede']) . " ↘ " . ($list['title'] ?? '');

	// 해당 카테고리수의 db수 구하기
	$uid_safe = (int)($list['uid'] ?? 0);
	$sql_count = "SELECT count(*) as count FROM {$dbinfo['table']} WHERE {$dbinfo['sql_where']} AND cateuid='{$uid_safe}'";
	$result_count = db_query($sql_count);
	$list['dbcount'] = $result_count ? (int)(db_array($result_count)['count'] ?? 0) : 0;

	// URL Link..
	$href['catereply']	= $_SERVER['PHP_SELF']."?".href_qs("cateuid=".($list['uid'] ?? 0),$qs_basic);
	$href['catemodify']	= $_SERVER['PHP_SELF']."?".href_qs("mode=catemodify&cateuid=".($list['uid'] ?? 0),$qs_basic);
	$href['catesort']		= "{$prefixurl}catesort.php?gid=".($groupinfo['uid'] ?? 0)."&cateuid=".($list['uid'] ?? 0);
	$href['catedelete']	= "{$prefixurl}cateok.php?".href_qs("gid=".($groupinfo['uid'] ?? 0)."&mode=catedelete&cateuid=".($list['uid'] ?? 0),$qs_basic);
	$href["list"]		= "{$prefixurl}.php?".href_qs("cateuid=".($list['uid'] ?? 0),$qs_basic);

	// 템플릿 YESRESULT 값들 입력
	$tpl->set_var('href'		, $href);
	$tpl->set_var('list'		, $list);
	
	$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
	$tpl->set_var('blockloop',true);	
} // end for
if(is_array($list)) {
	foreach($list as $key => $value) $tpl->drop_var("list.{$key}");
	unset($list);
}


/////////////////////////
// 템플릿할당 - 쓰기 부분
$cate_nevi = '';
// - 해당 카테고리 네비케이션 구하기
if($cateuid) {
	$cate_nevi = "<a href='{$_SERVER['PHP_SELF']}?db=" . ($dbinfo['db'] ?? '') . "'>Top</a> > ";
	$cateuid_safe = (int)$cateuid;
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE uid={$cateuid_safe} AND {$dbinfo['sql_where_cate']} LIMIT 1";
	$result_cate = db_query($sql);
	if(	$cateinfo = db_array($result_cate) ) {
		if(strlen($cateinfo['re'] ?? '')) {
			// ( re='' or re='a' or re='ac' ) 만들기, re='aca"일때
			$re_parts = [];
			for($i=0;$i<strlen($cateinfo['re']);$i++) {
					$re_parts[] = "re='" . db_escape(substr($cateinfo['re'],0,$i+1)) ."'";
			}
			$sql_where_cate_tmp = " (re='' OR " . implode(' OR ', $re_parts) . ") ";
			// 	카테고리 네비게이션 만들기
			$rs = db_query("SELECT * FROM {$dbinfo['table_cate']} WHERE {$dbinfo['sql_where_cate']} AND num={$cateinfo['num']} AND {$sql_where_cate_tmp} ORDER BY re");
			while($row=db_array($rs)) {
				$cate_nevi .= ($row['title'] ?? '') . " > ";
			}
		} // end if	
		if($mode == "catemodify")
			$list=$cateinfo;
		else
			$cate_nevi .= ($cateinfo['title'] ?? '') . " > ";
	} // end if
} // end if($cateuid)

$mode = $mode ? $mode : "catewrite";

$form_catewrite = " method='post' action='{$prefixurl}cateok.php'>";
$form_catewrite .= substr(href_qs("mode={$mode}&gid=".($groupinfo['uid'] ?? 0)."&cateuid=".($cateuid ?? 0),'',1),0,-1);
$tpl->set_var('form_catewrite',$form_catewrite);

// - 추가되어 있는 테이블 필드 포함
$list = $list ?? [];
$skip_fields = array('passwd', 'db', 'cateuid', 'num', 're', 'title', 'content', 'upfiles', 'upfiles_totalsize', 'docu_type', 'type', 'priv_level', 'ip', 'hit', 'hitip', 'hitdownload', 'vote', 'voteip', 'rdate');
if($fieldlist = userGetAppendFields($dbinfo['table_cate'], $skip_fields)) {
	foreach($fieldlist as $value) {
		$list[$value]	= htmlspecialchars($list[$value] ?? '',ENT_QUOTES);
	}
}
$tpl->set_var('list',	$list);

$tpl->set_var('cate_nevi', $cate_nevi);
if($mode=='catemodify') $tpl->set_var('is_modify', true);
/////////////////////////


// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			,$dbinfo);// boardinfo 정보 변수
$href['list']	= $_SERVER['PHP_SELF']."?".$qs_basic;
$tpl->set_var('href'			,$href);

// - 회원전체 서치 부분
$tpl->set_var('count_gsearch',$count_gsearch);
$tpl->set_var('gsc_column',$gsc_column_get);
$tpl->set_var('gsc_string',htmlspecialchars(stripslashes($gsc_string_get),ENT_QUOTES));
$tpl->set_var('groupinfo',$groupinfo);
$form_gsearch = " method=get action='{$_SERVER['PHP_SELF']}' ";
$tpl->set_var('form_gsearch',$form_gsearch);

// 마무리
$replacement = '$1' . $thisUrl . '/skin/' . ($dbinfo['skin'] ?? 'basic') . '/images/';
$pattern = '/([\'"])images\//';
echo preg_replace($pattern, $replacement, $tpl->process('', 'html', TPL_OPTIONAL));

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
/**
 * 추가 입력해야할 필드를 가져옵니다. (Modernized version)
 * @param string $table The table name.
 * @param array $skip_fields Fields to exclude.
 * @return array|false List of additional fields or false on failure.
 */
function userGetAppendFields(string $table, array $skip_fields = [])
{
	if (empty($table)) {
		return false;
	}

	$result = db_query("SHOW COLUMNS FROM {$table}");

	if (!$result) {
		return false;
	}

	$fieldlist = [];
	while($row = db_array($result)) {
		if(!in_array($row['Field'], $skip_fields)){
			$fieldlist[] = $row['Field'];
		}
	}
	db_free($result); 

	return isset($fieldlist) ? $fieldlist : false;
}
?>