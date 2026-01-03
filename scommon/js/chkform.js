<!--
/************************************** 
* 기능: 공통 라이브러리 
* 작성일: 2002-07-04 
* 작성자: 거친마루 
* 수정: 하근호 
* 2차수정 : 트론™ 
* 2차수정일 : 2002-09-09
* 3차수정 : 이동철
* 3차수정일 : 2002-12-24
* 4차수정 : saree By http://onuo.com/test/field.html
* sitePHPbasic 공식 JS : 03/09/17 By Sunmin Park(sponsor@new21.com)
* 03/11/03 박선민 regular 표현식 버그 수정
* 04/04/16 박선민 Submit 버튼 없을시에도 동작되게^^
***************************************
* 꼭 FORM에 name을 정의해 준다!!
<script LANGUAGE="JavaScript" src="/scommon/js/chkform.js" type="Text/JavaScript"></script>
<FORM name='form1' onSubmit='return chkForm(this)'>
<input type='text' name='title' hname="title를 입력바랍니다" required>
<input type=submit name=submit value="확인">

hname					// 입력값이 없을때 보여질 에러메시지
required				// 입력값이 있어야함
lengthchk="20"			// Byte개수 제한(한글은 2byte)

datechk					// 날짜 체크

option="regNum"			// 숫자인지 /[0-9]+$/; 
option="regPhone"		// 한국전화번호 /[0-9]{2,3}-[0-9]{3,4}-[0-9]{4}$/; 
option="regMail"		// 메일주소 /[_a-zA-Z0-9-]+@[._a-zA-Z0-9-]+\.[a-zA-Z]+$/; 
option="regDomain"		// 도메인네임 /[.a-zA-Z0-9-]+.[a-zA-Z]+$/; 
option="regAlpha"		// 알파벳 /[a-zA-Z]+$/; 
option="regHost"		// 알파벳과 "-" /[a-zA-Z-]+$/; 
option="regHangul"		// 한글한글자 /^[가-힣\s]+$/; 
option="regHangulEng"	// 한글,알파벳 /[가-힣a-zA-Z]/; 
option="regHangulOnly"	// 한글만(혹은빈값) =/[가-힣]*$/; 
option="regId"			// 아이디(영어로시작'_','-'포함) /[a-zA-Z]{1}[a-zA-Z0-9_-]{4,15}$/; 
option="regDate"		// YYYY-MM-DD /[0-9]{4}-[0-9]{2}-[0-9]{2}$/; 
***************************************/

var formSubmit;
var formSubmitValue;

function chkForm(f)
{ 
	formSubmit = f.Submit ? f.Submit : new Object();
	formSubmitValue = formSubmit.value;

	formSubmit.disabled = true;
	formSubmit.value = '처리중입니다..';

	var i,currEl;

	for(i = 0; i < f.elements.length; i++){ 
		currEl = f.elements[i]; 
		//필수 항목을 체크한다.  
		if (currEl.getAttribute("required") != null) { 
			if (currEl.type.toLowerCase() == "text" || 
			   currEl.tagName.toLowerCase() == "select" || 
			   currEl.tagName.toLowerCase() == "textarea"){ 
				if(!chkText(currEl,currEl.getAttribute("hname"))) return false; 

			} else if(currEl.type.toLowerCase() == "password"){ 
				if(!chkText(currEl,currEl.getAttribute("hname"))) return false; 

			} else if(currEl.type.toLowerCase() == "checkbox"){ 
				if(!chkCheckbox(f, currEl,currEl.getAttribute("hname"))) return false; 

			} else if(currEl.type.toLowerCase() == "radio"){ 
				if(!chkRadio(f, currEl,currEl.getAttribute("hname"))) return false; 

			}
		}
		// 입력 페턴을 체크한다.
		if(currEl.getAttribute("option") != null && currEl.value.length > 0){ 
			if(!chkpattern(currEl,currEl.getAttribute("option"),currEl.getAttribute("hname"))) return false; 
		}
		// 길이제한을 체크한다.
		if(currEl.getAttribute("lengthchk") != null && currEl.value.length > 0){ 
			if(!chkLength(currEl,currEl.lengthchk,currEl.getAttribute("hname"))) return false; 
		} 
		// 날짜 체크한다.
		if(currEl.getAttribute("datechk") != null && currEl.value.length > 0){ 
			if(!jsDayCheck(currEl)) return false; 
		} 
	}
	return true;
} 

function chkpattern(field,pattern,name)
{ 
	//debugger;
	var regNum =/^[0-9]+$/; 
//	var regPhone =/^[0-9]{2,3}-[0-9]{3,4}-[0-9]{4}$/; 
	var regPhone =/^(01[016789]{1}|02|0[3-9]{1}[0-9]{1})-[0-9]{3,4}-[0-9]{4}$/;	 
	var regMail =/^[_a-zA-Z0-9-]+@[._a-zA-Z0-9-]+\.[a-zA-Z]+$/; 
	var regDomain =/^[.a-zA-Z0-9-]+.[a-zA-Z]+$/; 
	var regAlpha =/^[a-zA-Z]+$/; 
	var regHost =/^[a-zA-Z-]+$/; 
	var regHangul =/^[가-힣\s]+$/; 
	var regHangulEng =/^[가-힣a-zA-Z]+$/; 
	var regHangulOnly =/^[가-힣]*$/; 
	var regId = /^[a-zA-Z]{1}[a-zA-Z0-9_-]{4,15}$/; 
	var regDate =/^(19|20)\d{2}-([0][1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/;  
	
	pattern = eval(pattern);
	var pattern = new RegExp(pattern); 
	if(!pattern.test(field.value)){ 
		alert(name + "\n항목의 형식이 올바르지 않습니다."); 
		field.focus(); 
		formSubmit.disabled = false;
		formSubmit.value = formSubmitValue;
		return false; 
	} 
	return true; 
} 

//-- 문자열 길이 검사
function getLength(str) {
	return (str.length + (escape(str) + "/%u").match(/%u/g).length-1);
}
function chkLength(field,length,name)
{
	if(getLength(field.value) > length){ 
		alert(name + "\n\n글자제한 영문,숫자 "+length+"자 , 한글 "+(length/2)+"자 이하 입력제한 입니다."); 
		field.focus(); 
		formSubmit.disabled = false;
		formSubmit.value = formSubmitValue;
		return false; 
	} 
	return true; 
}

function chkText(field, name)
{
	if(field.value.length < 1){ 
		alert(name); 
		field.focus(); 
		formSubmit.disabled = false;
		formSubmit.value = formSubmitValue;
		return false; 
	} 
	return true; 
}

function chkCheckbox(form, field, name)
{
	fieldname = eval(form.name+'.'+field.name);
	if (!fieldname.checked){
		alert(name); 
		field.focus(); 
		formSubmit.disabled = false;
		formSubmit.value = formSubmitValue;
		return false; 
	}
	return true; 
}

function chkRadio(form, field, name)
{
	fieldname = eval(form.name+'.'+field.name);
	for(i=0;i<fieldname.length;i++) {
		if (fieldname[i].checked)
			return true; 
	}
	alert(name); 
	field.focus(); 
	formSubmit.disabled = false;
	formSubmit.value = formSubmitValue;
	return false; 
} 

function jsDayCheck(Obj)   
{   
	var strValue = Obj.value;   
	var chk1 = /^(19|20)\d{2}-([1-9]|1[012])-([1-9]|[12][0-9]|3[01])$/;   
	var chk2 = /^(19|20)\d{2}-([0][1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/;	
	//var chk2 = /^(19|20)\d{2}-([0][1-9]|1[012])-([012][1-9]|3[01])$/;   
	if (strValue == "")   
	{ // 공백이면	
		alert("1999-01-01 형식으로 날자를 입력해주세요.");  
		 return false;   
	}   
  
	//-------------------------------------------------------------------------------   
	// 유효성 검사- 입력형식에 맞게 들왔는지 // 예) 2000-1-1, 2000-01-01 2가지 형태 지원   
	//-------------------------------------------------------------------------------   
	if (chk1.test(strValue) == false && chk2.test(strValue) == false)   
	{ // 유효성 검사에 둘다 성공하지 못했다면   
		alert("날짜 형식에 맞게 입력해주세요.");   
	   Obj.value = "";   
	   Obj.focus = true;   
	   return false;   
	}   
  
	//-------------------------------------------------------------------------------   
	// 존재하는 날자(유효한 날자) 인지 체크   
	//-------------------------------------------------------------------------------   
	var bDateCheck = true;   
	var arrDate = Obj.value.split("-");   
	var nYear = Number(arrDate[0]);   
	var nMonth = Number(arrDate[1]);   
	var nDay = Number(arrDate[2]);   
  
	if (nYear < 1900 || nYear > 3000)   
	{ // 사용가능 하지 않은 년도 체크   
		bDateCheck = false;   
	}   
  
	if (nMonth < 1 || nMonth > 12)   
	{ // 사용가능 하지 않은 달 체크   
		bDateCheck = false;   
	}   
  
	// 해당달의 마지막 일자 구하기   
	var nMaxDay = new Date(new Date(nYear, nMonth, 1) - 86400000).getDate();   
	if (nDay < 1 || nDay > nMaxDay)   
	{ // 사용가능 하지 않은 날자 체크   
		bDateCheck = false;   
	}   
  
	if(bDateCheck == false)	
	{	
	   alert("존재하지 않은 년월일을 입력하셨습니다. 다시한번 확인해주세요");   
	   return false;   
	}else{
		return true;	
	}
}  

function LoadPage() {
	CKEDITOR.replace('content');
}

function FormSubmit(f) {
	CKEDITOR.instances.content.updateElement();
	if(f.content.value == "") {
		alert("내용을 입력해 주세요.");
		return false;
	}
	alert(f.content.value);
	
	// 전송은 하지 않습니다.
	return false;
}

//-->