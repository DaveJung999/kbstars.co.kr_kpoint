<?php
//=======================================================
// 설 명 : 기본 평션 - 체크관련 함수(function_check.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/12
// Project: sitePHPbasic
// ChangeLog
//	 DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/12/29 박선민 더블쿼터,배열변수 문법정리
// 05/01/12 박선민 check_value()에 null 옵션 추가
//=======================================================
/* 포함함수
	// 아직 아래 함수 체크하지 않음
	check_value($query, $list)
	check_email($email)
	check_url($url)
	$idnum=check_idnum($id1, $id2) // 주민등록번호 검사후 합친 번호 리턴
	$compnum=check_idnum($id1, $id2, $id3 ) // 사업자등록번호체크
	check_spam($file, $text) 
*/

/**
 * get,post,session 등으로 넘어온값들 일괄 체크함수
 * * @param	
 *
 * @return	
 * @since 04/09/09
 * @author Sunmin Park sponsor@new21.com
 */
function check_value($query, $debug=0){
	$list = [];
	foreach($query as $key =>  $value){
		$checklist=explode(',',$value);
		foreach($checklist as $check){
			$tmp=$msg='';
			if(($tmp=strpos($check,'=')) !== false){
				$msg=urldecode(substr($check,$tmp+1));
				$check=substr($check,0,$tmp);				
			}
			switch($check){
				// get, post, cookie, session 값을 변수에 저장
				case 'get':
					$list[$key]=isset($_GET[$key]) ? $_GET[$key] : '';
					break;
				case 'post':
					$list[$key]=isset($_POST[$key]) ? $_POST[$key] : '';
					break;
				case 'file':
					$list[$key]=isset($_FILES[$key]['name']) ? $_FILES[$key]['name'] : '';
					break;
				case 'cookie':
					$list[$key]=isset($_COOKIE[$key]) ? $_COOKIE[$key] : '';
					break;
				case 'session':
					$list[$key]=isset($_SESSION[$key]) ? $_SESSION[$key] : '';
					break;
				case 'request':
					$list[$key]=isset($_REQUEST[$key]) ? $_REQUEST[$key] : '';
					break;
				
				// 값 체크
				case 'null' :		// 빈값이여야함
					if(is_array($list[$key])) back("{$key} 값은 배열로 넘어오면 안됩니다.");
					elseif(isset($list[$key]) && $list[$key]){
						if(!$msg) $msg=" 잘못된 요청 - {$key} 는 빈값이어야 합니다.";
						back($msg);
					}
					unset($list[$key]);
					break;				
				case 'notnull' :		// 빈값이면 에러메시지 출력
					if(is_array($list[$key])) back("{$key} 값은 배열로 넘어오면 안됩니다.");
					elseif(!isset($list[$key]) || !$list[$key]){
						if(!$msg) $msg=" {$key} 값이 빈값입니다.";
						back($msg);
					}
					break;
				case 'checkNumber' :	// 숫자인지(콤머가 있으면 없애고)
					if(isset($list[$key])){
						$list[$key] = preg_replace('/,/', '', $list[$key]);// 콤머가 있으면 없앰
						if(is_array($list[$key])) back("{$key} 값은 배열로 넘어오면 안됩니다.");
						elseif(!preg_match('/^[0-9\-][0-9]*$/', $list[$key]) ) { // 음수도 포함
							if(strlen($list[$key])){
								if(!$msg) $msg=" {$key} 값은 숫자만 입력하여야 합니다.";
								back($msg);
							}
							else $list[$key]=0; // 빈값이면 0으로 리턴.
						}
					}
					break;
				case 'checkAlphabet' :	// 알파벳인지
					if(isset($list[$key])){
						if(is_array($list[$key])) back("{$key} 값은 배열로 넘어오면 안됩니다.");
						elseif(strlen($list[$key])>0 and !preg_match("/^[a-zA-Z]+$/", $list[$key]) ){
							if(!$msg) $msg=" {$key} 값은 영문자만 입력하여야 합니다";
							back($msg);
						}
					}
					break;
				case 'checkAlphabetNumber' :	// 알파벳+숫자인지
				case 'checkNumberAlphabet' :
					if(isset($list[$key]) && !is_array($list[$key]) and strlen($list[$key])>0 and !preg_match("/^[a-zA-Z0-9]+$/", $list[$key]) ){
						if(!$msg) $msg=" {$key} 값은 영문자와 숫자만 입력하여야 합니다";
						back($msg);
					}
					break;
				case 'checkEmail':		// E메일 체크
					if(isset($list[$key])){
						if(is_array($list[$key])) back("{$key} 값은 배열로 넘어오면 안됩니다.");
						$list[$key] = check_email($list[$key], $msg);
					}
					break;
				case 'checkUrl':		// URL 체크 (리턴값은 http://.... 등으로)
					if(isset($list[$key])){
						if(is_array($list[$key])) back("{$key} 값은 배열로 넘어오면 안됩니다.");
						$list[$key] = check_url($list[$key], $msg);
					}
					break;

				// 값 변환
				case 'trim' :			// 변수의 양쪽 공백값 없앰
					if(isset($list[$key]) && !is_array($list[$key]))
						$list[$key]=trim($list[$key]);
					break;
			} // end of switch
		} // end of checklist
	} //end of query

	// 디버그모드(phpinfo와 아래 list값 할당 내용 출력후 종료)
	if($debug){
		echo "<pre>";
		print_r($list);
		echo "</pre>";
		phpinfo();
		exit;
	}

	return $list;
} // end of check_value function

// check_value함수에서도 사용함
function check_email($email,$errmsg=''){
	$email = trim($email);
	if(!preg_match("/^[\xA1-\xFEa-z0-9_-]+@[\xA1-\xFEa-z0-9_-]+\.[a-z0-9\._-]+$/i", $email)){
		if($errmsg) back($errmsg);
		else return false;
	}
	else return $email;
}

/*-------------------------------------------------------------------
	함수명		check_url
	인자		$url
	반환값		$url
	수정일		2000. 07. 03
	설명		URL의 형식이 올바른지 체크한다
-------------------------------------------------------------------*/
// check_value함수에서도 사용함
function check_url($url,$errmsg=''){
	$url = trim($url);

	# 프로토콜(http://, ftp://...)을 나타내는 부분이 없을 때 기본값으로
	# http://를 붙임
	if(!preg_match('/^(http:\/\/|https:\/\/|ftp:\/\/|telnet:\/\/|news:\/\/)/i', $url))
		$url = 'http://' . $url;

	if(!preg_match("/^(http|https|ftp|telnet|news):\/\/[\xA1-\xFEa-zA-Z0-9-]+\.[][\xA1-\xFEa-zA-Z0-9:&#@=_~%\?\/\.\+-]+$/i", $url)){
		if($errmsg) back($errmsg);
		else return false;
	}
	else return $url;
}


/**
 * 한국 주민등록번호 체크
 * * @param $id1	int 주민번호 숫자전체 혹은 주민번호 생일
 * @param $id2	int 주민번호 뒷자리 7자리
 * @param $errmsg	string 빈값이면 return false, 값있으면 에러메시지 보내고 이전페이지로 이동
 * @return int	합쳐진주민등록번호
 */
function check_idnum($id1, $id2='', $errmsg=''){
	if(13 == strlen($id1)){
		$id2 = substr($id1,6);
		$id1 = substr($id1,0,6);
	}
	
	if((strlen($id1) == 6) && (strlen($id2) == 7)){
		//입력된 두 숫자를 합한다. 편의상 '-' 대신 숫자 0을 넣었다. 
		$id = $id1. $id2;
		for($i=0; $i <13; $i++){
			$a[$i] = (int) $id[$i];
		}

		// 자릿수 합산 
		$j = $a['0']*2+$a['1']*3+$a['2']*4+$a['3']*5+$a['4']*6+$a['5']*7+$a['6']*8+$a['7']*9+$a['8']*2+$a['9']*3+$a['10']*4+$a['11']*5;
		$j = $j % 11;
		$k = 11 - $j;
		if($k > 9) 
			$k = $k % 10;
		
		$j = $a['12'];	//주민번호 맨 끝자리 숫자를 대입

		if($j != $k){
			if($errmsg) back( $errmsg );
			else return false;
		}
		
		// 생년월일 확인 및 체크 
		$year = (int) substr($id1, 0, 2);
		$month = (int) substr($id1, 2, 2);
		$day = (int) substr($id1, 4, 2);
		$gender = (int) substr($id2, 0, 1);
		
		if($gender == 1 || $gender == 2){
			$year += 1900;
		} elseif($gender == 3 || $gender == 4){
			$year += 2000;
		}

		if (!checkdate($month, $day, $year)){
			if($errmsg) back( $errmsg );
			else return false;
		}
	} else {
		if($errmsg) back( $errmsg );
		else return false;
	}
	
	return $id1 . '-' . $id2;
}

/**
 * 한국 사업자등록번호 체크
 * * @param $id1	int 사업자등록번호 혹은 1번째 3자리
 * @param $id2	int 사업자등록번호 2번째 2자리 혹은 빈값
 * @param $id3	int 사업자등록번호 3번째 5자리 혹은 빈값
 * @param $errmsg	string 빈값이면 return false, 값있으면 에러메시지 보내고 이전페이지로 이동
 * @return int	합쳐진사업자등록번호
 */
function check_compnum($id1, $id2='', $id3='',$errmsg=''){
	if(10 == strlen($id1)){
		$id2 = substr($id1,3,2);
		$id3 = substr($id1,5);
		$id1 = substr($id1,0,3);
	}
	
	if (preg_match("/^[0-9]{3}$/",$id1) and preg_match("/^[0-9]{2}$/",$id2) and preg_match("/^[0-9]{5}$/",$id3)){
		$c_num = $id1 . $id2 . $id3;
		
		$IDtot = 0;
		$IDAdd = '137137135';
		for ($i=0;$i < 9 ; $i++)
		{
			$IDtot = $IDtot + ((int)substr($c_num,$i,1) * (int)substr($IDAdd,$i,1));
		}
			
		$IDtot = $IDtot + (((int)substr($c_num,8,1)*5)/10);
		$IDtot = 10 - ($IDtot % 10);
	
		if (substr($c_num,-1) != substr($IDtot,-1)){
			if($errmsg) back( $errmsg );
			else return false;
		}
	} else {
		if($errmsg) back( $errmsg );
		else return false;
	}

	return $id1 . '-' . $id2 . '-' . $id3;
}
/*-------------------------------------------------------------------
	함수명		check_spam
	인자		$file, $text
	반환값		-
	수정일		2000. 07. 03
	설명		올라온 글이 Spam인지 체크한다.
-------------------------------------------------------------------*/
function check_spam($file, $text){
	$ff = file_get_contents($file);
	if ($ff === false){
		error('스팸 필터 파일을 읽을 수 없습니다.');
	}

	$spam = explode("\n", $ff);

	foreach($spam as $line){
		$line = trim($line);
		if($line && preg_match("/{$line}/i", $text)){
			error('스팸으로 판단되어 글쓰기를 거부합니다.');
		}
	}
}

/*-------------------------------------------------------------------
	함수명		check_hangul
	인자		$char
	반환값		1(한글)	0(한글아님)
	수정일		2000. 09. 22
	설명		넘어온 문자가 한글인지 체크한다.
-------------------------------------------------------------------*/
/* 04/09/09 잘못된 함수
function check_hangul($char){
	// 특정 문자가 한글의 범위내(0xA1A1 - 0xFEFE)에 있는지 검사
	$char = ord($char);
	if($char >= 0xa1 && $char <= 0xfe) 
		return 1;
}
*/
/*-------------------------------------------------------------------
	함수명		check_alphabet
	인자		$char
	반환값		2(대문자)	1(소문자)	0(영문자아님)
	수정일		2000. 09. 22
	설명		넘어온 문자가 알바벳(대문자, 소문자)인지 체크한다.
-------------------------------------------------------------------*/
/* 04/09/09 잘못된 함수
function check_alphabet($char){
	$char = ord($char);

	if($char >= 0x61 && $char <= 0x7a) 
		return 1;
	if($char >= 0x41 && $char <= 0x5a)
		return 2;
}
*/
?>