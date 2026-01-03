<?php

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'skin'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

$url	=	"/sboard2/list.php?db={$db}&cateuid={$cateuid}&html_type=no&skin={$skin}&html_echo=no&getinfo=cont"; 
			echo "<meta http-equiv = 'refresh' content = '0;url = {$url}'>"; 
?>
