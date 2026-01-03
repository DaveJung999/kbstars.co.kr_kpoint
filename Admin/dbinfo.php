
<?php
	$table				= "{$SITE['th']}" . "payment"; // new21_slist_event
	$table_logon		= "{$SITE['th']}" . "logon";
	$table_userinfo		= "{$SITE['th']}" . "userinfo";

	$dbinfo['db']		= "payment"; // new21_slist_event
	$dbinfo['title']		= "주문관리";
	$dbinfo['skin']		= "basic";
	$dbinfo['upload_dir']		= "{$_SERVER['DOCUMENT_ROOT']}/h_images";
	$dbinfo['pern']		= 10;
	$dbinfo['cut_length']	= 50;
	$dbinfo['priv_list']	= 99; // 본 list.php 볼 권한 설정
	$dbinfo['priv_write']	= 99; // write.php 글 올릴 권한 설정
	$dbinfo['priv_read']	= 99; // 본 read.php 볼 권한 설정
	$dbinfo['priv_delete']= 99; // 무조건 삭제권한
	$dbinfo['enable_upload']="multi"; // 업로드지원
	$dbinfo['html_headpattern'] = "no"; 
?>
