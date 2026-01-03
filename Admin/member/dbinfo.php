<?php
	$table_logon				= $SITE['th'] . "logon"; // new21_slist_event
	$table_mail_message			= $SITE['th'] . "mail_message"; // new21_slist_event

	$dbinfo['db']				= "logon"; // new21_slist_event
	$dbinfo['title']			= "";
	$dbinfo['skin']				= "basic";
	$dbinfo['upload_dir']		= "{$_SERVER['DOCUMENT_ROOT']}/h_images";
	$dbinfo['pern']				= 20;
	$dbinfo['page_pern']		= 10;
	$dbinfo['row_pern']			= 1;
	$dbinfo['cut_length']		= 50;
	$dbinfo['priv_list']		= '운영자'; // 본 list.php 볼 권한 설정
	$dbinfo['priv_write']		= '운영자'; // write.php 글 올릴 권한 설정
	$dbinfo['priv_read']		= '운영자'; // 본 read.php 볼 권한 설정
	$dbinfo['priv_delete']		= '운영자'; // 무조건 삭제권한
	$dbinfo['enable_upload']	= "multi"; // 업로드지원
	$dbinfo['html_headpattern']	= "no";
?>