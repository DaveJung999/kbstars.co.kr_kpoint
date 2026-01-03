<?php
//=======================================================
// 설	명 : 심플리스트(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/22
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/08/22 박선민 마지막 수정
// 25/08/15 Gemini AI PHP 7+ 마이그레이션 및 보안 강화
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useSkin' =>	1, // 템플릿 사용
	'useBoard2' => 1, // 보드관련 함수 포함
	'useApp' => 1,
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
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
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
// 변수 초기화
$cateuid = $_REQUEST['cateuid'] ?? '';
$pern = $_REQUEST['pern'] ?? '';
$sc_column = $_REQUEST['sc_column'] ?? '';
$sc_string = $_REQUEST['sc_string'] ?? '';
$page = $_REQUEST['page'] ?? 1;

include_once("./dbinfo.php"); // $dbinfo, $table 값 정의
$db = $dbinfo['db']; // dbinfo.php에서 정의된 db 값을 사용

// 인증 체크
//	if(!privAuth($dbinfo, "priv_list",1)) back("이용이 제한되었습니다.(레벨부족)");

// 기본 URL QueryString
$qs_basic = "db=".urlencode($db).
			"&cateuid=".urlencode($cateuid).
			"&pern=".urlencode($pern) .
			"&sc_column=".urlencode($sc_column).
			"&sc_string=" . urlencode(stripslashes($sc_string)) .
			"&page=".urlencode($page);

//===================
// SQL문 where절 정리
//===================
$sql_where = " 1 ";
// 서치 게시물만..
if(trim($sc_string)){
	$sc_string_escaped = db_escape($sc_string);
	if($sc_column) {
		$sql_where .=" AND (`" . db_escape($sc_column) . "` LIKE '%{$sc_string_escaped}%') ";
	} else {
		$sql_where .=" AND ((`userid` LIKE '%{$sc_string_escaped}%') OR (`title` LIKE '%{$sc_string_escaped}%') OR (`content` LIKE '%{$sc_string_escaped}%')) ";
	}
}

$sql_where .= " AND `content` = 'banner'	";

//=====
// misc
//=====
// 페이지 나눔등 각종 카운트 구하기
$count_total_result = db_arrayone("SELECT count(*) as cnt FROM {$table} WHERE  $sql_where ");
$count['total'] = $count_total_result['cnt'] ?? 0;
$count=board2Count($count['total'],$page,($dbinfo['pern'] ?? 10),($dbinfo['page_pern'] ?? 10)); // 각종 카운트 구하기
$count_today_result = db_arrayone("SELECT count(*) as cnt FROM {$table} WHERE (rdate > unix_timestamp(curdate())) and $sql_where ");
$count['today'] = $count_today_result['cnt'] ?? 0;

// 서치 폼의 hidden 필드 모두!!
$form_search =" name=search action='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "' method='post'>
				<input type='hidden' name='db' value='" . htmlspecialchars($db, ENT_QUOTES, 'UTF-8') . "'>
				<input type='hidden' name='cateuid' value='" . htmlspecialchars($cateuid, ENT_QUOTES, 'UTF-8') . "'>
				<input type='hidden' name='pern' value='" . (int)$count['pern'] . "'>
			";

// URL Link...
$href['write']	= "write.php?" . href_qs("mode=write",$qs_basic);
if($count['nowpage'] > 1) { // 처음, 이전 페이지
	$href['firstpage'] = htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("page=1",$qs_basic), ENT_QUOTES, 'UTF-8');
	$href['prevpage']	= htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("page=" . ($count['nowpage']-1),$qs_basic), ENT_QUOTES, 'UTF-8');
} else {
	$href['firstpage']="#";
	$href['prevpage']	="#";
}
if($count['nowpage'] < $count['totalpage']){ // 다음, 마지막 페이지
	$href['nextpage']	= htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("page=" . ($count['nowpage']+1),$qs_basic), ENT_QUOTES, 'UTF-8');
	$href['lastpage']	= htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("page=".$count['totalpage'],$qs_basic), ENT_QUOTES, 'UTF-8');
} else {
	$href['nextpage']	="#";
	$href['lastpage'] ="#";
}
$href['prevblock']= ($count['nowblock']>1) ? htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("page=" . ($count['firstpage']-1) ,$qs_basic), ENT_QUOTES, 'UTF-8'): "#";// 이전 페이지 블럭
$href['nextblock']= ($count['totalpage'] > $count['lastpage'])? htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("page=" . ($count['lastpage'] +1),$qs_basic), ENT_QUOTES, 'UTF-8') : "#";// 다음 페이지 블럭

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$tpl = new phemplate("stpl/{$dbinfo['skin']}/","remove_nonjs");
$tpl->set_file('html',"banner.htm",1); // here 1 mean extract blocks

// Limit로 필요한 게시물만 읽음.
$limitno = (int)($_REQUEST['limitno'] ?? $count['firstno']);
$limitrows = (int)($_REQUEST['limitrows'] ?? $count['pern']);
$rs_list = db_query("SELECT * FROM {$table} WHERE $sql_where ORDER BY `title` LIMIT {$limitno}, {$limitrows}");
if(!$total=db_count($rs_list)) {	// 게시물이 하나도 없다면...
	if($sc_string) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($sc_string),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면..
		$tpl->process('LIST', 'nolist');
}
else{
	if($dbinfo['row_pern'] < 1) $dbinfo['row_pern']=1; // 한줄에 여러값 출력이 아닌 경우
	for($i=0; $i<$total; $i+=$dbinfo['row_pern']){
		if($dbinfo['row_pern'] > 1) $tpl->set_var('CELL',"");

		for($j=$i; ($j-$i < $dbinfo['row_pern']) && ($j < $total); $j++) { // 한줄에 여러값 출력시 루틴
			if( $j>=$total ){
				if($dbinfo['row_pern'] > 1) $tpl->process('CELL','nocell',TPL_APPEND);
				continue;
			}
			$list		= db_array($rs_list);
			$list['no']	= $count['lastnum'];
			$list['rede']	= strlen($list['re'] ?? '');
			$list['rdate']= isset($list['rdate']) ? date("Y/m/d", $list['rdate']) : "";	//	날짜 변환
			
			//제목과 내용 자르기 :: 정대입
			$list['cut_title'] = cut_string($list['title'], $dbinfo['cut_length']);
			$list['cut_content'] = cut_string($list['content'],300);
			
			$list['data1_1checked'] = "";
			$list['data1_2checked'] = "";
			if (($list['data1'] ?? '') == '1') $list['data1_1checked'] = " checked";
			else $list['data1_2checked'] = " checked";
			
			if(empty($list['title'])) $list['title'] = "제목없음…";

			//	Search 단어 색깔 표시
			if($sc_string){
				$highlight_pattern = '/(' . preg_quote($sc_string, '/') . ')/i';
				$highlight_replacement = '<font color=darkred>$1</font>';
				if($sc_column){
					if($sc_column == "title")
						$list['cut_title'] = preg_replace($highlight_pattern, $highlight_replacement,	$list['cut_title']);
					else
						$list[$sc_column]	= preg_replace($highlight_pattern, $highlight_replacement, $list[$sc_column]);
				} else {
					$list['userid']	= preg_replace($highlight_pattern, $highlight_replacement, $list['userid']);
					$list['cut_title']= preg_replace($highlight_pattern, $highlight_replacement,	$list['cut_title']);
				}
			}

			// 업로드파일 처리
			if(($dbinfo['enable_upload'] ?? 'N') != 'N' && isset($list['upfiles'])){
				$upfiles=@unserialize($list['upfiles']);
				if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
					$upfiles = [];
					$upfiles['upfile']['name']=$list['upfiles'];
					$upfiles['upfile']['size']=(int)($list['upfiles_totalsize'] ?? 0);
				}
				foreach($upfiles as $key =>	$value){
					if(isset($value['name']))
						$upfiles[$key]['href']="download.php?" . href_qs("uid={$list['uid']}&name={$key}",$qs_basic);
				} // end foreach
				$list['upfiles']=$upfiles;
				unset($upfiles);
			} // end if 업로드파일 처리

			// URL Link...
			$href['read']		= "read.php?" . href_qs("uid={$list['uid']}",$qs_basic);
			$href['list']	= "list.php?db=".urlencode($db);
			$href['download']	= "download.php?db=".urlencode($db) . "&uid={$list['uid']}";

			$file_ext = strtolower(pathinfo($list['upfiles']['upfile']['name'] ?? '', PATHINFO_EXTENSION));
			if(in_array($file_ext, ['gif', 'jpg', 'jpeg', 'png', 'bmp'])) {
				$list['cur_banner'] = "<img src='" . htmlspecialchars($href['download'], ENT_QUOTES, 'UTF-8') . "'>";
			} elseif ($file_ext == 'swf') {
				$list['cur_banner'] =	"<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='http://active.macromedia.com/flash4/cabs/swflash.cab#version=4,0,0,0' width='200' height='65'> <param name='movie' value='" . htmlspecialchars($href['download'], ENT_QUOTES, 'UTF-8') . "'>	<param name='play' value='true'>	<param name='loop' value='true'>	<param name='quality' value='high'>	<embed src='" . htmlspecialchars($href['download'], ENT_QUOTES, 'UTF-8') . "' play='true' loop='true' quality='high' pluginspage='http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash' width='200' height='65'></embed></object>";
			} else {
				$list['cur_banner'] =	"<img src='/images/dot.gif'>";
			}
				
			// 템플릿 YESRESULT 값들 입력
			$tpl->set_var('href.read'		,htmlspecialchars($href['read'], ENT_QUOTES, 'UTF-8'));
			$tpl->set_var('href.download'	,htmlspecialchars($href['download'], ENT_QUOTES, 'UTF-8'));
			$tpl->set_var('href.list'		,htmlspecialchars($href['list'], ENT_QUOTES, 'UTF-8'));
			$tpl->set_var('list'			,$list);
			$count['lastnum']--;
			$tpl->set_var('count.lastnum'	,$count['lastnum'] + 1);

			if($dbinfo['row_pern'] > 1) $tpl->process('CELL','cell',TPL_APPEND);
		} // end for (j)
		$tpl->process('LIST','list',TPL_APPEND);
	} // end for (i)
} // end if (게시물이 있다면...)

// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			,$dbinfo);// boardinfo 정보 변수
$tpl->set_var('count'			,$count);	// 게시판 각종 카운트
$tpl->set_var('href'			,$href);	// 게시판 각종 링크
$tpl->set_var('sc_string'		,htmlspecialchars(stripslashes($sc_string),ENT_QUOTES));	// 서치 단어
$tpl->set_var('form_search'		,$form_search);	// form actions, hidden fileds
// 블럭 : 첫페이지, 이전페이지
if($count['nowpage'] > 1){
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
		if($i == $count['nowpage'])
			$tpl->process('BLOCK','noblock',TPL_APPEND);
		else {
			$tpl->set_var('href.blockcount', htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("page=".$i,$qs_basic), ENT_QUOTES, 'UTF-8') );
			$tpl->process('BLOCK','block',TPL_APPEND);
		}	
	} // end for
	// --> (다음블럭) 부분
	if ($count['totalpage'] > $count['lastpage']	) $tpl->process('NEXTBLOCK','nextblock');
	else $tpl->process('NEXTBLOCK','nonextblock');

// 블럭 : 다음페이지, 마지막 페이지
if($count['nowpage'] < $count['totalpage']){
	$tpl->process('NEXTPAGE','nextpage');
	$tpl->process('LASTPAGE','lastpage');
}
else {
	$tpl->process('NEXTPAGE','nonextpage');
	$tpl->process('LASTPAGE','nolastpage');
}

// 블럭 : 글쓰기
if(privAuth($dbinfo, "priv_write")) $tpl->process('WRITE','write');
else $tpl->process('WRITE','nowrite');

// 마무리
$val="\\1stpl/{$dbinfo['skin']}/images/";
$processed_html = $tpl->process('', 'html');

if(isset($_GET['skin'])){
	echo preg_replace("/([\"|\'])images\//", $val, $processed_html);
}
else {
	switch($dbinfo['html_headpattern'] ?? ''){
		case "ht":
			if( isset($dbinfo['html_headtpl']) && is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");

			echo ($SITE['head'] ?? '') . ($dbinfo['html_head'] ?? '');
			echo preg_replace("/([\"|\'])images\//", $val, $processed_html);
			echo ($dbinfo['html_tail'] ?? '') . ($SITE['tail'] ?? '');
			break;
		case "h":
			if( isset($dbinfo['html_headtpl']) && is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");

			echo ($SITE['head'] ?? '') . ($dbinfo['html_head'] ?? '');
			echo preg_replace("/([\"|\'])images\//", $val, $processed_html);
			echo ($dbinfo['html_tail'] ?? '');
			break;
		case "t":
			if( isset($dbinfo['html_headtpl']) && is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");

			echo ($dbinfo['html_head'] ?? '');
			echo preg_replace("/([\"|\'])images\//", $val, $processed_html);
			echo ($dbinfo['html_tail'] ?? '') . ($SITE['tail'] ?? '');
			break;
		default:
			echo ($dbinfo['html_head'] ?? '');
			echo preg_replace("/([\"|\'])images\//", $val, $processed_html);
			echo ($dbinfo['html_tail'] ?? '');
	} // end switch
} // end if
?>
