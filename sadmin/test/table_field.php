	$rs_inc=db_query("select * from `{$table['inc'][$inc]}` where `균주정보ID`='{$uid}'");
	$list_inc=db_array($rs_inc);
	table_field($rs_inc);
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
function table_field(&$result,$tableinfo=="") {
	$fields = mysql_num_fields($result);
	$rows	= mysql_num_rows($result);
	$table = mysql_field_table($result, 0);
	echo "Your '".$table."' table has ".$fields." fields and ".$rows." record(s)\n";
	echo "The table has the following fields:\n";
	for ($i=0; $i < $fields; $i++) {
		if($tableinfo=="") { // 그냥 출력
			$type  = mysql_field_type($result, $i);
			$name  = mysql_field_name($result, $i);
			$len	= mysql_field_len($result, $i);
			$flags = mysql_field_flags($result, $i);
			echo "<br>type:" . $type." name:".$name." len:".$len." flages:".$flags."\n";
		}
		else { // 폼을 위해 정말 사용!!
		$tableinfo[
		}
	}
} // function
