<?php
//=======================================================
// 설	명 : 인트라넷 최근 게시물	(last.php)
// 책임자 : 박선민(sponsor@new21.com), 검수: 03/05/23 
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/05/12 채혜진 마지막 수정
// 03/05/23 박선민 /sadmin/board/last.php로 이동
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?= → <?php echo 변환
//============================================
$HEADER=array(
	'priv'	 => '운영자', // 인증유무 (0:모두에게 허용)
		'part'	 =>	"root", // 관리자만 로그인
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp'	 => 1,
	'useBoard' => 1,
	'html_echo'	 => 0	 // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
page_security("", $HTTP_HOST);

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
	$table_board2info = $SITE['th'] . "board2info";	//게시판 관리 테이블

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<style type="text/css">
<!--
.thm8 {	font-family: "Tahoma"; font-size: 8pt; color: #000000; line-height: 150%}
.thm9 {	font-family: "verdana"; font-size: 9pt; color: #000000; line-height: 150%}
-->
</style>
<style type="text/css">
	<!--
	a:link {	font-family: "verdana"; font-size: 9pt; color: #000000; text-decoration: none}
	a:visited {	font-family: "verdana"; font-size: 9pt; color: #000000; text-decoration: none}
	a:hover {	font-family: "verdana"; font-size: 9pt; color: #FFAC0B; text-decoration: none}
	a:active {	font-family: "verdana"; font-size: 9pt; color: #FFAC0B; text-decoration: none}
	
	-->
</style>
<body	bgcolor="#DAE0E5">
<table bgcolor="#F8F8EA" style="border:solid 1 #C0C0C0">
<tr>
<td align="center">
<table width="400" border="0" cellpadding="5" cellspacing="0">
	<tr>
	<td align="center" class="thm9">
		2일 동안 올라온 최근게시물 입니다.<br>
		참고해 주세요~</td>
	</tr>
</table>
<?php
	// board2info 테이블 정보 가져와서 $dbinfo로 저장
	$rs_dbinfo=db_query("SELECT * FROM {$table_board2info}");
	$total = db_count();
	
	//게시판 리스트 출력
	for($i =0 ; $i < $total ; $i ++){
		$dbinfo= db_array($rs_dbinfo);
		$table = $SITE['th'] . "board_" . $dbinfo['table_name']; // 게시판 테이블
		
		//각 테이블에 접속해서 2일전의 게시물 불러 오기~
		$rs = db_query("SELECT * from {$table} WHERE type = 'docu' and	rdate > unix_timestamp()-172800 ORDER BY num DESC");
		$count = db_count();
		
		if($count){
		$qs_basic = "db={$dbinfo['table_name']}".					//table 이름
				"&mode=".					// mode값은 list.php에서는 당연히 빈값
				"&cateuid={$cateuid}".		//cateuid
				"&pern={$_GET['limitrows']}" .	// 페이지당 표시될 게시물 수
				"&page={$page}";				//현재 페이지
?>

		<table width="350"	height="25" cellpadding="0" cellspacing="0"	style="border:solid 1 #C0C0C0">
			<tr> 
				<td align="center" bgcolor="#EFEFEF" style=font-family:verdana;font-size:9pt ><strong><?php echo $dbinfo['title']?></strong></td>
			</tr>
		</table>
		<br>
		<table border="0" cellpadding="0" cellspacing="0">
			<tr bgcolor="#CD4110"> 
			<td height="1" colspan="4"></td>
			</tr>
			<tr align="center"> 
			<td width="300" height="25" class="thm8"> <font color="#CD4110"><strong>TITLE</strong></font></td>
			<td width="80" height="25" class="thm8"><font color="#CD4110"><strong>NAME</strong></font></td>
			<td width="80" height="25" class="thm8"><font color="#CD4110"><strong>DATE</strong></font></td>
			<td width="50" height="25" class="thm8"><font color="#CD4110"><strong>READ</strong></font></td>
			</tr>
			<tr bgcolor="#CD4110"> 
			<td height="1" colspan="4"></td>
			</tr>
<?php
			for($j=0;$j < $count ; $j++){
			$list = db_array($rs);
			if(!$list['title']) $list['title'] = "제목없음…";
		
			//답변이 있을 경우 자리는 길이를 더 줄임
			$cut_length = $list['rede'] ? $dbinfo['cut_length'] - $list['rede'] -3 : $dbinfo['cut_length']; 
			$list['cut_title'] = cut_string($list['title'], $cut_length);

			// 메모개수 구해서 제목 옆에 붙임
			if($dbinfo['enable_memo']=='Y') {
				// 메모 테이블 구함
				if($dbinfo['enable_type']=="Y") {
					$table_memo		=$table;
					$sql_where_memo	=" type='memo' ";
				}
				else {
					$table_memo		=$table . "_memo";
					$sql_where_memo	= " 1 ";
				} // end if
			
				$count_memo=db_result(db_query("select count(*) as count from {$table_memo} where {$sql_where_memo} and num='{$list['uid']}'"),0,"count");
				if($count_memo) {
					$tmp_before_24h=time() - 86400;
					$count_memo_24h=db_result(db_query("select count(*) as count from {$table_memo} where {$sql_where_memo} and num='{$list['uid']}' and rdate > {$tmp_before_24h}"),0,"count");
					if($count_memo_24h) $list['cut_title'] .= " [{$count_memo}+]";
					else $list['cut_title'] .= " [{$count_memo}]";
				}
			} // end if			

			if(($list['rdate'] + 86400 ) > time()){
				// $list['cut_title'] .= " <img src='/sboard2/stpl/board_ok/images/title_new.gif' border ='0'>";
			}
			$list['rdate']= date("Y/m/d", $list['rdate']);	//	날짜 변환
			
			// URL Link...
			$href['read']		= "/sboard2/read.php?" . href_qs("uid={$list['uid']}",$qs_basic);
			$href['list']		= "/sboard2/list.php?db={$dbinfo['table_name']}";
?>
			<tr> 
				<td width="300" height="25" class="thm9"><a href =<?php echo $href['read']?>><?php echo $list['cut_title']?></a></td>
				<td width="80" height="25" class="thm9"align="center"><?php echo $list['userid']?></td>
				<td width="80" height="25" class="thm9" align="center"><?php echo $list['rdate']?></td>
				<td width="50" height="25" class="thm9" align="center"><?php echo $list['hit']?></td>
			</tr>
			<tr><td colspan=4 height=1 bgcolor=#C0C0C0></td></tr>
<?php
			}
?>
		</table>
		<a href =<?php echo $href['list']?>> <font color="#CD4110"><strong>목록보기</strong></font></a>
		<br><br>
<?php
		}
	}
?>
	</td>
	</tr>
</table>
</body>

