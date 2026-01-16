<?php
//=======================================================
// 설  명 : 폼에 관한 각종 변환값(href.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/06/16 
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02/11/26 박선민 마지막 수정
// 02/12/25 박선민 마지막 수정
// 03/06/16 박선민 serialize 추가
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?php echo  → <?php echo 변환, eregi_replace() → preg_replace() 변환
//=======================================================

switch($mode) {
	case "gotourl" :
		header("Location: $url");
		break;
	case "geturl" :
		if(preg_match("/^http:///i", $url)) {
			$getHTML=file($url);
		}
		else echo "URL이 아님니다. http://로 시작바랍니다.<br>";

		break;	
	case "unserialize" :
		$url = stripslashes(trim($url));

		echo "<pre>";
		print_r(unserialize($url));
		echo "</pre>";

		break;	
	case "analyzeURL" :
		if(preg_match("/^http:///i", $url)) {
			$urlinfo=parse_url($url);
			if(!$urlinfo['path']) $urlinfo['path'] = "/";
			$getHTML=file($url);
			$getHTML=implode("",$getHTML);
			
			include("class_html_info.php");

			$info=new html_info($getHTML);
			echo "<pre>";
			echo "<br><b>tag: get_title</b><br>" . $info->get_title();
			$strings=$info->get_strings_headed(1,3);
			for($i=0;$i<count($strings);$i++){
				echo $strings[$i]."<br>\n";
			}
			/*
			echo "<br><b>tag: get_meta_data</b><br>  " . print_r($info->get_meta_data());
			echo "<br><b>tag: get_images</b><br>  " . print_r($info->get_images());
			echo "<br><b>tag: get_links</b><br>  " . print_r($info->get_links());
			*/

			echo "<br><b>tag: get_meta_data</b><br>";
			$strings=$info->get_meta_data();
			for($i=0;$i<count($strings);$i++){
				print_r($strings[$i]);
			}
			echo "<br><b>tag: get_images</b><br>";
			$strings=$info->get_images();
			for($i=0;$i<count($strings);$i++){
				if(preg_match("/^http/i", $strings[$i][src]))
					echo "<img src='{$strings[$i][src]}'>{$strings[$i][src]}<br>\n";
				elseif(preg_match("/^//i", $strings[$i][src]))
					echo "<img src='{$urlinfo['scheme']}://{$urlinfo['host']}{$strings[$i][src]}'>{$strings[$i][src]}<br>\n";
				else
					echo "<img src='{$urlinfo['scheme']}://{$urlinfo['host']}{$urlinfo['path']}{$strings[$i][src]}'>{$strings[$i][src]}<br>\n";
			}
			
			echo "<br><b>tag: get_links</b><br>";
			$strings=$info->get_links();
			for($i=0;$i<count($strings);$i++){
				if(preg_match("/^http/i", $strings[$i][href]))
					echo "<a href='{$strings[$i][href]}' target=_blank>{$strings[$i][href]}</a><br>\n";
				elseif(preg_match("/^//i", $strings[$i][href]))
					echo "<a href='{$urlinfo['scheme']}://{$urlinfo['host']}{$strings[$i][href]}' target=_blank>{$strings[$i][href]}</a><br>\n";
				else
					echo "<a href='{$urlinfo['scheme']}://{$urlinfo['host']}{$urlinfo['path']}{$strings[$i][href]}' target=_blank>{$strings[$i][href]}</a><br>\n";

				echo "<a href='http://{$_SERVER['HTTP_HOST']}/{$strings[$i][href]}' target=_blank>{$strings[$i][href]}</a><br>\n";
			}
			echo "</pre>";
		}
		else echo "URL이 아님니다. http://로 시작바랍니다.<br>";

		break;	
	case "domainwhois":
		$url=trim($url);
		include_once($_SERVER['DOCUMENT_ROOT'] . "/sinc/class_domainwhois.php");

		$domain=new domain($url);

		// Printing out whois data
		echo "<pre>".$domain->info()."</pre><br>";
		//echo $domain->html_info()."<br><br>";


		echo "Whois Server: ".$domain->get_whois_server()."<br>";
		echo "Domain: ".$domain->get_domain()."<br>";
		echo "Tld: ".$domain->get_tld()."<br>";

		// Checking if domain name is valid
		if($domain->is_valid()){
			echo "Domain name is valid!<br>";
		}
		else{
			echo "Domain name isn't valid!<br>";
		}
		// Checking if domain is available
		if($domain->is_available()){
			echo "Domain is available<br>";
		}
		else{
			echo "Domain is not Available<br>";
		}


		// Getting all suppoerted TLD's
		/*
		$tlds=$domain->get_tlds();
		for($i=0;$i<count($tlds);$i++){
			echo $tlds[$i]."<br>";
		}
		*/

		break;
	case "emailcheck" :
		$url=trim($url);
		header("Location: ./emailcheck.php?email=$url");
		break;
	case "normal" :
		echo "\{$_GET['url']} => {$_GET['url']}<br>";
		echo "\{$_POST['url']} => {$_POST['url']}<br>";
		echo "\{$_REQUEST['url']} => {$_REQUEST['url']}<br>";
		echo "\$url	=> $url<br><br>";

		$a="\"한국\"의 영명은 'korea'이다.";
		echo "\$a=$a <br><br>";
		
		echo "<b>Post 넘어온값 갖종 변환값</b><br><br>";
		echo "stripslashes하기전<br>";
		echo "----------------------------<br>";
		echo "<br><b>Nomal</b>:<br>" . $url . "<br>";
		echo "<br><b>addslashes</b>:<br>" . addslashes($url) . "<br>";
		echo "<br><b>stripslashes</b>:<br>" . stripslashes($url) . "<br>";

		$url=stripslashes($url);
		echo "<br><br>stripslashes 적용후<br>";
		echo "----------------------------<br>";
		echo "<br><b>Nomal</b>:<br>" . $url . "<br>";
		echo "<br><b>addslashes</b>:<br>" . addslashes($url) . "<br>";
		echo "<br><b>stripslashes</b>:<br>" . stripslashes($url) . "<br>";
		echo "<br><b>urldecode</b>:<br>" . urldecode($url) . "<br>";
		echo "<br><b>urlencode</b>:<br>" . urlencode($url) . "<br>";
		echo "<br><b>rawurlencode</b>:<br>" . rawurlencode($url) . "<br>";
		echo "<br><b>base64_encode</b>:<br><pre>" . base64_encode($url) . "</pre><br>";
		echo "<br><b>base64_decode</b>:<br><pre>" . htmlspecialchars(base64_decode($url),ENT_QUOTES) . "</pre><br>";
		echo "<br><b>quoted-printable Decode</b>:<br><pre>" . htmlspecialchars(quoted_printable_decode($url),ENT_QUOTES) . "</pre><br>";
		echo "<br><b>eregi_replace(해당단어를 #으로)</b>:<br>" . preg_replace('/' . preg_quote($url, '/') . '/i',"#",$a) . "<br>";
		break;
} // end switch
?>
<form method=post action=<?php echo $PHP_SELF ?>>
<input type=hidden name=mode value=ok>
<input type="radio" name="mode" value="gotourl" <?phpif(!$mode || $mode=="gotourl") echo "checked" ?>>
  Go URL
  <input type="radio" name="mode" value="geturl" <?phpif($mode=="geturl") echo "checked" ?>>
  Get URL 
  <input type="radio" name="mode" value="analyzeURL" <?phpif($mode=="analyzeURL") echo "checked" ?>>Analyze SITE
  <input type="radio" name="mode" value="normal" <?phpif($mode=="normal") echo "checked" ?>>Encode/Decode
<br>
<input type="radio" name="mode" value="domainwhois" <?phpif($mode=="domainwhois") echo "checked" ?>>Domain Whois
<input type="radio" name="mode" value="emailcheck" <?phpif($mode=="emailcheck") echo "checked" ?>>Email Check
<input type="radio" name="mode" value="unserialize" <?phpif($mode=="unserialize") echo "checked" ?>>unserialize
<br>
<br>
<textarea name="url" rows="10" cols="60"><?php echo $url ? htmlspecialchars($url,ENT_QUOTES) : "phpinfo.php?name=kim&age=10" ?></textarea>
<input type=submit value="GO->">
</form>
<hr>
<?php
// geturl인 경우 밑에다 출력
if($mode=="geturl" and $getHTML) {
	echo "<a href='{$url}' target='_blank'>$url</a>(<a href='view-source:$url' target='_blank'>Source View</a>)<br>";
	echo "<pre>".htmlspecialchars(implode("",$getHTML),ENT_QUOTES)."</pre><br>";
	echo "<hr><b>html view</b><hr>";
	echo "<iframe src='{$url}' marginwidth='0' height='400' width='600' marginheight='0' scrolling='auto' frameborder='0'></iframe>";
}
?>