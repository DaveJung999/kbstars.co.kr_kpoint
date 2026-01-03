<?php
//=======================================================
// 설	명 : 엑셀로 받기
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/12/01
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/12/01 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp' => 1,
	'useCheck' => 1,
	'useBoard2' => 1,
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// $db_conn 변수는 header.php에서 생성된 mysqli 커넥션 객체라고 가정합니다.
	global $db_conn;
	$urlprefix	= ""; // ???list.php ???write.ephp ???ok.php
$thisPath	= dirname(__FILE__);
$thisUrl	= "."; // 마지막 "/"이 빠져야함
	
	if(!isset($_GET['db']) || !$_GET['db']) back('잘못된 요청입니다 . 25L');
	
	// 3 . $dbinfo 가져오기
	include_once("{$thisPath}/config.php");

	// 4 . 권한 체크
	if(!siteAuth($dbinfo, "priv_list",1)) back("이용이 제한되었습니다.(레벨부족)");
	//======================
	// 5 . SQL문 where절 정리
	//======================
	$sql_where = ""; // init
	// 서치 게시물만..
	$sc_string = $_GET['sc_string'] ?? '';
	if(isset($sc_string)){
		$sc_string_safe = db_escape($sc_string);
		if($sql_where) $sql_where .= ' and ';
		$sc_column = $_GET['sc_column'] ?? '';
		if(isset($sc_column) && $sc_column){
			$safe_column = db_escape($sc_column);
			if(in_array($safe_column,array('bid','uid')))
				$sql_where .=" (`{$safe_column}`='{$sc_string_safe}') ";
			else
				$sql_where .=" (`{$safe_column}` like '%{$sc_string_safe}%') ";
		}
		else
			$sql_where .=" ((`userid` like '%{$sc_string_safe}%') or (`title` like '%{$sc_string_safe}%') or (`content` like '%{$sc_string_safe}%')) ";
	}
	if(!$sql_where) $sql_where= " 1 ";
	//===========================
	// 6 . SQL문 order by..절 정리
	//===========================
	switch($_GET['sort'] ?? ''){
		// case 'rdate': $sql_orderby = 'rdate'; break;
		// case '!rdate':$sql_orderby = 'rdate DESC'; break;
		default :
			$sql_orderby = isset($dbinfo['orderby']) ? db_escape($dbinfo['orderby']) : ' 1 ';
	}

	// 추가 필드이름 체크
	$fieldlist = array();
	// PHP 7에서 mysql_list_fields()가 제거되어 'SHOW COLUMNS' 쿼리로 대체
	$escaped_table = db_escape($dbinfo['table']);
	$sql = "SHOW COLUMNS FROM {$escaped_table}";
	$result = db_query($sql);
	
	if ($result) {
		while ($row = db_array($result)) {
			$a_fields = $row['Field'];
			if( !in_array($a_fields, array('uid','email','status','readtime')) ){
				$fieldlist[] = $a_fields;
			}
		}
	}

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
if(($_GET['mode'] ?? '') != 'view'){
	header( "Content-type: application/vnd.ms-excel;charset=KSC5601" );
	header( "Content-Disposition: attachment; filename=myfile.xls" );
	header( "Content-Description: PHP Generated Data" );
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
	header("Pragma: public");
}

?>
<html>
<body>
<table border="1">
	<tr>
	<th>uid</th>
	<th>메일</th>
<?php
if(sizeof($fieldlist)){
	foreach($fieldlist as $value){
		echo "	<th>{$value}</th>\n";
	}
}

?>
	<th>읽은시간</th>
	<th>status</th>
	</tr>
<?php
$sql="SELECT * FROM {$dbinfo['table']} WHERE $sql_where ORDER BY {$sql_orderby}";
$result = db_query($sql);
$total = db_count($result);

while($list = db_array($result)) {
?>
	<tr height="30">
	<td><?php echo $list['uid'] ?? '' ; ?> </td>
	<td><?php echo $list['email'] ?? '' ; ?> </td>
<?php
	if(sizeof($fieldlist)){
		foreach($fieldlist as $value){
			echo "	<td>" . ($list[$value] ?? '') . "</td>\n";
		}
	}
?>
		<td><?php echo isset($list['readtime']) ? date("y/m/d H:m:s",(int)$list['readtime']) : ''; ?> </td>
		<td><?php echo $list['status'] ?? '' ; ?> </td>
	</tr>
<?php
} // end while
?>
</table>
</body>
</html>