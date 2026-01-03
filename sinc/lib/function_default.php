<?php
//=======================================================
// 설	명 : 기본함수 : 웹상의 에러메시지 출력 함수 모음 (function_default.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	 DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/12/23 박선민 PrivAuth() 추가로 전면 수정
// 05/02/03 박선민 bugfix - prifAuth()
//=======================================================
/* 포함함수
	+ 인증 체크
		privAuth(&$dbinfo, $auth_priv, $go_loginpage=0)
	+ QueryString 관련함수
		href_qs($appendQueryString='',$queryString='',$output_formhidden=0)
	+ 페이지 이동 함수
		back(String $msg, URL $href=0)			// 메시지 박스로 알리고 이동
		back_close(String $msg, URL $href=0)	// 오픈 페이지시 사용
		go_url($url, $second=0)					// 메타테크 이용
	+ misc
		header_security(array $SECURITY, int $server_ip, int $domain, int $version) // 해더 해킹 검사
*/

// 04/12/20 박선민 priv,level 인증 함수
/**
* 인증 함수 (logon.priv, logon.level, joininfo.priv, joininfo.level)
*
- root 권한자, 관리자(bid), 카테고리관리자(cid)이면 무조건 통과
- bid=0이 아니면 운영자도 무조건 통과
'운영자,회원' : priv가 운영자이거나 회원이면 OK
'운영자+회원' : priv가 운영자이고 회원이면 OK
'회원^2 : level이 2이상인 회원이면 OK
'#park,운영자' : 아이디가 park이거나 운영자이면 OK
'@4,@45^2 : '그룹UID가 4인 회원'이거나, '그룹UID가 45인회원중 그룹내 priv가 2레벨이상'인 회원이면 OK
gid=4, '운영팀,개발팀' : 그룹UID가 4인 그룹내 priv가 운영팀이거나 개발팀이면 OK
gid=4, '운영팀^5,개발팀+팀장' : 그룹UID가 4인 그룹내 priv가 '5레벨이상운영팀'이거나 '개발팀이면서팀장'이면 OK
*
* @author	Sunmin Park <richard@php.net>
* @package sitePHPbasic
*/
function privAuth(&$dbinfo, $auth_priv, $go_loginpage=0){
	if(!isset($dbinfo[$auth_priv]) || !$dbinfo[$auth_priv]) return true; // '0'이거나 NULL, 혹은 설정이 되어있지 않으면, 무조건 OK

	if( isset($_SESSION['seUid']) && $_SESSION['seUid'] > 0 ) { // 로그인 여부
		if( (isset($_SESSION['sePriv']['root'])) // root 권한자는 모든 권한
			or ( isset($dbinfo['bid']) && $dbinfo['bid'] == $_SESSION['seUid'] ) // 관리자(bid)는 모든 권한
			or ( isset($dbinfo['cid']) and $dbinfo['cid'] == $_SESSION['seUid']) ) { // 카테고리 관리자(cid)도 모든 권한을
			return true;
		}
		elseif(isset($dbinfo[$auth_priv]) && $dbinfo[$auth_priv] == 'root' and !isset($_SESSION['sePriv']['root']) and (!isset($dbinfo['gid']) || $dbinfo['gid'] == 0))
			return false; // 권한없음!! - root 권한이어야만 함 (좀더 빨리 거부)
		//elseif( isset($_SESSION['sePriv']['운영자']) and !( isset($dbinfo['bid']) and $dbinfo['bid'] == 0 ) ){
		//	return true; // 운영자라면 모든 권한. 단, bid가 0이면 권한 없음.
		//}
		elseif( isset($dbinfo['gid']) and $dbinfo['gid']>0 ) { // 그룹 권한이면,
			if( isset($_SESSION['seGroup'][$dbinfo['gid']]['root']) ) { // 그룹개설자이면 무조건 통과
				return true;
			}
			
			$checkPriv = &$_SESSION['seGroup']['gid']; // 그룹 권한으로 체크
		}
		else $checkPriv = &$_SESSION['sePriv']; // 회원 권한으로 체크

		// 권한 검사!!
		$aPriv = explode(',',$dbinfo[$auth_priv]);
		foreach($aPriv as $v){
			settype($v, "string");
			
			if($v['0'] == '#') { // #userid 해당 회원이면
				if(isset($_SESSION['seUserid']) && $_SESSION['seUserid'] == substr($v,1)) return true;
				continue;
			} elseif($v['0'] == '@') { // @gid 해당 그룹회원이면
				$v = substr($v,1);
				if(strpos($v,'^') !== false) { // 레벨제한 - 예)@gid^7 (해당그룹 7레벨이상만) 
					list($tPriv,$tLevel) = explode('^',$v,2);
					if(isset($_SESSION['seGroup'][$tPriv]) && $tLevel <= $_SESSION['seGroup'][$tPriv]) return true;
				}
				elseif( isset($_SESSION['seGroup'][$v]) ) return true;
				continue;
			}
			
			if(strpos($v,'+') !== false){
				$aPriv2 = explode('+',$v);
				$okPriv = 1;
				foreach($aPriv2 as $v2){
					if(strpos($v2,'^') !== false) { // 레벨제한 - 예)회원^7 (7레벨이상회원만) 
						list($tPriv,$tLevel) = explode('^',$v2,2);
						if($tLevel > (isset($checkPriv[$tPriv]) ? $checkPriv[$tPriv] : 0)){
							$okPriv = 0; // 권한없음!! (복수권한에 들어있지 않음)
							break;
						}
					}
					elseif( !isset($checkPriv[$v2]) ){
						$okPriv = 0; // 권한없음!! (복수권한에 들어있지 않음)
						break;
					}
				}
				if($okPriv) return true;
			} else {
				if(strpos($v,'^') !== false) { // 레벨제한 - 예)회원^7 (7레벨이상회원만) 
					list($tPriv,$tLevel) = explode('^',$v,2);
					if($tLevel <= (isset($checkPriv[$tPriv]) ? $checkPriv[$tPriv] : 0)) return true;
				}
				elseif( isset($checkPriv[$v]) ) return true;
			}
		} // end foreach		
		return false; // 권한없음!!
	} else { // 로그인되지 않았을 경우
		if($go_loginpage) { // 로그인 페이지로 이동
			$_SESSION['seREQUEST_URI'] = $_SERVER['REQUEST_URI'];
			session_write_close();
			go_url('/sjoin/login.php',0,'로그인이 필요합니다.\\n\\n로그인 페이지로 이동합니다.');
			exit;
		} // end if
		return false; // 권한없음!! - 로그인하지 않았으니
	} // end if.. else..
} // end func siteAuth(&dbinfo, $auth_priv)

// 03/09/17 query_string 만들어주는 함수
function href_qs($appendQueryString='',$queryString='',$output_formhidden=0){
	if(!$queryString) $queryString=isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
	
	if($appendQueryString){
		parse_str($queryString,$qs1);
		parse_str($appendQueryString,$qs2);
		$qs=array_merge($qs1,$qs2);
	}
	else parse_str($queryString,$qs);

	$newquery = '';
	foreach ($qs as $key =>  $value){
		if( $key && (is_string($value) && strlen($value)>0) ){
			if($output_formhidden)
				$newquery.="\n<input type=hidden name='{$key}' value='".htmlspecialchars($value,ENT_QUOTES).'\'>'; // 필히 '>'여야함
			else
				$newquery.=urlencode($key) . '=' . urlencode($value) . '&';
		}
	}
	if ($output_formhidden){
		return $newquery;
	} else {
		return substr($newquery,0,-1);
	}
}
/******************************************************************************
	함수명	back(String $msg, URL $href=0)
	설명		$msg를 메시지박스로 보이고, 확인을 누를 경우 $href 페이지로 이동
			(단, $href 페이지가 없을 경우 이전 페이지로 이동)
******************************************************************************/
function back( $msg='', $href='' ){
	$go = $href ? "window.location.href='{$href}';" : 'history.go(-1);';

	if(isset($_SERVER['REQUEST_METHOD'])) { // 웹에서 동작된다면...
		echo <<<HTML
<script>
		if('{$msg}') {
			window.alert("{$msg}");
		}
		{$go}
</script>
HTML;
	} else { // Command Shell 상에서 동작된다면...
		echo "\n*********** 에러발생 ***************\n";
		echo "{$msg}";
		echo "\n------------------------------------\n";
	}
	exit;
}
/******************************************************************************
	함수명	back_close(String $msg, URL $href=0)
	설명		$msg를 메시지박스로 보이고, 확인을 누를 경우 오픈 페이지를 닫고 
			부모 페이지를 $href 페이지로 이동
			(단, $href 페이지가 없을 경우 이전 페이지로 이동)
	*에러가능성 : $href=0인 경우에 부모 페이지의 이전 페이지로 이동할지 테스트하지 못함
// 04/03/25 박선민 오픈창이 아니면, 해당 페이지로 페이지 이동
******************************************************************************/
function back_close($msg='', $href=0 ){
	echo ' <script> ';
	if($msg) echo "	window.alert(\"{$msg}\"); ";
	if($href)
		echo " if(window.opener) {window.opener.location.href='{$href}';self.close();} else {window.location.href='{$href}';} ";
	else 
		echo ' if(window.opener) {window.opener.location.reload(); self.close();} else {history.go(-1);}';
	echo '	</script> ';
	exit;
}

/******************************************************************************
	함수명	go_url(URL $url, int $second=0)
	설명		메타테크를 이용하여 $second 이후에 $url로 페이지 이동
******************************************************************************/
function go_url($url, $second=0, $msg='',$parent='window'){
	$url = str_replace(' ','%20',$url);
	if( $second or headers_sent() or $msg){
		echo ' <script> ';
		if($msg){
			echo "	window.alert(\"{$msg}\"); ";
		}

		if($second){
			echo '	</script> ';
			echo "	<meta http-equiv='Refresh' content='{$second}; URL=$url'>";
			echo '';
		} else {
			echo "	window.location.replace('{$url}');";
//			echo "	{$parent}.location='{$url}'"; // $parent='parent.window'
			echo '	</script> ';
		}
	}
	else{
//		header('Location: '.$url); 

		echo "<script>";
		echo "	window.location.replace('{$url}');";
		echo "</script>";
		
	}
	exit;
}

/******************************************************************************
	함수명	go_url(URL $url, int $second=0)
	설명		메타테크를 이용하여 $second 이후에 $url로 페이지 이동
******************************************************************************/
function go_url2($url, $second=0, $msg='',$parent='window'){
	$url = str_replace(' ','%20',$url);
	if( $second or headers_sent() or $msg){
		echo ' <script> ';
		if($msg){
			echo "	window.alert(\"{$msg}\"); ";
		}

		if($second){
			echo '	</script> ';
			echo "	<meta http-equiv='Refresh' content='{$second}; URL={$url}'>";
			echo '';
		} else {
			echo "	window.location.replace('{$url}');";
//			echo "	{$parent}.location='{$url}'"; // $parent='parent.window'
			echo '	</script> ';
		}
	}
	else{
		header('Location: '.$url); 
/*
		echo "<script>";
		echo "	window.location.replace('{$url}');";
		echo "</script>";
		*/
	}
	exit;
}

/**
 * 문자열 자르기 - 한글도 정확히 자르기
 * * @since 04/08/27
 * @auther Sunmin Park <sponsor@new21.com>
 * @param string	$str	string 자를 문자열
 * @param int	$length	int 최대 문자 byte
 * @param bool	$htmlToText	bool html에서 텍스트를 추출하고 자름
 * * @return	string 잘라진 문자 리턴
 */
function cut_string($str, $length=50, $htmlTotext=0){
	// UTF-8 환경에서 한글도 정확히 자르도록 mb_strimwidth 사용
	if($htmlTotext){
		$str = strip_tags($str);
	}
	
	if (mb_strlen($str, 'UTF-8') > $length){
		return mb_strimwidth($str, 0, $length, '...', 'UTF-8');
	}
	
	return $str;
}

/******************************************************************************
	함수명	header_security(array $SECURITY, int $server_ip, int $domain, int $version)
	설명		해더를 컨파일하더라도 그 해더를 복사하여 다른 서버에서 사용할 수 있기에
			서버 IP, 도메인, 해더 버전을 확인하여 해킹을 일부 방지함
	주의점	$version은 $HEADER['ver']를 넘기기 바람
******************************************************************************/
function header_security(){
	global $HEADER, $SECURITY;

	if((isset($SECURITY['server_ip']) && $_SERVER['SERVER_ADDR'] != $SECURITY['server_ip']) ||
		(isset($SECURITY['domain']) && ($_SERVER['HTTP_HOST'] != $SECURITY['domain'] and $_SERVER['HTTP_HOST'] != 'www.'.$SECURITY['domain']) ) || 
		(isset($SECURITY['version']) && ($HEADER['version'] != $SECURITY['header_version']))){
		back('해더파일을 사용할 수 없습니다.\\n관리자에게 문의 주시기 바랍니다.');
		exit;
	}
}

/******************************************************************************
	함수명	page_security(string $grant_method, string $referer)
	설명	페이지의 GET/POST중 하나만을 허락하는 경우와 외부 링크시 접속 제안
	주의점	$version은 $HEADER['ver']를 넘기기 바람
******************************************************************************/
function page_security($grant_method='', $referer=''){
	if($grant_method){
		$grant_method=trim(strtolower($grant_method));
		if($grant_method == 'get' && count($_POST)) // get만 허락할때
			back('Don\'t Support Method');
		elseif(count($_GET))// post만 허락할때
			back('Don\'t Support Method');
	}
	if($referer){
		if(!stristr($_SERVER['HTTP_REFERER'],$referer))
			back('Don\'t Access From your site\\n' . $_SERVER['HTTP_REFERER']);
	}
}


// 아파치 2.2 에서 한글이름 파일 깨지는 부분 위해
// 2007-08-02 ....................davej..................
function korfile($filename) 
{ 
	// UTF-8 환경이므로 별도 변환 필요 없음
	$filename = urlencode($filename); 
	$filename = str_replace("+", "%20", $filename); 
	$filename = str_replace("%2F", "/", $filename); 

	return $filename; 
} 


function strip_javascript($filter){ 

	// remove frame tags
	$filter = preg_replace("/(\<frameset)(.*?)(frameset>)/si", "", $filter);

	// remove iframe tags
	$filter = preg_replace("/(\<iframe)(.*?)(iframe>)/si", "", $filter);

	// remove script tags
	$filter = preg_replace("/(\<script)(.*?)(script>)/si", "", $filter);

	// realign javascript href to onclick
	$filter = preg_replace("/href=(['\"]).*?javascript:(.*)?\\1/i", "onclick=' $2 '", $filter);

	//remove javascript from tags
	while( preg_match("/<(.*)?javascript.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", $filter))
		$filter = preg_replace("/<(.*)?javascript.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", "<$1$3$4$5>", $filter);

	// dump expressions from contibuted content
	if(0) $filter = preg_replace("/:expression\(.*?((?>[^(.*?)]+)|(?R)).*?\)\)/i", "", $filter);

	while( preg_match("/<(.*)?:expr.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", $filter))
		$filter = preg_replace("/<(.*)?:expr.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", "<$1$3$4$5>", $filter);
	
	// remove all on* events
	while( preg_match("/<(.*)?\son.+?=?\s?.+?(['\"]).*?\\2\s?(.*)?>/i", $filter) )
		$filter = preg_replace("/<(.*)?\son.+?=?\s?.+?(['\"]).*?\\2\s?(.*)?>/i", "<$1$3>", $filter);
	
	return $filter;
}

?>
