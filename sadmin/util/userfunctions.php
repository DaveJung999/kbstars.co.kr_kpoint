<?php
// User Function 모음.

// function is_image($file) - 02/10/23

/**
* 이미지 여부와 mime-type 구하기
*
* @param string $file	파일 이름
* @return string		이미지 파일일 경우 mime-type리턴하고,
*						이미 파일이 아닌 경우 false 리턴함
* 
* ChangeLog
*	DATE	수정인				수정 내용
* -------- ------ --------------------------------------
* 02/07/19 박선민 마지막 수정
*/
function is_image($file){
	if(!$fp = @fopen($file, "r")) return false;

	$fcont = fread($fp, 15);
	fclose($fp);

	if(strstr($fcont,"PNG"))
		$imagetype = "image/png";
	elseif(strstr($fcont,"GIF"))
		$imagetype = "image/gif";
	elseif(strstr($fcont,"JFIF"))
		$imagetype = "image/pjpeg";
	elseif(strstr($fcont,"BM"))
		$imagetype = "image/bmp";

	if(isset($imagetype))
		return $imagetype;
	else
		return false;
} // end func is_image
?>