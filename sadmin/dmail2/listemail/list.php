<?php
//=======================================================
// 설	명 : 심플리스트
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/11/27
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/11/27 박선민 마지막 수정
// 25/08/11 Gemini	PHP 7 마이그레이션
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
	'useBoard2' => 1, // board2CateInfo(), board2Count()
	'useApp' => 1, // cut_string()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$urlprefix	= ""; // ???list.php ???write.ephp ???ok.php
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함
	
	// 1 . 넘어온값 체크
	$db = $_REQUEST['db'] ?? '';
	$cateuid = $_REQUEST['cateuid'] ?? '';
	$pern = isset($_GET['pern']) ? (int)$_GET['pern'] : '';
	$sc_column = $_REQUEST['sc_column'] ?? '';
	$sc_string = $_REQUEST['sc_string'] ?? '';
	$page = isset($_GET['page']) ? (int)$_GET['page'] : '';
	$limitno = isset($_GET['limitno']) ? (int)$_GET['limitno'] : '';
	$limitrows = isset($_GET['limitrows']) ? (int)$_GET['limitrows'] : '';
	$cut_length = isset($_GET['cut_length']) ? (int)$_GET['cut_length'] : 0;
	$sort = $_GET['sort'] ?? '';
	
	// 2 . 기본 URL QueryString
	$qs_basic = "mode=&limitno=&limitrows=";
	if(isset($_GET['getinfo']) && $_GET['getinfo'] != "cont")
		$qs_basic .= "&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=";
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화

	// 3 . $dbinfo 가져오기
	include_once("{$thisPath}/config.php");

	// 4 . 권한 체크
	if(!siteAuth($dbinfo, "priv_list",1)) back("이용이 제한되었습니다.(레벨부족)");
	
	//======================
	// 5 . SQL문 where절 정리
	//======================
	$sql_where = ""; // init
	// 서치 게시물만..
	$sc_string_safe = db_escape($sc_string);
	if(trim($sc_string_safe)){
		if($sql_where) $sql_where .= ' and ';
		if(isset($_GET['sc_column'])){
			$safe_column = db_escape($_GET['sc_column']);
			if(in_array($safe_column,array('bid','uid')))
				$sql_where .=" (`{$safe_column}`='{$sc_string_safe}') ";
			else
				$sql_where .=" (`{$safe_column}` like '%{$sc_string_safe}%') ";
		}
		else
			$sql_where .=" ((`userid` like '%{$sc_string_safe}%') or (`title` like '%{$sc_string_safe}%') or (`content` like '%{$sc_string_safe}%')) ";
	}
	if(!$sql_where) $sql_where= " 1 ";
	
	//===========================
	// 6 . SQL문 order by..절 정리
	//===========================
	$sql_orderby = ' 1 '; // 기본값
	if(isset($_GET['sort'])){
		switch($_GET['sort']){
			case 'rdate': $sql_orderby = 'rdate'; break;
			case '!rdate':$sql_orderby = 'rdate DESC'; break;
			default :
				$sql_orderby = isset($dbinfo['orderby']) ? db_escape($dbinfo['orderby']) : ' 1 ';
		}
	} else {
		$sql_orderby = isset($dbinfo['orderby']) ? db_escape($dbinfo['orderby']) : ' 1 ';
	}
	
	// 7 . 페이지 나눔등 각종 카운트 구하기
	$count['total']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE  $sql_where ", 0, "count(*)"); // 전체 게시물 수
	$count=board2Count($count['total'], $page, $dbinfo['pern'], $dbinfo['page_pern']); // 각종 카운트 구하기
	
	// 8 . URL Link...
	$href['listdb']	= "{$_SERVER['PHP_SELF']}?db={$dbinfo['db']}";
	$href['list']	= "{$_SERVER['PHP_SELF']}?db={$dbinfo['db']}&cateuid={$cateuid}";
	if(isset($count['nowpage']) && $count['nowpage'] > 1) { // 처음, 이전 페이지
		$href['firstpage']="{$_SERVER['PHP_SELF']}?" . href_qs("page=1",$qs_basic);
		$href['prevpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']-1),$qs_basic);
	} else {
		$href['firstpage']="javascript: void(0);";
		$href['prevpage']	="javascript: void(0);";
	}
	if(isset($count['nowpage']) && $count['nowpage'] < $count['totalpage']){ // 다음, 마지막 페이지
		$href['nextpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']+1),$qs_basic);
		$href['lastpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=".$count['totalpage'],$qs_basic);
	} else {
		$href['nextpage']	="javascript: void(0);";
		$href['lastpage'] ="javascript: void(0);";
	}
	$href['prevblock']= (isset($count['nowblock']) && $count['nowblock']>1)					? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['firstpage']-1) ,$qs_basic): "javascript: void(0)";// 이전 페이지 블럭
	$href['nextblock']= (isset($count['totalpage']) && $count['totalpage'] > $count['lastpage'])? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['lastpage'] +1),$qs_basic) : "javascript: void(0)";// 다음 페이지 블럭

	$href['write']	= "{$thisUrl}/{$urlprefix}write.php?" . href_qs("mode=write&time=".time(),$qs_basic);	// 글쓰기

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
$skin = isset($dbinfo['skin']) ? $dbinfo['skin'] : 'basic';
if( !is_file("{$thisPath}skin/{$skin}/{$skinfile}") ) $dbinfo['skin']='basic';
$tpl = new phemplate("{$thisPath}skin/{$dbinfo['skin']}"); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);
$tpl->set_var('get'				,$_GET);	// get값으로 넘어온것들
$tpl->set_var('get.sc_string'	,htmlspecialchars(stripslashes($sc_string),ENT_QUOTES));	// 서치 단어

// 추가 필드이름 체크
$fieldlist = array();
$sql = "SHOW COLUMNS FROM `".db_escape($dbinfo['table']) . "`";
$result = db_query($sql);
if($result && db_count($result) > 0){
	while($row = db_array($result)){
		$a_fields = $row['Field'];
		if( !in_array($a_fields, array('uid','email','status','readtime')) ){
			$fieldlist[] = $a_fields;
			$tpl->set_var('field', $a_fields);
			$tpl->process('FIELDNAME','fieldname',TPL_OPTIONAL|TPL_APPEND);
		}
	}
}

// Limit로 필요한 게시물만 읽음.
$limitno	= (int) (isset($limitno) ? $limitno : ($count['firstno'] ?? 0));
$limitrows	= (int) (isset($limitrows) ? $limitrows : ($count['pern'] ?? 0));
$sql = "SELECT * FROM {$dbinfo['table']} WHERE $sql_where ORDER BY {$sql_orderby} LIMIT {$limitno},{$limitrows}";
$rs_list = db_query($sql);

$total = db_count($rs_list);
if($total === 0) {	// 게시물이 하나도 없다면...
	if(isset($_GET['sc_string']) && $_GET['sc_string']) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면. .
		$tpl->process('LIST', 'nolist');
} else {
	for($i=0; $i<$total; $i++){
		$list		= db_array($rs_list);
		$list['no']	= ($count['lastnum'] ?? 0)--;
		$list['rede']	= strlen($list['re'] ?? '');
		//$list['rdate_date']= $list['rdate'] ? date("y/m/d", $list['rdate']) : "";	//	날짜 변환
		$list['cut_title'] = cut_string($list['title'] ?? '', (int)$cut_length); // 제목자름
		
		$list['readtime'] = isset($list['readtime']) && $list['readtime'] ? date("y/m/d", (int)$list['readtime']) : "X";
			
		//	Search 단어 색깔 표시
		if(isset($_GET['sc_string']) && $_GET['sc_string'] and isset($_GET['sc_column']) && $_GET['sc_column']){
			if($_GET['sc_column'] == "title")
				$list['cut_title'] = preg_replace("/(".preg_quote($_GET['sc_string'],'/') . ")/i", "<font color=darkred>\\0</font>",	$list['cut_title']);
			if(isset($list[$_GET['sc_column']])) {
				$list[$_GET['sc_column']]	= preg_replace("/(".preg_quote($_GET['sc_string'],'/') . ")/i", "<font color='darkred'>\\0</font>", $list[$_GET['sc_column']]);
			}
		} elseif(isset($_GET['sc_string']) && $_GET['sc_string']) {
			// 컬럼이 지정되지 않았을 경우
			$list['cut_title'] = preg_replace("/(".preg_quote($_GET['sc_string'],'/') . ")/i", "<font color=darkred>\\0</font>", $list['cut_title']);
			if(isset($list['userid'])) $list['userid'] = preg_replace("/(".preg_quote($_GET['sc_string'],'/') . ")/i", "<font color='darkred'>\\0</font>", $list['userid']);
			if(isset($list['content'])) $list['content'] = preg_replace("/(".preg_quote($_GET['sc_string'],'/') . ")/i", "<font color='darkred'>\\0</font>", $list['content']);
		}

		// 업로드파일 처리
		if(isset($dbinfo['enable_upload']) && $dbinfo['enable_upload'] != 'N' and isset($list['upfiles']) && $list['upfiles']){
			$upfiles=unserialize($list['upfiles']);
			if(!is_array($upfiles)){
				// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name']=isset($list['upfiles']) ? $list['upfiles'] : '';
				$upfiles['upfile']['size']=(int)(isset($list['upfiles_totalsize']) ? $list['upfiles_totalsize'] : 0);
			}
			if(is_array($upfiles)){
				foreach($upfiles as $key =>	$value){
					if(isset($value['name']) && $value['name'])
						$upfiles[$key]['href']="{$thisUrl}/{$urlprefix}download.php?" . href_qs("uid={$list['uid']}&upfile={$key}",$qs_basic);
				} // end foreach
				$list['upfiles']=$upfiles;
			}
			unset($upfiles);
		} // end if 업로드파일 처리

		// URL Link...
		$href['read']		= "{$thisUrl}/{$urlprefix}read.php?" . href_qs("uid={$list['uid']}",$qs_basic);
		$href['download']	= "{$thisUrl}/{$urlprefix}download.php?" . href_qs("db={$dbinfo['db']}&uid={$list['uid']}","uid=");

		// 템플릿 할당
		$tpl->set_var('href.read'		, $href['read']);
		$tpl->set_var('href.download'	, $href['download']);
		$tpl->set_var('list'			, $list);

		$tpl->set_var('blockloop',true);
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);

		// 업로드부분 템플릿내장값 지우기
		if(isset($list['upfiles']) && is_array($list['upfiles'])){
			foreach($list['upfiles'] as $key =>	$value){
				if(is_array($list['upfiles'][$key])){
					foreach($list['upfiles'][$key] as $key2 =>	$value) $tpl->drop_var("list.upfiles.{$key}.{$key2}");
				}
			}
		} // end if
		//	템플릿내장값 지우기
		if(is_array($list)){
			foreach($list as $key =>	$value){
				if(is_array($list[$key])) {
					foreach($list[$key] as $key2 =>	$value) $tpl->drop_var("list.{$key}.{$key2}");
				}
				else $tpl->drop_var("list.{$key}");
			}
		}
	} // end for (i)
} // end if (게시물이 있다면...)

// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			,$dbinfo);	// dbinfo 정보 변수
$tpl->set_var('count'			,$count);	// 게시판 각종 카운트
$tpl->set_var('href'			,$href);	// 게시판 각종 링크

// 서치 폼의 hidden 필드 모두!!
$form_search =" action='{$_SERVER['PHP_SELF']}' method='get'>";
$form_search .= href_qs("sc_column=&sc_string=",$qs_basic,1);
$form_search = substr($form_search,0,-1);
$tpl->set_var('form_search'		,$form_search);	// form actions, hidden fileds

// 블럭 : 첫페이지, 이전페이지
if(isset($count['nowpage']) && $count['nowpage'] > 1){
	$tpl->process('FIRSTPAGE','firstpage');
	$tpl->process('PREVPAGE','prevpage');
} else {
	$tpl->process('FIRSTPAGE','nofirstpage');
	$tpl->process('PREVPAGE','noprevpage');
}

// 블럭 : 페이지 블럭 표시
	// <-- (이전블럭) 부분
	if (isset($count['nowblock']) && $count['nowblock']>1) $tpl->process('PREVBLOCK','prevblock');
	else $tpl->process('PREVBLOCK','noprevblock');
	// 1 2 3 4 5 부분
	for ($i=(isset($count['firstpage']) ? $count['firstpage'] : 1);$i<=(isset($count['lastpage']) ? $count['lastpage'] : 1);$i++) {
		$tpl->set_var('blockcount',$i);
		if(isset($count['nowpage']) && $i == $count['nowpage'])
			$tpl->process('BLOCK','noblock',TPL_APPEND);
		else {
			$tpl->set_var('href.blockcount', "{$_SERVER['PHP_SELF']}?" . href_qs("page=".$i,$qs_basic) );
			$tpl->process('BLOCK','block',TPL_APPEND);
		}
	} // end for
	// --> (다음블럭) 부분
	if (isset($count['totalpage']) && $count['totalpage'] > ($count['lastpage'] ?? 0)) $tpl->process('NEXTBLOCK','nextblock');
	else $tpl->process('NEXTBLOCK','nonextblock');

// 블럭 : 다음페이지, 마지막 페이지
if(isset($count['nowpage']) && $count['nowpage'] < $count['totalpage']){
	$tpl->process('NEXTPAGE','nextpage');
	$tpl->process('LASTPAGE','lastpage');
} else {
	$tpl->process('NEXTPAGE','nonextpage');
	$tpl->process('LASTPAGE','nolastpage');
}

// 블럭 : 글쓰기
if(siteAuth($dbinfo, "priv_write")) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);

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