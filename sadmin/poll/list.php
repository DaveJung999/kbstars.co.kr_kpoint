<?php
//=======================================================
// 설	명 : 설문 종합관리(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/25
// Project: sitePHPbasic
// ChangeLog
//	 DATE	 수정인				 수정 내용
// -------- ------ --------------------------------------
// 03/08/25 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?php echo  → <?php echo 변환
//=======================================================
$HEADER=array(
	'priv'	 => 2, // 인증유무 (0:모두에게 허용)
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
		usedb2	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp'	 => 1,
		useBoard => 1,
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
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
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'html_headtpl'];
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

/*	// 관리자페이지 환경파일 읽어드림
	$rs=db_query("select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
	$pageinfo=db_count() ? db_array($rs) : back("관리자페이지 환경파일을 읽을 수가 없습니다");
*/
	// URL Link
	$href['write']="./write.php";

// 테이블이 존재하지 않을 경우 admin_tableinfo 테이블정보대로 table생성
// 03/12/15
function userCreateByTableinfo($table,$createtable){
	global $SITE;
	
	$sql = "select `sql_syntax`,`comment` from {$SITE['th']}admin_tableinfo where table_name='{$table}'";
	if($tableinfo=db_arrayone($sql)){
		$sql="CREATE TABLE {$createtable} ({$tableinfo['sql_syntax']})";
		$sql .= " COMMENT='{$tableinfo['comment']}'"; // MySQL 5.5+에서 TYPE은 무시되고 MyISAM이 기본값
		if(@db_query($sql))
			return 1;
		else // 아마 해당 데이터베이스가 존재할 경우겠지. . 생성하다가 실패했으니..
			return -1; // -1로 리턴함..
	}
	else return 0;
} // end func

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<html>
<head>
<title>설문조사 리스트</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<body bgcolor="#FFFFFF" text="#000000">
<table width="950" border="1">
	<tr> 
	<td> 
		<table width="950" cellspacing="0" cellpadding="3" align="left" style="border-top : 0;border: 1px solid #484848;background-color:whitesmoke;color:black;border-left:0;border-right:0">
		<tr> 
			<td width="73" height="20" bgcolor="#CCCCCC"> <div align="center"><font size="2" color="#000000"><b>설문 기간</b></font></div></td>
			<td width="120" height="20" bgcolor="#CCCCCC"> <div align="center"><font size="2" color="#000000"><b>설문주제</b></font></div></td>
			<td width="243" height="20" bgcolor="#CCCCCC"> <div align="center"><font size="2" color="#000000"><b>설문 내용</b></font></div></td>
			<td width="49" height="20" bgcolor="#CCCCCC"> <div align="center"><font size="2" color="#000000"><b>참여자</b></font></div></td>
			<td width="38" height="20" bgcolor="#CCCCCC"> <div align="center"><font color="#000000" size="2"><b>성별</b></font></div></td>
			<td width="111" height="20" bgcolor="#CCCCCC"> <div align="center"><font color="#000000" size="2"><b>연령층</b></font></div></td>
			<td width="87" height="20" bgcolor="#CCCCCC"> <div align="center"><font color="#000000" size="2"><b>멤버</b></font></div></td>
			<td width="66" height="20" bgcolor="#CCCCCC"> <div align="center"><font color="#000000" size="2"><b>수정</b></font></div></td>
			<td width="42" height="20" bgcolor="#CCCCCC"> <div align="center"><font color="#000000" size="2"><b>삭제</b></font></div></td>
		</tr>
		<tr> 
			<td colspan="9" height="20">
			<hr width=100%>
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


	// $SITE['th']poll_??? 테이블 생성
	if(!userCreateByTableinfo("poll",$list['table'])) {
		echo "$list['table'] 설문 생성중 실패하였습니다. 관리자에게 문의 바랍니다";
		exit;
	}

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
			<td width="73" bgcolor="#FDFDFD" style="border-bottom : 1px solid #b4b4b4"> 
			<div align="center"> <font size="2"><?php echo $list['startdate'];?> <br>~<br><?php echo $list['enddate'];?></font></div></td>
			<td width="120" style="border-bottom : 1px solid #b4b4b4" valign="top"> 
			<font size="2"><a href="<?php echo $href['poll'];?>" target=_blank><?php echo $list['title'];?></a></font></td>
			<td width="243" bgcolor="#FFFFFF" style="border-bottom : 1px solid #b4b4b4"> 
			<table width="100%" cellspacing="0" cellpadding="0" height="100%">
<?php
			$rs_poll = db_query("SELECT val, count(1) as count FROM {$list['table']} GROUP BY val");

			$list['total_poll'] =0;
			while( $list_poll = db_array($rs_poll) ) {
				$list["an{$list_poll['val']}"]=$list_poll['count'];
				$list['total_poll'] += $list_poll['count'];
			}

			for($j=1; $j < $list['q_num']+1; $j++){
			
?>
				<tr> 
				<td width="66%" height="12" bgcolor="#FDFDFD"> <font size="2"> <?php echo $list["q{$j}"]; ?></font></td>
				<td width="34%" height="12" bgcolor="#f6f6f6"><font size="2">(<?php echo $list["an{$j}"]; ?>, <?php echo ($list['total_poll'] ==0 ? "" : round(($list["an{$j}"]/$list['total_poll'])*100)); ?> %) </font></td>
				</tr>
<?php 
} 
?>
			</table>
			</td>
			<td width="49" bgcolor="#FDFDFD" style="border-bottom : 1px solid #b4b4b4"> 
			<div align="center"> <font size="2"> <?php echo $list['total_poll']; ?>명</font></div></td>
			<td width="38" bgcolor="#f6f6f6" style="border-bottom : 1px solid #b4b4b4"> <div align="center"><font size="2"> <?php echo $list['sex'] ?></font></div></td>
			<td width="111" bgcolor="#FDFDFD" style="border-bottom : 1px solid #b4b4b4"><div align="center"><font size="2"> <?php echo $list['age']; ?></font></div></td>
			<td width="87" bgcolor="#FDFDFD" style="border-bottom : 1px solid #b4b4b4"> <div align="center"><font size="2"> <?php echo $list['member'] ?></font></div></td>
			<td width="66" bgcolor="#FDFDFD" style="border-bottom : 1px solid #b4b4b4"> <div align="center"><font size="2"><a href="./write.php?mode=modify&uid=<?php echo $list['uid'] ?>">수정</a></font></div></td>
			<td width="42" bgcolor="#FDFDFD" style="border-bottom : 1px solid #b4b4b4"> <div align="center"><font size="2"><a href="<?php echo $href['delete']?>" onClick="javascript: return confirm('앨범의 모든 사진이 삭제됩니다. 정말 삭제하시겠습니다.');">삭제</a></font></div></td>
		</tr>
<?php 
} // end for			
?>
		</table>
	</td>
	</tr>
	<tr> 
	<td align=center><a href="write.php">설문 추가하기</a></td>
	</tr>
</table>
</body>
</html>