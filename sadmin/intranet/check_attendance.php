<?php
$table_att = $SITE['th'].'intranet_attendance';

// 출근여부
$sql = "SELECT * from {$table_att} where workday='".date("Ymd") . "' and bid={$_SESSION['seUid']}";
if(!db_arrayone($sql)) { // 오전 6시 이후에 출근부를 보고자할 경우 출근여부 확인함
	if(date('H')>6) back("출근 페이지로 이동합니다.","/sadmin/intranet/attend/beginwork.php");
}
?>