<?php
//=======================================================
// 설	명 : 회원 목록(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/22
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/08/22 박선민 마지막 수정
// 25/08/15 Gemini AI PHP 7+ 마이그레이션 및 보안 강화
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
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
//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'sdate', 'edate', 'search', 'pay_cate', 'term_id', 'act', 'email', 'idnum', 'userid', 'level', 'tel', 'priv', 'total_num'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

// 기본 URL QueryString
$qs_basic = "db=".urlencode($db).
			"&cateuid=".urlencode($cateuid).
			"&pern=".urlencode($pern) .
			"&sc_column=".urlencode($sc_column).
			"&sc_string=" . urlencode(stripslashes($sc_string)) .
			"&priv=" . urlencode($priv) .
			"&page=".urlencode($page).
			"&sdate=".urlencode($sdate).
			"&edate=".urlencode($edate).
			"&search=".urlencode($search).
			"&pay_cate=".urlencode($pay_cate).
			"&term_id=".urlencode($term_id)
			;

include_once("./dbinfo.php"); // $dbinfo, $table 값 정의

// 인증 체크
if(!privAuth($dbinfo, "priv_list",1)) back("이용이 제한되었습니다.(레벨부족)");

//===================
// SQL문 where절 정리
//===================
$sql_where = "1=1";
if(trim($act)){
	if(trim($sdate) && trim($edate)){
		$ssdate = strtotime($sdate);
		$eedate = strtotime(date('Y-m-d', strtotime($edate) + 86400)) ;
		if($ssdate > $eedate) back("기간이 잘못설정되었습니다");
			$sql_where .= " and (`rdate` >= {$ssdate}) AND (`rdate` < {$eedate} ) ";
	}

	if(trim($name))		$sql_where .=" AND (`name` LIKE '%" . db_escape($name) . "%') ";
	if(trim($email))	$sql_where .=" AND (`email` LIKE '%" . db_escape($email) . "%') ";
	if(trim($idnum))	$sql_where .=" AND (`idnum` LIKE '%" . db_escape($idnum) . "%') ";
	if(trim($userid))	$sql_where .=" AND (`userid` LIKE '%" . db_escape($userid) . "%') ";
	if(trim($level))	$sql_where .=" AND (`level` = '" . db_escape($level) . "') ";
	if(trim($tel))		$sql_where .=" AND (`tel` LIKE '%" . db_escape($tel) . "%') ";
	if(trim($search))	$sql_where .=" AND (`region` LIKE '%" . db_escape($search) . "%') ";
}

if(trim($sc_string)){
	$sc_string_escaped = db_escape($sc_string);
	if($sc_column)
		$sql_where .=" and (`" . db_escape($sc_column) . "` LIKE '%{$sc_string_escaped}%') ";
	else
		$sql_where .=" and ((`userid` LIKE '%{$sc_string_escaped}%') or (`title` LIKE '%{$sc_string_escaped}%') or (`content` LIKE '%{$sc_string_escaped}%')) ";
}

$title_priv = "전체회원";
if (trim($priv)){
	switch ($priv){
		case 'person': $title_priv = "일반회원"; break;
		case 'researcher': $title_priv = "내부연구원"; break;
		case 'expert': $title_priv = "중간관리자"; break;
		case 'company': $title_priv = "업체회원"; break;
		case 'root': $title_priv = "최고관리자"; break;
		case '운영자': $title_priv = "최고관리자"; break;
		default : $title_priv = "전체회원"; break;
	}
	$sql_where .= " and `priv` LIKE '%" . db_escape($priv) . "%'";
}

//=====
// misc
//=====
// 페이지 나눔등 각종 카운트 구하기
$count_total_result = db_arrayone("SELECT count(*) as cnt FROM {$table_logon} WHERE  $sql_where ");
$count['total'] = $count_total_result['cnt'] ?? 0;
$count=board2Count($count['total'],$page,($dbinfo['pern'] ?? 10),($dbinfo['page_pern'] ?? 10)); // 각종 카운트 구하기
$count_today_result = db_arrayone("SELECT count(*) as cnt FROM {$table_logon} WHERE (rdate > unix_timestamp(curdate())) and $sql_where ");
$count['today'] = $count_today_result['cnt'] ?? 0;

$limitno	= (int)($_GET['limitno'] ?? $count['firstno']);
$limitrows	= (int)($_GET['limitrows'] ?? $count['pern']);

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
$tpl->set_file('html',"index.htm",TPL_BLOCK); // here 1 mean extract blocks

// Limit로 필요한 게시물만 읽음.
$sql = "SELECT * FROM {$table_logon} WHERE $sql_where ORDER BY `name`, `userid` LIMIT {$limitno},{$limitrows}";
$rs_list = db_query($sql);
	
if(!$total=db_count($rs_list)) {	// 게시물이 하나도 없다면...
	if($sc_string) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($sc_string),ENT_QUOTES));
		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면. .
		$tpl->process('LIST', 'nolist');
}
else{
	if(!isset($dbinfo['row_pern']) or ($dbinfo['row_pern'] ?? 0) < 1 or !$tpl->get_var('cell')){
		$dbinfo['row_pern'] = 1;
	} // 스킨에 cell 블럭이 없으면, row_pern을 1로 바꿈

	for($i=0; $i<$total; $i+=$dbinfo['row_pern']){ // Added `?? 1` to prevent division by zero
		if($dbinfo['row_pern'] > 1){
			$tpl->drop_var('blockloop');
			$tpl->drop_var('CELL');
		}
		
		for($j=$i; ($j-$i < $dbinfo['row_pern']) && ($j < $total); $j++) { // 한줄에 여러값 출력시 루틴
			if( $j>=$total ){
				if($dbinfo['row_pern'] > 1){
					$tpl->process('CELL','nocell',TPL_APPEND);
				}
				continue;
			}
			$list		= db_array($rs_list);
			$list['no']	= $count['lastnum'];
			$list['rede']	= strlen($list['re'] ?? '');
			$list['rdate']= isset($list['rdate']) ? date("Y/m/d", $list['rdate']) : "";	//	날짜 변환
			switch ($list['priv'] ?? ''){
				case 'person': $list['priv_text'] = "일반회원"; break;
				case 'researcher': $list['priv_text'] = "내부연구원"; break;
				case 'expert': $list['priv_text'] = "중간관리자"; break;
				case 'company': $list['priv_text'] = "업체회원"; break;
				case 'root': $list['priv_text'] = "최고관리자"; break;
				case '운영자': $list['priv_text'] = "최고관리자"; break;
				default: $list['priv_text'] = $list['priv']; break;
			}
			
			//제목과 내용 자르기 :: 정대입
			$list['cut_title'] = cut_string($list['title'], $dbinfo['cut_length']);
			$list['cut_content'] = cut_string($list['content'],300);
			
			$list['data1_1checked'] = "";
			$list['data1_2checked'] = "";
			if ($list['data1'] == '1') $list['data1_1checked'] = " checked";
			else $list['data1_2checked'] = " checked";
			
			if(empty($list['title'])) $list['title'] = "제목없음…";

			//	Search 단어 색깔 표시
			if($sc_string){
				$highlight_pattern = '/(' . preg_quote($sc_string, '/') . ')/i';
				$highlight_replacement = '<font color=darkred>$1</font>';
				if($sc_column){
					$list[$sc_column] = preg_replace($highlight_pattern, $highlight_replacement, $list[$sc_column]);
				} else {
					$list['userid']	= preg_replace($highlight_pattern, $highlight_replacement, $list['userid']);
					$list['title']= preg_replace($highlight_pattern, $highlight_replacement,	$list['title']);
				}
			}

			// 업로드파일 처리
			if($dbinfo['enable_upload']!='N' and $list['upfiles']) {
				$upfiles=unserialize($list['upfiles']);
				if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
					$upfiles['upfile'][name]=$list['upfiles'];
					$upfiles['upfile'][size]=(int)$list['upfiles_totalsize'];
				}
				foreach($upfiles as $key => $value) {
					if($value['name'])
						$upfiles[$key]['href']="download.php?" . href_qs("uid={$list['uid']}&name={$key}",$qs_basic);
				} // end foreach
				$list['upfiles']=$upfiles;
				unset($upfiles);
			} // end if 업로드파일 처리

			// URL Link...
			$href['read']		= "read.php?" . href_qs("uid={$list['uid']}",$qs_basic);
			$href['list']		= "list.php?db=".urlencode($db);
			$href['download']	= "download.php?db=".urlencode($db) . "&uid={$list['uid']}";

			// 템플릿 YESRESULT 값들 입력
			$tpl->set_var('href.read'		,htmlspecialchars($href['read'], ENT_QUOTES, 'UTF-8'));
			$tpl->set_var('href.download'	,htmlspecialchars($href['download'], ENT_QUOTES, 'UTF-8'));
			$tpl->set_var('href.list'		,htmlspecialchars($href['list'], ENT_QUOTES, 'UTF-8'));
			$tpl->set_var('list'			,$list);

			$count['lastnum']--;
			
			if($dbinfo['row_pern'] >= 1) {
				if($j==0) $tpl->drop_var('blockloop');
				else $tpl->set_var('blockloop',true);
				$tpl->process('CELL','cell',TPL_APPEND);
			}
	
			// 업로드부분 템플릿내장값 지우기
			if(isset($list['upfiles']) && is_array($list['upfiles'])){
				foreach($list['upfiles'] as $key => $value){
					$tpl->drop_var('list.upfiles.'.$key, $list['upfiles'][$key]);
				}
			}

		} // end for (j)
		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
		$tpl->set_var('blockloop',true);
	} // end for (i)
	//	템플릿내장값 지우기
	$tpl->drop_var('blockloop');
	if(isset($href['read'])) unset($href['read']);
	if(isset($href['download'])) unset($href['download']);
	if(isset($list)){
		foreach($list as $key => $value){
			if(is_array($value)){
				foreach($value as $key2 => $value2){
					$tpl->drop_var('list.'.$key.'.'.$key2);
				}
			} else {
				$tpl->drop_var('list.'.$key);
			}
		}
		unset($list);
	}
} // end if (게시물이 있다면...)

${"t_ch{$term_id}"} = " checked";
$tpl->set_var("t_ch{$term_id}"			,${"t_ch{$term_id}"});
$tpl->set_var("term_id"			,$term_id);

${"s_{$search}"} = " selected";
$tpl->set_var("s_{$search}"			,${"s_{$search}"});
$tpl->set_var("search"				,$search);
$tpl->set_var("title_priv"			,$title_priv);
$tpl->set_var("priv"			,$priv);

$tpl->set_var('sdate'		,$sdate);
$tpl->set_var('edate'		,$edate);
$tpl->set_var("name"			,$name);
$tpl->set_var("email"			,$email);
$tpl->set_var("idnum"			,$idnum);
$tpl->set_var("userid"			,$userid);

// 템플릿 마무리 할당
$tpl->set_var('dbinfo'			,$dbinfo);
$tpl->set_var('count'			,$count);
$tpl->set_var('href'			,$href);
$tpl->set_var('sc_string'		,htmlspecialchars(stripslashes($sc_string),ENT_QUOTES));
$tpl->set_var('form_search'		,$form_search);
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
	if ($count['nowblock']>1) $tpl->process('PREVBLOCK','prevblock');
	else $tpl->process('PREVBLOCK','noprevblock');
	for ($i=$count['firstpage'];$i<=$count['lastpage'];$i++) {
		$tpl->set_var('blockcount',$i);
		if($i == $count['nowpage'])
			$tpl->process('BLOCK','noblock',TPL_APPEND);
		else {
			$tpl->set_var('href.blockcount', htmlspecialchars($_SERVER['PHP_SELF'] . "?" . href_qs("page=".$i,$qs_basic), ENT_QUOTES, 'UTF-8') );
			$tpl->process('BLOCK','block',TPL_APPEND);
		}	
	}
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
