<?php
//=======================================================
// 설	명 : 심플목록보기(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/11/26
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/11/26 박선민 마지막 수정
// 24/05/20 Gemini PHP 7 마이그레이션
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

	// 2 . 기본 URL QueryString
	if(isset($_GET['getinfo']) && $_GET['getinfo'] == "cont") {
		$qs_basic = "mode=&limitno=&limitrows=";
	} else {
		$qs_basic = "mode=&pern=&row_pern&page_pern&limitno=&limitrows=&html_type=&html_skin&skin=";
	}
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화

	// 3 . $dbinfo 가져오기
	include_once("{$thisPath}/config.php");

	// 4 . 권한 체크
	if(!privAuth(($dbinfo ?? null), "priv_list",1)) back("이용이 제한되었습니다.(레벨부족)");

	//======================
	// 5 . SQL문 where절 정리
	//======================
	$sql_where = ""; // init
	$sc_string = $_GET['sc_string'] ?? '';
	$sc_column = $_GET['sc_column'] ?? '';
	
	// 서치 게시물만..
	if(trim($sc_string)){
		if($sql_where) $sql_where .= ' and ';
		if($sc_column){
			if(in_array($sc_column,array('bid','uid'))) {
				$sql_where .=" ({$sc_column}='{$sc_string}') ";
			} else {
				$sql_where .=" ({$sc_column} like '%{$sc_string}%') ";
			}
		} else {
			$sql_where .=" ((userid like '%{$sc_string}%') or (title like '%{$sc_string}%') or (content like '%{$sc_string}%')) ";
		}
	}
	if(!$sql_where) $sql_where= " 1 ";
	
	//===========================
	// 6 . SQL문 order by..절 정리
	//===========================
	$sort = $_GET['sort'] ?? null;
	switch($sort){
		case 'rdate': $sql_orderby = 'rdate'; break;
		case '!rdate':$sql_orderby = 'rdate DESC'; break;
		default :
			$sql_orderby = ($dbinfo['orderby'] ?? null) ? $dbinfo['orderby'] : ' 1 ';
	}

	// 7 . 페이지 나눔등 각종 카운트 구하기
	$count['total']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE  $sql_where ", 0, "count(*)"); // 전체 게시물 수
	$page = $_GET['page'] ?? 1;
	$count=board2Count(($count['total'] ?? 0),$page,($dbinfo['pern'] ?? 10),($dbinfo['page_pern'] ?? 5)); // 각종 카운트 구하기
	$count['today']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE (rdate > unix_timestamp(curdate())) and $sql_where " , 0, "count(*)");

	// 8 . URL Link...
	$href['listdb']	= "{$_SERVER['PHP_SELF']}?db={$dbinfo['db']}";
	$href['list']	= "{$_SERVER['PHP_SELF']}?db={$dbinfo['db']}&cateuid={$cateinfo['uid']}";
	if(($count['nowpage'] ?? 1) > 1) { // 처음, 이전 페이지
		$href['firstpage']="{$_SERVER['PHP_SELF']}?" . href_qs("page=1",$qs_basic);
		$href['prevpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']-1),$qs_basic);
	} else {
		$href['firstpage']="javascript: void(0);";
		$href['prevpage']	="javascript: void(0);";
	}
	if(($count['nowpage'] ?? 1) < ($count['totalpage'] ?? 1)){ // 다음, 마지막 페이지
		$href['nextpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']+1),$qs_basic);
		$href['lastpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['totalpage'] ?? 1),$qs_basic);
	} else {
		$href['nextpage']	="javascript: void(0);";
		$href['lastpage'] ="javascript: void(0);";
	}
	$href['prevblock']= (($count['nowblock'] ?? 0)>1)					? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['firstpage']-1) ,$qs_basic): "javascript: void(0)";// 이전 페이지 블럭
	$href['nextblock']= (($count['totalpage'] ?? 0) > ($count['lastpage'] ?? 0))? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['lastpage'] +1),$qs_basic) : "javascript: void(0)";// 다음 페이지 블럭

	$href['write']	= "{$thisUrl}/{$urlprefix}write.php?" . href_qs("mode=write&time=".time(),$qs_basic);	// 글쓰기

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';

if( !is_file($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// Limit로 필요한 게시물만 읽음.
$limitno	= (int)($_GET['limitno'] ?? ($count['firstno'] ?? 0));
$limitrows	= (int)($_GET['limitrows'] ?? ($count['pern'] ?? 10));
$sql = "SELECT * FROM {$dbinfo['table']} WHERE $sql_where ORDER BY {$sql_orderby} LIMIT {$limitno},{$limitrows}";
$rs_list = db_query($sql);

if(!$total=db_count($rs_list)) {	// 게시물이 하나도 없다면...
	if($sc_string) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($sc_string),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면. .
		$tpl->process('LIST', 'nolist');
} else {
	for($i=0; $i<$total; $i++){
		$list		= db_array($rs_list);
		$list['no']	= ($count['lastnum'] ?? 0)--;
		$list['rede']	= strlen($list['re'] ?? '');
		$list['rdate_date']= ($list['rdate'] ?? null) ? date("y/m/d", $list['rdate']) : "";	//	날짜 변환
		$cut_length = $_GET['cut_length'] ?? 50;
		$list['cut_title'] = cut_string(($list['title'] ?? ''), (int)$cut_length); // 제목자름
	
		// TODO
		$list['table_name']	= "{$SITE['th']}dmail_{$list['db']}";
		$list['count_total']	= db_resultone("SELECT count(*) as count FROM {$list['table_name']} ", 0, "count");
		$list['count_send']	= ($list['count_total'] ?? 0) - db_resultone("SELECT count(*) as count FROM {$list['table_name']} where status is null", 0, "count");
		$list['count_read']	= db_resultone("SELECT count(*) as count FROM {$list['table_name']} where status='READ'", 0, "count");

		//	Search 단어 색깔 표시
		if($sc_string && $sc_column){
			if($sc_column == "title") {
				$list['cut_title'] = preg_replace('/('.preg_quote($sc_string, '/').')/i', "<font color=darkred>\\0</font>",	$list['cut_title']);
			}
			$list[$sc_column]	= preg_replace('/('.preg_quote($sc_string, '/').')/i', "<font color='darkred'>\\0</font>", ($list[$sc_column] ?? ''));
		}

		// 업로드파일 처리
		if(($dbinfo['enable_upload'] ?? null) != 'N' && ($list['upfiles'] ?? null)){
			$upfiles=unserialize($list['upfiles']);
			if(!is_array($upfiles)){
				// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name']=$list['upfiles'];
				$upfiles['upfile']['size']=(int)($list['upfiles_totalsize'] ?? 0);
			}
			foreach($upfiles as $key =>	$value){
				if(isset($value['name']) && $value['name'])
					$upfiles[$key]['href']="{$thisUrl}/{$urlprefix}download.php?" . href_qs("uid={$list['uid']}&upfile={$key}",$qs_basic);
			} // end foreach
			$list['upfiles']=$upfiles;
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
		if(is_array(($list['upfiles'] ?? null))){
			foreach($list['upfiles'] as $key =>	$value){
				if(is_array($list['upfiles'][$key])){
					foreach($list['upfiles'][$key] as $key2 =>	$value)
						$tpl->drop_var("list.upfiles.{$key}.{$key2}");
				}
			}
		} // end if
	} // end for (i)
	//	템플릿내장값 지우기
	$tpl->drop_var('blockloop');
	$tpl->drop_var('href.read'); unset($href['read']);
	$tpl->drop_var('href.download'); unset($href['download']);
	if(is_array(($list ?? null))){
		foreach($list as $key =>	$value){
			if(is_array($list[$key])) {
				foreach($list[$key] as $key2 =>	$value) {
					$tpl->drop_var("list.{$key}.{$key2}");
				}
			} else {
				$tpl->drop_var("list.{$key}");
			}
		}
		unset($list);
	}
} // end if (게시물이 있다면...)

// 템플릿 마무리 할당
$tpl->set_var('get'				,($_GET ?? []));	// get값으로 넘어온것들
$tpl->set_var('get.sc_string'	,htmlspecialchars(stripslashes($sc_string),ENT_QUOTES));	// 서치 단어
$tpl->set_var('dbinfo'			,($dbinfo ?? []));	// dbinfo 정보 변수
$tpl->set_var('count'			,($count ?? []));	// 게시판 각종 카운트
$tpl->set_var('href'			,($href ?? []));	// 게시판 각종 링크

// 서치 폼의 hidden 필드 모두!!
$form_search =" action='{$_SERVER['PHP_SELF']}' method='get'>";
$form_search .= href_qs("sc_column=&sc_string=",$qs_basic,1);
$form_search = substr($form_search,0,-1);
$tpl->set_var('form_search'		, $form_search);	// form actions, hidden fileds

// 블럭 : 첫페이지, 이전페이지
if(($count['nowpage'] ?? 1) > 1){
	$tpl->process('FIRSTPAGE','firstpage');
	$tpl->process('PREVPAGE','prevpage');
} else {
	$tpl->process('FIRSTPAGE','nofirstpage');
	$tpl->process('PREVPAGE','noprevpage');
}

// 블럭 : 페이지 블럭 표시
	// <-- (이전블럭) 부분
	if (($count['nowblock'] ?? 0) >1) $tpl->process('PREVBLOCK','prevblock');
	else $tpl->process('PREVBLOCK','noprevblock');
	// 1 2 3 4 5 부분
	for ($i=($count['firstpage'] ?? 1);$i<=($count['lastpage'] ?? 1);$i++) {
		$tpl->set_var('blockcount',$i);
		if($i == ($count['nowpage'] ?? 1))
			$tpl->process('BLOCK','noblock',TPL_APPEND);
		else {
			$tpl->set_var('href.blockcount', "{$_SERVER['PHP_SELF']}?" . href_qs("page=".$i,$qs_basic) );
			$tpl->process('BLOCK','block',TPL_APPEND);
		}	
	} // end for
	// --> (다음블럭) 부분
	if (($count['totalpage'] ?? 0) > ($count['lastpage'] ?? 0)) $tpl->process('NEXTBLOCK','nextblock');
	else $tpl->process('NEXTBLOCK','nonextblock');

// 블럭 : 다음페이지, 마지막 페이지
if(($count['nowpage'] ?? 1) < ($count['totalpage'] ?? 1)){
	$tpl->process('NEXTPAGE','nextpage');
	$tpl->process('LASTPAGE','lastpage');
} else {
	$tpl->process('NEXTPAGE','nonextpage');
	$tpl->process('LASTPAGE','nolastpage');
}

// 블럭 : 글쓰기
if(privAuth(($dbinfo ?? null), "priv_write")) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$tpl->echoHtml(($dbinfo ?? []), ($SITE ?? []), $thisUrl);
?>