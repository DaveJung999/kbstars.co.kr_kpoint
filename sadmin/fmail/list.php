<?php
//=======================================================
// 설 명 : 심플목록보기(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/04/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/04/14 박선민 마지막 수정
// 24/05/20 Gemini PHP 7 마이그레이션
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
	'useBoard2' => 1, // boardAuth()
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
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함

	// 기본 URL QueryString
	if(isset($_GET['getinfo']) && $_GET['getinfo'] == "cont") {
		$qs_basic = "mode=&limitno=&limitrows=";
	} else {
		$qs_basic = "mode=&pern=&row_pern&page_pern&limitno=&limitrows=&html_type=&html_skin&skin=";
	}
	$qs_basic		= href_qs($qs_basic); // 해당값 초기화

	include_once("{$thisPath}/config.php"); // $dbinfo 정의

	// 인증 체크
	if(!boardAuth((isset($dbinfo) ? $dbinfo : null), "priv_list",1)) back("이용이 제한되었습니다.(레벨부족)");

	// 넘어온 값에 따라 $dbinfo값 변경
	if(isset($dbinfo['enable_getinfo']) && $dbinfo['enable_getinfo'] == 'Y'){
		if(isset($_GET['cut_length']))	$dbinfo['cut_length']	= $_GET['cut_length'];
		if(isset($_GET['pern']))			$dbinfo['pern']		= $_GET['pern'];

		// skin관련
		if(isset($_GET['html_type']))	$dbinfo['html_type'] = $_GET['html_type'];
		if( isset($_GET['html_skin']) && preg_match('/^[_a-z0-9]+$/i',$_GET['html_skin'])
			&& is_file((isset($SITE['html_path']) ? $SITE['html_path'] : '') .'index_'.$_GET['html_skin'].'.php') )
			$dbinfo['html_skin'] = $_GET['html_skin'];
		if( isset($_GET['skin']) && preg_match("/^[_a-z0-9]+$/i",$_GET['skin'])
			&& is_dir($thisPath.'/skin/'.$_GET['skin']) )
			$dbinfo['skin']	= $_GET['skin'];
	}

	//===================
	// SQL문 where절 정리
	//===================
	$sql_where = ""; // $sql_where 사용 시작
	$sc_column = $_GET['sc_column'] ?? null;
	$sc_string = $_GET['sc_string'] ?? null;

	// 서치 게시물만..
	if(trim($sc_string ?? '')){
		if($sql_where) $sql_where .= " and ";
		if(isset($sc_column))
			$sql_where .=" ({$sc_column} like '%".db_escape($sc_string) . "%') ";
		else
			$sql_where .=" ((userid like '%".db_escape($sc_string) . "%') or (title like '%".db_escape($sc_string) . "%') or (content like '%".db_escape($sc_string) . "%')) ";
	}
	if(!$sql_where) $sql_where= " 1 ";

	//============================
	// SQL문 order by..부분 만들기
	//============================
	$sql_orderby = '';
	$sort = $_GET['sort'] ?? null;
	switch($sort){
		case "title": $sql_orderby = "title"; break;
		case "!title":$sql_orderby = "title DESC"; break;
		case "rdate": $sql_orderby = "rdate"; break;
		case "!rdate":$sql_orderby = "rdate DESC"; break;
		case "hit" : $sql_orderby = "hit DESC";	break;
		default :
			$sql_orderby = (isset($dbinfo['orderby']) ? $dbinfo['orderby'] : " rdate DESC ");
	}
	//=====
	// misc
	//=====
	// 페이지 나눔등 각종 카운트 구하기
	$page = $_GET['page'] ?? 1;

	global $db_conn;
	$count['total']=db_result(db_query("SELECT count(*) FROM {$dbinfo['table']} WHERE  $sql_where "), 0, "count(*)"); // 전체 게시물 수
	$pern = isset($dbinfo['pern']) ? $dbinfo['pern'] : 5;
	$page_pern = isset($dbinfo['page_pern']) ? $dbinfo['page_pern'] : 5;
	$count=board2Count($count['total'],$page,$pern,$page_pern); // 각종 카운트 구하기
	//$count['today']=db_result(db_query("SELECT count(*) FROM {$dbinfo['table']} WHERE (rdate > unix_timestamp(curdate())) and $sql_where ") , 0, "count(*)");

	// 서치 폼의 hidden 필드 모두!!
	$form_search =" action='{$_SERVER['PHP_SELF']}' method='get'>";
	$form_search .= substr(href_qs("",$qs_basic,1),0,-1);

	// URL Link...
	$href['list']	= "{$thisUrl}/list.php?db={$dbinfo['db']}";
	$href['write']	= "{$thisUrl}/write.php?" . href_qs("mode=write",$qs_basic);	// 글쓰기
	if(isset($count['nowpage']) && $count['nowpage'] > 1) { // 처음, 이전 페이지
		$href['firstpage']="{$_SERVER['PHP_SELF']}?" . href_qs("page=1",$qs_basic);
		$href['prevpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']-1),$qs_basic);
	} else {
		$href['firstpage']="javascript: void(0)";
		$href['prevpage']	="javascript: void(0)";
	}
	if(isset($count['nowpage']) && $count['nowpage'] < (isset($count['totalpage']) ? $count['totalpage'] : 1)){ // 다음, 마지막 페이지
		$href['nextpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']+1),$qs_basic);
		$href['lastpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . (isset($count['totalpage']) ? $count['totalpage'] : 1),$qs_basic);
	} else {
		$href['nextpage']	="javascript: void(0)";
		$href['lastpage'] ="javascript: void(0)";
	}
	$href['prevblock']= (isset($count['nowblock']) && $count['nowblock']>1) ? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['firstpage']-1) ,$qs_basic): "javascript: void(0)";// 이전 페이지 블럭
	$href['nextblock']= (isset($count['totalpage']) && $count['totalpage'] > (isset($count['lastpage']) ? $count['lastpage'] : 1) ) ? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['lastpage'] +1),$qs_basic) : "javascript: void(0)";// 다음 페이지 블럭

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$skinfile=basename(__FILE__,'.php').'.html';

if( !is_file($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'/skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// Limit로 필요한 게시물만 읽음.
$limitno	= $_GET['limitno'] ?? ($count['firstno'] ?? 0);
$limitrows	= $_GET['limitrows'] ?? ($count['pern'] ?? 10);
$sql = "SELECT * FROM {$dbinfo['table']} WHERE $sql_where ORDER BY {$sql_orderby} LIMIT {$limitno},{$limitrows}";
$rs_list = db_query($sql);

if(!$total=db_count($rs_list)) {	// 게시물이 하나도 없다면...
	if(isset($_GET['sc_string'])) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면..
		$tpl->process('LIST', 'nolist');
} else {
	for($i=0; $i<$total; $i++){
		$list		= db_array($rs_list);
		$list['no']	= ($count['lastnum'] ?? $total)--;
		$list['rede']	= strlen($list['re'] ?? '');
		$list['rdate_date'] = ($list['rdate'] ?? null) ? date("y/m/d", $list['rdate']) : "";	//	날짜 변환

		// new image넣을 수 있게 <opt name="enable_new">..
		if(($list['rdate'] ?? 0) > time()-3600*24) {
			$list['enable_new']="Y";
		} else {
			$list['enable_new']="";
		}

		if(!isset($list['title']) || !$list['title']) $list['title'] = "제목없음…";

		//답변이 있을 경우 자리는 길이를 더 줄임
		$cut_length = ($list['rede'] ?? 0) ? ($dbinfo['cut_length'] ?? 50) - ($list['rede'] ?? 0) -3 : ($dbinfo['cut_length'] ?? 50);
		$list['cut_title'] = cut_string($list['title'] ?? '', $cut_length);

		//	Search 단어 색깔 표시
		if(isset($sc_string)){
			if(isset($sc_column)){
				if($sc_column == "title")
					$list['cut_title'] = preg_replace('/'.preg_quote($sc_string, '/').'/i', "<font color=darkred>\\0</font>",	$list['cut_title']);
				else
					$list[$sc_column]	= preg_replace('/'.preg_quote($sc_string, '/').'/i', "<font color='darkred'>\\0</font>", $list[$sc_column]);
			} else {
				$list['userid']	= preg_replace('/'.preg_quote($sc_string, '/').'/i', "<font color=darkred>\\0</font>", ($list['userid'] ?? ''));
				$list['cut_title']= preg_replace('/'.preg_quote($sc_string, '/').'/i', "<font color=darkred>\\0</font>",	$list['cut_title']);
			}
		}

		// 메모개수 구해서 제목 옆에 붙임
		if(($dbinfo['enable_memo'] ?? null) == 'Y'){
			$sql_where_memo	= " 1 ";

			$sql = "select count(*) as count from {$dbinfo['table']}_memo where {$sql_where_memo} and num='" . ($list['uid'] ?? '') . "'";
			$count_memo=db_resultone($sql,0,"count");
			if($count_memo){
				$sql = "select count(*) as count from {$dbinfo['table']}_memo where {$sql_where_memo} and num='" . ($list['uid'] ?? '') . "' and rdate > unix_timestamp()-86400";
				$count_memo_24h=db_resultone($sql,0,"count");
				if($count_memo_24h) $list['cut_title'] .= " [{$count_memo}+]";
				else $list['cut_title'] .= " [{$count_memo}]";
			}
		} // end if

		//	답변 게시물 답변 아이콘 표시
		if(($list['rede'] ?? 0) > 0){
			//$list['cut_title'] = str_repeat("&nbsp;", $count_redespace*($list['rede']-1)) .	 "<img src=\"images/re.gif\" align='absmiddle' border=0> {$list['cut_title']}";
			$list['cut_title'] = "<img src='/scommon/spacer.gif' width='" . (($list['rede']-1)*8) . "' border=0><img src='/scommon/re.gif' align='absmiddle' border=0> {$list['cut_title']}";
		}

		// 업로드파일 처리
		if((($dbinfo['enable_upload'] ?? null) != 'N') and ($list['upfiles'] ?? null)){
			$upfiles=unserialize($list['upfiles']);
			if(!is_array($upfiles)) {
				// 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name']=$list['upfiles'] ?? '';
				$upfiles['upfile']['size']=(int)($list['upfiles_totalsize'] ?? 0);
			}
			foreach($upfiles as $key =>	$value){
				if(isset($value['name']) && $value['name'])
					$upfiles[$key]['href']="{$thisUrl}/download.php?" . href_qs("uid=" . ($list['uid'] ?? '') . "&upfile={$key}",$qs_basic);
			} // end foreach
			$list['upfiles']=$upfiles;
			unset($upfiles);
		} // end if 업로드파일 처리

		// URL Link...
		$href['download']	= "{$thisUrl}/download.php?db=" . ($dbinfo['db'] ?? '') . "&uid=" . ($list['uid'] ?? '');
		$href['read']		= "{$thisUrl}/read.php?" . href_qs("uid=" . ($list['uid'] ?? ''),$qs_basic);
		$href['modify']	= "{$thisUrl}/write.php?" . href_qs("mode=modify&uid=" . ($list['uid'] ?? '') . "&num=" . ($list['num'] ?? '') . "&time=".time(),$qs_basic);
		$href['delete']	= "{$thisUrl}/ok.php?" . href_qs("mode=delete&uid=" . ($list['uid'] ?? ''),$qs_basic);

		// 템플릿 YESRESULT 값들 입력
		$tpl->set_var('href.read'		, $href['read']);
		$tpl->set_var('href.download'	, $href['download']);
		$tpl->set_var('href.modify'		, $href['modify']);
		$tpl->set_var('href.delete'		, $href['delete']);
		$tpl->set_var('list'			, $list);

		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
		$tpl->set_var('blockloop',true);
	} // end for (i)
	$tpl->drop_var('blockloop');
	$tpl->drop_var('href.read');	unset($href['read']);
	$tpl->drop_var('href.download');unset($href['download']);
	$tpl->drop_var('href.modify');	unset($href['modify']);
	$tpl->drop_var('href.delete');	unset($href['delete']);
	if(is_array($list)){
		foreach($list as $key =>	$value){
			$tpl->drop_var("list.{$key}"); unset($href["list.{$key}"]);
		}
	}
} // end if (게시물이 있다면...)

// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			, $dbinfo ?? []);// boardinfo 정보 변수
$tpl->set_var('count'			, $count ?? []);	// 게시판 각종 카운트
$tpl->set_var('href'			, $href ?? []);	// 게시판 각종 링크
$tpl->set_var('sc_string'		,htmlspecialchars(stripslashes($sc_string ?? ''),ENT_QUOTES));	// 서치 단어
$tpl->set_var('form_search'		, $form_search ?? '');	// form actions, hidden fileds


if(!isset($_GET['limitrows'])) { // 게시물 일부 보기에서는 카테고리, 블럭이 필요 없을 것임
	// 블럭 : 카테고리(상위, 동일, 서브) 생성
	if(isset($dbinfo['enable_cate']) && $dbinfo['enable_cate'] == 'Y'){
		if(isset($cateinfo['catelist']) && $cateinfo['catelist']){
			$tpl->set_var('cateinfo.catelist',$cateinfo['catelist']);
			$tpl->process('CATELIST','catelist',TPL_APPEND);
		}

		if(isset($cateinfo['highcate']) && is_array($cateinfo['highcate'])){
			foreach($cateinfo['highcate'] as $key =>	$value){
				$tpl->set_var('href.highcate',"{$_SERVER['PHP_SELF']}?" . href_qs("cateuid=".$key,$qs_basic));
				$tpl->set_var('highcate.uid',$key);
				$tpl->set_var('highcate.title',$value);
				$tpl->process('HIGHCATE','highcate',TPL_OPTIONAL|TPL_APPEND);
				$tpl->set_var('blockloop',true);
			}
			$tpl->drop_var('blockloop');
		} // end if
		if(isset($cateinfo['samecate']) && is_array($cateinfo['samecate'])){
			foreach($cateinfo['samecate'] as $key =>	$value){
				if($key == ($cateinfo['uid'] ?? null))
					$tpl->set_var('samecate.selected'," selected ");
				else
					$tpl->set_var('samecate.selected',"");
				$tpl->set_var('href.samecate',"{$_SERVER['PHP_SELF']}?" . href_qs("cateuid=".$key,$qs_basic));
				$tpl->set_var('samecate.uid',$key);
				$tpl->set_var('samecate.title',$value);
				$tpl->process('SAMECATE','samecate',TPL_OPTIONAL|TPL_APPEND);
				$tpl->set_var('blockloop',true);
			}
			$tpl->drop_var('blockloop');
		} // end if
		if(isset($cateinfo['subcate']) && is_array($cateinfo['subcate'])){
			foreach($cateinfo['subcate'] as $key =>	$value){
				// subsubcate...
				$tpl->drop_var('SUBSUBCATE');
				if(isset($cateinfo['subsubcate'][$key]) && is_array($cateinfo['subsubcate'][$key])){
					$blockloop = $tpl->get_var('blockloop');
					$tpl->drop_var('blockloop');
					foreach($cateinfo['subsubcate'][$key] as $subkey =>	$subvalue){
						$tpl->set_var('href.subsubcate',"{$_SERVER['PHP_SELF']}?" . href_qs("cateuid=".$subkey,$qs_basic));
						$tpl->set_var('subsubcate.uid',$subkey);
						$tpl->set_var('subsubcate.title',$subvalue);
						$tpl->process('SUBSUBCATE','subsubcate',TPL_OPTIONAL|TPL_APPEND);
						$tpl->set_var('blockloop',true);
					}
					$tpl->set_var('blockloop',$blockloop);
				} // end if

				$tpl->set_var('href.subcate',"{$_SERVER['PHP_SELF']}?" . href_qs("cateuid=".$key,$qs_basic));
				$tpl->set_var('subcate.uid',$key);
				$tpl->set_var('subcate.title',$value);
				$tpl->process('SUBCATE','subcate',TPL_OPTIONAL|TPL_APPEND);
				$tpl->set_var('blockloop',true);
			}
			$tpl->drop_var('blockloop');
		} // end if
	} // end if

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
		if (isset($count['totalpage']) && $count['totalpage'] > ($count['lastpage'] ?? 1) ) $tpl->process('NEXTBLOCK','nextblock');
		else $tpl->process('NEXTBLOCK','nonextblock');

	// 블럭 : 다음페이지, 마지막 페이지
	if(isset($count['nowpage']) && $count['nowpage'] < ($count['totalpage'] ?? 1)){
		$tpl->process('NEXTPAGE','nextpage');
		$tpl->process('LASTPAGE','lastpage');
	} else {
		$tpl->process('NEXTPAGE','nonextpage');
		$tpl->process('LASTPAGE','nolastpage');
	}
} // end if

// 블럭 : 글쓰기
if(boardAuth(($dbinfo ?? null), "priv_write")) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$tpl->echoHtml(($dbinfo ?? []), ($SITE ?? []), $thisUrl);
?>