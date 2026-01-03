<?php
//=======================================================
// 설	명 : 심플리스트
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
// 24/05/20 Gemini PHP 7 마이그레이션
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useApp' => 1, // cut_string()
	'useBoard2' => 1, // board2Count()
	'useSkin' => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1 . 넘어온값 체크

	// 2 . 기본 URL QueryString
	$qs_basic	= 'mode=&limitno=&limitrows=&time=';
	if(($_GET['getinfo'] ?? null) != 'cont')
		$qs_basic .= '&pern=&row_pern=&page_pern=&=&html_skin=&skin=';
	$qs_basic	= href_qs($qs_basic); // 해당값 초기화

	// 3 . $dbinfo 가져오기
	include_once($thisPath.'config.php');

	// 4 . 권한 체크
	if(!privAuth(($dbinfo ?? null), 'priv_list',1)) back('페이지를 보실 권한이 없습니다.');

	//======================
	// 5 . SQL문 where절 정리
	//======================
	$sql_where = ''; // init
	$sc_string = $_GET['sc_string'] ?? '';
	$sc_column = $_GET['sc_column'] ?? '';
	
	// 서치 게시물만..
	if(trim($sc_string) && $sc_column){
		// sc_column으로 title,content이면, or로 두필드 검색하도록
		$aTemp = explode(',', $sc_column);
		$tmp = '';
		for($i=0;$i<count($aTemp);$i++){
			if(!preg_match('/^[a-z0-9_-]+$/i',$aTemp[$i])) continue;
			if($i>0) $tmp .= ' or ';
			switch($aTemp[$i]){
				case 'bid':
				case 'uid':
					$tmp .=' ('.$aTemp[$i].'="'.db_escape($sc_string).'") '; break;
				default : // bug - sc_column 장난 우려
					$tmp .=' ('.$aTemp[$i].' like "%'.db_escape(preg_replace('/([%_])/', '\\\\1', $sc_string)).'%") ';
				// default : back('잘못된 요청입니다.');
			}
		} // end for
		if($tmp){
			if($sql_where) $sql_where .= ' and ';
			$sql_where .= ' ('.$tmp.') ';
		}
	} // end if
	if(!$sql_where) $sql_where= ' 1 '; // 값이 없다면
	
	//===========================
	// 6 . SQL문 order by..절 정리
	//===========================
	$sort = $_GET['sort'] ?? null;
	switch($sort){
		// get 해킹을 막기 위해 특정 값에만 order by 생성
		case 'uid':
		case 'title':
		case 'rdate':
			$sql_orderby = $sort; break;
		case '!uid':
		case '!title':
		case '!rdate':
			$sql_orderby = substr($sort,1).' DESC'; break;
		default :
			$sql_orderby = ($dbinfo['orderby'] ?? null) ? $dbinfo['orderby'] : ' 1 ';
	}

	// 7 . 페이지 나눔등 각종 카운트 구하기
	$count['total']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE  $sql_where ", 0, 'count(*)'); // 전체 게시물 수
	$page = $_GET['page'] ?? 1;
	$count=board2Count(($count['total'] ?? 0),$page,($dbinfo['pern'] ?? 10),($dbinfo['page_pern'] ?? 5)); // 각종 카운트 구하기
	$count['today']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE (rdate > unix_timestamp(curdate())) and  $sql_where ", 0, 'count(*)');

	// 8 . URL Link...
	$href['list']		= $thisUrl.'list.php?'.href_qs('page=',$qs_basic);
	if(($count['nowpage'] ?? 1) > 1) { // 처음, 이전 페이지
		$href['firstpage']	=$thisUrl.'list.php?'.href_qs('page=1',$qs_basic);
		$href['prevpage']	=$thisUrl.'list.php?'.href_qs('page='.($count['nowpage']-1),$qs_basic);
	} else {
		$href['firstpage']	='javascript: void(0);';
		$href['prevpage']	='javascript: void(0);';
	}
	if(($count['nowpage'] ?? 1) < ($count['totalpage'] ?? 1)){ // 다음, 마지막 페이지
		$href['nextpage']	=$thisUrl.'list.php?'.href_qs('page='.($count['nowpage']+1),$qs_basic);
		$href['lastpage']	=$thisUrl.'list.php?'.href_qs('page='.($count['totalpage'] ?? 1),$qs_basic);
	} else {
		$href['nextpage']	='javascript: void(0);';
		$href['lastpage'] ='javascript: void(0);';
	}
	$href['prevblock']= (($count['nowblock'] ?? 0)>1)					? $thisUrl.'list.php?'.href_qs('page='.($count['firstpage']-1) ,$qs_basic): 'javascript: void(0)';// 이전 페이지 블럭
	$href['nextblock']= (($count['totalpage'] ?? 0) > ($count['lastpage'] ?? 0))? $thisUrl.'list.php?'.href_qs('page='.($count['lastpage'] +1),$qs_basic) : 'javascript: void(0)';// 다음 페이지 블럭

	$href['write']	= $thisUrl.'write.php?'	. href_qs('mode=write',$qs_basic);	// 글쓰기

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';

if( !is_file('skin/'.($dbinfo['skin'] ?? 'basic').'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate('skin/'.($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
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
		$list['rdate_date']= ($list['rdate'] ?? null) ? date('y/m/d', $list['rdate']) : '';	//	날짜 변환
		if(!($list['title'] ?? null)) $list['title'] = '제목없음…';		
		$list['cut_title'] = cut_string(($list['title'] ?? ''), (int)($_GET['cut_length'] ?? 50)); // 제목자름

		//	Search 단어 색깔 표시
		if($sc_string && $sc_column){
			if($sc_column == 'title') {
				$list['cut_title'] = preg_replace('/('.preg_quote($sc_string, '/').')/i', '<font color=darkred>\\0</font>',	$list['cut_title']);
			}
			$list[$sc_column]	= preg_replace('/('.preg_quote($sc_string, '/').')/i', '<font color=darkred>\\0</font>', $list[$sc_column]);
		}

	// TODO
		//////////////////////////////////////////////////
		// 게시판 테이블이 생성되었는지 체크와 없으면 생성
		$table_name = ($SITE['th'] ?? '').'board2_'.($list['db'] ?? '');
		if(!userCreateByTableinfo('board2', $table_name)){
			echo "{$table_name} 게시판 테이블이 없어서 생성중 문제 발생";
			exit;
		}
		// - $SITE['th']board2_???_cate 카테고리 테이블이 없을 경우 생성
		if(($list['enable_cate'] ?? null) == 'Y' and !userCreateByTableinfo('board2_cate', $table_name.'_cate')){
			echo "{$table_name}_cate 게시판 카테고리 테이블이 없어서 생성중 문제 발생";
			exit;
		}
		// - $SITE['th']board2_???_memo 메모 테이블이 없을 경우 생성
		if(($list['enable_memo'] ?? null) == 'Y' and !userCreateByTableinfo('board2_memo', $table_name.'_memo')){
			echo "{$table_name}_memo 게시판 메모 테이블이 없어서 생성중 문제 발생";
			exit;
		}
		// - $SITE['th']board2_???_readlog 읽은사람리스트 테이블이 없을 경우 생성
		if(($list['enable_readlog'] ?? null) == 'Y' and !userCreateByTableinfo('board2_readlog', $table_name.'_readlog')){
			echo "{$table_name}_readlog 게시판 메모 테이블이 없어서 생성중 문제 발생";
			exit;
		}
		//////////////////////////////////////////////////

		// 게시물수 구하기
		$sql = "select count(*) as count from " . ($SITE['th'] ?? '') . "board2_" . ($list['db'] ?? '');
		$list['dbcount'] = db_resultone($sql,0,'count');
		$sql = "select count(*) as count from " . ($SITE['th'] ?? '') . "board2_" . ($list['db'] ?? '') . " where rdate > unix_timestamp()-86400";
		$list['dbcount24'] = db_resultone($sql,0,'count');
		
		// URL Link...
		$href['read']		= $thisUrl.'read.php?'	. href_qs('uid='.($list['uid'] ?? ''),$qs_basic);

		// 템플릿 할당
		$tpl->set_var('href.read'		, $href['read']);
		$tpl->set_var('list'			, $list);
		// TODO
		$tpl->set_var('href.cate'		, ($href['cate'] ?? null));

		$tpl->set_var('blockloop',true);
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
	} // end for (i)
	//	템플릿내장값 지우기
	$tpl->drop_var('blockloop');
	$tpl->drop_var('href.read'); unset($href['read']);
	// $tpl->drop_var('href.download'); unset($href['download']);
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
$tpl->set_var('sort_'.$sort,true);	// sort_???

// 서치 폼의 hidden 필드 모두!!
$form_search =' action="'.$thisUrl.'list.php"'.' method="get">';
$form_search .= href_qs('sc_column=&sc_string=',$qs_basic,1);
$form_search = substr($form_search,0,-1);
$tpl->set_var('form_search'		,$form_search);	// form actions, hidden fileds

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
	if (($count['nowblock'] ?? 0)>1) $tpl->process('PREVBLOCK','prevblock');
	else $tpl->process('PREVBLOCK','noprevblock');
	// 1 2 3 4 5 부분
	for ($i=($count['firstpage'] ?? 1);$i<=($count['lastpage'] ?? 1);$i++) {
		$tpl->set_var('blockcount',$i);
		if($i == ($count['nowpage'] ?? 1))
			$tpl->process('BLOCK','noblock',TPL_APPEND);
		else {
			$tpl->set_var('href.blockcount', $thisUrl.'list.php?'.href_qs('page='.$i,$qs_basic) );
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
if(privAuth(($dbinfo ?? null), 'priv_write')) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$tpl->echoHtml(($dbinfo ?? []), ($SITE ?? []), $thisUrl);

//=======================================================
// User functions.. . (사용자 함수 정의)
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