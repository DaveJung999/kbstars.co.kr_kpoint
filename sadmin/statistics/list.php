<?php
//=======================================================
// 설	명 : 심플리스트
// 책임자 : 박선민 (), 검수: 05/01/11
// Project: sitePHPbasic
// ChangeLog
//	 DATE	 수정인				 수정 내용
// -------- ------ --------------------------------------
// 05/01/11 박선민 마지막 수정
//
// 25/08/12 Gemini (PHP 7, MariaDB 11 호환성 개선)
//=======================================================
$HEADER=array(
	'priv'		 => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useApp'	 => 1, // cut_string()
	'useBoard2'	 => 1, // board2Count()
	'useSkin'	 => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security('', $_SERVER['HTTP_HOST']);

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
$thisPath	= dirname(__FILE__);
$thisUrl	= '.'; // 마지막 '/'이 빠져야함
	
	// 1. 넘어온값 체크

	// 2. 기본 URL QueryString
	$qs_basic	= 'mode=&limitno=&limitrows=&time=';
	if(isset($_GET['getinfo']) && $_GET['getinfo']!='cont')
		$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
	$qs_basic	= href_qs($qs_basic); // 해당값 초기화

	// 3. $dbinfo 가져오기
	include_once($thisPath.'/config.php');

	// 4. 권한 체크
	if(!privAuth($dbinfo, 'priv_list',1)) back('페이지를 보실 권한이 없습니다.');

	//======================
	// 5. SQL문 where절 정리
	//======================
	$sql_where = ''; // init
	// 서치 게시물만..
	if(isset($_GET['sc_string']) && isset($_GET['sc_column'])) {
		// sc_column으로 title,content이면, or로 두필드 검색하도록
		$aTemp = explode(',',$_GET['sc_column']);
		$tmp = '';
		for($i=0;$i<count($aTemp);$i++) {
			if(!preg_match('/^[a-z0-9_-]+$/',$aTemp[$i])) continue;
			if($i>0) $tmp .= ' or ';
			switch($aTemp[$i]) {
				case 'bid':
				case 'uid':
					$tmp .=' ('.$aTemp[$i].'="'.addslashes($_GET['sc_string']).'") '; break;
				default : // bug - sc_column 장난 우려
					$tmp .=' ('.$aTemp[$i].' like "%'.addslashes(preg_replace('/([%_])/', '\\\\1', $_GET['sc_string'])).'%") ';
				// default : back('잘못된 요청입니다.');
			}
		} // end for
		if($tmp) {
			if($sql_where) $sql_where .= ' and ';
			$sql_where .= ' ('.$tmp.') ';
		}
	} // end if
	if(!$sql_where) $sql_where= ' 1 '; // 값이 없다면
	
	//===========================
	// 6. SQL문 order by..절 정리
	//===========================
	switch(isset($_GET['sort']) ? $_GET['sort'] : '') {
		// get 해킹을 막기 위해 특정 값에만 order by 생성
		case 'uid':
		case 'title':
		case 'rdate':
			$sql_orderby = $_GET['sort']; break;
		case '!uid':
		case '!title':
		case '!rdate':
			$sql_orderby = substr($_GET['sort'],1).' DESC'; break;
		default :
			$sql_orderby = isset($dbinfo['orderby']) ? $dbinfo['orderby'] : ' 1 ';
	}	

	// 7. 페이지 나눔등 각종 카운트 구하기
	$count['total']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE $sql_where LIMIT 1", 0, 'count(*)'); // 전체 게시물 수
	$count=board2Count($count['total'],isset($_REQUEST['page']) ? $_REQUEST['page'] : 1,$dbinfo['pern'],$dbinfo['page_pern']); // 각종 카운트 구하기
	$count['today']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE (rdate > unix_timestamp(curdate())) and $sql_where LIMIT 1", 0, 'count(*)');

	// 8. URL Link...
	$href['list']		= $thisUrl.'list.php?'.href_qs('page=',$qs_basic);
	if($count['nowpage'] > 1) { // 처음, 이전 페이지
		$href['firstpage']	=$thisUrl.'list.php?'.href_qs('page=1',$qs_basic);
		$href['prevpage']		=$thisUrl.'list.php?'.href_qs('page='.($count['nowpage']-1),$qs_basic);
	}
	else {
		$href['firstpage']	='javascript: void(0);';
		$href['prevpage']	='javascript: void(0);';
	}
	if($count['nowpage'] < $count['totalpage']){ // 다음, 마지막 페이지
		$href['nextpage']	=$thisUrl.'list.php?'.href_qs('page='.($count['nowpage']+1),$qs_basic);
		$href['lastpage']	=$thisUrl.'list.php?'.href_qs('page='.$count['totalpage'],$qs_basic);
	}
	else {
		$href['nextpage']	='javascript: void(0);';
		$href['lastpage'] ='javascript: void(0);';
	}
	$href['prevblock']= ($count['nowblock']>1)					? $thisUrl.'list.php?'.href_qs('page='.($count['firstpage']-1) ,$qs_basic): 'javascript: void(0)';// 이전 페이지 블럭
	$href['nextblock']= ($count['totalpage'] > $count['lastpage'])? $thisUrl.'list.php?'.href_qs('page='.($count['lastpage'] +1),$qs_basic) : 'javascript: void(0)';// 다음 페이지 블럭

	$href['write']	= $thisUrl.'write.php?' . href_qs('mode=write',$qs_basic);	// 글쓰기

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';

if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$sql = "SELECT * FROM {$dbinfo['table']} WHERE $sql_where ORDER BY {$sql_orderby} LIMIT {$count['firstno']},{$count['pern']}";
$rs_list = db_query($sql);

if(!db_count($rs_list)) {	// 게시물이 하나도 없다면...
	if(isset($_GET['sc_string']) && $_GET['sc_string']) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면..
		$tpl->process('LIST', 'nolist');
} else {
	while($list = db_array($rs_list)) {
		$list['no']	= $count['lastnum']--;
		$list['rede']	= strlen($list['re']);
		$list['rdate_date']= $list['rdate'] ? date('y/m/d', $list['rdate']) : '';	//	날짜 변환
		if(!$list['title']) $list['title'] = '제목없음…';
		$list['cut_title'] = cut_string($list['title'], (int) (isset($_REQUEST['cut_length']) ? $_REQUEST['cut_length'] : 0)); // 제목자름

		//	Search 단어 색깔 표시
		if(isset($_GET['sc_string']) && isset($_GET['sc_column'])) {
			$search_string = preg_quote($_GET['sc_string'], '/');
			if($_GET['sc_column']=='title')
				$list['cut_title'] = preg_replace('/('.$search_string.')/i', '<font color=darkred>\\0</font>', $list['cut_title']);
			$list[$_GET['sc_column']]	= preg_replace('/('.$search_string.')/i', '<font color=darkred>\\0</font>', $list[$_GET['sc_column']]);
		}

		// URL Link...
		$href['read']		= $thisUrl.'read.php?' . href_qs('uid='.$list['uid'],$qs_basic);

		// 템플릿 할당
		$tpl->set_var('href.read'		, $href['read']);
		$tpl->set_var('list'			, $list);

		$tpl->set_var('blockloop',true);
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
	} // end while
	//	템플릿내장값 지우기
	$tpl->drop_var('blockloop');
	$tpl->drop_var('href.read'); unset($href['read']);
	if(is_array($list)) {
		foreach($list as $key => $value) {
			if(is_array($list[$key]))
				foreach($list[$key] as $key2 => $value2) $tpl->drop_var("list.{$key}.{$key2}");
			else $tpl->drop_var('list.'.$key);
		}
		unset($list);
	}
} // end if (게시물이 있다면...)

// 템플릿 마무리 할당
$tpl->tie_var('get'				,$_GET); 	// get값으로 넘어온것들
$tpl->set_var('get.sc_string'	,isset($_GET['sc_string']) ? htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES) : '');	// 서치 단어
$tpl->tie_var('dbinfo'			,$dbinfo);	// dbinfo 정보 변수
$tpl->tie_var('count'			,$count);	// 게시판 각종 카운트
$tpl->tie_var('href'			,$href);	// 게시판 각종 링크
$tpl->set_var('sort_'.(isset($_GET['sort']) ? $_GET['sort'] : ''),true);	// sort_???

// 서치 폼의 hidden 필드 모두!!
$form_search =' action="'.$thisUrl.'list.php"'.' method="get">';
$form_search .= href_qs('sc_column=&sc_string=',$qs_basic,1);
$form_search = substr($form_search,0,-1);
$tpl->set_var('form_search'		,$form_search);	// form actions, hidden fileds

// 블럭 : 첫페이지, 이전페이지
if($count['nowpage'] > 1) {
	$tpl->process('FIRSTPAGE','firstpage');
	$tpl->process('PREVPAGE','prevpage');
} else {
	$tpl->process('FIRSTPAGE','nofirstpage');
	$tpl->process('PREVPAGE','noprevpage');
}

// 블럭 : 페이지 블럭 표시
	// <-- (이전블럭) 부분
	if ($count['nowblock']>1) $tpl->process('PREVBLOCK','prevblock');
	else $tpl->process('PREVBLOCK','noprevblock');
	// 1 2 3 4 5 부분
	for ($i=$count['firstpage'];$i<=$count['lastpage'];$i++) {
		$tpl->set_var('blockcount',$i);
		if($i==$count['nowpage'])
			$tpl->process('BLOCK','noblock',TPL_APPEND);
		else {
			$tpl->set_var('href.blockcount', $thisUrl.'list.php?'.href_qs('page='.$i,$qs_basic) );
			$tpl->process('BLOCK','block',TPL_APPEND);
		}
	} // end for
	// --> (다음블럭) 부분
	if ($count['totalpage'] > $count['lastpage']	) $tpl->process('NEXTBLOCK','nextblock');
	else $tpl->process('NEXTBLOCK','nonextblock');

// 블럭 : 다음페이지, 마지막 페이지
if($count['nowpage'] < $count['totalpage']) {
	$tpl->process('NEXTPAGE','nextpage');
	$tpl->process('LASTPAGE','lastpage');
} else {
	$tpl->process('NEXTPAGE','nonextpage');
	$tpl->process('LASTPAGE','nolastpage');
}

// 블럭 : 글쓰기
if(privAuth($dbinfo, 'priv_write')) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
// 테이블이 존재하지 않을 경우 admin_tableinfo 테이블정보대로 table생성
function userCreateByTableinfo($table,$createtable){
	global $SITE;
	
	$sql = "select `sql_syntax`,`comment` from {$SITE['th']}admin_tableinfo where table_name='{$table}'";
	if($tableinfo=db_arrayone($sql)){
		$sql="CREATE TABLE {$createtable} ({$tableinfo['sql_syntax']})";
		$sql .= " COMMENT='{$tableinfo['comment']}'"; // MySQL 5.5+에서 TYPE은 무시되고 MyISAM이 기본값
		if(@db_query($sql))
			return 1;
		else // 아마 해당 데이터베이스가 존재할 경우겠지. . 생성하다가 실패했으니..
			return -1; // -1로 리턴함..
	}
	else return 0;
} // end func
?>
