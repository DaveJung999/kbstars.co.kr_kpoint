<?php
	$dbinfo = array(
		'table' =>	$SITE['th'] . 'fmailinfo',
		'skin' =>	'basic',
		'pern' =>	10000,
		'page_pern' =>	5,
		'cut_length' =>	40,
		'bid' =>	0,
		'gid' =>	0,
	'priv_list' =>	1,
	'priv_write' =>	1,
	'priv_read' =>	1,
	'priv_delete' =>	99,
		'goto_write' =>	'list.php',
		'goto_modify' =>	'list.php',
		'goto_delete' =>	'list.php',
		'enable_upload' => 'N',
	'html_type' =>	'N', // ht, h, t, N, no
	'html_skin' =>	'basic'
	);
		
$table = $dbinfo['table'];
?>
