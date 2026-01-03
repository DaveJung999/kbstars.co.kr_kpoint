/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */
// +--------------------------------------------------------+
// | Copyright (c) 2003-2004 Song Hyo-Jin				   |
// +--------------------------------------------------------+
// | Author : Song Hyo-Jin <crosser@hanmail.net>			|
// |								  (MSN Messengerable)   |
// +--------------------------------------------------------+
//
// Category : SHJ/js
// Package  : SHJ JavaScript
//
// $Id: direct_validate.js, v 0.0.3 2004/06/25 17:57:55 crucify Exp $
/*
refer : http://www.phpschool.com/bbs2/inc_view.html?id=10930&code=tnt2
TESTPAGE: http://my.netian.com/~crosser/validate.html
<form id="test" method="post" action="test" validate onsubmit="return validate_submit(this);">
form 태그에 validate 선언을 해 주면 적용됨.(validate="UTF-8" 로 맞추면 한글 한글자를 3bytes 로 계산)
form 태그에 id 필수


onlyNumber : 숫자만 
onlyAlphabet : 영문만 
onlyAlnum : 영문숫자 
onlyHangul : 한글만 
onlyHanNum : 한글숫자 
onlyText : 한글영문숫자 
onlyUID : 아이디 
lTrim : 왼쪽 Trim (여러줄 가능) 
rTrim : 오른쪽 Trim (여러줄 가능) 
trim : 전체 Trim (여러줄 가능) 

denyBlank : 반드시 채워야 함 
denySpace : 공백 불허 
toUpper : 대문자로 
toLower : 소문자로 
limitMin : 최소값 
limitMax : 최대값 
limitMinLen : 최소길이 bytes (값에 # 을 붙이면 글자수로 계산) 
limitMaxLen : 최대길이 bytes (값에 # 을 붙이면 글자수로 계산) 
checkTel : 전화번호 (name 을 같은 배열명으로 해야 함. 제일 처음 오는것은 checkTel1) 
checkCell : 휴대전화번호 (name 을 같은 배열명으로 해야 함. 제일 처음 오는것은 checkCell1) 
checkPersonalNumber : 주민등록번호 (name 을 같은 배열명으로 해야 함. 제일 처음 오는것은 checkPersonalNumber1) 
checkPassWord : 암호, 암호확인 (name 을 같은 배열명으로 해야 함. 제일 처음 오는것은 checkPassWord1) 
checkEmail : 이메일 

limitCheckMin : 체크박스 최소 선택 갯수 (name 을 같은 배열명으로 해야 하며, 가장 먼저 나온것에 선언하면 됨.) 
limitCheckMax : 체크박스 최대 선택 갯수 (name 을 같은 배열명으로 해야 하며, 가장 먼저 나온것에 선언하면 됨.) 


normalBG : 보통 색 속성. 지정안하면 태그의 속성으로 적용. 
alertBG : 경고 색 속성. 지정안하면 #FFDDDD 으로 적용. 
*/


var hangul_bytes = 2;

var gahih, tempDiv, hangulPattern, hannumPattern, textPattern, EunNun, IeGa;
tempDiv = document.createElement("DIV");
tempDiv.innerHTML = "&#44032;-&#55203;";
gahih = tempDiv.innerHTML;

hangulPattern = eval("/[^" + gahih + " ]+/g"); // /[^가-힣 ]+/g

hannumPattern = eval("/[^" + gahih + "0-9 ]+/g"); // /[^가-힣0-9 ]+/g

textPattern = eval("/[^" + gahih + "A-Za-z0-9 ]+/g"); // /[^가-힣A-Za-z0-9 ]+/g

tempDiv.innerHTML = "&#51008;&#45716;"; // 은는

EunNun = tempDiv.innerHTML;

tempDiv.innerHTML = "&#51060;&#44032;"; // 이가

IeGa = tempDiv.innerHTML;

function entityAlert(message)
{
	tempDiv.innerHTML = message;
	window.alert(tempDiv.innerHTML);
}

function josa(str, tail)
{
	var strTemp;

	strTemp = str.substr(str.length - 1);
	if(strTemp.charCodeAt(0) < 129) {
		pattern = /[lmnr]/i;
		if(pattern.test(strTemp))
			return str + tail.substr(0, 1);
		pattern = /([aeiou][^aeiouwy]e|mb|ck)$/i;
		return pattern.test(str) ? str + tail.substr(0, 1) : str + tail.substr(1, 1);
	}

	return ((strTemp.charCodeAt(0) - 16) % 28 != 0) ? str + tail.substr(0, 1) : str + tail.substr(1, 1);
}

function onlyNumber()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = only_Number(obj.value);
	return true;
}

function onlyAlphabet()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = only_Alphabet(obj.value);
	return true;
}

function onlyAlnum()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = only_Alnum(obj.value);
	return true;
}

function onlyHangul()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = only_Hangul(obj.value);
	return true;
}

function onlyHanNum()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = only_HanNum(obj.value);
	return true;
}

function onlyText()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = only_Text(obj.value);
	return true;
}

function onlyUID()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = only_UID(obj.value);
	return true;
}

function lTrim_()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = obj.value.lTrim();
	return true;
}

function rTrim_()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = obj.value.rTrim();
	return true;
}

function trim_()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = obj.value.trim();
	return true;
}

function denyBlank()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	return deny_Blank(obj);
}

function denySpace()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = deny_Space(obj.value);
	return true;
}

function toUpper()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = obj.value.toUpperCase();
	return true;
}

function toLower()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	obj.value = obj.value.toLowerCase();
	return true;
}

function limitMin()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	return limit_Min(obj);
}

function limitMax()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	return limit_Max(obj);
}

function limitMinLen()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	return limit_MinLen(obj);
}

function limitMaxLen()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	return limit_MaxLen(obj);
}

function checkTel()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	return check_Tel(obj);
}

function checkCell()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	return check_Cell(obj);
}

function checkPersonalNumber()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	return check_PersonalNumber(obj);
}

function checkPassWord()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	return check_PassWord(obj);
}

function checkEmail()
{
	var obj;
	if(document.all)
		obj = event.srcElement;
	else
		obj = this;

	return check_Email(obj);
}

function only_Number(str)
{
	return str.replace(/[^0-9 ]+/g, '').replace(/ +/g, ' ');
}

function only_Alphabet(str)
{
	return str.replace(/[^A-Za-z ]+/g, '').replace(/ +/g, ' ');
}

function only_Alnum(str)
{
	return str.replace(/[^A-Za-z0-9 ]+/g, '').replace(/ +/g, ' ');
}

function only_UID(str)
{
	return str.toLowerCase().replace(/[^a-z0-9]+/g, '').replace(/^[^a-z]+/, '');
}

String.prototype.lTrim = function() {
	return this.replace(/^ +/mg, '');
}

String.prototype.rTrim = function() {
	return this.replace(/ +$/mg, '');
}

String.prototype.trim = function() {
	return this.lTrim().rTrim();
}

function deny_Space(str)
{
	return str.replace(/ +/mg, '');
}

function only_Hangul(str)
{
	return str.replace(hangulPattern, '').replace(/ +/g, ' ');
}

function only_HanNum(str)
{
	return str.replace(hannumPattern, '').replace(/ +/g, ' ');
}

function only_Text()
{
	return str.replace(textPattern, '').replace(/ +/g, ' ');
}

String.prototype.len = function() {
	var len, j, chr;

	len = 0;

	for(j = 0; j < this.length; j ++) {
		var chr = this.charAt(j);
		len += (chr.charCodeAt() > 128) ? hangul_bytes : 1
		if(chr == 13)
			j ++;
	}

	return len;
}

function lenCut(str, maxlen) {
	var len, ret, j, chr, b;

	len = 0;
	ret = "";

	for(j = 0; j < str.length; j ++) {
		var chr = str.charAt(j);
		b = (chr.charCodeAt() > 128) ? hangul_bytes : 1
		if(len + b > maxlen)
			return ret;
		len += b;
		ret += chr;
		if(chr == 13)
			j ++;
	}

	return str;
}

function getHname(inp)
{
	var hname;

	hname = inp.getAttribute("hname");

	if(hname == null)
		hname = inp.name;

	return hname;
}

function deny_Blank(inp)
{
	var normalbg, alertbg, i;

	normalbg = inp.getAttribute("normalBG");
	alertbg = inp.getAttribute("alertBG");

	for(i = 0; i < 10000; i ++);
	if(inp.value == "") {
		inp.style.backgroundColor = alertbg;
		return false;
	} else {
		inp.style.backgroundColor = normalbg;
		return true;
	}
}

function limit_MinLen(inp)
{
	var normalbg, alertbg, att, must, flag, leng;

	normalbg = inp.getAttribute("normalBG");
	alertbg = inp.getAttribute("alertBG");

	att = inp.getAttribute("denyBlank");
	if(att == null)
		must = false;
	else
		must = true;

	att = inp.getAttribute("limitMinLen");

	flag = true;

	if(!must && inp.value == "")
		flag = true;
	else {
		if(att.charAt(0) == '#') {
			leng = inp.value.length;
			att = att.substr(1) * 1;
		} else {
			leng = inp.value.len();
			att *= 1;
		}

		if(leng < att)
			flag = false;
	}

	if(flag) {
		inp.style.backgroundColor = normalbg;
		return true;
	} else {
		inp.style.backgroundColor = alertbg;
		return false;
	}
}

function limit_Min(inp)
{
	var att, must, flag;

	att = inp.getAttribute("denyBlank");
	if(att == null)
		must = false;
	else
		must = true;

	att = inp.getAttribute("limitMin");

	flag = true;

	if(!must && inp.value == "")
		flag = true;
	else if(inp.value * 1 < att)
		flag = false;

	if(!flag)
		inp.value = att;

	return true;
}

function limit_MaxLen(inp)
{
	var att, leng;

	att = inp.getAttribute("limitMaxLen");
	if(att.charAt(0) == '#') {
		leng = inp.value.length;
		att = att.substr(1) * 1;
		if(leng > att) {
			inp.value = inp.value.substr(0, att);
		}
	} else {
		leng = inp.value.len();
		att *= 1;
		if(leng > att) {
			inp.value = lenCut(inp.value, att);
		}
	}

	return true;
}

function limit_Max(inp)
{
	var att;

	att = inp.getAttribute("limitMax");

	if(inp.value * 1 > att) {
		inp.value = att;
	}

	return true;
}

function limit_CheckMin(inp)
{
	var setname, formid, att, frm, tag, ret, k, l;

	setname = inp.name;
	formid = inp.getAttribute("formId");
	att = inp.getAttribute("limitCheckMin") * 1;

	frm = document.getElementById(formid);

	tag = frm.getElementsByTagName("INPUT");

	ret = new Array();
	k = 0;
	for(l = 0; l < tag.length; l ++) {
		if(tag[l].name == setname) {
			if(tag[l].checked)
				k ++;
		}
	}

	return (k < att) ? false : true;
}

function limit_CheckMax(inp)
{
	var setname, formid, att, frm, tag, ret, k, l;

	setname = inp.name;
	formid = inp.getAttribute("formId");
	att = inp.getAttribute("limitCheckMax") * 1;

	frm = document.getElementById(formid);

	tag = frm.getElementsByTagName("INPUT");

	ret = new Array();
	k = 0;
	for(l = 0; l < tag.length; l ++) {
		if(tag[l].name == setname) {
			if(tag[l].checked)
				k ++;
		}
	}

	return (k > att) ? false : true;
}

function check_Tel(inp)
{
	var setname, formid, frm, tag, ret, k, l;
	setname = inp.name;
	formid = inp.getAttribute("formId");

	frm = document.getElementById(formid);

	tag = frm.getElementsByTagName("INPUT");

	ret = new Array();
	k = 0;
	for(l = 0; l < tag.length; l ++) {
		if(tag[l].name == setname) {
			ret[k] = tag[l];
			k ++;
		}
	}

	if(k != 3) {
		entityAlert(setname + " &#54596;&#46300; &#49688;&#44032; &#47582;&#51648; &#50506;&#49845;&#45768;&#45796;."); // 필드 수가 맞지 않습니다.
		return false;
	}

	return checkTelCell(ret, 1);
}

function check_Cell(inp)
{
	var setname, formid, frm, tag, ret, k, l;

	setname = inp.name;
	formid = inp.getAttribute("formId");

	frm = document.getElementById(formid);

	tag = frm.getElementsByTagName("INPUT");

	ret = new Array();
	k = 0;
	for(l = 0; l < tag.length; l ++) {
		if(tag[l].name == setname) {
			ret[k] = tag[l];
			k ++;
		}
	}

	if(k != 3) {
		entityAlert(setname + " &#54596;&#46300; &#49688;&#44032; &#47582;&#51648; &#50506;&#49845;&#45768;&#45796;."); // 필드 수가 맞지 않습니다.
		return false;
	}

	return checkTelCell(ret, 2);
}

function checkTelCell(inp, type)
{
	var normalbg, alertbg, att, must, pattern1, pattern2, pattern3, flag, concat_value, focus_ord;

	normalbg = inp[0].getAttribute("normalBG");
	alertbg = inp[0].getAttribute("alertBG");

	att = inp[0].getAttribute("denyBlank");
	if(att == null)
		must = false;
	else
		must = true;
	
	inp[0].value = only_Number(inp[0].value);
	inp[1].value = only_Number(inp[1].value);
	inp[2].value = only_Number(inp[2].value);
	
	if(type == 1) {
		pattern1 = /^0(2|3[1-3]|4[1-3]|5[1-5]|6[1-4]|50[25]|80)$/;
		pattern2 = /^[2-9][0-9]{2,3}$/;
		pattern3 = /^[0-9]{4}$/;
	} else {
		pattern1 = /^01[016789]$/;
		pattern2 = /^[1-9][0-9]{2,3}$/;
		pattern3 = /^[0-9]{4}$/;
	}

	flag = true;

	concat_value = "" + inp[0].value + inp[1].value + inp[2].value;

	if(!must && concat_value.trim() == "")
		flag = true;
	else if(!pattern1.test(inp[0].value)) {
		flag = false;
		focus_ord = 0;
	} else if(!pattern2.test(inp[1].value)) {
		flag = false;
		focus_ord = 1;
	} else if(!pattern3.test(inp[2].value)) {
		flag = false;
		focus_ord = 2;
	}

	if(flag) {
		inp[0].style.backgroundColor = normalbg;
		inp[1].style.backgroundColor = normalbg;
		inp[2].style.backgroundColor = normalbg;
		inp[0].setAttribute("focusOrd", "0");
		inp[1].setAttribute("focusOrd", "0");
		inp[2].setAttribute("focusOrd", "0");
		return true;
	} else {
		inp[0].style.backgroundColor = alertbg;
		inp[1].style.backgroundColor = alertbg;
		inp[2].style.backgroundColor = alertbg;
		inp[focus_ord].setAttribute("focusOrd", "1");
		return false;
	}
}

function check_PersonalNumber(inp)
{
	var setname, formid, frm, tag, ret, k, l;

	setname = inp.name;
	formid = inp.getAttribute("formId");

	frm = document.getElementById(formid);

	tag = frm.getElementsByTagName("INPUT");

	ret = new Array();
	k = 0;
	for(l = 0; l < tag.length; l ++) {
		if(tag[l].name == setname) {
			ret[k] = tag[l];
			k ++;
		}
	}

	if(k != 2) {
		entityAlert(setname + " &#54596;&#46300; &#49688;&#44032; &#47582;&#51648; &#50506;&#49845;&#45768;&#45796;."); // 필드 수가 맞지 않습니다.
		return false;
	}

	return checkPersonalNo(ret);
}

function checkPersonalNo(inp)
{
	var normalbg, alertbg, att, must, pattern1, pattern2, flag, concat_value, focus_ord, year, month, day, check, field, yy, mm, dd;

	normalbg = inp[0].getAttribute("normalBG");
	alertbg = inp[0].getAttribute("alertBG");

	att = inp[0].getAttribute("denyBlank");
	if(att == null)
		must = false;
	else
		must = true;

	inp[0].value = only_Number(inp[0].value);
	inp[1].value = only_Number(inp[1].value);

	pattern1 = /^[0-9]{6}$/;
	pattern2 = /^[1-4][0-9]{6}$/;

	flag = true;

	concat_value = "" + inp[0].value + inp[1].value;
	concat_value = concat_value.trim();

	if(!must && concat_value == "")
		flag = true;
	else {
		if(!pattern1.test(inp[0].value)) {
			flag = false;
			focus_ord = 0;
		} else if(!pattern2.test(inp[1].value)) {
			flag = false;
			focus_ord = 1;
		}
		if(flag) {
			year = concat_value.substr(0, 2);
			switch(concat_value.charAt(6)) {
			case '1':
			case '2':
				year = ('19' + year) * 1;
				break;
			case '3':
			case '4':
				year = ('20' + year) * 1;
				break;
			}

			month = concat_value.substr(2, 2) * 1;
			day = concat_value.substr(4, 2) * 1;

			if(month < 1 || month > 12) {
				flag = false;
				focus_ord = 0;
			} else if(day < 1 || day > 31) {
				flag = false;
				focus_ord = 0;
			} else {
				check = 0;
				mul = 2;

				for(i = 0; i < 12; i ++) {
					check += concat_value.charAt(i) * mul;
					mul ++;
					if(mul > 9)
						mul = 2;
				}

				check = 11 - (check % 11);

				if(check > 9)
					check %= 10;
				if(check != concat_value.charAt(12)) {
					flag = false;
					focus_ord = 1;
				}
			}
		}

		if(flag) {
			field = inp[0].getAttribute("toFields");
			if(field != null) {
				yy = document.getElementById(field + "year");
				yy.value = year;

				mm = document.getElementById(field + "month");
				mm.value = month;

				dd = document.getElementById(field + "day");
				dd.value = day;
			}
		}
	}

	if(flag) {
		inp[0].style.backgroundColor = normalbg;
		inp[1].style.backgroundColor = normalbg;
		inp[0].setAttribute("focusOrd", "0");
		inp[1].setAttribute("focusOrd", "0");
		return true;
	} else {
		inp[focus_ord].style.backgroundColor = alertbg;
		inp[focus_ord].style.backgroundColor = alertbg;
		inp[focus_ord].setAttribute("focusOrd", "1");
		return false;
	}
}

function check_Email(inp)
{
	var normalbg, alertbg, att, must, pattern, flag;

	normalbg = inp.getAttribute("normalBG");
	alertbg = inp.getAttribute("alertBG");

	att = inp.getAttribute("denyBlank");
	if(att == null)
		must = false;
	else
		must = true;
	pattern = /^[_a-z0-9-\.]+@[\.a-z0-9-]+\.(com|net|org|af|al|dz|as|ad|ao|ai|aq|ag|ar|am|aw|au|at|az|bs|bh|bd|bb|by|be|bz|bj|bm|bt|bo|ba|bw|bv|br|io|vg|bn|bg|bf|bi|kh|cm|ca|cv|ky|cf|td|cl|cn|cx|cc|co|km|cd|cg|ck|cr|ci|cu|cy|cz|dk|dj|dm|do|ec|eg|sv|gq|er|ee|et|fo|fk|fj|fi|fr|gf|pf|tf|ga|gm|ge|de|gh|gi|gr|gl|gd|gp|gu|gt|gn|gw|gy|ht|hm|va|hn|hk|hr|hu|is|in|id|ir|iq|ie|il|it|jm|jp|jo|kz|ke|ki|kp|kr|kw|kg|la|lv|lb|ls|lr|ly|li|lt|lu|mo|mk|mg|mw|my|mv|ml|mt|mh|mq|mr|mu|yt|mx|fm|md|mc|mn|ms|ma|mz|mm|na|nr|np|an|nl|nc|nz|ni|ne|ng|nu|nf|mp|no|om|pk|pw|ps|pa|pg|py|pe|ph|pn|pl|pt|pr|qa|re|ro|ru|rw|sh|kn|lc|pm|vc|ws|sm|st|sa|sn|cs|sc|sl|sg|sk|si|sb|so|za|gs|es|lk|sd|sr|sj|sz|se|ch|sy|tw|tj|tz|th|tl|tg|tk|to|tt|tn|tr|tm|tc|tv|vi|ug|ua|ae|gb|um|us|uy|uz|vu|ve|vn|wf|eh|ye|zm|zw)$/;

	flag = true;

	if(!must && inp.value == "")
		flag = true;
	else {
		inp.value = inp.value.toLowerCase().trim();
		if(!pattern.test(inp.value))
			flag = false;
	}

	if(flag) {
		inp.style.backgroundColor = normalbg;
		return true;
	} else {
		inp.style.backgroundColor = alertbg;
		return false;
	}
}

function check_PassWord(inp)
{
	var setname, formid, frm, tag, ret, k, l;

	setname = inp.name;
	formid = inp.getAttribute("formId");

	frm = document.getElementById(formid);

	tag = frm.getElementsByTagName("INPUT");

	ret = new Array();
	k = 0;
	for(l = 0; l < tag.length; l ++) {
		if(tag[l].name == setname) {
			ret[k] = tag[l];
			k ++;
		}
	}

	if(k != 2) {
		entityAlert(setname + " &#54596;&#46300; &#49688;&#44032; &#47582;&#51648; &#50506;&#49845;&#45768;&#45796;."); // 필드 수가 맞지 않습니다.
		return false;
	}

	return checkPassWd(ret);
}

function checkPassWd(inp)
{
	var normalbg, alertbg, att, must, flag, concat_value;

	normalbg = inp[0].getAttribute("normalBG");
	alertbg = inp[0].getAttribute("alertBG");

	att = inp[0].getAttribute("denyBlank");
	if(att == null)
		must = false;
	else
		must = true;

	inp[0].value = inp[0].value.trim();
	inp[1].value = inp[1].value.trim();

	flag = true;

	concat_value = "" + inp[0].value + inp[1].value;

	if(!must && concat_value.trim() == "")
		flag = true;
	else if(inp[0].value != inp[1].value)
		flag = false;

	if(flag) {
		inp[1].style.backgroundColor = normalbg;
		return true;
	} else {
		inp[1].style.backgroundColor = alertbg;
		return false;
	}
}

function toFocus(inp)
{
	var setname, formid, frm, tag, i;

	setname = inp.name;
	formid = inp.getAttribute("formId");

	frm = document.getElementById(formid);

	tag = frm.getElementsByTagName("INPUT");

	for(i = 0; i < tag.length; i ++) {
		if(tag[i].name == setname) {
			att = tag[i].getAttribute("focusOrd");
			if(att == 1) {
				tag[i].focus();
				break;
			}
		}
	}

	return true;
}

function validate_add_event_process()
{
	var frm, k, att;

	frm = document.getElementsByTagName("FORM");
	if(frm == null)
		return true;

	for(k = 0; k < frm.length; k ++) {
		att = frm[k].getAttribute("validate");
		if(att == null)
			continue;

		if(att == 'UTF-8')
			hangul_bytes = 3;

		validate_add_event(frm[k].getElementsByTagName("INPUT"), frm[k].id);
		validate_add_event(frm[k].getElementsByTagName("TEXTAREA"), frm[k].id);
	}

	return true;
}

function addFocusOutEvent(obj, etext)
{
	if(document.all)
		obj.attachEvent("onfocusout", eval(etext));
	else
		obj.addEventListener("blur", eval(etext), false);

	return true;
}

function validate_add_event(tag, formid)
{
	var tag, formid, i, att, flag;

	if(tag != null) {
		for(i = 0; i < tag.length; i ++) {
			tag[i].setAttribute("formId", formid);

			att = tag[i].getAttribute("normalBG");
			if(att == null) {
				att = tag[i].style.backgroundColor;
				tag[i].setAttribute("normalBG", att);
			}
			att = tag[i].getAttribute("alertBG");
			if(att == null)
				tag[i].setAttribute("alertBG", "#FDD4D2");

			flag = true;

			att = tag[i].getAttribute("onlyNumber");
			if(att != null) {
				addFocusOutEvent(tag[i], "onlyNumber");
				tag[i].value = only_Number(tag[i].value);
			}
			att = tag[i].getAttribute("onlyAlphabet");
			if(att != null) {
				addFocusOutEvent(tag[i], "onlyAlphabet");
				tag[i].value = only_Alphabet(tag[i].value);
			}
			att = tag[i].getAttribute("onlyAlnum");
			if(att != null) {
				addFocusOutEvent(tag[i], "onlyAlnum");
				tag[i].value = only_Alnum(tag[i].value);
			}
			att = tag[i].getAttribute("onlyHangul");
			if(att != null) {
				addFocusOutEvent(tag[i], "onlyHangul");
				tag[i].value = only_Hangul(tag[i].value);
			}
			att = tag[i].getAttribute("onlyHanNum");
			if(att != null) {
				addFocusOutEvent(tag[i], "onlyHanNum");
				tag[i].value = only_HanNum(tag[i].value);
			}
			att = tag[i].getAttribute("onlyText");
			if(att != null) {
				addFocusOutEvent(tag[i], "onlyText");
				tag[i].value = only_Text(tag[i].value);
			}
			att = tag[i].getAttribute("onlyUID");
			if(att != null) {
				addFocusOutEvent(tag[i], "onlyUID");
				tag[i].value = only_UID(tag[i].value);
			}
			att = tag[i].getAttribute("lTrim");
			if(att != null) {
				addFocusOutEvent(tag[i], "lTrim_");
				tag[i].value = tag[i].value.lTrim();
			}
			att = tag[i].getAttribute("rTrim");
			if(att != null) {
				addFocusOutEvent(tag[i], "rTrim_");
				tag[i].value = tag[i].value.rTrim();
			}
			att = tag[i].getAttribute("trim");
			if(att != null) {
				addFocusOutEvent(tag[i], "trim_");
				tag[i].value = tag[i].value.trim();
			}
			att = tag[i].getAttribute("denySpace");
			if(att != null) {
				addFocusOutEvent(tag[i], "denySpace");
				tag[i].value = deny_Space(tag[i].value);
			}
			att = tag[i].getAttribute("toUpper");
			if(att != null) {
				addFocusOutEvent(tag[i], "toUpper");
				tag[i].value = tag[i].value.toUpperCase();
			}
			att = tag[i].getAttribute("toLower");
			if(att != null) {
				addFocusOutEvent(tag[i], "toLower");
				tag[i].value = tag[i].value.toLowerCase();
			}
			att = tag[i].getAttribute("limitMin");
			if(att != null) {
				addFocusOutEvent(tag[i], "limitMin");
				limit_Min(tag[i]);
				flag = false;
			}
			att = tag[i].getAttribute("limitMax");
			if(att != null) {
				addFocusOutEvent(tag[i], "limitMax");
				limit_Max(tag[i]);
				flag = false;
			}
			att = tag[i].getAttribute("limitMinLen");
			if(att != null) {
				addFocusOutEvent(tag[i], "limitMinLen");
				limit_MinLen(tag[i]);
				flag = false;
			}
			att = tag[i].getAttribute("limitMaxLen");
			if(att != null) {
				addFocusOutEvent(tag[i], "limitMaxLen");
				limit_MaxLen(tag[i]);
			}
			att = tag[i].getAttribute("checkTel1");
			if(att != null) {
				addFocusOutEvent(tag[i], "checkTel");
				check_Tel(tag[i]);
				flag = false;
			}
			att = tag[i].getAttribute("checkTel");
			if(att != null) {
				addFocusOutEvent(tag[i], "checkTel");
				flag = false;
			}
			att = tag[i].getAttribute("checkCell1");
			if(att != null) {
				addFocusOutEvent(tag[i], "checkCell");
				check_Cell(tag[i]);
				flag = false;
			}
			att = tag[i].getAttribute("checkCell");
			if(att != null) {
				addFocusOutEvent(tag[i], "checkCell");
				flag = false;
			}
			att = tag[i].getAttribute("checkPersonalNumber1");
			if(att != null) {
				addFocusOutEvent(tag[i], "checkPersonalNumber");
				check_PersonalNumber(tag[i]);
				flag = false;
			}
			att = tag[i].getAttribute("checkPersonalNumber");
			if(att != null) {
				addFocusOutEvent(tag[i], "checkPersonalNumber");
				flag = false;
			}
			att = tag[i].getAttribute("checkPassWord1");
			if(att != null) {
				addFocusOutEvent(tag[i], "checkPassWord");
				check_PassWord(tag[i]);
				flag = false;
			}
			att = tag[i].getAttribute("checkPassWord");
			if(att != null) {
				addFocusOutEvent(tag[i], "checkPassWord");
				flag = false;
			}
			att = tag[i].getAttribute("checkEmail");
			if(att != null) {
				addFocusOutEvent(tag[i], "checkEmail");
				check_Email(tag[i]);
				flag = false;
			}
			att = tag[i].getAttribute("limitCheckMin");
			if(att != null)
				addFocusOutEvent(tag[i], "limitCheckMin");
			att = tag[i].getAttribute("limitCheckMax");
			if(att != null)
				addFocusOutEvent(tag[i], "limitCheckMax");
			if(flag) {
				att = tag[i].getAttribute("denyBlank");
				if(att != null) {
					addFocusOutEvent(tag[i], "denyBlank");
					deny_Blank(tag[i]);
				}
			}
		}
	}

	return true;
}

function validate_check(tag)
{
	var m, mwill, att;

	if(tag == null)
		return true;

	for(m = 0; m < tag.length; m ++) {
		mwill = 0;

		att = tag[m].getAttribute("denyBlank");
		if(att != null) {
			if(!deny_Blank(tag[m])) {
				hname = getHname(tag[m]);
				entityAlert(hname + " &#51032; &#45236;&#50857;&#51008; &#48152;&#46300;&#49884; &#52292;&#50864;&#49492;&#50556; &#54633;&#45768;&#45796;."); // 의 내용은 반드시 채우셔야 합니다.
				tag[m].focus();
				return false;
			}
		}

		att = tag[m].getAttribute("limitMinLen");
		if(att != null) {
			if(!limit_MinLen(tag[m])) {
				hname = getHname(tag[m]);
				if(att.charAt(0) == "#") {
					len = att.substr(1);
					han = "&#54620;&#44544;&#47196; "; // 한글로
				} else {
					len = att;
					han = "";
				}
				entityAlert(josa(hname, EunNun) + " " + han + len + " &#44544;&#51088; &#51060;&#49345; &#51077;&#47141;&#54616;&#49492;&#50556; &#54633;&#45768;&#45796;."); // 은는 글자 이상 입력하셔야 합니다.
				tag[m].focus();
				return false;
			}
		}

		att = tag[m].getAttribute("limitCheckMin");
		if(att != null) {
			if(!limit_CheckMin(tag[m])) {
				hname = getHname(tag[m]);

				entityAlert(josa(hname, EunNun) + " " + att + " &#44032;&#51648; &#51060;&#49345; &#49440;&#53469;&#54616;&#49492;&#50556; &#54633;&#45768;&#45796;."); // 은는 가지 이상 선택하셔야 합니다.
				tag[m].focus();
				return false;
			}
		}

		att = tag[m].getAttribute("limitCheckMax");
		if(att != null) {
			if(!limit_CheckMax(tag[m])) {
				hname = getHname(tag[m]);

				entityAlert(josa(hname, EunNun) + " " + att + "  &#44032;&#51648; &#51060;&#54616;&#47196; &#49440;&#53469;&#54616;&#49492;&#50556; &#54633;&#45768;&#45796;."); // 은는 가지 이하로 선택하셔야 합니다.
				tag[m].focus();
				return false;
			}
		}

		att = tag[m].getAttribute("checkTel1");
		if(att != null) {
			if(!check_Tel(tag[m])) {
				hname = getHname(tag[m]);
				entityAlert(josa(hname, EunNun) + " &#51068;&#48152;&#51204;&#54868; &#48264;&#54840;&#47484; &#51077;&#47141;&#54616;&#49492;&#50556; &#54633;&#45768;&#45796;."); // 은는 일반전화 번호를 입력하셔야 합니다.
				toFocus(tag[m]);
				return false;
			}
			mwill = 2;
		}

		att = tag[m].getAttribute("checkCell1");
		if(att != null) {
			if(!check_Cell(tag[m])) {
				hname = getHname(tag[m]);
				entityAlert(josa(hname, EunNun) + " &#55092;&#45824;&#51204;&#54868; &#48264;&#54840;&#47484; &#51077;&#47141;&#54616;&#49492;&#50556; &#54633;&#45768;&#45796;."); // 은는 휴대전화 번호를 입력하셔야 합니다.
				toFocus(tag[m]);
				return false;
			}
			mwill = 2;
		}

		att = tag[m].getAttribute("checkPersonalNumber1");
		if(att != null) {
			if(!check_PersonalNumber(tag[m])) {
				hname = getHname(tag[m]);
				entityAlert(josa(hname, IeGa) + " &#51096;&#47803;&#46104;&#50632;&#49845;&#45768;&#45796;."); // 이가 잘못되었습니다.
				toFocus(tag[m]);
				return false;
			}
			mwill = 1;
		}

		att = tag[m].getAttribute("checkEmail");
		if(att != null) {
			if(!check_Email(tag[m])) {
				hname = getHname(tag[m]);
				entityAlert(josa(hname, IeGa) + " &#51096;&#47803;&#46104;&#50632;&#49845;&#45768;&#45796;."); // 이가 잘못되었습니다.
				tag[m].focus();
				return false;
			}
		}

		att = tag[m].getAttribute("checkPassWord1");
		if(att != null) {
			if(!check_PassWord(tag[m])) {
				hname = getHname(tag[m]);
				entityAlert(josa(hname, IeGa) + " 맞지 않습니다."); // 이가 맞지 않습니다.
				tag[m].focus();
				return false;
			}
			mwill = 1;
		}
		m += mwill;
	}
	return true;
}

function validate_submit(target_form)
{
	var ret;

	ret = validate_check(target_form.getElementsByTagName("INPUT"));
	if(ret)
		ret = validate_check(target_form.getElementsByTagName("TEXTAREA"));

	return ret;
}

document.onload = setTimeout("validate_add_event_process();", 500);
