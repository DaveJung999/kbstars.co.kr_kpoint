<?php
//=======================================================
// 설	명 : 관리자 페이지 : 서비스 이용정보 검색
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/02/03 박선민 처음
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

	// 넘어온값 처리
	$_GET['mode'] = 'serviceinfo';

	// table
	$table_logon	= $SITE['th'].'logon';
	$table_groupinfo= $SITE['th'].'groupinfo';
	$table_joininfo	= $SITE['th'].'joininfo';
	$table_payment	= $SITE['th'].'payment';
	$table_service	= $SITE['th'].'service';
	$table_loguser	= $SITE['th'].'log_userinfo';
	$table_log_wtmp	= $SITE['th'].'log_wtmp';
	$table_log_lastlog=$SITE['th'].'log_lastlog';
	
	$dbinfo = array(
				'skin'	 =>	'basic',
				'table'	 =>	$table_logon				
			);

	// uid=???, hp=???, order=??? 처럼 짧은키워드 검색 지원
	if($_GET['bid']) { $_GET['msc_column']='logon.uid';$_GET['msc_string']=$_GET['bid'];}
	elseif($_GET['userid']) { $_GET['msc_column']='logon.userid';$_GET['msc_string']=$_GET['userid'];}
	elseif($_GET['tel']) { $_GET['msc_column']='logon.tel';$_GET['msc_string']=$_GET['tel'];}
	elseif($_GET['hp']) { $_GET['msc_column']='logon.hp';$_GET['msc_string']=$_GET['hp'];}
	elseif($_GET['order']) { $_GET['msc_column']='payment.num';$_GET['msc_string']=$_GET['order'];}
	elseif(!$_GET['msc_column']) { $_GET['msc_column']='logon.userid'; $_GET['msc_string']='%';}

	/////////////////////////////////
	// 회원 검색 및 회원정보 가져오기
	// - 넘어온값 체크
	$sql_table= explode('.',$_GET['msc_column']);
	if(sizeof($sql_table)!=2 or empty($_GET['msc_string'])) go_url('msearch.php');
	// - $sql_where
	if (preg_match('/%/', $_GET['msc_string'])) {
		if($_GET['msc_string']=='%') $_GET['msc_string'] = '%%';
		$sql_where	= " ({$SITE['th']}{$sql_table['0']}.{$sql_table['1']} like '{$_GET['msc_string']}') ";
	}
	else $sql_where	= " ({$SITE['th']}{$sql_table['0']}.{$sql_table['1']} = '{$_GET['msc_string']}') ";
	// - $sql문 완성
	switch ($sql_table['0']) {
		case 'logon' :
			$sql="select *, email as msc_column from {$SITE['th']}{$sql_table['0']} where  $sql_where ";
			break;
		case 'payment':
			$sql="select {$table_logon}.*, {$SITE['th']}{$sql_table['0']}.{$sql_table['1']} as msc_column from {$table_logon}, {$SITE['th']}{$sql_table['0']} where {$table_logon}.uid={$SITE['th']}{$sql_table['0']}.bid and  $sql_where ";
			break;
	} // end switch
	$rs_msearch=db_query($sql);
	// 결과값이 한명이 아니라면, 서치 페이지로 이동시킴.
	if(db_count($rs_msearch)!=1) 
		go_url("msearch.php?mode={$_GET['mode']}&msc_column={$_GET['msc_column']}&msc_string={$_GET['msc_string']}");
	$logon		= db_array($rs_msearch);
	/////////////////////////////////
	
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 해당 게시물 불러들임
$sql_where = " bid='{$logon['uid']}' "; // init
$sql = "SELECT * from {$table_service} WHERE $sql_where ORDER BY rdate DESC";
$result=db_query($sql);

if(!$count_service=db_count()) {
	$tpl->process('LIST','nolist');
}
else {
	for($i=0;$i<$count_payment;$i++) {
		$list = db_array($result);
		$list['rdate_date'] = date('y-m-d',$list['rdate']);
		$list['expire_date_date'] = date('y-m-d',$list['expire_date']);

		$tpl->set_var('list',$list);

		$tpl->process('LIST','list',TPL_OPTIONAL|TPL_APPEND);
	} // end for
} // end if.. else..

// ===================================
// 회원 로그 파일 출력
// ===================================
	$rs_loguser = db_query("SELECT * from {$table_loguser} where userbid='{$logon['uid']}' order by rdate DESC");
	while($row=db_array($rs_loguser)) {
		$row['rdate_date']=date('y-m-d',$row['rdate']);
		$row['logon']=db_array(db_query("SELECT * from {$table_logon} where uid={$row['bid']}"));
		
		// 문서 형식에 맞추어서 내용 변경
		$row['content'] = replace_string($row['content'], (substr($row['content'],0,1)=="<")?"html":"text");	

		// URL Link..
		$href['delete']="./ok.php?mode=loguserdelete&uid={$row['uid']}&bid={$row['bid']}";

		$tpl->set_var('href.delete',$href['delete']);
		$tpl->set_var('loguserlist',$row);
		$tpl->process('LOGUSERLIST','loguserlist',TPL_OPTIONAL|TPL_APPEND);
	} // end while
	$form_loguser = " method='post' action='ok.php'>";
	$form_loguser .= substr(href_qs("mode=loguserwrite&userbid={$logon['uid']}",'userbid=',1),0,-1);
	$tpl->set_var('form_loguser',$form_loguser);


// 템플릿 할당
$date_lastlog = db_result(db_query("select from_unixtime(rdate,'%Y-%m-%d [%H:%i]') as date from {$table_log_wtmp} where bid='{$logon['uid']}' order by rdate DESC limit 0,1"),0,"date");
$tpl->set_var('date_lastlog',$date_lastlog);

$count_recmder	= (int)db_result(db_query("SELECT count(*) as count FROM {$table_logon} WHERE recommender='{$logon['userid']}' ORDER BY rdate DESC"),0,"count");
$tpl->set_var('count_recmder',$count_recmder);

// 템플릿 마무리 할당
$tpl->set_var('href',$href);
$tpl->set_var('msc_column',$_GET['msc_column']);
$tpl->set_var('msc_string',htmlspecialchars(stripslashes($_GET['msc_string']),ENT_QUOTES));
$tpl->set_var('logon',$logon);

$form_msearch = " method=get action='msearch.php' ";
$tpl->set_var('form_msearch',$form_msearch);

// 마무리
$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
echo preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));
?>