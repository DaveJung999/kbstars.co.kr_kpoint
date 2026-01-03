/* 함수 리스트
	<script src="/common/join_check.js"></script>
	function isEmpty( data ) // 입력값 공백 체크
	function check_idnum(str1,str2)	 // 주민번호 체크
	function isInteger(st)   // 숫자인지 체크
	function check_userid(userid)	// 회원아이디체크(영문자로시작숫자로만)
	function check_passwd(passwd, passwd_ok) // 패스워드 동일한지 체크
	function check_email(strEmail)			// 메일이 정확한지 체크
*/


// 입력값 공백 체크 함수
function isEmpty( data )
{
	for ( var i = 0 ; i < data.length ; i++ )
	{
		if ( data.substring( i, i+1 ) != " " )
			return false;
	}
	return true;
}

// 주민 등록 번호 체크 함수
function check_idnum(str1,str2)	
{

	var li_lastid,li_mod,li_minus,li_last;
	var value0,value1,value2,value3,value4,value5,value6;
	var value7,value8,value9,value10,value11,value12;
	
	if (isInteger(str1) &&  isInteger(str2)) 
	{
		li_lastid	= parseFloat(str2.substring(6,7));
		value0  = parseFloat(str1.substring(0,1))  * 2;
		value1  = parseFloat(str1.substring(1,2))  * 3;
		value2  = parseFloat(str1.substring(2,3))  * 4;
		value3  = parseFloat(str1.substring(3,4))  * 5;
		value4  = parseFloat(str1.substring(4,5))  * 6;
		value5  = parseFloat(str1.substring(5,6))  * 7;
		value6  = parseFloat(str2.substring(0,1))  * 8;
		value7  = parseFloat(str2.substring(1,2))  * 9;
		value8  = parseFloat(str2.substring(2,3))  * 2;
		value9  = parseFloat(str2.substring(3,4))  * 3;
		value10 = parseFloat(str2.substring(4,5))  * 4;
		value11 = parseFloat(str2.substring(5,6))  * 5;
		value12 = 0;
		
		value12 = value0+value1+value2+value3+value4+value5+value6+value7+value8+value9+value10+value11+value12 ;
		
		li_mod = value12 %11;
		li_minus = 11 - li_mod;
		li_last = li_minus % 10;

		if (li_last != li_lastid)
		{
			return false;
			alert('주민등록번호를 정확히 입력 해 주세요.');
		} else 
		{
			return true;
		}
	} else {
		alert('주민등록번호를 정확히 입력 해 주세요.');
		return false;
	}
}

// 정수인지 비교하는 함수
function isInteger(st)
{
	if (!isEmpty(st))
	{
		for (j=0; j<st.length; j++)
		{
			if (((st.substring(j, j+1) < "0") || (st.substring(j, j+1) > "9")))
			return false;
	   }
	} 
	else
	{
	   return false ;
	}
	return true ;
}

// 아이디 체크
function check_userid(userid)
{
	var i;
	var CurrentChar;
	var bReturn;

	bReturn = true;
	
	// 한문자씩 아이디 검사
	for ( i = 0; i < userid.length; i++)
	{
		// 아이디를 한문자씩 할당
		CurrentChar = userid.charAt(i);

		// 문자 값 검사
		if ( !((CurrentChar >= '0' && CurrentChar <= '9' ) || (CurrentChar >= 'a' && CurrentChar <= 'z') || (CurrentChar >= 'A' && CurrentChar <= 'Z') )) 
		{
			bReturn = false;	
			break;
		}
		
		// 첫문자가 영문자인지 검사
		if(i == 0) 
		{
			if (!((CurrentChar >= 'a' && CurrentChar <= 'z') || (CurrentChar >= 'A' && CurrentChar <= 'Z'))) 
			{
				bReturn = false;	
				break;
			}
		}
		
	}
	
	if ( bReturn && ( ( userid.length < 5) || ( userid.length > 16 ) ) )
	{
		bReturn = false;
	}
	return bReturn;
}

// 비밀번호 체크
function check_passwd(passwd, passwd_ok)
{
	if(passwd != passwd_ok) {
		return false;
	}
	if((passwd.length < 6)) {
		return false;
	}

	return true;
}

// 이메일 체크
function check_email(strEmail)
{
	//var f_email = document.join.email;
	//var strEmail = f_email.value;
//	이메일 주소는 입력한 경우에만 valid한지 체크한다.
	var i;
	var strCheck1 = false;
	var strCheck2 = false;
	var iEmailLen = strEmail.length
	if (iEmailLen > 0) {
		// strEmail 에 '.@', '@.' 이 있는 경우 에러메시지.
		// strEmail의 맨앞 또는 맨뒤에  '@', '.' 이 있는 경우 에러메시지.
		if ((strEmail.indexOf(".@") != -1) || (strEmail.indexOf("@.") != -1) ||
			(strEmail.substring(0,1) == ".") || (strEmail.substring(0,1) == "@") ||
			(strEmail.substring(iEmailLen-1,iEmailLen) == ".") || (strEmail.substring(iEmailLen-1,iEmailLen) == "@"))
		{	
			return false;
		}
		
		for(i=0; i < iEmailLen; i++) {
			if ((strEmail.substring(i,i+1) == ".") || 
				(strEmail.substring(i,i+1) == "-") || (strEmail.substring(i,i+1) == "_") ||
			   ((strEmail.substring(i,i+1) >= "0") && (strEmail.substring(i,i+1) <= "9")) ||
			   ((strEmail.substring(i,i+1) >= "@") && (strEmail.substring(i,i+1) <= "Z")) ||
			   ((strEmail.substring(i,i+1) >= "a") && (strEmail.substring(i,i+1) <= "z"))) {
					if (strEmail.substring(i,i+1) == ".")
						strCheck1 = true;
					if (strEmail.substring(i,i+1) == "@")
						strCheck2 = true;
			}
			else {
				return false;
			}
		}
	
		if ((strCheck1 == false) || (strCheck2 == false)) {
			return false;
		}
	}
	return true;
}

/*
// 전화 번호 체크
function check_tel(phone1, phone2, phone3)
{
	if(!(isInteger(phone1) && isInteger(phone2) && isInteger(phone3))) 
	{
		return false;
	}
	//정확한 지역번호인지, 이동통신번호인지 check ( 2000.07.02기준 )
	if(!(phone1 == '031' || phone1 == '033' || phone1 == '02' || phone1 == '032' || phone1 == '041' || phone1 == '043' || phone1 == '042' || 
		 phone1 == '054' || phone1 == '053' || phone1 == '063' || phone1 == '055' || phone1 == '052' || phone1 == '062' || phone1 == '051' || phone1 == '061' || phone1 == '064' || phone1 == '011' || phone1 == '012' || phone1 == '015' || phone1 == '016' || phone1 == '017' || phone1 == '018' || phone1 == '019')) 
	{
		return false;
	}
	
	return true;
}
*/