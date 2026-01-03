/*
	필수 자바 스크립트 정의

	Xonda.Net

	작성일	:	2004.10.30  이상열
	수정일	:	2005.05.02  이상열

	Copyright(c) 2001-2004 Xonda.net Co,.Ltd. All Rights Reserved.
*/
/*
	롤오버 이미지 처리 함수
*/
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
	var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
	if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
	d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}

/*
	공용 일반
*/
var IEVer = 0;
var MSVer = 0;

if(navigator.appName.indexOf("Microsoft") > -1)
{
	IEVer = parseFloat(navigator.appVersion.substring(navigator.appVersion.indexOf("MSIE")+5, navigator.appVersion.indexOf("Windows")-2));
}

function parseURL(u_Cnt)
{ // 현재의 URL 파싱...
	var openURL = "";
	var tmStr = "";
	var tmCnt = -2;

	var cURL = document.URL;

	for (var i=0; i<cURL.length; i++)
	{
		if(cURL.substring(i, i+1) == "/")
		{
			openURL += tmStr;
			tmStr = "";

			if(tmCnt >= u_Cnt && u_Cnt > -1) break;

			tmCnt++;
		}

		tmStr += cURL.substring(i, i+1);
	}

	return openURL;
}

/*
	문자열 처리관련
*/
function replaceString(srcTXT, orgTXT, repTXT)
{
	while(srcTXT.match(orgTXT) != null)
	{
		srcTXT = srcTXT.replace(orgTXT, repTXT);
	}

	return srcTXT;
}

function chkInt()
{
	if(event.keyCode == 8) return;
	if(event.keyCode == 9) return;
	if(event.keyCode == 45) return;
	if(event.keyCode == 46) return;
	if(event.keyCode == 35) return;
	if(event.keyCode == 36) return;
	if(event.keyCode == 36) return;
	if(event.keyCode >= 37 && event.keyCode <= 40) return;

	if(event.keyCode >= 48 && event.keyCode <= 57) return;
	if(event.keyCode >= 96 && event.keyCode <= 105) return;

	event.returnValue=false;
}

function chkIntEx()
{
	if(event.keyCode == 8) return true;
	if(event.keyCode == 9) return true;
	if(event.keyCode == 45) return true;
	if(event.keyCode == 46) return true;
	if(event.keyCode == 35) return true;
	if(event.keyCode == 36) return true;
	if(event.keyCode == 36) return true;
	if(event.keyCode >= 37 && event.keyCode <= 40) return true;

	if(event.keyCode >= 48 && event.keyCode <= 57) return true;
	if(event.keyCode >= 96 && event.keyCode <= 105) return true;

	return false;
}

function chkNum(phonenum)
{
	for (var zz = 0; zz < phonenum.length; zz++)
		{
		var ch = phonenum.substring(zz, zz+1);

		if (ch >= "0" && ch <= "9")
				{

		}
				else if (ch == "-")
				{

				}
		else
				{
					//alert (ch);
			return false;
				}
	}
		return true;
}

function chkMobileNum(phoneNum)
{
	if(!parseFloat(phoneNum) || (phoneNum.length != 10 && phoneNum.length != 11))
		return false;

	switch(phoneNum.substring(0, 3))
	{
		case "010" :
		case "011" :
		case "013" :
		case "016" :
		case "017" :
		case "018" :
		case "019" :
			var chkNum = "";

				chkNum = phoneNum.substring(3, phoneNum.length-4);

			//if(parseFloat(chkNum) == 0 || (parseFloat(chkNum) + "").length != chkNum.length)
			if(parseFloat(chkNum) == 0)
				return false;

			//	chkNum = phoneNum.substring(phoneNum.length-4, phoneNum.length);

			//if(parseFloat(chkNum) == 0)
			//	return false;

			break;
		default :
			return false;
	}

	return true;
}

function chkPhoneNum(phoneNum)
{
	if(!parseFloat(phoneNum))
		return false;

	switch(phoneNum.substring(0, 2))
	{
		case "00" :
			// 국제 전화...
			return false;

			break;
		case "02" :
			// 서울 지역 번호
			if(phoneNum.length < 8 || phoneNum.length > 10)
				return false;

			var chkNum = "";

				chkNum = phoneNum.substring(2, phoneNum.length-4);

			if(parseFloat(chkNum) == 0 || (parseFloat(chkNum) + "").length != chkNum.length)
				return false;

			//	chkNum = phoneNum.substring(phoneNum.length-4, phoneNum.length);

			//if(parseFloat(chkNum) == 0)
			//	return false;

			break;
		case "01" :
		case "03" :
		case "04" :
		case "05" :
		case "06" :
		case "07" :
		case "08" :
		case "09" :
			//휴대폰 번호, 서울이외의 지역번호 및 서비스 번호
			if(phoneNum.length < 9 || phoneNum.length > 11)
				return false;

			var chkNum = "";

				chkNum = phoneNum.substring(3, phoneNum.length-4);

			if(parseFloat(chkNum) == 0 || (parseFloat(chkNum) + "").length != chkNum.length)
				return false;

			//	chkNum = phoneNum.substring(phoneNum.length-4, phoneNum.length);

			//if(parseFloat(chkNum) == 0)
			//	return false;

			break;
		default :
			return false;

			break;
	}

	return true;
}

function LimitTextSizeEx(orgText, limitSize)
{// 텍스트 입력창의 글자 바이트수를 제한 한다.
	var prvByte = StrByteLengthEx(orgText);
	var evKey = event.keyCode;

	switch(evKey)
	{
		case 8 :
		case 9 :
		case 16 :
		case 17 :
		case 18 :
		case 20 :
		case 33 :
		case 34 :
		case 35 :
		case 36 :
		case 37 :
		case 38 :
		case 39 :
		case 40 :
		case 46 :

			break;
		case 229 :
			if(prvByte >= (limitSize-1))
				return false;

			break;
		default :
			if(prvByte >= limitSize)
				return false;

			break;
	}

	return true;

}

function StrByteLength(strVal)
{// 리턴 문자가 2Byte(13+10) 계산됨...
	var strLen = 0;

	for(var i=0; i<strVal.length; i++)
	{
		var chrCode = strVal.charCodeAt(i);

		strLen++;

		if(chrCode > 255)
			strLen++;
	}

	return strLen;
}

function StrByteLengthEx(strVal)
{// 2Byte(13+10)의 리턴 문자에서 1바이트(10)는 계산에서 제외...
	var strLen = 0;

	for(var i=0; i<strVal.length; i++)
	{
		var chrCode = strVal.charCodeAt(i);

		//if(chrCode == 10) // 무시하기 위한 바이트값
		//	continue;

		strLen++;

		if(chrCode > 255)
			strLen++;
	}

	return strLen;
}

function GetTextByteEx(strVal, limitByte)
{// 해당 바이트 만큼만 가져오기
	var strLen = 0;

	var retVal = "";

	for(var i=0; i<strVal.length; i++)
	{
		var chrCode = strVal.charCodeAt(i);

		if(chrCode == 10) // 무시하기 위한 바이트값
			continue;

		strLen++;

		if(chrCode > 255)
			strLen++;

		if(strLen > limitByte)
			break;

		retVal += strVal.charAt(i);
	}

	return retVal;
}

function checkInt(str) {

	var point_C = 0;

	if (!str) return 0;

	var ok = "";

	for (var zz = 0; zz < str.length; zz++) {

		var ch = str.substring(zz, zz+1);

		if(zz == 0 && ch == "-" && str.length > 1){

		}else if(ch == "." && point_C < 1){

			point_C++;
		}else if (ch < "0" || "9" < ch){
			return false;
		}
		else
			ok += ch;
	}

	return true;
}

function IntToMoney(val){

	var temp = "";
	var temp_sign;

	var tm_c1 = "";
	var tm_c2 = "";

	var tm_pc = 0;

	tmp_sign = val.substring(0,1);

	v_len = val.length;

	if(tmp_sign == "-")
		val = val.substring(1, v_len);

	if(checkInt(val)){

		for(tt=0; tt<v_len; tt++){

			ch = val.substring(tt,tt+1);

			if(ch == "."){
				tm_pc++;
				tm_c2 = ch;
			}else{

				if (tm_pc < 1){
					tm_c1 += ch;
				}else{
					tm_pc++;
					tm_c2 += ch;
				}
			}
		}

		v_len = tm_c1.length;

		comma_count = v_len/3;
		first_count = v_len%3;

		k = 0;
		c = 0;

		for(zz=0; zz<v_len; zz++){

			if((first_count == zz || k == 3) && zz != 0){

				k = 0;

				temp = temp + ",";

				c++;
			}

			k++;

			temp = temp + tm_c1.substring(zz,zz+1);
		}

		if(tmp_sign == "-")
			temp = tmp_sign + temp;

		temp += tm_c2;

		return temp;

	}else{
		return "0";
	}
}

function MoneyToInt(val){

	var temp = "";

	if(val.substring(0,1) == "-"){

		if(!checkInt(val.substring(1,2))){

			return false;

		}else{
			for( zz=1; zz<val.length; zz++){
				if(checkInt(val.substring(zz,zz+1))){

					temp = temp + val.substring(zz,zz+1);

				}else if(val.substring(zz,zz+1) == ","){


				}else{

					return false;
				}
			}

			temp = "-" + temp;

			return temp;
		}

	}else if(val == "Null" || val == "NULL" || val == "null" || val == ""){

		return "";

	}else if(val.substring(0,1) < "0" || val.substring(0,1) > "9"){

		return false;

	}else{

		for( zz=0; zz<val.length; zz++){
			if(checkInt(val.substring(zz,zz+1))){

				temp = temp + val.substring(zz,zz+1);

			}else if(val.substring(zz,zz+1) == ","){

			}else{

				return false;
			}
		}

		return temp;

	}
}

/*
	입력 상자 처리관련
*/
function checkInputBox(fld, max_size, next_fld, typeBOOL)
{
	if(!parseFloat(max_size))
		max_size = 1048576;
	else if(parseFloat(max_size) < 1)
		max_size = 1048576;

	if(typeBOOL == 1 && !chkIntEx())
	{// 숫자만 입력...(Ctrl-C, V 가능하게 하기 위해 comment 처리)
		//event.returnValue = false;
		//return;
	}
	else if(typeBOOL == 2 && event.keyCode == 229)
	{// 한글 입력 취소...
		event.returnValue = false;
		return;
	}

	if(!LimitTextSizeEx(fld.value, max_size))
	{
		//event.returnValue = false;

		fld.blur();

		if(typeof(next_fld) == "object")
		{
			next_fld.focus();
			//next_fld.select();
		}
	}
}

function checkInputBoxEx(fld, max_size, next_fld, typeBOOL)
{
	if(!parseFloat(max_size))
		max_size = 1048576;
	else if(parseFloat(max_size) < 1)
		max_size = 1048576;

	if(typeBOOL == 1 && !chkIntEx())
	{// 숫자만 입력...
		event.returnValue = false;
		return;
	}
	else if(typeBOOL == 2 && event.keyCode == 229)
	{// 한글 입력 취소...
		event.returnValue = false;
		return;
	}

	if(!LimitTextSizeEx(fld.value, max_size))
	{
		event.returnValue = false;

		fld.blur();

		if(typeof(next_fld) == "object")
		{
			next_fld.focus();
			//next_fld.select();
		}
	}
}

/*
	체크 박스 처리관련
*/
var marking_flag = false;

function All_check_marking(field)
{
	if(typeof(field) != "object") return;

	var len = field.length;

	if(typeof(len) == "undefined"){ field.checked = marking_flag; return;}

	for(var i=0; i<len; i++)
	{
		field[i].checked = marking_flag;
	}

	return;
}

function isAllChecked(field)
{
	try
	{
		if(typeof(field) != "object") return;

		var len = field.length;

		if(typeof(len) == "undefined") return field.checked;

		for(var i=0; i<len; i++)
		{
			if(!field[i].checked)
				return false;
		}

		return true;
	}
	catch(e)
	{
		return false;
	}
}

/*
	윈도우 열기 관련
*/
var c_LEFT			= 1;
var c_RIGHT			= 2;
var c_CENTER		= 3;

var c_TOP			= 1;
var c_BOTTOM		= 2;
var c_MIDDLE		= 3;

function openWindow(Url, WindowName, Width, Height, Align, v_Align, stScroll, stResizable)
{ // 일반 윈도우 열기
	var createWin;
	var	winX, winY;

	try
	{
		if(WindowName == "") WindowName = "openWindow";

		if(Width == "") Width = 100; else if(parseInt(Width) < 100) Width = 100;
		if(Height == "") Height = 50; else if(parseInt(Height) < 50) Height = 50;

		switch(Align)
		{
			case 0 :
				winX = (screen.availWidth - Width) * 0.5;
				break;
			case 1 :
				winX = 0;
				break;
			case 2 :
				winX = screen.availWidth - Width - 10;
				break;
			case 3 :
				winX = (screen.availWidth - Width) * 0.5;
				break;
			default :
				winX = Align;
		}

		switch(v_Align)
		{
			case 0 :
				winY = (screen.availHeight - Height) * 0.5;
				break;
			case 1 :
				winY = 0;
				break;
			case 2 :
				winY = screen.availHeight - Height - 48;
				break;
			case 3 :
				winY = (screen.availHeight - Height) * 0.5;
				break;
			default :
				winY = v_Align;
		}

		var w_scroll = "no";
		var w_resizable = "yes";

		if(stScroll)
			w_scroll = "yes";

		if(stResizable)
			w_resizable = "yes";


							   var w_style = "width=" + Width + "px,height=" + Height + "px,left=" + winX + "px,top=" + winY + "px,resizable=" + w_resizable + ",scrollbars=" + w_scroll + ",status=yes";

		createWin = window.open(Url, WindowName, w_style);

	}
	catch(e)
	{
		alertMSG("[" + WindowName + "] openWindow Failled !!!");
		return false;
	}

	return createWin;

}

function openDlgWindow(Url, WindowName, Width, Height, Align, v_Align, argOBJ, stScroll, stResizable)
{ // 모달다이알로그 윈도우 열기
	var createWin;
	var	winX, winY;

	try
	{
		if(WindowName == "") WindowName = "openDlgWindow";

		if(Width == "") Width = 100; else if(parseInt(Width) < 100) Width = 100;
		if(Height == "") Height = 50; else if(parseInt(Height) < 50) Height = 50;

		switch(Align)
		{
			case 0 :
				winX = (screen.availWidth - Width) * 0.5;
				break;
			case 1 :
				winX = 0;
				break;
			case 2 :
				winX = screen.availWidth - Width - 10;
				break;
			case 3 :
				winX = (screen.availWidth - Width) * 0.5;
				break;
			default :
				winX = Align;
		}

		switch(v_Align)
		{
			case 0 :
				winY = (screen.availHeight - Height) * 0.5;
				break;
			case 1 :
				winY = 0;
				break;
			case 2 :
				winY = screen.availHeight - Height - 48;
				break;
			case 3 :
				winY = (screen.availHeight - Height) * 0.5;
				break;
			default :
				winY = v_Align;
		}

		var w_scroll = "no";
		var w_resizable = "yes";

		if(stScroll)
			w_scroll = "yes";

		if(stResizable)
			w_resizable = "yes";


		var w_style = "dialogLeft:" + winX + "px; dialogTop:" + winY + "px; dialogWidth:" + Width + "px; dialogHeight:" + Height + "px; scroll:" + w_scroll + "; status:yes; help:no; resizable:" + w_resizable + "; unadorned:yes;";

		createWin = window.showModalDialog(Url, argOBJ, w_style);

	}
	catch(e)
	{
		alertMSG("[" + WindowName + "] openDlgWindow Failled !!!");
		return false;
	}

	return;

}

function openDlgWindowEx(Url, WindowName, Width, Height, Align, v_Align, argOBJ, stScroll, stResizable)
{ // 모달리스 윈도우 열기
	var createWin;
	var	winX, winY;

	try
	{
		if(WindowName == "") WindowName = "openDlgWindowEx";

		if(Width == "") Width = 100; else if(parseInt(Width) < 100) Width = 100;
		if(Height == "") Height = 50; else if(parseInt(Height) < 50) Height = 50;

		switch(Align)
		{
			case 0 :
				winX = (screen.availWidth - Width) * 0.5;
				break;
			case 1 :
				winX = 0;
				break;
			case 2 :
				winX = screen.availWidth - Width - 10;
				break;
			case 3 :
				winX = (screen.availWidth - Width) * 0.5;
				break;
			default :
				winX = Align;
		}

		switch(v_Align)
		{
			case 0 :
				winY = (screen.availHeight - Height) * 0.5;
				break;
			case 1 :
				winY = 0;
				break;
			case 2 :
				winY = screen.availHeight - Height - 48;
				break;
			case 3 :
				winY = (screen.availHeight - Height) * 0.5;
				break;
			default :
				winY = v_Align;
		}

		var w_scroll = "no";
		var w_resizable = "yes";

		if(stScroll)
			w_scroll = "yes";

		if(stResizable)
			w_resizable = "yes";

		 var w_style = "dialogLeft:" + winX + "px; dialogTop:" + winY + "px; dialogWidth:" + Width + "px; dialogHeight:" + Height + "px; scroll:" + w_scroll + "; status:yes; help:no; resizable:" + w_resizable + "; unadorned:yes;";

		createWin = window.showModelessDialog(Url, argOBJ, w_style);

	}
	catch(e)
	{
		alertMSG("[" + WindowName + "] openDlgWindowEx Failled !!!");
		return false;
	}

	return createWin;

}

function reSizeWindow(scrollType, statusType)
{

	var Width, Height;

	if(scrollType)
		Width = document.body.childNodes[0].clientWidth + 30;
	else
		Width = document.body.childNodes[0].clientWidth + 10;

	if(statusType)
		Height = document.body.childNodes[0].clientHeight + 50;
	else
		Height = document.body.childNodes[0].clientHeight + 30;

	window.resizeTo(Width, Height);
	window.resizable = "no";

	var winX = (screen.availWidth - Width) * 0.5;
	var winY = (screen.availHeight - Height) * 0.5;

	window.moveTo(winX, winY);
}

function reSizeWindowEx(scrollType, statusType)
{
	var Width, Height;

	if(scrollType)
		Width = document.body.childNodes[0].clientWidth + 30;
	else
		Width = document.body.childNodes[0].clientWidth + 10;

	if(statusType)
		Height = document.body.childNodes[0].clientHeight + 50;
	else
		Height = document.body.childNodes[0].clientHeight + 30;

	window.resizeTo(Width, Height);
	window.resizable = "no";

}

/*
	사용자 메시지 처리 관련
*/
var BUTTON_OK			= 0x10000000;
var BUTTON_CANCEL		= 0x01000000;
var BUTTON_CONFIRM		= 0x00100000;
var BUTTON_YES			= 0x00010000;
var BUTTON_NO			= 0x00001000;

function alertMSG(msg)
{
	alert(msg);
	/*
	var argOBJ = new Object;

		argOBJ.ButtonType = BUTTON_OK;

	openDlgWindow("/Common/MessageBox.asp", "MessageBox", 10, 10, 0, 0, argOBJ)
	*/
	return true;
}

function confirmMSG(msg, buttonType, defaultBT)
{
	return confirm(msg);
}

/*
	개체 접근 관련...
*/
function getText_TextBox(type, baseOBJ)
{// 메시지 박스의 텍스트를 가져오기 위함....
	try
	{
		switch(type)
		{
			case 0 : // 기본적인 이모티콘 출력 형태...
				return baseOBJ.parentElement.parentElement.parentElement.childNodes[0].childNodes[0].childNodes[0].innerText;

				break;
			case 1 :

				break;
			case 2 :

				break;
			default : // 기본적인 이모티콘 출력 형태...
				return baseOBJ.parentElement.parentElement.parentElement.childNodes[0].childNodes[0].childNodes[0].innerText;

				break;
		}
	}
	catch(e)
	{
		return "";
	}
}

/*
	날짜 처리 관련 함수...
*/

function isDate2(val1,val2,val3) {
 var ret = false;
 var year, month, day;
 var thisMonth, nextMonth, maxDay;

 year = month = day = 0;

 year = val1;

 month = val2;

 day = val3;

 if (year >= 1900 && (month >= 1 || month <=12)) {
  thisMonth = new Date(year, month-1, 1);
  nextMonth = new Date(year, month, 1);

  maxDay  = (nextMonth - thisMonth) / 1000 / 60 / 60 / 24;

  if (day >= 1 && day <= maxDay)
   ret = true;
 }

 return ret;
}


function isDate(year, month, day)
{
	var tmDate = new Date();

	try
	{
		tmDate.setFullYear(parseFloat(year));
		tmDate.setMonth(parseFloat(month)-1);
		tmDate.setDate(parseFloat(day));

		if(tmDate.getFullYear() != parseFloat(year)
			|| (tmDate.getMonth()+1) != parseFloat(month)
			|| tmDate.getDate() != parseFloat(day))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	catch(e)
	{
		return false;
	}
}

/*
	쿠키 관련
*/
function setCookie(name, value, expire)
{
	document.cookie = name + "=" + escape(value) + ( (expire) ? "; path=/; expires=" + expire.toGMTString() : "");
}

function registCookie(uName)
{
	var today = new Date();

	var expire = new Date(today.getTime() + 60*60*24*31*1000);

	setCookie("userName", uName, expire);
}

function getCookie(uName)
{
	var flag = document.cookie.indexOf(uName+"=");

	if(flag != -1)
	{
		flag += uName.length + 1;

		var end = document.cookie.indexOf(";", flag);

		if(end == -1)
			end = document.cookie.length;

		return unescape(document.cookie.substring(flag, end));
	}

	return "";
}
// 2006.3.1 active-x 배포방식 변경에 따른 수정, 최상호
function playflash(file,width,height,bgcolor,quality,name){
		document.write('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="'+width+'" height="'+height+'" id="'+name+'">');  //플래쉬플레이어 버전이 이전버전일경우 버전 8을 변경
		document.write('<param name="movie" value="'+file+'" />');
		document.write('<param name="quality" value="'+quality+'" />');
		document.write('<param name="wmode" value="transparent" />');  //투명플래쉬가 아닐경우 이 라인을 삭제
		document.write('<param name="bgcolor" value="'+bgcolor+'" />');
		document.write('<embed src="'+file+'" quality="'+quality+'" wmode="transparent" bgcolor="'+bgcolor+'" width="'+width+'" height="'+height+'" name="'+name+'" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />');
		document.write('</object>')
}

