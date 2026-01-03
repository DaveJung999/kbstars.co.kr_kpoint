<?php
//=======================================================
// 설 명 : 관리자 페이지 : 그룹정보 서치
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/04
// Project: sitePHPbasic
// ChangeLog
// DATE 수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/04 박선민 처음
// 25/08/13 Gemini	PHP7 및 mariadb 11 버전 업그레이드 대응
//=======================================================
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useApp'	 => 1, // cut_string()
	'useBoard2'	 => 1, // board2Count()
	'useSkin'	 => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	include_once($thisPath.'config.php');

	// table
	$table_logon		= $SITE['th'].'logon';
	$table_groupinfo	= $SITE['th'].'groupinfo';
	$table_joininfo		= $SITE['th'].'joininfo';
	$table_joininfo_cate= $SITE['th'].'joininfo_cate';
	$table_payment		= $SITE['th'].'payment';
	$table_service		= $SITE['th'].'service';
	$table_log_userinfo	= $SITE['th'].'log_userinfo';
	$table_log_wtmp		= $SITE['th'].'log_wtmp';
	$table_log_lastlog	= $SITE['th'].'log_lastlog';
	
	// uid=???, hp=???, order=??? 처럼 짧은키워드 검색 지원
	if(isset($_GET['gid'])) {$_GET['gsc_column']='groupinfo.uid';$_GET['gsc_string']=$_GET['gid'];}
	elseif(isset($_GET['groupid'])) {$_GET['gsc_column']='groupinfo.groupid';$_GET['gsc_string']=$_GET['groupid'];}
	elseif(!isset($_GET['gsc_column'])) $_GET['gsc_column']='groupinfo.uid';
	if(!isset($_GET['gsc_string'])) $_GET['gsc_string']='%%';
	
	// 기본 URL QueryString
	$qs_basic		= "gsc_column={$_GET['gsc_column']}&gsc_string={$_GET['gsc_string']}";
	
	/////////////////////////////////
	// $sql문 결정 (Limit ?,? 부분 제외)
	$sql_table= explode('.',$_GET['gsc_column']);
	if(sizeof($sql_table)!=2) go_url($_SERVER['PHP_SELF']);

	if( preg_match('/%/', $_GET['gsc_string']) ) {
		if($_GET['gsc_string']=='%') $_GET['gsc_string'] = '%%';
		$sql_where	= " `({$SITE['th']}{$sql_table['0']}`.{$sql_table['1']} like '{$_GET['gsc_string']}') ";
	}
	else $sql_where	= " `({$SITE['th']}{$sql_table['0']}`.{$sql_table['1']} = '{$_GET['gsc_string']}') ";

	switch ($sql_table['0']) {
		case 'groupinfo' :
			$sql="select *,". $SITE['th'].$sql_table['0'].".".$sql_table['1']." as gsc_column from {$table_groupinfo} where  $sql_where ";
			break;
		case 'logon' :
			$sql="select {$table_groupinfo}.*,{$SITE['th']}{$sql_table['0']}.{$sql_table['1']} as gsc_column from {$table_logon}, {$table_groupinfo} where $sql_where and {$table_groupinfo}.uid={$table_logon}.uid";
			break;
		default :
			back('지원하지 않는 서치 옵션을 선택하였습니다. 관리자에게 문의 바랍니다');
	} // end switch
	$rs_gsearch=db_query($sql);
	$count_gsearch = db_count($rs_gsearch);

	// - 결과값이 한명이 아니라면, 서치 페이지로 이동시킴
	if(!isset($_GET['mode']) or !is_file("{$_GET['mode']}.php") or !preg_match('/^[a-z0-9]+$/i',$_GET['mode']) or $_GET['mode']=='gsearch') $_GET['mode'] = 'gjoininfo';
	if($count_gsearch!=1) go_url("gsearch.php?mode=gjoininfo&gsc_column={$_GET['gsc_column']}&gsc_string={$_GET['gsc_string']}");
	$groupinfo	= db_array($rs_gsearch);
	$sql = "select * from {$table_logon} where uid='{$groupinfo['bid']}'";
	$grupinfo['logon']= db_arrayone($sql);	
	/////////////////////////////////

	$dbinfo['table'] = $table_joininfo;
	$sw_catelist = CATELIST_VIEW | CATELIST_VIEW_CATE_DEPTH;
	if(strlen($_GET['sc_string'])) $sw_catelist &= ~CATELIST_VIEW_DATACOUNT;
	$cateinfo=board2CateInfo($dbinfo, $_GET['cateuid'], 'Y', 1,1,$tmp_itemcount,"(종합)");

	// sql_where 구하기
	$sql_where = " gid='{$groupinfo['uid']}' ";
	// 해당 카테고리만 볼려면
	if(is_array($cateinfo['subcate_uid']) and sizeof($cateinfo['subcate_uid'])>0 ) $sql_where = $sql_where ? $sql_where . " and ( cateuid in ( " . implode(",",$cateinfo['subcate_uid']) . ") ) " : " ( cateuid in ( " . implode(",",$cateinfo['subcate_uid']) . ") ) ";
	// 게시판 특정 서치 게시물만..
	if(isset($_GET['sc_string']) && trim($_GET['sc_string'])) {
		if($sql_where) $sql_where .= " and ";
		if(isset($_GET['sc_column']))
			if(in_array($_GET['sc_column'],array("bid","uid")))
				$sql_where .=" ({$_GET['sc_column']}='{$_GET['sc_string']}') ";
			else
				$sql_where .=" ({$_GET['sc_column']} like '%{$_GET['sc_string']}%') ";
	}
	if(!isset($sql_where) || !$sql_where) $sql_where = ' 1 ';

	// 페이지 나눔등 각종 카운트 구하기
	$count['total']=db_result(db_query("SELECT count(*) FROM {$table_joininfo} WHERE  $sql_where "), 0, "count(*)"); // 전체 게시물 수
	$count=board2Count($count['total'],$page,$dbinfo['pern'],$dbinfo['page_pern']); // 각종 카운트 구하기
	$count['today']=db_result(db_query("SELECT count(*) FROM {$table_joininfo} WHERE (rdate > unix_timestamp(curdate())) and $sql_where ") , 0, "count(*)");

	//============================
	// SQL문 order by..부분 만들기
	//============================
	switch(isset($_GET['sort']) ? $_GET['sort'] : '') {
		case "rdate": $sql_orderby = "rdate"; break;
		case "!rdate":$sql_orderby = "rdate DESC"; break;
		case "level": $sql_orderby = "level"; break;
		case "!level":$sql_orderby = "level DESC"; break;
		default :
			$sql_orderby = isset($dbinfo['orderby']) ? $dbinfo['orderby'] : " cateuid DESC ";
	}


	//페이지 블럭 표시
	// <-- (이전블럭) 부분
	if ($count['nowblock']>1)
		$html['blockcount'] .= "<a href='{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['firstpage']-1) ,$qs_basic) . "'>◀</a>&nbsp;";
	else
		$html['blockcount'] .= "◀&nbsp;";
	// 1 2 3 4 5 부분
	for ($i=$count['firstpage'];$i<=$count['lastpage'];$i++) {
		if($i!=$count['nowpage'])
			$html['blockcount'] .= "<a href='{$_SERVER['PHP_SELF']}?" . href_qs("page=".$i,$qs_basic) . "'>[$i]</a>&nbsp;";
		else
			$html['blockcount'] .= "<b>[{$i}]</b>&nbsp;";
	}
	// --> (다음블럭) 부분
	if ($count['totalpage'] > $count['lastpage'] )
		$html['blockcount'] .= "<a href='{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['lastpage'] +1),$qs_basic) . "'>▶</a>";
	else
		$html['blockcount'] .= "▶";

	// 서치 폼의 hidden 필드 모두!!
	$form_search =" name=search action='{$_SERVER['PHP_SELF']}' method='post'>
					<input type='hidden' name='db' value='{$db}'>
					<input type='hidden' name='cateuid' value='{$_GET['cateuid']}'>
					<input type='hidden' name='pern' value='{$count['pern']}'
				";

	// URL Link...
	$href['list']	= "{$_SERVER['PHP_SELF']}?" . href_qs("page=&sc_column=&sc_string=",$qs_basic);
	$href['listthis']	= "{$_SERVER['PHP_SELF']}?{$qs_basic}";

	if($count['nowpage'] > 1) { // 처음, 이전 페이지
		$href['firstpage']="{$_SERVER['PHP_SELF']}?" . href_qs("page=1",$qs_basic);
		$href['prevpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']-1),$qs_basic);
	}
	else {
		$href['firstpage']="javascript: void(0)";
		$href['prevpage']	="javascript: void(0)";
	}
	if($count['nowpage'] < $count['totalpage']){ // 다음, 마지막 페이지
		$href['nextpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['nowpage']+1),$qs_basic);
		$href['lastpage']	="{$_SERVER['PHP_SELF']}?" . href_qs("page=".$count['totalpage'],$qs_basic);
	}
	else {
		$href['nextpage']	="javascript: void(0)";
		$href['lastpage'] ="javascript: void(0)";
	}
	$href['prevblock']= ($count['nowblock']>1)					? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['firstpage']-1) ,$qs_basic): "javascript: void(0)";// 이전 페이지 블럭
	$href['nextblock']= ($count['totalpage'] > $count['lastpage'])? "{$_SERVER['PHP_SELF']}?" . href_qs("page=" . ($count['lastpage'] +1),$qs_basic) : "javascript: void(0)";// 다음 페이지 블럭
	

	// 카테고리 네비게이션 출력
	$cate_nevi = "<a href='{$_SERVER['PHP_SELF']}?" . href_qs("cateuid=",$qs_basic) . "'>TOP</a> > ";
	if(is_array($cateinfo['highcate'])) {
		foreach($cateinfo['highcate'] as $key => $value) {
			$cate_nevi .= " <a href='{$_SERVER['PHP_SELF']}?" . href_qs("cateuid=".$key,$qs_basic) ."'>{$value}</a> > ";
		}
	} // end if
	$cate_nevi .= " {$cateinfo['title']}";
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
	$skinfile=basename(__FILE__,'.php').'.html';
	if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
	$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
	$tpl->set_file('html',$skinfile,TPL_BLOCK);

// Limit로 필요한 게시물만 읽음.
	$sql = "SELECT * FROM {$table_joininfo} WHERE $sql_where ORDER BY	{$sql_orderby}	LIMIT {$count['firstno']},{$count['pern']}";
	$rs_list = db_query($sql);
// 해당 게시물 불러들임
	if(!$total=db_count($rs_list)) {
		if(isset($_GET['sc_string']) && $_GET['sc_string']) { // 서치시 게시물이 없다면..
			$tpl->set_var('sc_string',htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));
			$tpl->process('LIST', 'nosearch');
		}
		else // 게시물이 없다면..
			$tpl->process('LIST', 'nolist');
	}
	else {
		for($i=0;$i<$total;$i++) {
			$list = db_array($rs_list);
			$list['no']	= $count['lastnum'];
			$list['rede']	= strlen($list['re']);
			$list['rdate_date']= isset($list['rdate']) && $list['rdate'] ? date("Y/m/d", $list['rdate']) : "";	//	날짜 변환
			if(!isset($list['title']) || !$list['title']) $list['title'] = "제목없음…";
			$list['logon'] = db_arrayone("select userid, name,uid from {$table_logon} where uid='{$list['bid']}'");
			$list['userinfo'] = db_arrayone("select hp from {$table_userinfo} where bid='{$list['bid']}'");

			// URL Link...
			//$href['read']		= "read.php?" . href_qs("uid={$list['uid']}",$qs_basic);
			$href['listcate']	= "{$_SERVER['PHP_SELF']}?" . href_qs("uid={$list['uid']}",$qs_basic);
			$href['modify']= "gjoininfoadd.php?" . href_qs("mode=modify&bid={$list['bid']}&gid={$list['gid']}",$qs_basic);
			$href['delete']="./groupok.php?mode=gjoininfodelete&gid={$list['gid']}&bid={$list['bid']}";


			$href['searchlogon']="search.php?mode=search&sc_column=logon.uid&sc_string={$list['bid']}";

			// 	카테고리 네비게이션 만들기
			$list['cate_nevi']="";
			if(isset($list['cateuid']) && $list['cateuid']) {
				$dbinfo_tmp = array(
						'table_cate' 		 =>	$table_joininfo_cate,
						'sql_where_cate'	 =>	" gid='{$groupinfo['uid']}' ",
					);
				$list['cate_nevi'] = userGetCateNevi($dbinfo_tmp,$list['cateuid']);
				$list['cate_nevi'] = "<a href='{$_SERVER['PHP_SELF']}?" . href_qs("cateuid=".$list['cateuid'],$qs_basic) ."'>{$list['cate_nevi']}</a>";
			}
			
			$tpl->set_var('href.modify',$href['modify']);
			$tpl->set_var('href.delete',$href['delete']);
			$tpl->set_var('href.go',$href['go']);
			$tpl->set_var('list',$list);
			$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
		} // end for
		
		$tpl->drop_var('href.modify'); unset($href['modify']);
		$tpl->drop_var('href.delete'); unset($href['delete']);
		foreach($list as $key => $value) $tpl->drop_var('list.'.$key);
		unset($list);
	} // end if.. else..

// 템플릿 마무리 할당
// - 게시판 부분
	$tpl->set_var('cate_nevi'		,$cate_nevi);
	$tpl->set_var('dbinfo'			,$dbinfo);
	$tpl->set_var('count'			,$count);	// 게시판 각종 카운트
	$tpl->set_var('get'				,$_GET);

	$href['write']	= "{$thisUrl}/gjoininfoadd.php?" . href_qs("mode=write",$qs_basic);	// 글씨기
	$tpl->set_var('href'			,$href);	// 게시판 각종 링크

// - boardlist 부분
	$form_boardlist = "method='post' action='groupok.php'>";
	$form_boardlist .= substr(href_qs("mode=any&gid={$groupinfo['uid']}",$qs_basic,1),0,-1);
	$tpl->set_var('form_boardlist',$form_boardlist);

// - 서치 부분
	$form_search =" action='{$_SERVER['PHP_SELF']}' method='get'>";
	$form_search .= substr(href_qs('page=',$qs_basic,1),0,-1);
	$tpl->set_var('form_search'		,$form_search);
	$tpl->set_var('get.sc_string'		,htmlspecialchars(stripslashes($_GET['sc_string']),ENT_QUOTES));	// 서치 단어
	$tpl->set_var('sort_'.(isset($_GET['sort']) ? $_GET['sort'] : ''),true);	// sort_???

// - 회원전체 서치 부분
	$tpl->tie_var('groupinfo',$groupinfo);
	$tpl->set_var('count_gsearch',$count_gsearch);
	$tpl->set_var('get.gsc_string',htmlspecialchars(stripslashes($_GET['gsc_string']),ENT_QUOTES));
	$form_gsearch = " method=get action='{$_SERVER['PHP_SELF']}'> ";
	$form_gsearch .= substr(href_qs("mode={$_GET['mode']}",'mode=',1),0,-1);
	$tpl->set_var('form_gsearch',$form_gsearch);


	if(!isset($_GET['limitrows'])) { // 게시물 일부 보기에서는 카테고리, 블럭이 필요 없을 것임
		// 블럭 : 첫페이지, 이전페이지
		if(isset($count['nowpage']) && $count['nowpage'] > 1) {
			$tpl->process('FIRSTPAGE','firstpage');
			$tpl->process('PREVPAGE','prevpage');
		}
		else {
			$tpl->process('FIRSTPAGE','nofirstpage');
			$tpl->process('PREVPAGE','noprevpage');
		}

		// 블럭 : 페이지 블럭 표시
			// <-- (이전블럭) 부분
			if (isset($count['nowblock']) && $count['nowblock']>1) $tpl->process('PREVBLOCK','prevblock');
			else $tpl->process('PREVBLOCK','noprevblock');
			// 1 2 3 4 5 부분
			for ($i=$count['firstpage'];$i<=$count['lastpage'];$i++) {
				$tpl->set_var('blockcount',$i);
				if($i==$count['nowpage'])
					$tpl->process('BLOCK','noblock',TPL_APPEND);
				else {
					$tpl->set_var('href.blockcount', "{$_SERVER['PHP_SELF']}?" . href_qs("page=".$i,$qs_basic) );
					$tpl->process('BLOCK','block',TPL_APPEND);
				}
			} // end for
			// --> (다음블럭) 부분
			if (isset($count['totalpage']) && isset($count['lastpage']) && $count['totalpage'] > $count['lastpage'] ) $tpl->process('NEXTBLOCK','nextblock');
			else $tpl->process('NEXTBLOCK','nonextblock');

		// 블럭 : 다음페이지, 마지막 페이지
		if(isset($count['nowpage']) && isset($count['totalpage']) && $count['nowpage'] < $count['totalpage']) {
			$tpl->process('NEXTPAGE','nextpage');
			$tpl->process('LASTPAGE','lastpage');
		}
		else {
			$tpl->process('NEXTPAGE','nonextpage');
			$tpl->process('LASTPAGE','nolastpage');
		}
	} // end if

// 블럭 : 글쓰기
	if(privAuth($dbinfo, "priv_write")) $tpl->process('WRITE','write');
	else $tpl->process('WRITE','nowrite');

// 마무리
	$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
	echo preg_replace('/([="\'])images/',$val,$tpl->process('', 'html',TPL_OPTIONAL));

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================

	function userGetCateNevi($dbinfo,$cateuid) {
		if(!isset($dbinfo['sql_where_cate']) || !$dbinfo['sql_where_cate']) $dbinfo['sql_where_cate'] = ' 1 ';
		
		$sql = "SELECT * FROM {$dbinfo['table_cate']} where {$dbinfo['sql_where_cate']} and uid='{$cateuid}'";
		if($cateinfo_tmp=db_arrayone($sql)) {
			if(isset($cateinfo_tmp['re']) && strlen($cateinfo_tmp['re'])) {
				// ( re='' or re='a' or re='ac' ) 만들기, re='aca"일때
				$tmp_sql_where_cate = " (re='' ";
				for($i=0;$i<strlen($cateinfo_tmp['re'])-1;$i++) {
						$tmp_sql_where_cate .= " or re='" . substr($cateinfo_tmp['re'],0,$i+1) ."' ";
				}
				$tmp_sql_where_cate .= " ) ";
				// 	카테고리 네비게이션 만들기
				$rs_listcate = db_query("SELECT * FROM {$dbinfo['table_cate']} WHERE {$dbinfo['sql_where_cate']} and num={$cateinfo_tmp['num']} and {$tmp_sql_where_cate} order by re");
				while($row=db_array($rs_listcate)) {
					$cate_nevi .= $row['title'] . " > ";
				}
				db_free($rs_listcate);
			} // end if
			$cate_nevi .= $cateinfo_tmp['title'];
		} // end if
		
		return $cate_nevi;
	} // end func
?>
