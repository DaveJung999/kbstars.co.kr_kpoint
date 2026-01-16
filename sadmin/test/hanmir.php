<?php
set_time_limit(0);
//=======================================================
// 설  명 : 한미르 검색결과 필터링(hanmir.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/10/31
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02/10/31 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?php echo  → <?php echo 변환, eregi_replace() → preg_replace() 변환
//=======================================================

switch($mode) {
	case "directory" :
		if(preg_match("/^http:///i", $url)) {
			$getHTML=file($url);
		}
		else echo "URL이 아님니다. http://로 시작바랍니다.<br>";

		break;
	case "search" :
		$getHTMLS[]=array();
		if(preg_match("/^http:///i", $url)) {
			$tmp_prs_url=parse_url($url);
			parse_str($tmp_prs_url['query'],$tmp_query);
			if($tmp_query['end']) {
				if(!$tmp_query['DL']) $tmp_query['DL']=10;
				for($i=(int)$tmp_query['BA'];$i<(int)$tmp_query['end'];$i+=$tmp_query['DL']) {
					echo " $i ";
					if(is_array($tmp_getHTML=file($url."&BA=$i&DL={$tmp_query['DL']}"))) {
						$getHTMLS[]=$tmp_getHTML;
					}
				}
			}
			else {
				$getHTMLS[]=file($url);
			}
		}
		else echo "URL이 아님니다. http://로 시작바랍니다.<br>";
		break;			
} // end switch
?>
<form method=post action=<?php echo $PHP_SELF ?>>
 <input type="radio" name="mode" value="directory" <?phpif($mode=="" or $mode=="directory") echo "checked" ?>>
  드렉토리서치
<input type="radio" name="mode" value="search" <?phpif($mode=="search") echo "checked" ?>>전화번호서치결과(&BA=0&DL=100&end=1000)
<br>
<br>
<textarea name="url" rows="10" cols="60"><?php echo $url ? htmlspecialchars($url,ENT_QUOTES) : "http://dir.hanmir.com/비즈니스,경제/업종별_회사/광고,마케팅/광고/판촉물,기념품/index.html" ?></textarea>
<input type=submit value="GO->">
</form>
<hr>
<?php
// geturl인 경우 밑에다 출력
if($mode=="directory" and $getHTML) {
	//echo "<pre>".htmlspecialchars(implode("",$getHTML),ENT_QUOTES)."</pre><br>";

	$getData=array();
	$tmp_startTag=0;
	$tmp_data="";
	$count_getHTML=count($getHTML);
	for($i;$i<$count_getHTML;$i++) {
		if($tmp_startTag) {
			//<!-- hanmir_tag_end -->
			if( preg_match("/<!-- hanmir_tag_end -->/i", $getHTML[$i]) ) {
				$getData[]		= $tmp_data;
				$tmp_startTag	= 0;
			}
			else {
				$tmp_data.=$getHTML[$i];
			}
		}
		else{
			//<!-- hanmir_tag_start0hanmir_tag_start가가네닷컴hanmir_tag_start -->
			if( preg_match("/hanmir_tag_start/i", $getHTML[$i]) ) {
				$tmp_data		= $getHTML[$i];
				$tmp_startTag	= 1;
			}
		} // end if.. else..
	} // end for
	//print_r($getData);

	$count_getData=count($getData);
	for($i=0;$i<$count_getData;$i++){
		// 타이틀
		$tmp_data	= substr($getData[$i],strpos($getData[$i],"<b>"));
		$rs_data[$i][title]	= trim(substr($tmp_data,3,strpos($tmp_data,"</b>")-3));

		// 설명
		$tmp_data	= substr($tmp_data,strpos($tmp_data,"</b>")+10);
		$rs_data[$i][content]= substr($tmp_data,0,strpos($tmp_data,"</td>"));
		$rs_data[$i][content]= trim(preg_replace('/<\/small>/i','',$rs_data[$i][content]));

		// 전화번호, 주소
		if( $tmp_count_small=strpos($tmp_data,"<small>") ) {
			// 전화번호
			$tmp_data	= substr($tmp_data,$tmp_count_small + 7);
			$rs_data[$i][tel]= trim(substr($tmp_data,0,strpos($tmp_data,"&nbsp;")));

			// 주소
			$tmp_data	= substr($tmp_data,strpos($tmp_data,"#000000")+9);
			$rs_data[$i][address]= trim(substr($tmp_data,0,strpos($tmp_data,"</font>")));
		}
	} // end for
	//print_r($rs_data);

	// 테이블로 보이고
	echo ("
		<table border=1>
		<tr>
			<td>no</td>
			<td>title</td>
			<td>tel</td>
			<td>address</td>
			<td>content</td>
		</tr>
	");
	$i=0;
	if(is_array($rs_data)) {
		foreach($rs_data as $value) {
			if(trim($value['title'])<>"") {
				$i++;
				echo ("
					<tr>
						<td nowrap>$i</td>
						<td nowrap>$value['title']</td>
						<td nowrap>$value['tel']</td>
						<td nowrap>$value['address']</td>
						<td nowrap>$value['content']</td>
					</tr>
				");
			}
		} // end foreach
	} // end if
	echo "</table>";

} // end if(hanmir)
elseif( $mode=="search" and is_array($getHTMLS) ) {
	//echo "<pre>".htmlspecialchars(implode("",$getHTML),ENT_QUOTES)."</pre><br>";

	$getData=array();
	
	foreach($getHTMLS as $getHTML) {
		$tmp_start=0;
		$tmp_startTag=0;
		$tmp_data="";
		$count_getHTML=count($getHTML);
		for($i;$i<$count_getHTML;$i++) {
			if($tmp_start) {
				if($tmp_startTag) {
					//패턴끝 : </table>
					if( preg_match("/</table/i", $getHTML[$i]) ) {
						$tmp_data		.=$getHTML[$i];
						$getData[]		= $tmp_data;
						$tmp_startTag	= 0;
					}
					else {
						$tmp_data.=$getHTML[$i];
					}
				}
				else{
					//패턴시작 : <table...
					if( preg_match("/<table/i", $getHTML[$i]) ) {
						$tmp_data		= $getHTML[$i];
						$tmp_startTag	= 1;
					}
					// 전체 끝 : <!--검색 결과 끝-->
					elseif( preg_match("/검색 결과 끝/i", $getHTML[$i]) ) {
					break; // 끝냄
				}
				} // end if.. else..
			}
			else {
				// 전체 시작: <!--결과 갯수 보여주기 끝-->
				if( preg_match("/결과 갯수 보여주기 끝/i", $getHTML[$i]) ) 
					$tmp_start	= 1;
			} // if.. else..
		} // end for
		//echo "<pre>";print_r($getData);echo "</pre>";


		$count_getData=count($getData);
		for($i=0;$i<$count_getData;$i++){
			// 타이틀
			$tmp_data	= substr($getData[$i],strpos($getData[$i],"#0000FF")+9);
			$rs_data[$i][title]	= trim(substr($tmp_data,0,strpos($tmp_data,"(<a href")));
			
			// 전화번호
			$tmp_data	= substr($tmp_data,strpos($tmp_data,"search_directory001.gif"));
			$tmp_data	= substr($tmp_data,strpos($tmp_data,"<small>")+7);

			$rs_data[$i][tel]= trim(strip_tags(substr($tmp_data,0,strpos($tmp_data,"&nbsp;"))));

			// 주소
			$tmp_data	= substr($tmp_data,strpos($tmp_data,"&nbsp;")+6);
			$rs_data[$i][address]= trim(substr($tmp_data,0,strpos($tmp_data,"</small>")));
		} // end for
		//echo "<pre>";print_r($rs_data);echo "</pre>";
	} // end foreach

	// 테이블로 보이고
	echo ("
		<table border=1>
		<tr>
			<td>no</td>
			<td>title</td>
			<td>tel</td>
			<td>address</td>
		</tr>
	");
	$i=0;
	if(is_array($rs_data)) {
		foreach($rs_data as $value) {
			if(trim($value['title'])<>"") {
				$i++;
				echo ("
					<tr>
						<td nowrap>$i</td>
						<td nowrap>$value['title']</td>
						<td nowrap>$value['tel']</td>
						<td nowrap>$value['address']</td>
					</tr>
				");
			}
		} // end foreach
	} // end if
	echo "</table>";

} // end if(search)
?>