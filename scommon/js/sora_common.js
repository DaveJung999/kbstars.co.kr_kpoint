/***********************************************
  Sora.net 공통 Javascript
 ----------------------------------------------
  최초작성
  2002.07.23 : 방민석 (noticeme@fid.co.kr)
***********************************************/

	/* =================================================================
		영문자와 숫자로만 이루어졌는지 Check
	================================================================= */

	String.prototype.isid = function() {

		if (this.search(/[^A-Za-z0-9_-]/) == -1) return true;
		else return false;
	}

	/* =================================================================
		전화번호 Check
	================================================================= */

	String.prototype.istel = function() {

		if (this.search(/[^0-9_-]/) == -1) return true;
		else return false;
	}

	/* =================================================================
		영문자만 이루어졌는지  Check
	================================================================= */

	String.prototype.isalpha = function() {

		if (this.search(/[^A-Za-z]/) == -1) return true;
		else return false;
	}

	/* =================================================================
		숫자로만 이루어졌는지  Check
	================================================================= */

	String.prototype.isnumber = function() {

		if (this.search(/[^0-9]/) == -1) return true;
		else return false;
	}

	/* =================================================================
		주민등록번호 Check 
	================================================================= */

	String.prototype.isjumin = function() {

		var jumin = this;

		if (jumin.length != 13)  return false;

		tval = jumin.charAt(0)*2 + jumin.charAt(1)*3 + jumin.charAt(2)*4
		+ jumin.charAt(3)*5 + jumin.charAt(4)*6 + jumin.charAt(5)*7
		+ jumin.charAt(6)*8+ jumin.charAt(7)*9 + jumin.charAt(8)*2
		+ jumin.charAt(9)*3 + jumin.charAt(10)*4 + jumin.charAt(11)*5;

		tval2 = 11- (tval % 11);
		tval2 = tval2 % 10;
		
		if (jumin.charAt(12) == tval2 && (jumin.charAt(6) == "1" || jumin.charAt(6) == "2")) return true;
		else return false;
	}

	/* =================================================================
		E-Mail Check 
	================================================================= */

	String.prototype.isemail = function() {

		if (this.search(/(.+)@.+\..+/) == -1) return false;
		else {
			for (var i=0; i < this.length;i++) if (this.charCodeAt(i) > 256) return false;
			return true;
		}
	}

	/* =================================================================
		날짜 Check (YYYY-MM-DD)
	================================================================= */

	String.prototype.isdate = function() {

		if (this.search(/\d{4}\-\d{2}\-\d{2}/) == -1) return false;
		else return true;
	}

	/* =================================================================
		한글을 2글자로 계산하여 순수한 길이(Byte)를 계산한다
	================================================================= */

	String.prototype.strLen = function() {

		var temp;
		var set = 0;
		var mycount = 0;

		for (k = 0; k < this.length; k++) {

			temp = this.charAt(k);

			if (escape(temp).length > 4) mycount += 2
			else mycount++;
		}

		return mycount;
	}

	/* =================================================================
		앞 공백 제거
	================================================================= */

	String.prototype.ltrim = function() {

		var i, j = 0;
		var objstr

		for (i = 0; i < this.length; i++) {

			if (this.charAt(i) == ' ') j = j + 1;
			else break;
		}

		return this.substr(j, this.length - j + 1)  
	}

	/* =================================================================
		뒤 공백 제거
	================================================================= */

	String.prototype.rtrim = function() {

		var i, j = 0;

		for (i = this.length - 1; i >= 0; i--) {

			if (this.charAt(i) == ' ') j = j + 1;
			else break;
		}

		return this.substr(0, this.length - j);
	}

	/* =================================================================
		앞/뒤 공백 제거
	================================================================= */

	String.prototype.trim = function() {
		return this.replace(/\s/g, "");
	}

	/* =================================================================
		메소드 명: _cmdfocus()
		내	 용: formobj에 Focus이동
		작  성 자: FID Noticeme PANG (noticeme@fid.co.kr)
		작  성 일: 2003.06.30
	   ----------------------------------------------------------------
		formobj
	================================================================= */

	function _cmdfocus(formobj){
		formobj.select();
		formobj.focus();
	}

	/* =================================================================
		메소드 명: soraChkForm()
		내	 용: 각 Field별 입력값 체크
				   입력값을 체크하고 alert를 출력한 후 
				   해당하는 Field에 Focus를 맞춘다
		작  성 자: FID Noticeme PANG (noticeme@fid.co.kr)
		작  성 일: 2003.06.30
	   ----------------------------------------------------------------
		formField		값을 Check할 Field
		checkName		체크할 값의 종류
		message			출력할 메시지 앞부분 (ex. '제목은 ')
		minlength		최소 길이
		maxlength		최대 길이 
				(minlength와 maxlength 모두 0 이면 길이를 체크하지 않음)
		@return			정상적인 값이면 true, 그렇지않으면 false
	================================================================= */

	function soraChkForm(formField, checkName, message, minlength, maxlength) {	

		formField.value = formField.value.ltrim().rtrim()	
		formValue = formField.value;

		//  -------------------- 주민등록번호가 아닐 경우 길이 Check  --------------------
		if(checkName != 'jumin' || (minlength == maxlength == 0)){
			if (formField == null ) {
				return false;
			}
		
			if (formValue == '' && minlength > 0){
				alert(message + " 필수입력 항목입니다.");
				_cmdfocus(formField);
				return false;
			}

			if (minlength > 1 && formValue.strLen() < minlength) {
				alert(message + " 최소 " + minlength + " 자이상 입력하세요.");
				_cmdfocus(formField);
				return false;
			}

			if (formValue.strLen() > maxlength) {
				alert(message + " 최대 영어 " + maxlength + "자, 한글 " + (maxlength / 2 - (maxlength % 2) / 2) + "자까지 입력 가능합니다.");
				_cmdfocus(formField);
				return false;
			}
		}		

		//  -------------------- 욕설 Check  --------------------
		slang = new Array(
				"개새끼","소새끼","병신","지랄","씨팔",
				"십팔","니기미","찌랄","지랄","쌍년","쌍놈",
				"빙신","좆까","니기미","좆같은","잡놈","벼엉신","바보새끼",
				"씹새끼","씨발","씨팔","시벌","씨벌","떠그랄","좆밥","쉐이",
				"등신","싸가지","미친놈","미친넘","미친년","찌랄","씨밸넘");
	//	for (i = 0; i <= slang.length; i++)  if (formValue.indexOf(slang[i]) >= 0) {
	//		alert("욕설이나 비속어는 입력할 수 없습니다.");
	//		_cmdfocus(formField);
	//		return false;
	//	}

		switch(checkName) {
			
			case "" :
				return true;

			// ------------------- 영문자로만 이루어져 있는지 --------------------
			case "alpha" :
				if (formValue.isalpha()) {
					return true;
				} else {
					alert(message + " 영문자만 입력 가능 합니다.");
					_cmdfocus(formField);
					return false;
				}
				break;

			// ------------------- 숫자로만 이루어져있는지 --------------------
			case "number" :

				if (formValue.isnumber()) {
					return true;
				} else {
					alert(message + " 숫자만 입력 가능 합니다.");
					_cmdfocus(formField);		
					return false;
				}
				break;

			// ------------------- 영문자와 숫자로만 이루어져 있는지 --------------------
			case "id" :
				if (formValue.isid()) {
					return true;
				} else {
					alert(message + " 영문자와 숫자만 입력 가능 합니다.");
					_cmdfocus(formField);		
					return false;
				}
				break;
			
			// ------------------- 전화번호인지 --------------------
			case "tel" :
				if (formValue.istel()) {
					return true;
				} else {
					alert(message + " 숫자와 - 만 입력 가능합니다.");
					_cmdfocus(formField);		
					return false;
				}
				break;
			
			// ------------------- E-Mail인지 --------------------
			case "email" :
				if (formValue.isemail()) {
					return true;
				} else {
					alert("E-Mail 형식이 틀립니다. 다시 입력해 주세요 (형식: account@localhost.com)");
					_cmdfocus(formField);		
					return false;
				}
				break;
			
			// ------------------- 날짜인지 (YYYY-MM-DD) --------------------
			case "date" :
				if (formValue.isdate()) {
					return true;
				} else {
					alert("날짜 형식이 틀립니다. 다시 입력해 주세요 (형식: 2002-09-20)");
					_cmdfocus(formField);		
					return false;
				}
				break;

			// ------------------- 주민등록번호인지 --------------------
			case "jumin" :
				if(formValue.strLen() != 13){
					alert("주민등록번호를 정확히 입력해주세요");
					return false
				}

				if (formValue.isjumin()) {
					return true;
				} else {
					alert("주민등록번호를 정확히 입력해주세요");
					return false;
				}
				break;
		}
	}
	

	/* =================================================================
		메소드 명: soraGetCookie()
		내	 용: Cookie 값 조회
		작  성 자: FID Noticeme PANG (noticeme@fid.co.kr)
		작  성 일: 2003.06.30
	   ----------------------------------------------------------------
		name			쿠키명
	================================================================= */

	function soraGetCookie(name){
		var cname = name + "=";
		var dc = document.cookie;
		var val = "";

		if (dc.length > 0) {
			begin = dc.indexOf(cname);
			if (begin != -1) {
				begin += cname.length;
				end = dc.indexOf(";", begin);
				if (end == -1) end = dc.length;
				val += unescape(dc.substring(begin, end));
			}
		}

		return val;
	}

	/* =================================================================
		메소드 명: soraSetCookie()
		내	 용: Cookie Setting
		작  성 자: FID Noticeme PANG (noticeme@fid.co.kr)
		작  성 일: 2003.06.30
	   ----------------------------------------------------------------
		name			쿠키명
		value			쿠키값
		expiredays		만료시간
	================================================================= */

	function soraSetCookie(name, value, expiredays){
		var today = new Date();
		today.setDate(today.getDate() + expiredays);
		document.cookie = name + "=" + escape( value ) + "; path=/; expires=" + today.toGMTString() + ";";
	}

	/* =================================================================
		메소드 명: soraShowUserLayer()
		내	 용: 사용자정보 보기 Layer 출력
		작  성 자: FID Noticeme PANG (noticeme@fid.co.kr)
		작  성 일: 2003.06.30
	   ----------------------------------------------------------------
		userid			User ID
		num				구분자(중복 User ID를 위한..)
		status			visible/hidden
	================================================================= */

	var select_obj;

	function soraShowUserLayer(userid, num, status) { 

		var obj = document.all['soraUserLayer' + userid + num];
		var _tmpx,_tmpy, marginx, marginy;

		_tmpx = event.clientX + parseInt(obj.offsetWidth);
		_tmpy = event.clientY + parseInt(obj.offsetHeight);

		_marginx = document.body.clientWidth - _tmpx;
		_marginy = document.body.clientHeight - _tmpy ;

		if(_marginx < 0) _tmpx = event.clientX + document.body.scrollLeft + _marginx;
		else			 _tmpx = event.clientX + document.body.scrollLeft;

		if(_marginy < 0) _tmpy = event.clientY + document.body.scrollTop + _marginy + 20;
		else			 _tmpy = event.clientY + document.body.scrollTop;

		obj.style.posLeft = _tmpx - 13;
		obj.style.posTop  = _tmpy - 12;

		if (status == 'visible') {

			// 왼쪽 Mouse Click만 Check..
			//if (event.button != 1) return;

			if(select_obj) {
				select_obj.style.visibility = 'hidden';
				select_obj = null;
			}
			select_obj = obj;

		} else select_obj = null;
		
		
		obj.style.visibility = status; 
	}

	/* =================================================================
		메소드 명: soraPrintUserLayer()
		내	 용: 사용자정보 보기 Layer 출력
		작  성 자: FID Noticeme PANG (noticeme@fid.co.kr)
		작  성 일: 2003.06.30
	   ----------------------------------------------------------------
		userid			User ID
		num				구분자(중복 User ID를 위한..)
		email			E-Mail
	================================================================= */

	function soraPrintUserLayer(userid, num, email) {

		layer  = '<div id=soraUserLayer' + userid + num + ' style="visibility:hidden;position:absolute;left:10px;top:25px;" onMouseLeave="soraShowUserLayer(\'' + userid + '\', \'' + num + '\', \'hidden\')">';
		layer += '<table border=0 cellpadding=0 cellspacing=0 width=96 style="cursor:hand" >';
		layer += '<colgroup valign=top>';
		layer += '<col width=5>';
		layer += '<col width=91>';
		layer += '</colgroup>';
		layer += '<tr>';
		layer += '<td><img src="/common/images/detail_left.gif" vspace=1></td>';
		layer += '<td style="border:3px solid #AFAFAF; padding:4 0 1 0;" xheight=85 bgcolor=#FFFFFF>';
		layer += '	<table border=0 cellpadding=0 cellspacing=0>';
		layer += '	<colgroup valign=top>';
		layer += '	<col width=15 align=center>';
		layer += '	<col width=70>';
		layer += '	</colgroup>';
		layer += '	<tr>';
		layer += '	<td><img src="/common/images/detail_bul_01.gif" vspace=1></td>';
		layer += '	<td class=member_detail onClick="window.open(\'http://my.sora.net/msg/pop_send_new.php?p_userid=' + userid + '\', \'sora_memo_new\', \'width=300,height=380\');soraShowUserLayer(\'' + userid + '\', \'' + num + '\', \'hidden\')"><a href="javascript:;">쪽지보내기</a></td>';
		layer += '	</tr>';
		layer += '	<tr height=6><td colspan=2><img src="/common/images/0.gif" width=1 height=1></td></tr>';
		layer += '	<tr>';
		layer += '	<td><img src="/common/images/detail_bul_01.gif" vspace=1></td>';
		layer += '	<td class=member_detail onClick="window.open(\'mailto:' + email + '\');soraShowUserLayer(\'' + userid + '\', \'' + num + '\', \'hidden\')"><a href="javascript:;">메일보내기</a></td>';
		layer += '	</tr>';
		layer += '	<tr height=6><td colspan=2><img src="/common/images/0.gif" width=1 height=1></td></tr>';
		layer += '	<tr>';
		layer += '	<td><img src="/common/images/detail_bul_01.gif" vspace=1></td>';
		layer += '	<td class=member_detail onClick="window.open(\'http://www.sora.net/member/pop_user_info.php?p_userid=' + userid + '\', \'memberinfo\', \'width=318,height=313,scrollbars=yes\');soraShowUserLayer(\'' + userid + '\', \'' + num + '\', \'hidden\')"><a href="javascript:;">정보보기</a></td>';
		layer += '	</tr>';
		//layer += '	<tr height=6><td colspan=2><img src="/common/images/0.gif" width=1 height=1></td></tr>';
		//layer += '	<tr>';
		//layer += '	<td><img src="/common/images/detail_bul_01.gif" vspace=1></td>';
		//layer += '	<td class=member_detail onClick="soraShowUserLayer(\'' + userid + '\', \'' + num + '\', \'hidden\')"><a href="javascript:;">닫기</a></td>';
		//layer += '	</tr>';
		layer += '	</table>';
		layer += '</td>';
		layer += '</tr>';
		layer += '</table></div>';

		document.writeln(layer);
	}

	/* =================================================================
		메소드 명: soraPops()
		내	 용: 스크롤 있는 팝업 띄우기
		작  성 자: 박용욱(pwook@fid.co.kr)
		작  성 일: 2003.07.14
	   ----------------------------------------------------------------
		url			Popup창 URL
		name		Popup창 이름
		x,y			출력위치
		wd,he		폭,높이
	================================================================= */

	function soraPops(url, name, x, y, wd, he)
	{
		if (x == "") x = 100;
		if (y == "") x = 50;
		window.open(url, name, "left=" + x + ", top=" + y + ", toolbar=0, menubar=0, scrollbars=yes, resizable=no, width=" + wd + ", height=" + he + ";")
	}


	/* =================================================================
		메소드 명: soraPop()
		내	 용: 스크롤 없는 팝업 띄우기
		작  성 자: 박용욱(pwook@fid.co.kr)
		작  성 일: 2003.07.14
	   ----------------------------------------------------------------
		url			Popup창 URL
		name		Popup창 이름
		x,y			출력위치
		wd,he		폭,높이
	================================================================= */

	function soraPop(url, name, x, y, wd, he)
	{
		if (x == "") x = 100;
		if (y == "") x = 50;
		window.open(url, name, "left=" + x + ", top=" + y + ", toolbar=0, menubar=0, scrollbars=no, resizable=no, width=" + wd + ", height=" + he + ";")
	}

	/* =================================================================
		메소드 명: soraChkAll()
		내	 용: 여러개의 Checkbox에 대해 Check 여부 변경
		작  성 자: 방민석(noticeme@fid.co.kr)
		작  성 일: 2003.07.14
	   ----------------------------------------------------------------
		Parameter: form, field, value
	================================================================= */

	function soraChkAll(form, field, value) {

		try {
			with(form) {
				p_mnum = elements(field);

				// 체크박스가 여러개일 때
				if (p_mnum.length > 0) {
					for (i = 0; i < p_mnum.length; i++) p_mnum[i].checked = value;
				}
				// 체크박스가 하나일 때
				else {
					p_mnum.checked = value;
				}
			}
		}
		catch (exception) {
			//체크박스가 없거나 에러 날 때
		}
	}

