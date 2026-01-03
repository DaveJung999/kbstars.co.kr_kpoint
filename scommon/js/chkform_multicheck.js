/*
* 비공식 sitePHPbasic JS : 03/11/05 By Sunmin Park(sponsor@new21.com)
<script LANGUAGE="JavaScript" src="/scommon/js/chkform_multicheck.js" type="Text/JavaScript"></script>
<SCRIPT LANGUAGE="JavaScript">
<!--
function CheckSubmit(theform,elementName) {
	var uids = "";
	var sUrl = "";
	for( var i=0; i<theform.elements.length; i++) {
		var ele = theform.elements[i];
		if(ele.name == elementName) {
			if(ele.checked) {
				uids += ele.value + ",";
			}
		}
	}
	if(uids=="") {
		alert("먼저 체크하세요");
		return false;
	}

	sUrl = "/ssms/sms.php?mode=uids&uids="+uids;

	//window.open(sUrl , "new21sms" , "width=300,height=400,resizable=1,scrollbars=1,toolbar=no,location=no,directories=no,status=no,menubar=no");
	theform.action=sUrl;
	theform.submit();
}
//-->
</SCRIPT>
# 체크박스들 전체체크하는것
<input type=checkbox name='allcheck' onClick="javascript: CheckRevAll(this.form,'uids[]')">
# 해당 체크박스
<input type=checkbox name='uids[]'  value='{$list[uid]}'></center>
# 체크된 것 처리하는 것
<input type=button onClick="javascript: CheckSubmit(this.form,'uids');" value='삭제'>

# PHP에서는
	$uids = explode(",",$_GET[uids]);
	if(sizeof($uids)>0) {
		foreach($uids as $value) {
			$value = trim($value);
			if($value) {
				// 
			}
		} // end foreach
	}
*/

// 체크박스 관련 자바스크립트
// 03/08/08 by Sunmin Park
function CheckRevAll(theform,elementName) {
	for( var i=0; i<theform.elements.length; i++) {
		var ele = theform.elements[i];
		if(ele.name == elementName)
			ele.checked = !ele.checked;
	}
	return;
}
function CheckAll(theform,elementName) {
	for( var i=0; i<theform.elements.length; i++) {
		var ele = theform.elements[i];
		if(ele.name == elementName)
			ele.checked = true;
	}
	return;
}
function UnCheckAll(theform,elementName) {
	for( var i=0; i<theform.elements.length; i++) {
		var ele = theform.elements[i];
		if(ele.name == elementName)
			ele.checked = false;
	}
	return;
}