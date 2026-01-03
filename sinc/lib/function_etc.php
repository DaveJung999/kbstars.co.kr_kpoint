<?php
//=======================================================
// 설	명 : 기타 함수들 (function_misc.php)
// 이것은 include하는 것이 아니구,,, 쓸만한 function을 모아둔 것임..
// 책임자 : 박선민 (sponsor@new21.com)
// ChangeLog
//	 DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 24/05/18 Gemini	PHP 7 마이그레이션
//=======================================================
/**
string_clean($var)// 폼에서 넘어온 값등의 매직쿼터 없앰
form_clean_note($text)
getmicrotime()
posttohost($url, $data)
make_popup_link ($url, $linktext=false, $target=false, $windowprops="", $extras=false)
*/

// 폼에서 넘어온 값등의 매직쿼터 없앰
function form_clean($var){
	// get_magic_quotes_gpc() 함수는 PHP 7에서 삭제됨.
	// 이제 Magic Quotes는 기본적으로 비활성화되어 있으므로 htmlspecialchars만 사용
	return htmlspecialchars($var);
}

// 폼에서 넘오온 테스트필드값을 <pre></pre>에 넣을때 각종 값 처리
function form_clean_note($text){
	$text = htmlspecialchars(trim($text));

	/* turn urls into links */
	$text = preg_replace("/((mailto|http|ftp|nntp|news):.+?)(&gt;|\\s|\\)|\\.\\s|$)/i", "<a href=\"\\1\">\\1</a>\\3", $text);

	/* this 'fixing' code will go away eventually. */
	$fixes = array('<br>','<p>','</p>');
	// each() 함수는 PHP 7에서 제거됨. foreach로 변경
	foreach($fixes as $f){
		$text=str_replace(htmlspecialchars($f), $f, $text);
		$text=str_replace(htmlspecialchars(strtoupper($f)), $f, $text);
	}

	/* this will only break long lines */
	if (function_exists("wordwrap")){
		$text = wordwrap($text);
	}

	$text = "<pre class=\"note\">".$text."</pre>";
	return $text;
}

// 마이크로 타임 구함
function getmicrotime(){
	// split() 함수는 PHP 7에서 제거됨. explode로 변경
	$microtimestmp = explode(" ",microtime());
	return $microtimestmp['0']+$microtimestmp['1'];
}

// 특정 사이트에서 post방식으로 값넘기기
function posttohost($url, $data){
	$url = parse_url($url);
	if (!$url) return "couldn't parse url";

	$encoded = "";

	// each() 함수는 PHP 7에서 제거됨. foreach로 변경
	foreach ($data as $k =>  $v){
		$encoded .= ($encoded ? "&" : "");
		$encoded .= rawurlencode($k) . "=".rawurlencode($v);
	}

	// fsockopen 오류 처리 추가
	$fp = @fsockopen($url['host'],isset($url['port'])?$url['port']:80, $errno, $errstr, 30);
	if (!$fp) return "failed to open socket to ".$url['host'].": ".$errstr." (".$errno.")";

	fputs($fp, sprintf("POST %s%s%s HTTP/1.0\r\n", $url['path'], isset($url['query']) ? "?" : "", isset($url['query']) ? $url['query'] : ''));
	fputs($fp, "Host: ".$url['host']."\r\n");
	fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
	fputs($fp, "Content-length: " . strlen($encoded) . "\r\n");
	fputs($fp, "Connection: close\r\n\r\n");

	fputs($fp, "{$encoded}\r\n");

	$line = fgets($fp,1024);
	// eregi() 함수는 PHP 7에서 제거됨. preg_match로 변경.
	if (!preg_match("/^HTTP\/1\.. 200/i", $line)) return;

	$results = ""; $inheader = 1;
	while(!feof($fp)){
		$line = fgets($fp,1024);
		if ($inheader && ($line == "\n" || $line == "\r\n")){
			$inheader = 0;
		} elseif (!$inheader){
			$results .= $line;
		}
	}
	fclose($fp);

	return $results;
}

# make_popup_link()
# return a hyperlink to something, within the site, that pops up a new window
#
function make_popup_link ($url, $linktext=false, $target=false, $windowprops="", $extras=false){
	return sprintf("<a href=\"%s\" target=\"%s\" onclick=\"window.open('%s','%s','%s');return false;\"%s>%s</a>",
		htmlspecialchars($url),
		($target ? $target : "_new"),
		htmlspecialchars($url),
		($target ? $target : "_new"),
		$windowprops,
		($extras ? ' '.$extras : ''),
		($linktext ? $linktext : $url)
	);
}
?>
