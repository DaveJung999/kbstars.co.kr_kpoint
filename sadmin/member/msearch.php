<?php
//=======================================================
// 설 명 : 관리자 페이지 : 지불정보, 회원로그정보 검색
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
	if(isset($_GET['bid'])) { $_GET['msc_column']='logon.uid';$_GET['msc_string']=$_GET['bid'];}
	elseif(isset($_GET['userid'])) { $_GET['msc_column']='logon.userid';$_GET['msc_string']=$_GET['userid'];}
	elseif(isset($_GET['tel'])) { $_GET['msc_column']='logon.tel';$_GET['msc_string']=$_GET['tel'];}
	elseif(isset($_GET['hp'])) { $_GET['msc_column']='logon.hp';$_GET['msc_string']=$_GET['hp'];}
	elseif(isset($_GET['order'])) { $_GET['msc_column']='payment.num';$_GET['msc_string']=$_GET['order'];}
	elseif(!isset($_GET['msc_column'])) $_GET['msc_column']='logon.userid';
	if(!isset($_GET['msc_string'])) $_GET['msc_string']='%';

	// 기본 URL QueryString
	$qs_basic		= href_qs("msc_column={$_GET['msc_column']}&msc_string={$_GET['msc_string']}");

	/////////////////////////////////
	// 회원 검색 및 회원정보 가져오기
	// - 넘어온값 체크
	$sql_table= explode('.',$_GET['msc_column']);
	if(sizeof($sql_table)!=2) go_url($_SERVER['PHP_SELF']);
	// - $sql_where
	if( preg_match('/%/', $_GET['msc_string']) ) {
		if($_GET['msc_string']=='%') $_GET['msc_string'] = '%%';
		$sql_where	= " `({$SITE['th']}{$sql_table['0']}`.{$sql_table['1']} like '{$_GET['msc_string']}') ";
	}
	else $sql_where	= " `({$SITE['th']}{$sql_table['0']}`.{$sql_table['1']} = '{$_GET['msc_string']}') ";
	// - 게시판 특정 서치 게시물만..
	if(isset($_GET['sc_string']) && trim($_GET['sc_string'])) {
		if(isset($sql_where)) $sql_where .= ' and ';
		if(isset($_GET['sc_column']))
			if(in_array($_GET['sc_column'],array('bid','uid')))
				$sql_where .=" ({$_GET['sc_column']}='{$_GET['sc_string']}') ";
			else
				$sql_where .=" ({$_GET['sc_column']} like '%{$_GET['sc_string']}%') ";
	}
	// - $sql문 완성
	switch ($sql_table['0']) {
		case 'logon' :
			$sql="select {$table_logon}.*, {$table_log_lastlog}.rdate as lastlog, `{$SITE['th']}{$sql_table['0']}`.{$sql_table['1']} as msc_column
				from {$table_logon}
				left join {$table_log_lastlog} on {$table_logon}.uid={$table_log_lastlog}.bid and {$table_log_lastlog}.gid=0
				where  $sql_where ";

				if(!isset($_GET['sort'])) $_GET['sort'] = '!lastlog'; // 마지막 로그인으로 소트하는 것이 더 좋을 것임
			break;
		/* 느려지면 아래 방식으로 바꿈)
		case 'logon' :
			$sql="select *, email as msc_column from `{$SITE['th']}{$sql_table['0']}` where  $sql_where ";
			break;
			*/
		case 'payment':
			$sql="select {$table_logon}.*, `{$SITE['th']}{$sql_table['0']}`.{$sql_table['1']} as msc_column from {$table_logon}, `{$SITE['th']}{$sql_table['0']}` where {$table_logon}.uid={$SITE['th']}{$sql_table['0']}.bid and  $sql_where ";
			break;
	} // end switch
	$rs_msearch=db_query($sql);
	$count_msearch = db_count($rs_msearch);

	// - 결과값이 한명이 아니라면, 서치 페이지로 이동시킴
	if(!isset($_GET['mode']) or !is_file($_GET['mode'].'.php') or !preg_match('/^[a-z0-9]+$/i',$_GET['mode']) or $_GET['mode']=='msearch') $_GET['mode'] = 'paymentinfo';
	if($count_msearch==1 and $_GET['msc_string']!='%') {
		$logon		= db_array($rs_msearch);
		go_url("{$_GET['mode']}.php?msc_column={$_GET['msc_column']}&msc_string={$logon['msc_column']}");
	}
	/////////////////////////////////

	//============================
	// SQL문 order by..부분 만들기
	//============================
	switch(isset($_GET['sort']) ? $_GET['sort'] : '') {
		case 'uid': $sql_orderby = 'uid'; break;
		case '!uid':$sql_orderby = 'uid DESC'; break;
		case 'priv': $sql_orderby = 'priv'; break;
		case '!priv':$sql_orderby = 'priv DESC'; break;
		case 'userid': $sql_orderby = 'userid'; break;
		case '!userid':$sql_orderby = 'userid DESC'; break;
		case 'name': $sql_orderby = 'name'; break;
		case '!name':$sql_orderby = 'name DESC'; break;
		case 'nickname': $sql_orderby = 'nickname'; break;
		case '!nickname':$sql_orderby = 'nickname DESC'; break;
		case 'email': $sql_orderby = 'email'; break;
		case '!email':$sql_orderby = 'email DESC'; break;
		case 'level': $sql_orderby = 'level'; break;
		case '!level':$sql_orderby = 'level DESC'; break;
		case 'tel': $sql_orderby = 'tel'; break;
		case '!tel':$sql_orderby = 'tel DESC'; break;
		case 'hp': $sql_orderby = 'hp'; break;
		case '!hp':$sql_orderby = 'hp DESC'; break;
		case 'address': $sql_orderby = 'address'; break;
		case '!address':$sql_orderby = 'address DESC'; break;
		case 'lastlog': $sql_orderby = 'lastlog'; break;
		case '!lastlog':$sql_orderby = 'lastlog DESC'; break;
		default :
			$sql_orderby = isset($dbinfo['orderby']) ? $dbinfo['orderby'] : ' msc_column ';
	}

	// 각종 카운트 구하기
	$count=board2Count($count_msearch,isset($_REQUEST['page']) ? $_REQUEST['page'] : 1,$dbinfo['pern'],$dbinfo['page_pern']);

	// URL Link...
	$href['list']	= $_SERVER['PHP_SELF'].'?' . href_qs('page=&sc_column=&sc_string=',$qs_basic);
	$href['listthis']	= $_SERVER['PHP_SELF'].'?'.$qs_basic;
	$href['write']	= $thisUrl.'userinfo.php?' . href_qs('mode=write',$qs_basic);	// 글씨기
	
	if(isset($count['nowpage']) && $count['nowpage'] > 1) { // 처음, 이전 페이지
		$href['firstpage']	=$thisUrl.'msearch.php?'.href_qs('page=1',$qs_basic);
		$href['prevpage']	 =$thisUrl.'msearch.php?'.href_qs('page='.($count['nowpage']-1),$qs_basic);
	}
	else {
		$href['firstpage']	='javascript: void(0);';
		$href['prevpage']	='javascript: void(0);';
	}
	if(isset($count['nowpage']) && isset($count['totalpage']) && $count['nowpage'] < $count['totalpage']){ // 다음, 마지막 페이지
		$href['nextpage']	=$thisUrl.'msearch.php?'.href_qs('page='.($count['nowpage']+1),$qs_basic);
		$href['lastpage']	=$thisUrl.'msearch.php?'.href_qs('page='.$count['totalpage'],$qs_basic);
	}
	else {
		$href['nextpage']	='javascript: void(0);';
		$href['lastpage'] ='javascript: void(0);';
	}
	$href['prevblock']= (isset($count['nowblock']) && $count['nowblock']>1) ? $thisUrl.'msearch.php?'.href_qs('page='.($count['firstpage']-1) ,$qs_basic): 'javascript: void(0)';// 이전 페이지 블럭
	$href['nextblock']= (isset($count['totalpage']) && isset($count['lastpage']) && $count['totalpage'] > $count['lastpage'])? $thisUrl.'msearch.php?'.href_qs('page='.($count['lastpage'] +1),$qs_basic) : 'javascript: void(0)';// 다음 페이지 블럭
	
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
	$skinfile=basename(__FILE__,'.php').'.html';
	if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
	$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
	$tpl->set_file('html',$skinfile,TPL_BLOCK);

	$sql = $sql . " ORDER BY {$sql_orderby} LIMIT {$count['firstno']},{$count['pern']}";
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
			if(isset($list['lastlog']) && $list['lastlog']) $list['lastlog'] = date('y-m-d [H:i:s]',$list['lastlog']);
			if(isset($list['rdate']) && $list['rdate']) $list['rdate_date'] = date('y-m-d',$list['rdate']);
			
			// URL Link..
			$href['modify'] = $thisUrl.'userinfo.php?' . href_qs("mode=modify&bid={$list['uid']}",$qs_basic);
			$href['delete'] = $thisUrl.'joinout.php?' . href_qs("bid={$list['uid']}",$qs_basic);
			$href['go'] = "{$_GET['mode']}.php?userid={$list['userid']}";

			$tpl->set_var('href.modify',$href['modify']);
			$tpl->set_var('href.delete',$href['delete']);
			$tpl->set_var('href.go',$href['go']);
			$tpl->set_var('list',$list);
			$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
		} // end for
		
		$tpl->drop_var('href.modify'); unset($href['modify']);
		$tpl->drop_var('href.delete'); unset($href['delete']);
		if(isset($list)) {
			foreach($list as $key => $value) $tpl->drop_var('list.'.$key);
		}
		unset($list);
	} // end if.. else..

// 템플릿 마무리 할당
// - 게시판 부분
	$tpl->set_var('dbinfo'			,$dbinfo);
	$tpl->set_var('count'			,$count);	// 게시판 각종 카운트
	$tpl->set_var('href'			,$href);	// 게시판 각종 링크<br>
	$tpl->set_var('get'				,$_GET);

// - 서치 부분
	$form_search =" action='{$_SERVER['PHP_SELF']}' method='get'>";
	$form_search .= substr(href_qs('page=', $qs_basic, 1), 0, -1);
	$tpl->set_var('form_search'		,$form_search);
	$tpl->set_var('get.sc_string'		,htmlspecialchars(stripslashes(isset($_REQUEST['sc_string']) ? $_REQUEST['sc_string'] : ''),ENT_QUOTES));	// 서치 단어
	$tpl->set_var('sort_'.(isset($_GET['sort']) ? $_GET['sort'] : ''),true);	// sort_???

// - 회원전체 서치 부분
	$tpl->set_var('count_msearch',$count_msearch);
	$tpl->set_var('get.msc_string',htmlspecialchars(stripslashes(isset($_GET['msc_string']) ? $_GET['msc_string'] : ''),ENT_QUOTES));
	$form_msearch = " method=get action='{$_SERVER['PHP_SELF']}'> ";
	$form_msearch .= substr(href_qs("mode={$_GET['mode']}",'mode=',1),0,-1);
	$tpl->set_var('form_msearch',$form_msearch);


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
			$tpl->set_var('href.blockcount', "{$thisUrl}msearch.php?".href_qs("page=".$i,$qs_basic) );
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

// 마무리
	$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
	echo preg_replace('/([="\'])images/',$val,$tpl->process('', 'html',TPL_OPTIONAL));
?>
