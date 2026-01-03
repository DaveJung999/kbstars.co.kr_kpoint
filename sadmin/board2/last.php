<?php
//=======================================================
// 설	명 : 인트라넷 최근 게시물	(last.php)
// 책임자 : 박선민(sponsor@new21.com), 검수: 03/05/23 
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/05/12 채혜진 마지막 수정
// 03/05/23 박선민 /sadmin/board/last.php로 이동
// 05/04/13 채혜진 템플릿으로 수정
//============================================ 
$HEADER=array(
	'priv' => '', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useApp' => 1, // cut_string()
	'useBoard2' => 1, // board2Count(),board2CateInfo()
	'useSkin' => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$prefix		= 'admin/board2'; // board? album? 등의 접두사
$thisUrl	= '/s'.$prefix.'/'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
$table_boardinfo = $SITE['th'] . "board2info";	//게시판 관리 테이블
$qs_basic	= 'mode=&limitno=&limitrows=&time=';
if($_GET['getinfo'] != 'cont') 
	$qs_basic .= '&pern=&row_pern=&page_pern=&=&html_skin=&skin=';
$qs_basic	= href_qs($qs_basic); // 해당값 초기화
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file('skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate('skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

	
// boardinfo 테이블 정보 가져와서 $dbinfo로 저장
$rs_dbinfo=db_query("SELECT * from {$table_boardinfo}");
$total = db_count();

	
//게시판 리스트 출력
for($i =0 ; $i < $total ; $i ++){
	$list= db_array($rs_dbinfo);
	$table = $SITE['th'] . "board2_" . $list['db']; // 게시판 테이블
	
	//각 테이블에 접속해서 2일전의 게시물 불러 오기~
	$rs = db_query("SELECT * from {$table} WHERE type = 'docu' and	rdate > unix_timestamp()-172800 ORDER BY num DESC");
	$count = db_count();

	if($count){
		// 2 . 기본 URL QueryString
		for($j=0;$j < $count ; $j++){
		$cell = db_array($rs);
		
		if(!$cell['title']) $cell['title'] = "제목없음…";
	
		//답변이 있을 경우 자리는 길이를 더 줄임
		$cut_length = $cell['rede'] ? $dbinfo['cut_length'] - $cell['rede'] -3 : $dbinfo['cut_length']; 
		$cell['cut_title'] = cut_string($cell['title'], $cut_length);
		$cell['rdate_date']= $cell['rdate'] ? date('y/m/d', $cell['rdate']) : '';	//	날짜 변환
	
		// 메모개수 구해서 제목 옆에 붙임
		if($dbinfo['enable_memo'] == 'Y'){
			// 메모 테이블 구함
			
			$table_memo		=$table	. "_memo";
			$sql_where_memo	= " 1 ";
			
		
			$count_memo=db_result(db_query("select count(*) as count from {$table_memo} where {$sql_where_memo} and pid='{$cell['uid']}'"),0,"count");
			if($count_memo){
				$tmp_before_24h=time() - 86400;
				$count_memo_24h=db_result(db_query("select count(*) as count from {$table_memo} where {$sql_where_memo} and pid='{$cell['uid']}' and rdate > {$tmp_before_24h}"),0,"count");
				if($count_memo_24h) $cell['cut_title'] .= " [{$count_memo}+]";
				else $cell['cut_title'] .= " [{$count_memo}]";
			}
		} // end if			

		if(($cell['rdate'] + 86400 ) > time()){
			// $cell['cut_title'] .= " <img src='/sboard/skin/board_ok/images/title_new.gif' border ='0'>";
		}
		$cell['rdate']= date("Y/m/d", $cell['rdate']);	//	날짜 변환
		//	답변 게시물 답변 아이콘 표시
		if($cell['re'] > 0){
			//$cell['cut_title'] = str_repeat('&nbsp;', $count_redespace*($cell['rede']-1)) .	'<img src="images/re.gif" align="absmiddle" border=0> '.$cell['cut_title'];
			$cell['cut_title'] = '<img src="/scommon/spacer.gif" width="'	. ($cell['rede']-1)*8	. '" height=1 border=0><img src="images/re.gif" align="absmiddle" border=0> '.$cell['cut_title'];
		}
		
		// URL Link...
		$href['read']		= '/sboard2/read.php?'	. href_qs('uid='.$cell['uid'].'&db='.$list['db'],$qs_basic);
		$href['list']		= "/sboard2/list.php?db={$list['db']}";
		
		$tpl->set_var('href.read'		, $href['read']);
		$tpl->set_var('href.list'		, $href['list']);
		$tpl->set_var('cell'			, $cell);
		$tpl->process('CELL','cell',TPL_OPTIONAL|TPL_APPEND);
		}
		
			
		$tpl->set_var('list'			, $list);
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
		
		$tpl->drop_var('CELL');
	}
	
	
}
// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
