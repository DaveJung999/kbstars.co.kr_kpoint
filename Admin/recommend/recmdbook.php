<?php
//=======================================================
// 설	명 : (/index.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/01/06
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/01/06 박선민 추가 수정
//=======================================================
$HEADER=array(
	'priv'	 => 99, // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard' => 1, // privAuth()
	'useApp'	 => 1,
		 // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
$search_text = isset($_REQUEST['search_text']) ? $_REQUEST['search_text'] : (isset($search_text) ? $search_text : '');

?>

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

<script language='javascript'>
<!--
function onSearch(){
	if (event.keyCode == 13 ) ManageForm.submit();
}

function EnterCheck(){
	if(event.keyCode==13)
		on_groupadd_submit();
}
function ModifySubmit(){
	var LenOfWholemember,i;
	var QueryStr='';
	var count=0;

/*	if (document.ManageForm.category_code.value=='')
	{
		alert('대상 도서분류를 선택해 주십시오.');
		document.ManageForm.category_code.focus();
		return;
	}
	*/
	LenOfWholemember=document.ManageForm.groupmember.length;

	for(i=0;i<LenOfWholemember;i++){
		if(count==0){
			 QueryStr=document.ManageForm.groupmember.options[i].value;
		} else {
			 QueryStr+='|'+document.ManageForm.groupmember.options[i].value;
		}
		count=count+1;
	}

	if(count>100) {
		alert('한 번에 100개까지의 도서만 이동하실 수 있습니다.');
		return;
	} else if(count <=0 ){
		alert('선택된 도서가 없습니다.');
		return;
	}
	document.ManageForm.querystr.value=QueryStr;
	document.ManageForm.action='ok.php?mode=modify_recmdbook&data2='+count;
	document.ManageForm.submit();
}
function moveto_groupmember(){
/*	if (document.ManageForm.category_code.value==''){
		alert('대상 도서분류를 먼저 선택해 주십시오.');
		document.ManageForm.category_code.focus();
		return;
	}
	*/
	if (document.ManageForm.wholemember.selectedIndex == -1){
		alert('도서를 선택해 주십시오.');
		return;
	}

	var temp1, num;
	var i = 0;
	var flag = false;

	num = document.ManageForm.wholemember.selectedIndex;
	while (num != -1){
		temp1 = document.ManageForm.wholemember.options[num].value;
		while(document.ManageForm.groupmember.options[i] != null){
		 if (document.ManageForm.groupmember.options[i].value == temp1)
			{ flag = true; break; };
		 i++;
		}
		i=document.ManageForm.groupmember.options.length;
		if(flag==false){
		 splited_temp=temp1.split(';');
		 temp = new Option(splited_temp[1],splited_temp[1]);
		 document.ManageForm.groupmember.options[i] = temp;
		 document.ManageForm.groupmember.options[i].value = temp1;
		}
		document.ManageForm.wholemember.options[num].selected = false;
		num = document.ManageForm.wholemember.selectedIndex;
	}
}
function moveto_wholemember()
{
/*	if (document.ManageForm.category_code.value=='')
	{
		alert('대상 도서분류를 먼저 선택해 주십시오.');
		document.ManageForm.category_code.focus();
		return;
	}
	*/
	if (document.ManageForm.groupmember.selectedIndex == -1){
		alert('도서를 선택해 주십시오.');
		return;
	}
	var temp1, num;
	var i = 0;
	var flag = false;
	num = document.ManageForm.groupmember.selectedIndex;
	while (num != -1){
		temp1 = document.ManageForm.groupmember.options[num].value;
		i=document.ManageForm.wholemember.options.length;
		document.ManageForm.groupmember.options[num]=null;
		num = document.ManageForm.groupmember.selectedIndex;
	}
}
function on_groupcode_change()
{
	document.ManageForm.action='admin.cgi?act=RecommendedGoodsManage';
	document.ManageForm.submit();
}
function MoveUp(){
	var inx, txt,val, id
	inx = document.ManageForm.groupmember.selectedIndex

	if ( inx == -1) {
		alert("순서을 바꾸실 메뉴를 선택하세요");
		return;
	}
	if ( inx == 0 ){
		alert("더 이상 위로 올릴 수 없습니다.");
		return;
	}
	//alert(inx);
	//이름 바꾸기
	txt = document.ManageForm.groupmember.options[inx].text
	document.ManageForm.groupmember.options[inx].text = document.ManageForm.groupmember.options[inx-1].text
	document.ManageForm.groupmember.options[inx-1].text = txt
	//value 바꾸기
	val = document.ManageForm.groupmember.options[inx].value
	document.ManageForm.groupmember.options[inx].value = document.ManageForm.groupmember.options[inx-1].value
	document.ManageForm.groupmember.options[inx-1].value = val
	//ID 바꾸기
/*	id = arrID[inx]
	arrID[inx] = arrID[inx-1]
	arrID[inx-1] = id	
*/	
	document.ManageForm.groupmember.selectedIndex = inx-1

}
function MoveDown(){
	var inx, txt,val
	inx = document.ManageForm.groupmember.selectedIndex
	if ( inx == -1) {
		alert("순서을 바꾸실 메뉴를 선택하세요");
		return;
	}
	if ( inx == document.ManageForm.groupmember.length-1 ){
		alert("더 이상 아래로 내릴 수 없습니다.");
		return;
	}
	//alert(inx);
	//이름 바꾸기
	txt = document.ManageForm.groupmember.options[inx].text
	document.ManageForm.groupmember.options[inx].text = document.ManageForm.groupmember.options[inx+1].text
	document.ManageForm.groupmember.options[inx+1].text = txt
	//value 바꾸기
	val = document.ManageForm.groupmember.options[inx].value
	document.ManageForm.groupmember.options[inx].value = document.ManageForm.groupmember.options[inx+1].value
	document.ManageForm.groupmember.options[inx+1].value = val
	//ID 바꾸기
/*	id = arrID[inx]
	arrID[inx] = arrID[inx+1]
	arrID[inx+1] = id	
*/	
	document.ManageForm.groupmember.selectedIndex = inx+1

}


function onHistdel(uid){
	var rtn = confirm('삭제되면 정보는 되 돌릴 수 없습니다.\n\n정말로 삭제하시겠습니까?');
	if (rtn == false) return;
	document.ManageForm.action='ok.php?mode=hist_del&uid='+uid;
	document.ManageForm.submit();
}

function onHistwrite(){
	var rtn = confirm('위 도서를 추천 도서목록 리스트로 저장하시겠습니까?');
	if (rtn == false) return;
	var LenOfWholemember,i;
	var QueryStr='';
	var count=0;

	LenOfWholemember=document.ManageForm.groupmember.length;

	for(i=0;i<LenOfWholemember;i++){
		if(count==0){
			 QueryStr=document.ManageForm.groupmember.options[i].value;
		} else {
			 QueryStr+='|'+document.ManageForm.groupmember.options[i].value;
		}
		count=count+1;
	}

	if(count>100)
	{
		alert('한 번에 100개까지의 도서만 이동하실 수 있습니다.');
		return;
	} else if(count <=0 ){
		alert('선택된 도서가 없습니다.');
		return;
	}
	document.ManageForm.querystr.value=QueryStr;
	document.ManageForm.action='ok.php?mode=hist_write&data1=recmdbook&data2='+count;
	document.ManageForm.submit();

}

//-->
</script>

<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">

<form name='ManageForm' method=post onSubmit='javascript:return false;'>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td>
		<table width="97%" border="0" align="center" cellpadding="0" cellspacing="0">
			<tr>
			<td width="22"><img src="/images/admin/tbox_l.gif" width="22" height="22"></td>
			<td background="/images/admin/tbox_bg.gif"><strong>추천 도서목록 관리 </strong></td>
			<td align="right" width="5"><img src="/images/admin/tbox_r.gif" width="5" height="22"></td>
			</tr>
		</table>
		<br>
		<table width='97%' border='0' align="center" cellpadding='4' cellspacing='1' bgcolor='#aaaaaa'>
			<tr height=25 bgcolor=#F8F8EA>
			 <td height="25" bgcolor="#F0EBD6" align="center"><table width="98%"	border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
				 <td height="18" valign="middle"><img src="/images/admin/recmd_reserve.gif" width="16" height="15" border="0" align="absmiddle" /> <strong>추천 도서목록 관리</strong>&nbsp;</td>
				</tr>
			 </table></td>
			</tr>
			<tr bgcolor=#F8F8EA>
			<td width="97%" bgcolor="#F8F8EA">
			 <table border=0 cellpadding=0 cellspacing=0 width='100%' bgcolor='#F8F8EA'>
				<tr>
					<td width=97%><table width="100%"	border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
		<td height="1"></td>
	</tr>
</table>

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
		<input type=hidden name="querystr" value=''>
	<tr>
		<td align="center">
		<table width='100%' border='0' align="center" cellpadding='0' cellspacing='0'>
			<tr bgcolor=#F0EBD6>
			<td bgcolor="#F0EBD6">
				<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#aaaaaa">
				<tr>
					<td width="45%" height="200">
					<table border=0 cellspacing=1 cellpadding=3 width='100%'>
						<tr>
						<td bgcolor='#D2BF7E'>&nbsp;&nbsp;전체 도서 목록 (총 : 
<?php
 
								$sql_where = " (data13 != '' or data13 != '0' ) ";
							if ($search_text != "") $sql_where .= " and (title like '%$search_text%' or data2 like '%$search_text%') ";
							echo db_resultone("Select count(uid) as cnt	from {$SITE['th']}board2_books where $sql_where ", 0, "cnt");
						
?> 개)</td>
						</tr>
					</table>
					<select name="wholemember" size='15' multiple class="styleselect" style='width:100%;' border='0' ondblclick="javascript:moveto_groupmember();">
<?php
							$sql="Select * from {$SITE['th']}board2_books where $sql_where order by title";
							$rs_list = db_query($sql);
							$count['total_1'] = db_count();
							for ($i=0;$i<$count['total_1'];$i++)
							{
								$list = db_array($rs_list);
								$list['rdate'] = date('y-m-d', $list['rdate']);
								echo "<option value='{$list['uid']};{$list['title']}[{$list['catetitle']}] ({$list['data2']})'>{$list['title']}[{$list['catetitle']}] ({$list['data2']})</option>\n";
							}
						
?>
					</select></td>
					<td width="10%" align="center" bgcolor="#F0EBD6"><input name="button2" type='image' onclick="javascript:moveto_groupmember();" src="/images/admin/recmd_plus.gif" width="50" height="18"	border="0" align="absmiddle" />
					<br>
					<br>
					<input name="button" type='image' onClick="javascript:moveto_wholemember();"	src="/images/admin/recmd_del.gif" width="50" height="18"	border="0" align="absmiddle">					</td>
					<td width="45%" align="center">
					<table border=0 cellspacing=1 cellpadding=3 width='100%'>
						<tr>
						<td bgcolor='#D2BF7E'><table width="95%" border="0" align="center" cellpadding="0" cellspacing="0">
							<tr>
								<td>&nbsp;선택된 도서 목록 (총 :
<?php
 
								$sql_where = " data13 > 0	";
							echo db_resultone("Select count(uid) as cnt	from {$SITE['th']}board2_books where $sql_where ", 0, "cnt");
						
?>
								개)</td>
								<td align="right"><a href="#" onclick="javascript:ModifySubmit();"><img src="/images/admin/recmd_save.gif" width="50" height="18" border="0" align="absmiddle" /></a></td>
							</tr>
							</table></td>
						</tr>
					</table>
					<select name="groupmember" size='15' multiple class="styleselect" style='width:100%' border='0' ondblclick="javascript:moveto_wholemember();">
<?php
							$sql="Select * from {$SITE['th']}board2_books where $sql_where order by data13, num desc, re";
							$rs_list = db_query($sql);
							$count['total_2'] = db_count();
							for ($i=0;$i<$count['total_2'];$i++)
							{
								$list = db_array($rs_list);
								$list['rdate'] = date('y-m-d', $list['rdate']);
								echo "<option value='{$list['uid']};{$list['title']}[{$list['catetitle']}]	({$list['data2']})'>{$list['title']}[{$list['catetitle']}]	({$list['data2']})</option>\n";
							}
							
?>
					</select>					</td>
				</tr>
				</table>
				<input name="search_text" type="text" class="styleinput"	size="25"	onKeyDown="onSearch();">
				<input name="search_btn" type="button" class="stylebutton_blue" value=" 검색 " onClick="javascript:document.ManageForm.submit();">
				<input name="show_all" type="button" class="stylebutton_blue" value=" 전체 " onClick="javascript:document.ManageForm.search_text.value='';javascript:document.ManageForm.submit();">			</td>
			</tr>
		</table>
		
		<table width="100%"	border="0" align="center" cellpadding="0" cellspacing="0">
			<tr>
				<td height="1"></td>
			</tr>
		</table>
			
		<table width="100%" border="0" align="center" cellpadding="1" cellspacing="1" bgcolor="#aaaaaa">
			<tr height="24">
				<td height="25" bgcolor="#F0EBD6"><table width="98%"	border="0" align="center" cellpadding="0" cellspacing="0">
					<tr>
						<td width="44%" valign="middle"><img src="/images/admin/recmd_decide.gif" width="14" height="15" border="0" align="absmiddle"> <strong>History</strong>&nbsp;</td>
						<td width="56%" align="right"><a href="#" onClick="onHistwrite();"><img src="/images/admin/recmd_save.gif" width="50" height="18" border="0" align="absmiddle"></a> </td>
					</tr>
				</table></td>
			</tr>
		</table>
		<table width="100%"	border="0" align="center" cellpadding="0" cellspacing="0">
			<tr>
				<td height="1"></td>
			</tr>
		</table>
		
		<table width='100%' border='0' align="center" cellpadding='0' cellspacing='1' bgcolor='#bbbbbb'>
			<tr bgcolor=#F0EBD6>
			<td height="150" align=center valign="top" bgcolor="#F0EBD6">
				<table width="100%"	border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td><table width="100%"	border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td width="4%" bgcolor="#D2BF7E" ><table width="100%"	border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td width="99%" height="23" align="center" >No</td>
										<td width="1%" align="right"></td>
									</tr>
							</table></td>
							<td width="60%" bgcolor="#D2BF7E" ><table width="100%"	border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td width="98%" align="center">도서목록</td>
										<td width="2%" align="right"></td>
									</tr>
							</table></td>
							<td width="11%" bgcolor="#D2BF7E" ><table width="100%"	border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td width="98%" align="center">건수</td>
										<td width="2%" align="right"></td>
									</tr>
							</table></td>
							<td width="13%" bgcolor="#D2BF7E" ><table width="100%"	border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td width="98%" align="center">날짜</td>
										<td width="2%" align="right"></td>
									</tr>
							</table></td>
							<td width="12%" bgcolor="#D2BF7E" ><table width="100%"	border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td width="98%" align="center">관리</td>
										<td width="2%" align="right"></td>
									</tr>
							</table></td>
						</tr>
<?php
							$sql_hist = "select * from {$SITE['th']}board2_books_history where data1 = 'recmdbook' order by rdate desc";
							$rs_list_hist = db_query($sql_hist);
							$cnt_hist = db_count();
							for ($k = 0 ; $k < $cnt_hist; $k++){
								$list_hist = db_array($rs_list_hist);
								$no++;
								$list_hist['rdate'] = date("Y-m-d", $list_hist['rdate']);
							
						
?>
						<tr>
							<td height="20" align="center"	bgcolor="#F0EBD6"><?=$no?></td>
							<td bgcolor="#F0EBD6">&nbsp;<a href="#" onClick="window.open('./view_history.php?uid=<?=$list_hist['uid']?>','hist_pop','scrollbars=yes,status=no,toolbar=no,location=no,menubar=no,resizable=no,width=500,height=555');">
								<?=cut_string($list_hist['title'], 75)
?>
							</a></td>
							<td align="center" bgcolor="#F0EBD6"><?=$list_hist['data2']?> 건 </td>
							<td align="center" bgcolor="#F0EBD6"><?=$list_hist['rdate']?></td>
							<td align="center" bgcolor="#F0EBD6"><input name="del" type="button" class="stylebutton_yellow" value=" 삭 제 " onClick="onHistdel('<?=$list_hist['uid'] ?>');"></td>
						</tr>
						<tr>
							<td height="1" colspan="5" align="center"	bgcolor="#bbbbbb"></td>
							</tr>
						
<?php
							}
						
?>
					</table></td>
				</tr>
			</table>				</td>
			</tr>
		</table>		</td>
	</tr>
</table></td></tr>
			 </table>
			 </td>
			</tr>
		</table>
	</td>
	</tr>
</table>
</form>

<?php
//=======================================================
echo $SITE['tail'];
?>