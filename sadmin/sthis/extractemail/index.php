<?php
//=======================================================
// 설  명 : URL에서 email 추출 프로그램(extractemail/index.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/11/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02/03/20 김평수 자체 개발 완료
// 02/11/14 박선민 마지막 수정
// 25/11/10 Gemini AI PHP 7+ 호환성 수정 (mysql_* -> db_*, 변수 중괄호 {}, 탭 변환, Short Tag)
//=======================================================
$HEADER=array(
	'priv'		=>'', // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard2'=>1, // href_qs()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $_SERVER['HTTP_HOST']); // [!] FIX: $HTTP_HOST -> $_SERVER['HTTP_HOST']

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	set_time_limit(0);


	// 관리자페이지 환경파일 읽어드림
	// [!] FIX: {$SITE['th']} -> {$SITE['th']}
	$rs=db_query("select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
	$pageinfo=db_count() ? db_array($rs) : back("관리자페이지 환경파일을 읽을 수가 없습니다");


	$table = $SITE['th'] . "extractemail";
	// 테이블이 없을 경우 생성
	// [!] FIX: @mysql_query($sql) -> @db_query($sql), $table 변수 중괄호 {} 적용
	$sql="
			CREATE TABLE {$table} (
				uid mediumint(8) unsigned NOT NULL auto_increment,
				userid varchar(20) NOT NULL default '',
				email varchar(40) NOT NULL default '',
				category varchar(20) NOT NULL default '',
				rdate int(10) unsigned NOT NULL default '0',
				PRIMARY KEY	(uid),
				KEY cartegory (category)
			)
		";
	@db_query($sql);

	// 넘오온값필터링
	$url		=trim($url);
	$page		=trim($page);
	$start_num	=trim($start_num);
	$end_num	=trim($end_num);

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<html>
<?php echo $pageinfo['html_header']; ?>
<body>
<form name="form1" method="post" action="">
<input type="hidden" name="mode" value="extract">
<table border=0 cellspacing='<?php echo $pageinfo['table_cellspacing']; ?>' cellpadding='<?php echo $pageinfo['table_cellpadding']; ?>' bgcolor='<?php echo $pageinfo['table_linecolor']; ?>'>
	<tr bgcolor='<?php echo $pageinfo['table_thcolor']; ?>'>
		<td width="466"> <font size="2"> 
		URL : <input type="text" name="url" size="40" value="<?php echo htmlspecialchars(stripslashes($url),ENT_QUOTES); ?>"></font></td>
	</tr>
	<tr bgcolor='<?php echo $pageinfo['table_tdcolor']; ?>'>
		<td height="28" width="466"> <font size="2"> 
		<input type="text" name="page" size="6" value="<?php echo $page; ?>">(게시판 서브쿼리 예를 들어 page...) 
		<input type="text" name="start_num" size="2" value="<?php echo $start_num; ?>">
		~ 
		<input type="text" name="end_num" size="2" value="<?php echo $end_num; ?>">
		뽑고 싶은 페이지 수</font></td>
	</tr>
	<tr bgcolor='<?php echo $pageinfo['table_thcolor']; ?>'>
		<td width="466"> <font size="2"> 
		<input type="submit" name="Submit" value=" 이메일 추출하기 ">
		</font></td>
	</tr>
	</table>
</form>



<table border=0 cellspacing='<?php echo $pageinfo['table_cellspacing']; ?>' cellpadding='<?php echo $pageinfo['table_cellpadding']; ?>' bgcolor='<?php echo $pageinfo['table_linecolor']; ?>'>
	<tr bgcolor='<?php echo $pageinfo['table_thcolor']; ?>'>
	<td colspan="4"><font size="2"><b>[DB에 쌓인 DATA]</b></font></td>
	</tr>
	<tr bgcolor='<?php echo $pageinfo['table_thcolor']; ?>'>
	<td> 
		<div align="center"><font size="2">추출한관리자</font></div>
	</td>
	<td> 
		<div align="center"><font size="2">카테고리</font></div>
	</td>
	<td> 
		<div align="center"><font size="2">수량</font></div>
	</td>
	<td> 
		<div align="center"><font size="2">추출날짜</font></div>
	</td>
	</tr>
	<?php
################################## 
# Email 추출한 내역 DB 에서 뽑아옴
##################################
$result = db_query("SELECT userid,category,count(category) as num,rdate FROM {$table} group by category, userid ");
$total = db_count();
for($i=0; $i<$total; $i++){
	$list = db_array($result);
	$total_email += $list['num'];
	
?>
		<tr bgcolor='<?php echo $pageinfo['table_tdcolor']; ?>'>
		<td height="23"> 
			<div align="center"><font size="2"> 
<?php 
echo $list['userid']; 
?>
			</font></div>
		</td>
		<td height="23"> 
			<div align="center"><font size="2"> 
<?php 
echo $list['category']; 
?>
			</font></div>
		</td>
		<td height="23"> 
			<div align="center"><font size="2"> 
<?php 
echo $list['num']; 
?>
			</font></div>
		</td>
		<td height="23"> 
			<div align="center"><font size="2"> 
<?php 
echo date('Y.m.d', $list['rdate']); 
?>
			</font></div>
		</td>
		</tr>
<?php
} // end for	
?>
	<tr bgcolor='<?php echo $pageinfo['table_tdcolor']; ?>'>
	<td colspan="4"> 
		<div align="right"><font size="2">이메일 총수량 : <b><?php 
echo $total_email; ?></b> 개</font></div>
	</td>
	</tr>

</table>

<?php
if($mode == "extract"){
	############################################################################
	# 이메일 추출 하는 부분 
	# axxxx.com/board/list.php?page=1 부터 axxxx.com/board/list.php?page=3 까지 
	# 구할경우를 위해서브쿼리의 페이지 만큼 포를 돌려 모든 이메일을 싸그리 추출한다.
	#
	# $page 는 서브쿼리
	############################################################################
	
	$first_url = $url;	// 아랫부분에서 $url 값이 변경되지만. 처음 넘어온 $url 값을 저장
	if(!$url) back("주소를 입력해주시기 바랍니다.");
	if($page =="" && ($start_num != $end_num)){
		back("서브 쿼리를 입력해주시기 바랍니다.");
	}
	
	if($page !="" && $start_num !="" && $end_num!="" && $start_num != $end_num){
		if($start_num > $end_num){
			back("시작되는 페이지의 수가 끝나는 페이지수보다 작을순 없습니다.");
		}

		$parse_url=parse_url($url);
		for($paging=(int)$start_num; $paging <= $end_num; $paging++) {
			$url = $parse_url['scheme'] . "://" . $parse_url['host'] . $parse_url['path'] . "?" . href_qs("{$page}={$paging}",$parse_url['query']);	//사이트의 정확한 세부주소까지 포함

			"<br>\n{$url}\n";
			echo str_repeat(" ",300); flush();
			$site = file($url);
			if (is_array($site)) { // file() 함수 결과가 배열인지 확인
				foreach($site as $line_num=>$line){	// 메일 추출
					if(preg_match("/[a-z0-9_-]+@[a-z0-9-]+\.[a-z0-9-]+\.[a-z0-9-]+/i", $line,$reg_line) || preg_match("/[_a-z0-9_-]+@[a-z0-9-]+\.[a-z0-9-]+/i", $line,$reg_line)){
						$all_email .= "%".$reg_line['0'];
					}
				} // end foreach
			}
		} // end for
	}
	else{
		$site = file($url);
		if (is_array($site)) { // file() 함수 결과가 배열인지 확인
			foreach($site as $line_num=>$line){	// 메일 추출
				if(preg_match("/[a-z0-9_-]+@[a-z0-9-]+\.[a-z0-9-]+\.[a-z0-9-]+/i", $line,$reg_line) || preg_match("/[a-z0-9_-]+@[a-z0-9-]+\.[a-z0-9-]+/i", $line,$reg_line)){
					
					$all_email .= "%".$reg_line['0'];
				}
			}
		}
	}
	$all_email_arr = explode("%",$all_email);
?>
<br>
	<form name="form2" method="post" action="">
	<input type="hidden" name="mode" value="emailtodb">
	<input type="hidden" name="all_email" value="<?php 
echo $all_email; 
?>">

		
<table border=0 cellspacing='<?php echo $pageinfo['table_cellspacing']; ?>' cellpadding='<?php echo $pageinfo['table_cellpadding']; ?>' bgcolor='<?php echo $pageinfo['table_linecolor']; ?>'>
	<tr bgcolor='<?php echo $pageinfo['table_thcolor']; ?>'>
		<td width="183" height="20"><font size="2"><b>[이메일 추출]</b></font></td>
		<td width="210" height="20"><font size="2"></font></td>
	</tr>
	
	<tr bgcolor='<?php echo $pageinfo['table_tdcolor']; ?>'>
		<td colspan="2" height="20"><font size="2">이메일을 추출한 사이트 : <b> 
<?php 
echo $first_url; 
?>
		</b> <br>
		추출한 E-mail 수량 <font color="#FF0000"> 
<?php 
echo sizeof($all_email_arr) -1 ; 
?>
		<font color="#000000">개</font></font></font></td>
	</tr>
	<tr bgcolor='<?php echo $pageinfo['table_tdcolor']; ?>'>
		<td width="183"> 
		<table border=0 cellspacing='<?php echo $pageinfo['table_cellspacing']; ?>' cellpadding='<?php echo $pageinfo['table_cellpadding']; ?>' bgcolor='<?php echo $pageinfo['table_linecolor']; ?>'>

<?php
	// 추출한 메일을 출력해주는 부분
	for($i=1; $i <= sizeof($all_email_arr); $i++ ){	
	
?>
			<tr bgcolor='<?php echo $pageinfo['table_tdcolor']; ?>'> 
			<td><font size="2"> 
				<?php 
echo $all_email_arr[$i]; 
?>
				</font></td>
			</tr>
			<?php
	}	
	
?>
		</table>
		</td>
		<td valign="bottom" width="210"> <font size="2"> </font> 
		<table border=0 cellspacing='<?php echo $pageinfo['table_cellspacing']; ?>' cellpadding='<?php echo $pageinfo['table_cellpadding']; ?>' bgcolor='<?php echo $pageinfo['table_linecolor']; ?>'>

		 <tr bgcolor='<?php echo $pageinfo['table_tdcolor']; ?>'>
			<td valign="top"><font size="2" color="#3F7058">본 이메일 추출은 악의성<br>
				없이 내부적으로 사용할 <br>
				것을 굳게 다짐합니다.<br>
				ㅋㅋㅋ<br>
				<br>
				DB 에 이메일이 입력될땐<br>
				중복된 이메일은 DB에서<br>
				제외됩니다.</font></td>
			</tr>
		 <tr bgcolor='<?php echo $pageinfo['table_tdcolor']; ?>'>
			<td valign="bottom"><font size="2"> 
				<select name="category">
				<option>신문사</option>
				<option>게시판</option>
				<option>뉴21커뮤니티</option>
				<option>야후</option>
				</select>
				</font></td>
			</tr>
		<tr bgcolor='<?php echo $pageinfo['table_tdcolor']; ?>'>
			<td valign="bottom" height="41"><font size="2"> 
				<input type="text" name="direct_cartegory">
				<input type="checkbox" name="direct_check" value="1">
				<br>
				(직접 분류를 원할땐 체크)</font></td>
			</tr>
		 <tr bgcolor='<?php echo $pageinfo['table_tdcolor']; ?>'>
			<td valign="bottom"><font size="2"> 
				<input type="submit" name="Submit2" value=" DB에 입력하기 ">
				</font></td>
			</tr>
		</table>
		<font size="2"><br>
		<br>
		</font></td>
	</tr>
	</table>
	</form>
	<br>
</body>
</html>
<?php
} // end if($mode == "extract")
elseif($mode=="emailtodb") {
	$rdate = time();

	if($direct_check){
		if(!$direct_cartegory) back("카테고리를 직접입력해주시기 바랍니다.");
		$category = $direct_cartegory;
	}
	$all_email_arr = explode("%",$all_email);

	for($i=1; $i< sizeof($all_email_arr); $i++){
		
		// [!] FIX: $table, $category, $all_email_arr 변수 중괄호 {} 적용
		$result = db_query("SELECT * from {$table} WHERE category='{$category}' and email='{$all_email_arr[$i]}'");
		if(!db_count()){
			// [!] FIX: $table, $_SESSION, $category 변수 중괄호 {} 적용
			db_query("INSERT INTO {$table}(userid,email,category,rdate) VALUES('{$_SESSION['seUserid']}','{$all_email_arr[$i]}','{$category}',{$rdate})");	
		}
		db_free($result); // [!] FIX: mysql_free_result -> db_free

	}

	go_url($_SERVER['PHP_SELF']); // [!] FIX: $PHP_SELF -> $_SERVER['PHP_SELF']
} // end if($mode=="emailtodb") {

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
// 테이블이 존재하지 않을 경우 admin_tableinfo 테이블정보대로 table생성
function mysql_table_create($table,$createtable) {
	global $SITE;
	// [!] FIX: $SITE['th'], $table 변수 중괄호 {} 적용
	$rs=db_query("select sql from {$SITE['th']}admin_tableinfo where table_name='{$table}'");
	if(db_count()) {
		// [!] FIX: $createtable 변수 중괄호 {} 적용
		$sql="CREATE TABLE {$createtable} (" . db_result($rs,0,"sql") . ")";
		if(@db_query($sql)) // [!] FIX: @mysql_query -> @db_query
			return 1;
		else // 아마 해당 데이터베이스가 존재할 경우겠지.. 생성하다가 실패했으니..
			return -1; // -1로 리턴함..
	}
	else {
		return 0;
	}
} // end func
?>