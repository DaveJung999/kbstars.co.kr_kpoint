<?php
$dbinfo['table_memo']	= $dbinfo['table'] . '_memo';

// 비공개글 제외시킴
$sql_where_memo = '';
if(isset($dbinfo['enable_memohidelevel']) && $dbinfo['enable_memohidelevel'] == 'Y'){
	if($sql_where_memo) $sql_where_memo .= ' and ';
	if(isset($_SESSION['seUid'])){
		$priv_hidelevel	= isset($dbinfo['gid']) ? (int)$_SESSION['seGroup'][$dbinfo['gid']]['level'] : (int)$_SESSION['sePriv']['level'];
		$sql_where_memo .=" ( priv_hidelevel<={$priv_hidelevel} or bid='{$_SESSION['seUid']}' ) ";
	}
	else $sql_where_memo .=' priv_hidelevel=0 ';
} // end if
if(!$sql_where_memo) $sql_where_memo = ' 1 ';

// 메모삭제가능한지
if(privAuth($dbinfo, 'priv_delete')) $dbinfo['canMemoDelete'] =1;

// 메모 DB 읽어드림
$sql = "select * from {$dbinfo['table_memo']} where pid='{$list['uid']}' and {$sql_where_memo} order by rdate";
$rs_memolist=db_query($sql);
if(!($total_memo=db_count($rs_memolist))) // 메모된 DB가 없다면
	$tpl->process('MEMOLIST','nomemolist');
else {
	for($i=0;$i<$total_memo;$i++){
		$memolist=db_array($rs_memolist);
		$memolist['no'] = $total_memo -$i;
		$memolist['rdate_date']=date('Y/m/d', $memolist['rdate']);
		$memolist['rdate_dates']=date('Y/m/d H:i:s', $memolist['rdate']);
		$memolist['rdate_date_']=date('m/d', $memolist['rdate']);
		$memolist['rdate_date-']=date('Y-m-d', $memolist['rdate']);
		$memolist['title'] = replace_string($memolist['title'],"strip");

		$memolist['canMemoDelete'] = 0;
		if(isset($dbinfo['canMemoDelete']) && $dbinfo['canMemoDelete'] or
			( 'nobid' != substr($dbinfo['priv_delete'],0,5)
				and isset($memolist['bid']) && $memolist['bid'] == $_SESSION['seUid'] ) ){
			$memolist['canDelete'] = 1;
		}

		// master인 경우 {list.userid} 특정 이미지로
		/*if(isset($memolist['bid']) && $memolist['bid']>0 and $memolist['bid']<1100) $memolist['userid'] = "<img src='/img/master_icon.gif'>";
		if(isset($memolist['bid']) && ($memolist['bid'] == 1217 or $memolist['bid'] == 1226 or $list['bid'] == 5796)) $memolist['userid'] = "<img src='/img/master_icon.gif'>";
		if(isset($memolist['bid']) && $memolist['bid'] == 5331) $memolist['userid'] = "<img src='/img/master_icon2.gif'>";*/

		// URL Link...
		$memolist['href']['delete']=$thisUrl.'/ok.php?'.href_qs("mode=memodelete&uid={$memolist['uid']}&pid={$list['uid']}", $qs_basic);

		/*if(isset($memolist['bid']) && $memolist['bid']>0 and $memolist['bid']<1100) $memolist['userid'] = "<img src='/img/master_icon.gif'>";
		if(isset($memolist['bid']) && ($memolist['bid'] == 1217 or $memolist['bid'] == 1226 or $list['bid'] == 5796)) $memolist['userid'] = "<img src='/img/master_icon.gif'>";
		if(isset($memolist['bid']) && $memolist['bid'] == 5331) $memolist['userid'] = "<img src='/img/master_icon2.gif'>";*/

		$tpl->set_var('memolist',$memolist);
		$tpl->process('MEMOLIST','memolist',TPL_APPEND|TPL_OPTIONAL);
	} // end while
} // end if


$form_memo=" action='{$thisUrl}/ok.php' method='post' ENCTYPE='multipart/form-data'>";
$form_memo .= substr(href_qs("db={$dbinfo['db']}&mode=memowrite&pid={$list['uid']}",'mode=',1),0,-1);

$memouserid = '';
if(isset($dbinfo['enable_userid'])){
	switch($dbinfo['enable_userid']){
		case 'name'		: $memouserid = isset($_SESSION['seName']) ? $_SESSION['seName'] : ''; break;
		case 'nickname'	: $memouserid = isset($_SESSION['seNickname']) ? $_SESSION['seNickname'] : ''; break;
		default			: $memouserid= isset($_SESSION['seUserid']) ? $_SESSION['seUserid'] : ''; break;
	}
}

// 템플릿 할당
$tpl->set_var('form_memo',$form_memo);
$tpl->set_var('memouserid',$memouserid);

/*
	if($_SERVER['REMOTE_ADDR'] == '59.3.40.149'){
		print_r($dbinfo);
	}

*/

if(isset($dbinfo['enable_memohidelevel']) && privAuth($dbinfo, 'priv_memowrite')) $tpl->process('MEMO','memo',TPL_APPEND|TPL_OPTIONAL);
else $tpl->process('MEMO','nomemo',TPL_APPEND|TPL_OPTIONAL);
?>
