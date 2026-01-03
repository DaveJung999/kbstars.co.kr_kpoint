<?php
//=======================================================
// 설	명 : 일정칼렌더 추가 - infoadd.php
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/09/16
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/09/16 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv' => 2, // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard2' => 1,
	'useApp' => 1,
	'html_echo' => '', // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
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
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
$qs_basic	= "mode=&uid={$_GET['uid']}&groupid={$_GET['groupid']}";
$table_calendarinfo	= $SITE['th'] . "calendarinfo";
$table_groupinfo	= $SITE['th'] . "groupinfo";

if($_GET['mode'] == "infomodify"){
	$sql = "SELECT * from {$table_calendarinfo} WHERE uid='{$_GET['uid']}'";
	if(!$list=db_arrayone($sql)) back("해당 데이터가 없습니다");

	// 권한 체크(자기 글, 관리자이면 모든 권한)
	$auth	= array(bid => $list['bid'],gid => $list['gid'],priv_level => 99);
	if(!privAuth($auth, "priv_level",1)) back("수정 권한이 없습니다");
	unset($auth);

	$list['title']	= htmlspecialchars($list['title'],ENT_QUOTES);
	$list['content']	= htmlspecialchars($list['content'],ENT_QUOTES);
	$list['html_headpattern_s'][$list['html_headpattern']] = " selected ";

	$href['list']		= "/scalendar/index.php?db={$list['db']}";
}
elseif($_GET['mode'] == "user"){
	$href['list'] = "/scalendar/index.php?db={$_SESSION['seUserid']}";

	$sql = "SELECT * from {$table_calendarinfo} WHERE db='{$_SESSION['seUserid']}'";
	if($list=db_arrayone($sql)){
		if($list['bid'] == $_SESSION['seUid']){
			back("이미 일정칼렌더가 생성되어있습니다. 이동합니다",$href['list']);
		} else {
			back("관리자에게 문의하셔야 합니다.\\n다른 회원이 사용하여 일정칼렌더 생성이 불가능합니다");
		}
	}
}
elseif($_GET['mode'] == "group"){
	$href['list'] = "/scalendar/index.php?db=@{$_GET['groupid']}{";

	$sql = "SELECT * from {$table_calendarinfo} WHERE db='@{$_GET['groupid']}'";
	if($list=db_arrayone($sql)){
		back("이미 그룹 일정칼렌더가 생성되어있습니다. 이동합니다",$href['list']);
	} else { // 그룹정보가져와서 그룹개설자인지 여부
		$sql = "SELECT * from {$table_groupinfo} WHERE groupid='{$_GET['groupid']}' and {$bid}='{$_SESSION['seUid']}'";
		if(!$groupinfo=db_arrayone($sql)) back("해당 그룹이 없거나 그룹개설자가 아님니다");
	}
}
else back("잘못된 요청입니다");

if( $_GET['mode'] == "user" || $_GET['mode'] == "group" ){
	$list['title']		= ($_GET['mode'] == "user") ? "{$_SESSION['seName']}님의 일정" : "{$groupinfo['name']} 그룹의 일정";
	$list['cut_length']	= 12;
	$list['cut_content']	= 150;
	$list['priv_list']	= 0;
	$list['priv_write']	= 1;
	$list['priv_delete']	= 99;
	$list['html_headpatten'] = "ht";
	$list['html_headtpl']	= "basic";
	$list['html_headpattern_s'][$list['html_headpattern']] = " selected ";
}

$form_input = "name=caladd action='infook.php' method=post >";
$form_input .= substr(href_qs("mode={$_GET['mode']}",$qs_basic,1),0,-1);
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<script src="/scommon/js/chkform.js"></script>
<h3>개인 혹은 그룹 일정칼렌더 추가 설정</h3>
<table border="0" width="590" cellspacing="0" cellpadding=0" bordercolor="#000000" bordercolorlight="#000000">
	<tr>
		<td>
			<table bgcolor=#E3F1FF border="1" width="590" cellspacing="0" cellpadding="0" bordercolor="#ffffff" bordercolorlight="#000000">
		<form onsubmit="return chkForm(this)" <?php echo $form_input ; ?>>
			<tr> 
				<td nowrap> <font color=#333399><span style="font-size: 9pt"><b>일정 URL</b></font> </td>
				<td> <span style="font-size: 9pt"><b> <?php echo $href['list'] ; ?></b></td>
			</tr>
			<tr> 
				<td nowrap> <font color=#333399><span style="font-size: 9pt"><b>제목</b></font> </td>
				<td> <input type=text name=title size=40 value="<?php echo $list['title'] ; ?>" hname="일정칼렌더 제목을 입력하여 주시기 바랍니다" required>	</td>
			</tr>
			<tr> 
				<td nowrap> <font color=#333399><span style="font-size: 9pt"><b>달력에서제목길이</b></font> </td>
				<td> <input type=text name=cut_length size=10 value="<?php echo $list['cut_length'] ; ?>" hname="숫자로 입력하여 주시기 바랍니다" required option="regNum">	</td>
			</tr>
			<tr> 
				<td nowrap> <font color=#333399><span style="font-size: 9pt"><b>달력에서본문길이</b></font> </td>
				<td> <input type=text name=cut_content size=10 value="<?php echo $list['cut_content'] ; ?>" hname="숫자로 입력하여 주시기 바랍니다" required option="regNum">	</td>
			</tr>
			<tr> 
				<td nowrap> <font color=#333399><span style="font-size: 9pt"><b>권한-달력보기</b></font> </td>
				<td> <input type=text name=priv_list size=10 value="<?php echo $list['priv_list'] ; ?>" hname="숫자로 입력하여 주시기 바랍니다" required option="regNum">	</td>
			</tr>
			<tr> 
				<td nowrap> <font color=#333399><span style="font-size: 9pt"><b>권한-일정추가</b></font> </td>
				<td> <input type=text name=priv_write size=10 value="<?php echo $list['priv_write'] ; ?>" hname="숫자로 입력하여 주시기 바랍니다" required option="regNum">	</td>
			</tr>
			<tr> 
				<td nowrap> <font color=#333399><span style="font-size: 9pt"><b>권한-세부일정보기</b></font> </td>
				<td> <input type=text name=priv_read size=10 value="<?php echo $list['priv_read'] ; ?>" hname="숫자로 입력하여 주시기 바랍니다" required option="regNum">	</td>
			</tr>
			<tr> 
				<td nowrap> <font color=#333399><span style="font-size: 9pt"><b>권한-삭제권한</b></font> </td>
				<td> <input type=text name=priv_delete size=10 value="<?php echo $list['priv_delete'] ; ?>" hname="숫자로 입력하여 주시기 바랍니다" required option="regNum">	</td>
			</tr>
			<tr> 
				<td height=30 nowrap> <font color=#333399><span style="font-size: 9pt"><b>사이트스킨</b></font> </td>
				<td> <font> 
					<select name="html_headpattern">
					<option value="N" <?php echo $list['html_headpattern_s']['N']	; ?>>사이트스킨삽입하지 
					않음</option>
					<option value="ht" <?php echo $list['html_headpattern_s']['ht']	; ?>>사이트스킨 삽입</option>
					<option value="h" <?php echo $list['html_headpattern_s']['h']	; ?>>사이트스킨 해더만삽입</option>
					<option value="t" <?php echo $list['html_headpattern_s']['t']	; ?>>사이트스킨 테이만삽입</option>
					</select>
					</font> <font> <span style="font-size: 9pt">스킨명:</span></font> <input type=text name="html_headtpl" value='<?php echo $list['html_headtpl'] ; ?>' size=20></td>
			</tr>
			<tr> 
				<td nowrap> <font color=#333399><span style="font-size: 9pt"><b>해더 
				HTML</b></font> </td>
				<td> <textarea name="html_head" cols="60" rows="8" hname="숫자로 입력하여 주시기 바랍니다" required="required" option="regNum"> <?php echo $list['html_head'] ; ?></textarea> </td>
			</tr>
			<tr> 
				<td nowrap> <font color=#333399><span style="font-size: 9pt"><b>테일 
				HTML</b></font> </td>
				<td> <textarea name="html_tail" cols="60" rows="8" hname="숫자로 입력하여 주시기 바랍니다" required="required" option="regNum"> <?php echo $list['html_tail'] ; ?></textarea> </td>
			</tr>
			<tr> 
				<td width="100" height=40 nowrap bgcolor=#efefef>&nbsp; </td>
				<td bgcolor=#efefef>&nbsp; <input type=submit name=Submit value="확인"> 
				<input type=button value="취소" onClick="javascript:history.back(-1)"> </td>
			</tr>
		</form>
		</table>
	</td>
</td>
</table>

