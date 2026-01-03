<?php
## function_app.php
## 설명 : 갖종 변수의 값 체크 함수 모음
## Last Edit 2002. 1. 24. by Sunmin Park(sponsor@new21.com)
## 03/10/09 박선민 replace_string()에서 nl2br()쓰지 않게 수정(버그)
/* 포함함수
	// 아직 아래 함수 체크하지 않음
	remote_addr($reverse = 0,$addr = 0)
	remove_space($arr, $user_function='trim')
	change_magic_quotes($arr, $type='')
	listFile($dir, $file_type)
	// sform($size) <-- 일단 삭제
	get_agent($compagent = '')
	file_upload($formuploadname,$updir)
	cut_string($str, $length)
	auto_link($str)
	ugly_han($arr)
	replace_url($url, $type=1)
	replace_string($content, $type='TEXT')
*/

function remote_addr($reverse = 0, $addr = null){
	if ($addr === null){
		// proxy를 요청 유무
		$host = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
	} else {
		$host = $addr;
	}

	$host = $reverse ? @gethostbyaddr($host) : $host;
	return $host;
}

/*-------------------------------------------------------------------
	함수명		remove_space
	인자		$arr	$user_function
	반환값		$arr2
	수정일		2002. 2. 18
	설명		넘어온 값을 함수에 맞춰서 공백을 제거한다.
				trim	양쪽 공백 제거
				ltrim	앞쪽 공백 제거
				chop	뒤쪽 공백 제거
-------------------------------------------------------------------*/
function remove_space($arr, $user_function='trim'){
	// 넘어온 값이 배열인지 판별
	if(is_array($arr)){
		$arr2 = [];
		// 배열에 있는 모든 Key와 Value를 $type에 맞추어 처리한다.
		foreach($arr as $key => $value){
			if(is_array($value)) { // 이게 또 배열이면
				$arr2[$key] = remove_space($value, $user_function); // 재귀 실행
			} else {
				$arr2[$key] = $user_function($value);
			}
		}
	} else {
		$arr2 = $user_function($arr);
	}
	return $arr2;
}

/*-------------------------------------------------------------------
	함수명		change_magic_quotes
	인자		$arr	$type
	반환값		$arr2
	수정일		2000. 11. 23
	설명		$type에 맞추어서 $arr배열에 있는 모든 값들의 매직 쿼터를 추가, 삭제한다.
-------------------------------------------------------------------*/
function change_magic_quotes($arr, $type = ''){
	// $type 이 없으면, return
	if(!$type){
		return $arr;
	}

	// 넘어온 값이 배열인지 판별
	if(is_array($arr)){
		$arr2 = [];
		// 배열에 있는 모든 Key와 Value를 $type에 맞추어 처리한다.
		foreach($arr as $key => $value){
			if($type == 'add'){
				$arr2[$key] = addslashes($value);
			} elseif($type == 'strip'){
				$arr2[$key] = stripslashes($value);
			}
		}
	} else {
		if($type == 'add'){
			$arr2 = addslashes($arr);
		}
		elseif($type == 'strip'){
			$arr2 = stripslashes($arr);
		}
	}

	return $arr2;
}


/**********************************************************************************
	함수명		: listFile($dir, $file_type)
	인자		: $dir, $file_type
	설명		: $dir 디렉토리에서 $file_type에 해당하는 파일 목록을 반환한다.
	반환값		: string
	수정일		: 2000. 09. 21
**********************************************************************************/
function listFile($dir, $file_type){
	if (!is_dir($dir)){
		return '';
	}

	$files = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ($iterator as $file){
		if ($file->isFile()){
			if (empty($file_type) || pathinfo($file->getFilename(), PATHINFO_EXTENSION) == $file_type){
				$files[] = $file->getPathname();
			}
		}
	}

	return implode("\n", $files);
}


/****************************************************************************************

	함수명		: get_agent
	인자		: $compagent
	설명		: 브라우저의 종류를 특정 값으로 반환한다.
	반환값		: $agent
	수정일		: 2000. 07. 03

*****************************************************************************************/
function get_agent($compagent = ''){
	$agent_env = $_SERVER['HTTP_USER_AGENT'] ?? '';
	$agent = ['br' => 'OTHER', 'os' => 'OTHER', 'ln' => ''];

	if(preg_match('/MSIE/i', $agent_env)){
		if(preg_match('/5\.5/i',$agent_env)) $agent['br'] = 'MSIE5.5';
		else $agent['br'] = 'MSIE';

		if(preg_match('/NT/i', $agent_env)) $agent['os'] = 'NT';
		else if(preg_match('/Win/i', $agent_env)) $agent['os'] = 'WIN';
		else $agent['os'] = 'OTHER';
	}
	elseif(preg_match('/Lynx/i', $agent_env)){
		$agent['br'] = 'LYNX';
	}
	elseif(preg_match('/Konqueror/i',$agent_env)){
		$agent['br'] = 'KONQ';
	}
	elseif(preg_match('/^Mozilla/i', $agent_env)){
		if(preg_match('/Gecko|Galeon/i',$agent_env)) $agent['br'] = 'MOZL6';
		else $agent['br'] = 'MOZL';

		if(preg_match('/NT/i', $agent_env)){
			$agent['os'] = 'NT';
			if(preg_match('/\[ko\]/i', $agent_env)) $agent['ln'] = 'KO';
		}
		elseif(preg_match('/Win/i', $agent_env)){
			$agent['os'] = 'WIN';
			if(preg_match('/\[ko\]/i', $agent_env)) $agent['ln'] = 'KO';
		}
		elseif(preg_match('/Linux/i', $agent_env)){
			$agent['os'] = 'LINUX';
			if(preg_match('/\[ko\]/i', $agent_env)) $agent['ln'] = 'KO';
		}
		else $agent['os'] = 'OTHER';
	}
	else $agent['br'] = 'OTHER';

	if($compagent){
		return preg_match("/{$compagent}/i", $agent['br']) ? 1 : 0;
	}
	return $agent;
}


/****************************************************************************************

	함수명		: file_upload
	인자		:
	설명		: 파일을 특정 디렉토리에 업로드한다.(정회원 전용 갤러리에 사용함)
	반환값		: $ufile - 파일 정보 배열
	수정일		: 04/12/23 $ufile['realname']으로 업로드 실제 파일명도 리턴
				04/12/28 bugfix-확장자 3자이상을 지우는 버그
*****************************************************************************************/
function file_upload($formuploadname, $updir){
	$maxfilesize = ini_get('upload_max_filesize');
	if (preg_match('/M/i', $maxfilesize)){
		$maxfilesize = intval($maxfilesize) * 1024 * 1024;
	} else {
		$maxfilesize = intval($maxfilesize);
	}

	$ufile = $_FILES[$formuploadname] ?? null;

	if ($ufile && $ufile['error'] == UPLOAD_ERR_OK && is_uploaded_file($ufile['tmp_name'])){
		if ($ufile['size'] > $maxfilesize){
			back("최대 업로드 허용 용량은 ". number_format($maxfilesize) ." Byte 입니다. \\n 용량을 초과하였습니다.");
			exit;
		}

		$ufile['name'] = preg_replace('/\s/','',$ufile['name']);
		$ufile['realname'] = $ufile['name'];

		if (preg_match('/[^\x{A1}-\x{FE}a-z0-9\._-]|(?<!\.)\.\.(?!\.)/iu', urldecode($ufile['name']))){
			back('이름에 특수문자가 포함되어 있습니다. \\n이름을 변경하여 다시 업로드하여 주시기 바랍니다');
			exit;
		}

		$ufile['name'] = preg_replace('/\.\.+/','.',$ufile['name']);
		$ufile['name'] = preg_replace('/\.{2,}/','.',$ufile['name']);
		$ufile['name'] = preg_replace('/\.(ph|inc|php[0-9a-z]*|phtml)$/i','.phps', $ufile['name']);
		$ufile['name'] = preg_replace('/(.*)\.(cgi|pl|sh|html|htm|shtml|vbs)$/i', '$1_$2.phps', $ufile['name']);
		$ufile['name'] = basename($ufile['name']);

		$file_extension = pathinfo($ufile['name'], PATHINFO_EXTENSION);
		$file_name_without_extension = basename($ufile['name'], '.' . $file_extension);

		$i = 1;
		while (file_exists($updir . '/' . $ufile['name'])){
			$ufile['name'] = $file_name_without_extension . '_' . $i . '.' . $file_extension;
			$i++;
		}


		if(!is_dir($updir)){
			$oldumask = umask(0);
			if(!@mkdir($updir,0777, true)){
				back('업로드 디렉토리를 만들 수 없습니다.\\n관리자님은 디렉토리 퍼미션을 확인바랍니다.');
			}
			umask($oldumask);
		}

		if(move_uploaded_file($ufile['tmp_name'], $updir.'/'.$ufile['name'] )){
			@chmod($updir.'/'.$ufile['name'],0644);
		} else {
			back('파일 업로드를 실패하였습니다. \\n다시 업로드하여 주시기 바랍니다.');
		}
	} else {
		// UPLOAD_ERR_NO_FILE 에러도 파일이 아예 선택되지 않았을 때 발생하므로, $ufile['tmp_name']이 없는 경우에 처리
		if (!$ufile) {
			back('파일이 업로드되지 않았습니다.');
			return null;
		}
		
		switch( $ufile['error'] ){
			case UPLOAD_ERR_INI_SIZE:
				back('업로드 가능한 최대크기는 '.ini_get('upload_max_filesize').'입니다.\\n용량을 줄여서 업로드하여 주시기 바람니다');
				break;
			case UPLOAD_ERR_FORM_SIZE:
				back('업로드 가능한 최대크기는 '.($_POST['MAX_FILE_SIZE'] ?? '').'입니다.\\n용량을 줄여서 업로드하여 주시기 바람니다');
				break;
			case UPLOAD_ERR_PARTIAL:
				back('파일이 정상적으로 업로드되지 않았습니다. 잠시후 다시 업로드하여주시기 바랍니다.');
				break;
			case UPLOAD_ERR_NO_FILE:
				back('파일이 업로드되지 않았습니다. 파일을 선택하여 주시기 바랍니다.');
				break;
			default:
				back('파일이 업로드되지 않았습니다.');
		}
	}

	unset($ufile['tmp_name']);
	return $ufile;
}


/****************************************************************************************

	함수명		: auto_link
	인자		: $str
	설명		: $str의 값에 자동으로 링크를 시켜준다.
	반환값		: -
	수정일		: 2000. 09. 18
	출처		: JS_Board 1.0.3

*****************************************************************************************/
function auto_link($str){
	$regex_http = "(https?|ftp|telnet|news):\/\/([a-z0-9_\-]+\.)+([a-zA-Z0-9:;&#@=_~%\?\/\.\,\+\-]+)";
	$regex_mail = "([a-z0-9_\-]+\.)+([a-z0-9_\-]+)@([a-z0-9_\-]+\.[a-z0-9\._\-]+)";

	// < 로 열린 태그가 그 줄에서 닫히지 않을 경우 nl2br()에서 <BR> 태그가
	// 붙어 깨지는 문제를 막기 위해 다음 줄까지 검사하여 이어줌
	$str = preg_replace('/<([^<>\n]+)\n([^\n<>]+)>/i', '<$1 $2>', $str);

	// 특수 문자와 링크시 target 삭제
	$str = preg_replace("/&(quot|gt|lt)/i", "!\\1", $str);
	$str = preg_replace("/([ ]*)target=[\"'_a-z,A-Z]+/", "", $str);
	$str = preg_replace("/([ ]+)on([a-z]+)=[\"'_a-z,A-Z\?\.\-_\/()]+/", "", $str);

	// html사용시 link 보호
	$str = preg_replace("/<a([ ]+)href=([\"']*)({$regex_http})([\"']*)>/i", "<a href=\"$3\" target=\"_blank\">", $str);
	$str = preg_replace("/<a([ ]+)href=([\"']*)mailto:({$regex_mail})([\"']*)>/i", "<a href=\"mailto:$3\">", $str);
	$str = preg_replace("/<img([ ]*)src=([\"']*)({$regex_http})([\"']*)>/i", "<img src=\"$3\">", $str);

	// 링크가 안된 url및 email address 자동링크
	$str = preg_replace("/({$regex_http})/i", "<a href=\"$1\" target=\"_blank\">$1</a>", $str);
	$str = preg_replace("/({$regex_mail})/i", "<a href=\"mailto:$1\">$1</a>", $str);


	// 보호를 위해 치환한 것들을 복구
	$str = preg_replace("/!(quot|gt|lt)/i", "&$1", $str);
	$str = preg_replace("/http_orig/i", "http", $str);
	$str = preg_replace("/#-#/i", "@", $str);

	// link가 2개 겹쳤을때 이를 하나로 줄여줌
	$str = preg_replace("/(<a href=([\"']*)({$regex_http})([\"']*)+([^>]*)>)+<a href=([\"']*)({$regex_http})([\"']*)+([^>]*)>/i", "$1", $str);
	$str = preg_replace("/(<a href=([\"']*)mailto:({$regex_mail})([\"']*)>)+<a href=([\"']*)mailto:({$regex_mail})([\"']*)>/i", "$1", $str);
	$str = preg_replace("/<\/a><\/a>/", "</a>", $str);

	return $str;
}



function ugly_han($arr, $html = 0){
	// html사용을 안할 경우 IE에서 문법에 맞지 않는 글자 표현시 깨지는 것을 수정
	// 넘어온 값이 배열인지 판별
	if(!$html){
		if(is_array($arr)){
			// 배열에 있는 모든 Key와 Value를 $type에 맞추어 처리한다.
			$arr2 = [];
			foreach($arr as $key => $value){
				if(is_array($value)) { // 이게 또 배열이면
					$arr2[$key] = ugly_han($value, $html); // 재귀 실행
				} else {
					$arr2[$key] = preg_replace('/&amp;(#|amp)/i','&$1',$value);
				}
			}
		} else {
			$arr2 = preg_replace('/&amp;(#|amp)/i','&$1',$arr);
		}
		return $arr2;
	}
	return $arr;
}


/****************************************************************************************

	함수명		: replace_url
	인자		: $url
	설명		: URL에 프로토콜을 나타내는 부분이 있는지 확인하여 http:// 를 붙인다.
	반환값		: $url
	수정일		: 2001. 11. 28

*****************************************************************************************/
function replace_url($url, $type = 1){
	$url = trim($url);

	// 기본적으로 넘어온 URL에 프로토콜을 나타내는 부분이 있는지 확인하여 http:// 를 붙인다.
	if(!preg_match('/^(http|https|ftp|telnet|news):\/\//i', $url)){
		$url = 'http://' . $url;
	}
	$url = preg_replace('/^(http|https|ftp|telnet|news):\/\//i', '', $url);

	// 넘어온 $type 에 따라서 URL 변경
	$url = $type ? 'http://' . $url : $url;

	return $url;
}

/****************************************************************************************

	함수명		: replace_string
	인자		: $content, $type
	설명		: $type에 맞추어 $content의 값을 변경시킨다.
	반환값		: string
	수정일		: 2000. 09. 18
	출처		: JS_Board 1.0.3

*****************************************************************************************/
function replace_string($content, $type = ''){
	$type = strtoupper($type);
	if($type == 'HTML'){
		$content = preg_replace('/<\?(.*)\?>/i', '&lt;?$1?&gt;', $content);
		$content = preg_replace('/<script([^>]*)>(.*)<\/script>/i', '&lt;script$1&gt;$2&lt;/script&gt;', $content);
	}
	elseif($type == 'PRE'){
		$content = "\n<pre>\n" . htmlspecialchars($content) . "\n</pre>\n";
	}
	elseif($type == 'STRIP'){
		$content = strip_tags($content);
		$content = preg_replace("/(\r\n|\n|\r)/", '<br />', $content);
		$content = auto_link($content);
	} else { // TEXT
		$content = htmlspecialchars($content);
		$content = preg_replace("/(\r\n|\n|\r)/", '<br />', $content);
		$content = auto_link($content);
	}

	return $content;
}
?>
