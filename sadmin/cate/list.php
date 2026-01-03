<?php
//=======================================================
// 설	명 : 즐겨찾기관리 -목록보기(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/27
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/27 박선민 마지막 수정
// 25/08/12 Gemini	PHP 7.x 버전 마이그레이션 및 보안 강화
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
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
	if($_GET['getinfo'] != 'cont')
		$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
	$qs_basic	= href_qs($qs_basic); // 해당값 초기화

	// 3 . $dbinfo 가져오기
	include_once($thisPath.'config.php');

	// 4 . 권한 체크
	if(!privAuth($dbinfo, 'priv_list',1)) back('페이지를 보실 권한이 없습니다.');

	//======================
	// 5 . SQL문 where절 정리
	//======================
	$sql_where = ''; // init
	// 서치 게시물만..
	if(isset($_GET['sc_string']) and isset($_GET['sc_column'])){
		// sc_column으로 title,content이면, or로 두필드 검색하도록
		$aTemp = explode(',',$_GET['sc_column']);
		$tmp = '';
		$sc_string = trim($_GET['sc_string']);
		
		for($i=0;$i<count($aTemp);$i++){
			$col = trim($aTemp[$i]);

			// sc_column 값은 화이트리스트로 검증 (SQL 인젝션 방지)
			switch($col){
				case 'bid':
				case 'uid':
				case 'title':
				case 'content':
					// 허용된 컬럼만 진행
					if($i>0) $tmp .= ' or ';
					if($col === 'bid' || $col === 'uid') {
						$tmp .=' ('.$col.'="'.$sc_string.'") ';
					} else {
						// 원본 소스의 ereg_replace() 기능을 str_replace()로 대체하여 %와 _를 이스케이프
						$escaped_sc_string = str_replace(['%', '_'], ['\%', '\_'], $sc_string);
						$tmp .=' ('.$col.' like "%'.$escaped_sc_string.'%") ';
					}
					break;
				default :
					// 허용되지 않은 컬럼은 무시
					continue 2; // for 루프를 종료하고 다음 단계로 넘어감
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
	switch($_GET['sort']){
		// get 해킹을 막기 위해 특정 값에만 order by 생성
		case 'uid':
		case 'rdate':
			$sql_orderby = $_GET['sort']; break;
		case '!uid':
		case '!rdate':
			$sql_orderby = substr($_GET['sort'],1).' DESC'; break;
		default :
			$sql_orderby = $dbinfo['orderby'] ? $dbinfo['orderby'] : ' 1 ';
	}	

	// 7 . 페이지 나눔등 각종 카운트 구하기
	$count['total']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE $sql_where LIMIT 1", 0, 'count(*)'); // 전체 게시물 수
	$count=board2Count($count['total'],(isset($_GET['page']) ? (int)$_GET['page'] : 1),$dbinfo['pern'],$dbinfo['page_pern']); // 각종 카운트 구하기
	$count['today']=db_resultone("SELECT count(*) FROM {$dbinfo['table']} WHERE (rdate > unix_timestamp(curdate())) and $sql_where LIMIT 1", 0, 'count(*)');

	// 8 . URL Link...
	$href['list']		= $thisUrl.'list.php?'.href_qs('page=',$qs_basic);
	if($count['nowpage'] > 1) { // 처음, 이전 페이지
		$href['firstpage']	=$thisUrl.'list.php?'.href_qs('page=1',$qs_basic);
		$href['prevpage']	=$thisUrl.'list.php?'.href_qs('page='.($count['nowpage']-1),$qs_basic);
	} else {
		$href['firstpage']	='javascript: void(0);';
		$href['prevpage']	='javascript: void(0);';
	}
	if($count['nowpage'] < $count['totalpage']){ // 다음, 마지막 페이지
		$href['nextpage']	=$thisUrl.'list.php?'.href_qs('page='.($count['nowpage']+1),$qs_basic);
		$href['lastpage']	=$thisUrl.'list.php?'.href_qs('page='.$count['totalpage'],$qs_basic);
	} else {
		$href['nextpage']	='javascript: void(0);';
		$href['lastpage'] ='javascript: void(0);';
	}
	$href['prevblock']= ($count['nowblock']>1)					? $thisUrl.'list.php?'.href_qs('page='.($count['firstpage']-1) ,$qs_basic): 'javascript: void(0)';// 이전 페이지 블럭
	$href['nextblock']= ($count['totalpage'] > $count['lastpage'])? $thisUrl.'list.php?'.href_qs('page='.($count['lastpage'] +1),$qs_basic) : 'javascript: void(0)';// 다음 페이지 블럭

	$href['write']	= $thisUrl.'write.php?'	. href_qs('mode=write',$qs_basic);	// 글쓰기

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';

if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$sql = "SELECT * FROM {$dbinfo['table']} WHERE $sql_where ORDER BY {$sql_orderby} LIMIT {$count['firstno']},{$count['pern']}";
$rs_list = db_query($sql);

if(!$total=db_count($rs_list)) {	// 게시물이 하나도 없다면...
	if(isset($_GET['sc_string'])) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면. .
		$tpl->process('LIST', 'nolist');
} else {
	for($i=0; $i<$total; $i++){
		$list		= db_array($rs_list);
		$list['no']	= $count['lastnum']--;
		$list['rede']	= strlen($list['re']);
		$list['rdate_date']= $list['rdate'] ? date('y/m/d', $list['rdate']) : '';	//	날짜 변환
		if(!$list['title']) $list['title'] = '제목없음…';		
		$list['cut_title'] = cut_string($list['title'], (int)$_GET['cut_length']); // 제목자름

		//	Search 단어 색깔 표시 (ereg_replace -> preg_replace)
		if(isset($_GET['sc_string']) and isset($_GET['sc_column'])){
			$sc_string = trim($_GET['sc_string']);
			$sc_column = trim($_GET['sc_column']);
			$pattern = '/' . preg_quote($sc_string, '/') . '/i'; // 정규식 특수문자 이스케이프 및 대소문자 구분 없음
			
			if($sc_column == 'title')
				// 원본 소스의 eregi_replace() 기능을 preg_replace()로 대체
				$list['cut_title'] = preg_replace($pattern, '<font color=darkred>\\0</font>',	$list['cut_title']);
			
			// sc_column이 배열일 경우
			$cols = explode(',', $sc_column);
			foreach ($cols as $col) {
				$col = trim($col);
				if (isset($list[$col])) {
					$list[$col] = preg_replace($pattern, '<font color=darkred>\\0</font>', $list[$col]);
				}
			}
		}

		// URL Link...
		$href['read']		= $thisUrl.'read.php?'	. href_qs('uid='.$list['uid'],$qs_basic);

		// TODO
		// 게시물수 구하기
		$sql = "select count(*) as count from {$SITE['th']}cate where db='{$list['db']}'";
		$list['count'] = db_resultone($sql,0,'count');

		// 템플릿 할당
		$tpl->set_var('href.read'		, $href['read']);
		$tpl->set_var('list'			, $list);

		$tpl->set_var('blockloop',true);
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
	} // end for (i)
	//	템플릿내장값 지우기
	$tpl->drop_var('blockloop');
	$tpl->drop_var('href.read'); unset($href['read']);
	$tpl->drop_var('list',$list);
} // end if (게시물이 있다면...)

// 템플릿 마무리 할당
$tpl->tie_var('get'				,$_GET);	// get값으로 넘어온것들
$tpl->set_var('get.sc_string'	,htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));	// 서치 단어
$tpl->tie_var('dbinfo'			,$dbinfo);	// dbinfo 정보 변수
$tpl->tie_var('href'			,$href);	// 게시판 각종 링크
$tpl->set_var('sort_'.(isset($_GET['sort']) ? $_GET['sort'] : ''),true);	// sort_???

// 서치 폼의 hidden 필드 모두!!
$form_search =' action="'.$thisUrl.'list.php"'.' method="get">';
$form_search .= href_qs('sc_column=&sc_string=',$qs_basic,1);
$form_search = substr($form_search,0,-1);
$tpl->set_var('form_search'		,$form_search);	// form actions, hidden fileds

// 블럭 : 글쓰기
if(privAuth($dbinfo, 'priv_write')) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);

?>
