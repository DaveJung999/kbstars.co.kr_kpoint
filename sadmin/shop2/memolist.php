<?php
//=======================================================
// 설	명 : 심플리스트
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		=>'운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1, // DB 커넥션 사용
	'useApp'	=>1, // cut_string()
	'useBoard2'	=>1, // board2Count()
	'useSkin'	=>1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 1. 넘어온값 체크

	// 2. 기본 URL QueryString
	$qs_basic	= 'mode=&limitno=&limitrows=&time=';
	if($_GET['getinfo']!='cont') 
		$qs_basic .= '&pern=&row_pern=&page_pern=&html_type=&html_skin=&skin=';
	$qs_basic	= href_qs($qs_basic); // 해당값 초기화

	// 3. $dbinfo 가져오기
	if($_GET['db']) {
		$sql	= "SELECT * FROM {$SITE['th']}shop2info WHERE db={$_GET['db']}";
		$dbinfo	= db_arrayone($sql) or back("사용하지 않은 DB입니다.");
		$dbinfo['pern'] = 100; // 메모 한번에 많이 보여야죠
		$dbinfo['html_type'] = "N";
		// 테이블이름 가져오기
		$table=$SITE['th'] . "shop2_" . $dbinfo['db'] ."_memo";
		
	}else back("DB 값이 없습니다");

	// 4. 권한 체크
	if(!privAuth($dbinfo, 'priv_list',1)) back('페이지를 보실 권한이 없습니다.');

	//======================
	// 5. SQL문 where절 정리
	//======================
	$sql_where = ''; // init
	// 서치 게시물만..
	if($_GET['sc_string'] and $_GET['sc_column']) {
		// sc_column으로 title,content이면, or로 두필드 검색하도록
		$aTemp = explode(',',$_GET['sc_column']);
		$tmp = '';
		for($i=0;$i<count($aTemp);$i++) {
			if(!preg_match('/^[a-z0-9_-]+$/i', $aTemp[$i])) continue;
			if($i>0) $tmp .= ' or ';
			switch($aTemp[$i]) {
				case 'bid':
				case 'uid':
					$tmp .=' ('.$aTemp[$i].'="'.$_GET['sc_string'].'") '; break;
				default : // bug - sc_column 장난 우려
					$tmp .=' ('.$aTemp[$i].' like "%'.preg_replace('/([%_])/', '\\$1', $_GET['sc_string']).'%") ';
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
	switch($_GET['sort']) {
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
			$sql_orderby = "rdate DESC";
	}	

	// 7. 페이지 나눔등 각종 카운트 구하기
	$count['total']=db_resultone("SELECT count(*) FROM {$table} WHERE $sql_where LIMIT 1", 0, 'count(*)'); // 전체 게시물 수
	$count=board2Count($count['total'],$_GET['page'],$dbinfo['pern'],$dbinfo['page_pern']); // 각종 카운트 구하기
	$count['today']=db_resultone("SELECT count(*) FROM {$table} WHERE (rdate > unix_timestamp(curdate())) and $sql_where LIMIT 1", 0, 'count(*)');

	// 8. URL Link...
	$href['list']		= $thisUrl.'memolist.php?'.href_qs('page=',$qs_basic);
	if($count['nowpage'] > 1) { // 처음, 이전 페이지
		$href['firstpage']	=$thisUrl.'memolist.php?'.href_qs('page=1',$qs_basic);
		$href['prevpage']		=$thisUrl.'memolist.php?'.href_qs('page='.($count['nowpage']-1),$qs_basic);
	}
	else {
		$href['firstpage']	='javascript: void(0);';
		$href['prevpage']	='javascript: void(0);';
	}
	if($count['nowpage'] < $count['totalpage']){ // 다음, 마지막 페이지
		$href['nextpage']	=$thisUrl.'memolist.php?'.href_qs('page='.($count['nowpage']+1),$qs_basic);
		$href['lastpage']	=$thisUrl.'memolist.php?'.href_qs('page='.$count['totalpage'],$qs_basic);
	}
	else {
		$href['nextpage']	='javascript: void(0);';
		$href['lastpage'] ='javascript: void(0);';
	}
	$href['prevblock']= ($count['nowblock']>1)					? $thisUrl.'memolist.php?'.href_qs('page='.($count['firstpage']-1) ,$qs_basic): 'javascript: void(0)';// 이전 페이지 블럭
	$href['nextblock']= ($count['totalpage'] > $count['lastpage'])? $thisUrl.'memolist.php?'.href_qs('page='.($count['lastpage'] +1),$qs_basic) : 'javascript: void(0)';// 다음 페이지 블럭

	$href['write']	= $thisUrl.'write.php?' . href_qs('mode=write',$qs_basic);	// 글쓰기 

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$sql = "SELECT * from {$table} WHERE $sql_where ORDER BY {$sql_orderby} LIMIT {$count['firstno']},{$count['pern']}";
$rs_list = db_query($sql);
if(!$total=db_count($rs_list)) {	// 게시물이 하나도 없다면...
	if($_GET['sc_string']) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면.. 
		$tpl->process('LIST', 'nolist');
}
else{
	for($i=0; $i<$total; $i++) {
		$list		= db_array($rs_list);
		$list['no']	= $count['lastnum']--;
		$list['rede']	= strlen($list['re']);
		$list['rdate_date']= $list['rdate'] ? date('y/m/d', $list['rdate']) : '';	//	날짜 변환
		if(!$list['title']) $list['title'] = '제목없음…';		
		$list['cut_title'] = cut_string($list['title'], (int)$_GET['cut_length']); // 제목자름
		
		$list['href']['modify']	= $thisUrl.'write.php?'.href_qs("mode=modify&uid={$list['uid']}&num={$list['num']}",$qs_basic);
		$list['href']['delete']	= $thisUrl.'ok.php?'.href_qs('mode=delete&uid='.$list['uid'],$qs_basic);
		
		$list['content']	= replace_string($list['content'], $list['docu_type']);	// 문서 형식에 맞추어서 내용 변경

		//	Search 단어 색깔 표시
		if($_GET['sc_string'] and $_GET['sc_column']) {
			if($_GET['sc_column']=='title') 
				$list['cut_title'] = eregi_replace($_GET['sc_string'], '<font color=darkred>\\0</font>',	$list['cut_title']);
			$list[$_GET['sc_column']]	= eregi_replace($_GET['sc_string'], '<font color=darkred>\\0</font>', $list[$_GET['sc_column']]);
		}
		//상품 이름 가져오기
		$qs = db_query("SELECT * FROM new21_shop2_{$_GET['db']} WHERE uid = {$list['pid']}");
		$list['p_name'] = db_result($qs,0,"title");
		$list['p_name'] = cut_string($list['p_name'], 20); // 제목자름
		//상품 링크
		$list['href'][p_link] = "/sshop2/read.php?db=".$_GET['db']."&uid=".$list['pid'];
		// URL Link...
		$href['read']		= $thisUrl.'read.php?' . href_qs('uid='.$list['uid'],$qs_basic);

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
$tpl->tie_var('get'				,$_GET); 	// get값으로 넘어온것들
$tpl->set_var('get.sc_string'	,htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));	// 서치 단어
$tpl->tie_var('dbinfo'			,$dbinfo);	// dbinfo 정보 변수
$tpl->tie_var('count'			,$count);	// 게시판 각종 카운트
$tpl->tie_var('href'			,$href);	// 게시판 각종 링크
$tpl->set_var('sort_'.$_GET['sort'],true);	// sort_???

// 서치 폼의 hidden 필드 모두!!
$form_search =' action="'.$thisUrl.'memolist.php"'.' method="get">';
$form_search .= href_qs('sc_column=&sc_string=',$qs_basic,1);
$form_search = substr($form_search,0,-1);
$tpl->set_var('form_search'		,$form_search);	// form actions, hidden fileds

// 블럭 : 첫페이지, 이전페이지
if($count['nowpage'] > 1) {
	$tpl->process('FIRSTPAGE','firstpage');
	$tpl->process('PREVPAGE','prevpage');
}
else {
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
			$tpl->set_var('href.blockcount', $thisUrl.'memolist.php?'.href_qs('page='.$i,$qs_basic) );
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
}
else {
	$tpl->process('NEXTPAGE','nonextpage');
	$tpl->process('LASTPAGE','nolastpage');
}

// 블럭 : 글쓰기
if(privAuth($dbinfo, 'priv_write')) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
