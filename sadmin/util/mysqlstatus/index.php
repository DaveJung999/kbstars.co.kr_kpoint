<?php
require 'class.mysql.status.php';

# 아래에서 HOSTNAME_OF_DBSERVER,USERNAME,PASSWD,DB_NAME 은 자신에게 맞게 수정하세요
// PHP 7 업그레이드: mysql_connect() → db_connect(), mysql_select_db() → db_select()
//db_connect('localhost','USERNAME','PASSWD');
//db_select('DB_NAME');

$status = new mysql_status;
$status->tohtml();

// PHP 7 업그레이드: mysql_close() → db_close()
db_close();
?>
