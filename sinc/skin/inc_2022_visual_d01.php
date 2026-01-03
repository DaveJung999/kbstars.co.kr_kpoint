<?php
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$sql = "select * from new21_board2_contents_2016 where uid = 60 ";
$list = db_arrayone($sql);
echo isset($list['content']) ? $list['content'] : '';
?>
