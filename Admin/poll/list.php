<?php
//=======================================================
// 설	명 : 설문 종합관리(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/25
// Project: sitePHPbasic
// ChangeLog
//	 DATE	 수정인				 수정 내용
// -------- ------ --------------------------------------
// 03/08/25 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'	 => 1, // 인증유무 (0:모두에게 허용)
	'class'	 =>	"root", // 관리자만 로그인
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp'	 => 1,
	'useBoard' => 1,
	'html_echo'	 => 0	 // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

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
// 기본 URL QueryString
$qs_basic = "db={$db}".					//table 이름
			"&mode=".					// mode값은 list.php에서는 당연히 빈값
			"&cateuid={$cateuid}".		//cateuid
			"&pern={$pern}" .				// 페이지당 표시될 게시물 수
			"&sc_column={$sc_column}".	//search column
			"&sc_string=" . urlencode(stripslashes($sc_string)). //search string
			"&page={$page}";				//현재 페이지

$table_pollinfo=$SITE['th'] . "pollinfo";	//게시판 관리 테이블

// 관리자페이지 환경파일 읽어드림
$rs=db_query("select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
$pageinfo=db_count() ? db_array($rs) : back("관리자페이지 환경파일을 읽을 수가 없습니다");

// URL Link
$href['write']="./write.php";

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<html>
<head>
<title>설문조사 리스트</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<style type="text/css">
<!--
body {
	margin-left: 5px;
	margin-top: 15px;
	margin-right: 5px;
	margin-bottom: 5px;
	background-color:F8F8EA;
}
-->
</style>

<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
<body leftmargin="0" topmargin="0">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td>
		<table width="97%" border="0" align="center" cellpadding="0" cellspacing="0">
			<tr>
				<td width="22"><img src="/images/admin/tbox_l.gif" width="22" height="22"></td>
				<td background="/images/admin/tbox_bg.gif"><strong>설문조사 관리 </strong></td>
				<td align="right" width="5"><img src="/images/admin/tbox_r.gif" width="5" height="22"></td>
			</tr>
		</table>
		<br>
		<table width="97%" border="0" align="center">
			<tr>
			<td><table width="100%" border="0" align="left" cellpadding="3" cellspacing="1" bgcolor="#aaaaaa">
				<tr>
					<td width="73" height="20" align="center" bgcolor="#D2BF7E"><b>설문 기간</b></td>
					<td width="120" height="20" align="center" bgcolor="#D2BF7E"><b>설문주제</b></td>
					<td width="243" height="20" align="center" bgcolor="#D2BF7E"><b>설문 내용</b></td>
					<td width="49" height="20" align="center" bgcolor="#D2BF7E"><b>참여자</b></td>
					<td width="38" height="20" align="center" bgcolor="#D2BF7E"><b>성별</b></td>
					<td width="111" height="20" align="center" bgcolor="#D2BF7E"><b>연령층</b></td>
					<td width="87" height="20" align="center" bgcolor="#D2BF7E"><b>멤버</b></td>
					<td width="66" height="20" align="center" bgcolor="#D2BF7E"><b>수정</b></td>
					<td width="42" height="20" align="center" bgcolor="#D2BF7E"><b>삭제</b></td>
				</tr>
				<tr>
					<td height="20" colspan="9" bgcolor="#F8F8EA"><hr width=100%>
					</td>
				</tr>
<?php
#######################################################################
# poll_info 의 필드 내용
# member 0: 회원 레벨(0은 비로그인, 숫자는 로그인후 레벨)
# sex	 0:전체	1:남자	2:여자
# age	 0:전체 나머진 10/20	(10대 ~ 20대) 이런식으로 현
#######################################################################

$result = db_query("SELECT * from {$table_pollinfo} ORDER BY rdate DESC");	//설문정보 테이블
$total = db_count();

if(!$total){
	echo "<tr><td colspan=9 align=center>지난 설문이 없습니다.</td></tr>";
}
for($i=0; $i<$total; $i++){
	$list = db_array($result);
	$list['table'] = "{$SITE['th']}poll_" . $list['db'];

	$list['startdate']	= date('Y.m.d',$list['startdate']); 
	$list['enddate']		= date('Y.m.d',$list['enddate']); 
	
	switch ($list['sex']){
		case '0' : 
			$list['sex'] = "전체";
			break;
		case '1' : 
			$list['sex'] = "남성";
			break;
		case '2' : 
			$list['sex'] = "여성";
			break;
	}
	
	if($list['member']==0) $list['member'] = "0:모두";
	else $list['member'] .="레벨이상";

	if($list['age'] == 0){
		$age_result = "전체";
	}
	else{
		$age_arr = explode("/",$list['age']);
		$age_arr['0'] = substr($age_arr['0'],0,-1) . "0";
		$age_arr['1'] = substr($age_arr['1'],0,-1) . "0";
		if($age_arr['0'] == $age_arr['1']){
			$age_result = $age_arr['0']."대";
		}
		elseif($age_arr['1'] =="100"){
			$age_result = $age_arr['0']."대 이상";
		}
		else{
			$age_result = $age_arr['0']."대 ~ ".$age_arr['1']."대";
		}
	}
	$list['age'] = $age_result;

	$list['total_poll'] = db_resultone("SELECT count(*) as count FROM {$list['table']}",0,"count");

	// URL Link..
	$href['poll'] = "/spoll/index.php?db={$list['db']}";
?>
				<tr>
					<td width="73" align="center" bgcolor="#F8F8EA" style="border-bottom : 1px solid #b4b4b4">
					<?=$list['startdate'];?><br>~<br><?=$list['enddate'];?></td>
					<td width="120" valign="top" bgcolor="#F8F8EA" style="border-bottom : 1px solid #b4b4b4">
						<a href="<?=$href['poll'];?>" target=_blank><?=$list['title'];?></a></td>
					<td width="243" bgcolor="#F8F8EA" style="border-bottom : 1px solid #b4b4b4"><table width="100%" cellspacing="0" cellpadding="0" height="100%">
<?php
			$rs_poll = db_query("SELECT value, count(value) as count FROM {$list['table']} GROUP BY value");

			$list['total_poll'] =0;
			while( $list_poll = db_array($rs_poll) ) {
				$list["an{$list_poll['value']}"]=$list_poll['count'];
				$list['total_poll'] += $list_poll['count'];
			}

			for($j=1; $j < $list['q_num']+1; $j++){
?>
						<tr>
							<td width="66%" height="12" bgcolor="#F8F8EA"><?php echo $list["q{$j}"]; ?> </td>
							<td width="34%" height="12" bgcolor="#F8F8EA">(<?php echo $list["an{$j}"]; ?> ,<?php echo ($list['total_poll'] ==0 ? "" : round(($list["an{$j}"]/$list['total_poll'])*100)); ?> %) </td>
						</tr>
<?php
 } 
?>
					</table></td>
					<td width="49" align="center" bgcolor="#F8F8EA" style="border-bottom : 1px solid #b4b4b4"><?php echo $list['total_poll']; ?> 명</td>
					<td width="38" align="center" bgcolor="#F8F8EA" style="border-bottom : 1px solid #b4b4b4"><?php echo $list['sex'] ?> </td>
					<td width="111" align="center" bgcolor="#F8F8EA" style="border-bottom : 1px solid #b4b4b4"><?php echo $list['age']; ?> </td>
					<td width="87" align="center" bgcolor="#F8F8EA" style="border-bottom : 1px solid #b4b4b4"><?php echo $list['member'] ?> </td>
					<td width="66" align="center" bgcolor="#F8F8EA" style="border-bottom : 1px solid #b4b4b4"><a href="./write.php?mode=modify&uid=<?=$list['uid'] ?>">수정</a></td>
					<td width="42" align="center" bgcolor="#F8F8EA" style="border-bottom : 1px solid #b4b4b4"><a href="./ok.php?mode=delete&uid=<?=$list['uid'] ?>" onClick="javascript: return confirm('정말 삭제하시겠습니다.');">삭제</a></td>
				</tr>
<?php
 
} // end for			
?>
			</table></td>
			</tr>
			<tr>
				<td height="40" align=center><a href="write.php"><strong>[설문 추가하기]</strong></a></td>
			</tr>
		</table></td>
	</tr>
</table>

</body>
</html>