<?php
//=======================================================
// 설	명 : 심플리스트(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/22
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/08/22 박선민 마지막 수정
// PHP 7 호환성 업데이트 (eregi_replace -> preg_replace)
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

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'sdate', 'edate', 'search', 'pay_cate', 'term_id', 'act', 'email', 'idnum', 'userid', 'level', 'tel', 'priv', 'total_num'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
// 기본 URL QueryString
$qs_basic = "db={$db}".					//table 이름
			"&mode=".					// mode값은 list.php에서는 당연히 빈값
			"&cateuid={$cateuid}".		//cateuid
			"&pern={$pern}" .	// 페이지당 표시될 게시물 수
			"&sc_column={$sc_column}".	//search column
			"&sc_string=" . urlencode(stripslashes($sc_string)) . //search string
			"&page={$page}";				//현재 페이지

include_once("./dbinfo.php"); // $dbinfo, $table 값 정의

// 인증 체크
if(!privAuth($dbinfo, "priv_list",1)) back("이용이 제한되었습니다.(레벨부족)");

//===================
// SQL문 where절 정리
//===================
// 서치 게시물만..
if(trim($sc_string)){
	if($sc_column)
		$sql_where .=" ({$sc_column} like '%{$sc_string}%') ";
	else
		$sql_where .=" ((userid like '%{$sc_string}%') or (title like '%{$sc_string}%') or (content like '%{$sc_string}%')) ";
}
if(!$sql_where) $sql_where= " {$table_logon_retire}.uid = {$table_userinfo_retire}.bid and {$table_logon_retire}.userid = '{$userid}' ";

//=====
// misc
//=====
// 페이지 나눔등 각종 카운트 구하기
$count['total']=db_result(db_query("SELECT count(*) FROM {$table_logon_retire_retire} WHERE  $sql_where "), 0, "count(*)"); // 전체 게시물 수
$count=board2Count($count['total'],$page,$dbinfo['pern'],$dbinfo['page_pern']); // 각종 카운트 구하기
$count['today']=db_result(db_query("SELECT count(*) FROM {$table_logon_retire_retire} WHERE (rdate > unix_timestamp(curdate())) and $sql_where ") , 0, "count(*)");

// 서치 폼의 hidden 필드 모두!!
$form_search =" name=search action='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "' method='post'>
				<input type='hidden' name='db' value='$db'>
				<input type='hidden' name='cateuid' value='{$cateuid}'>
				<input type='hidden' name='pern' value={$count['pern']}
			";

// URL Link...
$href['write']	= "write.php?" . href_qs("mode=write",$qs_basic);	// 글씨기
if($count['nowpage'] > 1) { // 처음, 이전 페이지
	$href['firstpage']="{$_SERVER['PHP_SELF']}?" . href_qs("page=1",$qs_basic);
	$href['prevpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']-1),$qs_basic);
} else {
	$href['firstpage']="#";
	$href['prevpage']	="#";
}
if($count['nowpage'] < $count['totalpage']){ // 다음, 마지막 페이지
	$href['nextpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']+1),$qs_basic);
	$href['lastpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=".$count['totalpage'],$qs_basic);
} else {
	$href['nextpage']	="#";
	$href['lastpage'] ="#";
}
$href['prevblock']= ($count['nowblock']>1)					? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['firstpage']-1) ,$qs_basic): "#";// 이전 페이지 블럭
$href['nextblock']= ($count['totalpage'] > $count['lastpage'])? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['lastpage'] +1),$qs_basic) : "#";// 다음 페이지 블럭

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$tpl = new phemplate("stpl/{$dbinfo['skin']}/","remove_nonjs");
$tpl->set_file('html',"retire_detail.htm",1); // here 1 mean extract blocks
//방금위의 $_GET['skin']값이 들어간 이유는 박선민(sponsor@new21.com)에게 물어보기바람

// Limit로 필요한 게시물만 읽음.
$_GET['limitno'] = $_GET['limitno'] ? $_GET['limitno'] : $count['firstno'];
$_GET['limitrows'] = $_GET['limitrows'] ? $_GET['limitrows'] : $count['pern'];
$rs_list = db_query("SELECT * from {$table_logon}_retire WHERE $sql_where ORDER BY c_name, userid LIMIT {$_GET['limitno']},{$_GET['limitrows']}");
if(!$total=db_count()) {	// 게시물이 하나도 없다면...
	if($sc_string) { // 서치시 게시물이 없다면..
		$tpl->set_var('sc_string',htmlspecialchars(stripslashes($sc_string),ENT_QUOTES));

		$tpl->process('LIST', 'nosearch');
	}
	else // 게시물이 없다면. .
		$tpl->process('LIST', 'nolist');
}
else{
	if($dbinfo['row_pern']<1) $dbinfo['row_pern']=1; // 한줄에 여러값 출력이 아닌 경우
	for($i=0; $i<$total; $i+=$dbinfo['row_pern']){
		if($dbinfo['row_pern'] > 1) $tpl->set_var('CELL',"");

		for($j=$i; ($j-$i < $dbinfo['row_pern']) && ($j < $total); $j++) { // 한줄에 여러값 출력시 루틴
			if( $j>=$total ){
				if($dbinfo['row_pern'] > 1) $tpl->process('CELL','nocell',TPL_APPEND);
				continue;
			}
			$list		= db_array($rs_list);
			$list['no']	= $count['lastnum'];
			$list['rede']	= strlen($list['re']);
			$list['rdate']= $list['rdate'] ? date("Y/m/d", $list['rdate']) : "";	//	날짜 변환

			if($list['wedding'] > 0){
				$list['wedding_ymd'] = $list['wedding'] ? date("Y/m/d", $list['wedding']) : "";	//	날짜 변환
				$list['wedding_ymd'] = $list['wedding_ymd']." (기혼)";
			}
			else $list['wedding_ymd'] = "(미혼)";

			if($list['wedding_yesno'] != ""){
				$list['wedding_yesno'] = $list['wedding_yesno'] ? date("Y/m/d", $list['wedding_yesno']) : "";	//	날짜 변환
				$list['wedding_yesno'] = $list['wedding_yesno']." (기혼)";
			}
			else $list['wedding_yesno'] = "(미혼)";

			$list['c_opendate']= $list['c_opendate'] ? date("Y/m/d", $list['c_opendate']) : "";	//	날짜 변


			//생일변환
			$list['birth_m']	= substr($list['birth'], 0, 2);
			$list['birth_d']	= substr($list['birth'], 2, 2);
			$list['birth_c']	= substr($list['birth'], 4, 1);
			$list['birth_y']	= substr($list['birth'], 5, 4);
			if ($list['birth_c'] == '-' ) $list['birth_c'] = ' (음력) ';
			else if ($list['birth_c'] == '+' ) $list['birth_c'] = ' (양력) ';

			if ($list['birth'] ) $list['birth_date'] = $list['birth_y']."/".$list['birth_m']."/".$list['birth_d']." &nbsp; ".$list['birth_c'];

			//제목과 내용 자르기 :: 정대입
			$list['cut_title'] = cut_string($list['title'], $dbinfo['cut_length']);
			$list['cut_content'] = cut_string($list['content'],300);

			$list['data1_1checked'] = "";
			$list['data1_2checked'] = "";
			if ($list['data1'] == '1') $list['data1_1checked'] = " checked";
			else $list['data1_2checked'] = " checked";

			if(!$list['title']) $list['title'] = "제목없음…";

			//	Search 단어 색깔 표시
			if($sc_string){
				// PHP 7 호환성을 위해 eregi_replace 대신 preg_replace 사용. 검색어 내 특수문자 오류 방지를 위해 preg_quote 사용
				$preg_sc_string = preg_quote($sc_string, '/');
				if($sc_column){
					if($sc_column == "title")
						$list['cut_title'] = preg_replace("/(" . $preg_sc_string . ")/i", "<font color=darkred>$1</font>",	$list['cut_title']);
					else
						$list[$sc_column]	= preg_replace("/(" . $preg_sc_string . ")/i", "<font color='darkred'>$1</font>", $list[$sc_column]);
				} else {
					$list['userid']	= preg_replace("/(" . $preg_sc_string . ")/i", "<font color=darkred>$1</font>", $list['userid']);
					$list['cut_title']= preg_replace("/(" . $preg_sc_string . ")/i", "<font color=darkred>$1</font>",	$list['cut_title']);
				}
			}

			// 업로드파일 처리
			if($dbinfo['enable_upload'] != 'N' and $list['upfiles']){
				$upfiles=unserialize($list['upfiles']);
				if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
					$upfiles['upfile']['name']=$list['upfiles'];
					$upfiles['upfile']['size']=(int)$list['upfiles_totalsize'];
				}
				foreach($upfiles as $key =>	$value){
					if($value['name'])
						$upfiles[$key]['href']="download.php?" . href_qs("uid={$list['uid']}&name={$key}",$qs_basic);
				} // end foreach
				$list['upfiles']=$upfiles;
				unset($upfiles);
			} // end if 업로드파일 처리

			//마지막 로그인 시간
			$sql = "select * from {$SITE['th']}log_lastlog where bid={$list['uid']}";
			$list_lastlog = db_arrayone($sql);
			$list_lastlog['rdate'] = date("Y/m/d H:i:s", $list_lastlog['rdate']);

			// 접속횟수
			$sql = "select count(*) as cnt from {$SITE['th']}log_wtmp where bid={$list['uid']}";
			$list_wtmp = db_arrayone($sql);


			// URL Link...
			$href['read']		= "read.php?" . href_qs("uid={$list['uid']}",$qs_basic);
			$href['list']	= "list.php?db={$db}";
			$href['download']	= "download.php?db={$db}&uid={$list['uid']}";

			// 템플릿 YESRESULT 값들 입력
			$tpl->set_var('href.read'		,$href['read']);
			$tpl->set_var('href.download'	,$href['download']);
			$tpl->set_var('href.list'		,$href['list']);
			$tpl->set_var('list'			,$list);
			$tpl->set_var('list_lastlog'			,$list_lastlog);
			$tpl->set_var('list_wtmp.cnt'			,$list_wtmp['cnt']);
			$tpl->set_var('count.lastnum'	,$count['lastnum']--);

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
			$tpl->set_var('href.blockcount', "{$_SERVER['PHP_SELF']}?" . href_qs("page=".$i,$qs_basic) );
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
if($_GET['skin']){
	echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); // 1 mean loop
}
else {
	switch($dbinfo['html_headpattern']){
		case "ht":
			// 전체 홈페이지 템플릿 읽어오기
		$HEADER['header'] == 2;
			if( $dbinfo['html_headtpl'] != "" and is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");
			echo $SITE['head'] . $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); // 1 mean loop
			echo $dbinfo['html_tail'] . $SITE['tail'];
			break;
		case "h":
			// 전체 홈페이지 템플릿 읽어오기
		$HEADER['header'] == 2;
			if( $dbinfo['html_headtpl'] != "" and is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");

			echo $SITE['head'] . $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); // 1 mean loop
			echo $dbinfo['html_tail'];
			break;
		case "t":
			// 전체 홈페이지 템플릿 읽어오기
		$HEADER['header'] == 2;
			if( $dbinfo['html_headtpl'] != "" and is_file("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php") )
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_{$dbinfo['html_headtpl']}.php");
			else
				@include_once("{$_SERVER['DOCUMENT_ROOT']}/stpl/basic/index_basic.php");

			echo $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); // 1 mean loop
			echo $dbinfo['html_tail'] . $SITE['tail'];
			break;
		default:
			echo $dbinfo['html_head'];
			echo preg_replace("/([\"|\'])images\//", "{$val}", $tpl->process('', 'html')); // 1 mean loop
			echo $dbinfo['html_tail'];
	} // end switch
} // end if
?>