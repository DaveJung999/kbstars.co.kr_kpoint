<?php
//=======================================================
// 설 명 : thumbnail.lib.php
// 책임자 : 박선민 (sponsor@new21.com)
// Project: sitePHPbasic
// ChangeLog
//	 DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02.02.18 거친마루 처음
// 03.11.03 박선민 추가 수정
// 04.03.10 박선민 추가 수정
// 24.05.18 Gemini	PHP 7 마이그레이션
//=======================================================
/*
사용법은 thumbnail(파일명,x사이즈,y사이즈) 입니다.
*/
/****************************************************************************
* thumbnail.lib.php
* 가로세로 비율이 흐트러지지 않고 지정한 크기로 썸네일을 만들어줌
* 2002.2.18 - 거친마루
* 03/11/03 박선민 추가 수정
* 04/03/10 박선민 추가 수정
*****************************************************************************/

## Image LoadImage (String $fName);
function LoadImage ($fName){
	$file_ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION)); //확장자
	switch ($file_ext){
		case "jpg":
		case "jpeg":
			$im = @ImageCreateFromJPEG($fName);
			break;
		case "gif":
			$im = @ImageCreateFromGIF($fName);
			break;
		case "png":
			$im = @ImageCreateFromPNG($fName);
			break;
		default:
			$im = false;
	}

	if (!$im){
		$im = ImageCreate(150, 30);
		$bgc = ImageColorAllocate($im, 255, 255, 255); // 하얀색
		$tc	= ImageColorAllocate($im, 0, 0, 0);
		ImageFilledRectangle($im, 0, 0, 150, 30, $bgc);
		ImageString($im, 1, 5, 5, "Error loading", $tc);
	}
	return $im;
}

## Image thumbnail_jpg(String $filepath, int $width, int $height);
function thumbnail($filepath,$width="",$height=""){
	if(!function_exists("imagecreatetruecolor")) return false;
	
	if(!$dst_im=@imagecreatetruecolor($width,$height))
		return false;

	$background_color = ImageColorAllocate($dst_im, 255,255,255);
	ImageFilledRectangle($dst_im, 0, 0, $width, $height, $background_color);
	ImageColorTransparent($dst_im, $background_color);

	if($size=@getimagesize($filepath)) { //원본 이미지사이즈를 구함
		$src_im=LoadImage($filepath);
		if($src_im === false){
			// 이미지 로드 실패 시 에러 이미지 반환
			return $dst_im;
		}
		
		$src_width = imagesx($src_im);
		$src_height = imagesy($src_im);

		$dst_ratio = $width / $height;
		$src_ratio = $src_width / $src_height;

		if($src_ratio > $dst_ratio) { // 원본이 더 가로가 긴 경우
			$new_width = $height * $src_ratio;
			$new_height = $height;
			$offsetX = ($width - $new_width) / 2;
			$offsetY = 0;
		} else { // 원본이 더 세로가 긴 경우
			$new_width = $width;
			$new_height = $width / $src_ratio;
			$offsetX = 0;
			$offsetY = ($height - $new_height) / 2;
		}
		
		imagecopyresampled($dst_im,$src_im,$offsetX,$offsetY,0,0,$new_width,$new_height,$src_width,$src_height);
	} else { // 이미지가 없다면
		$text_width = imagefontwidth(10) * strlen("No Image");
		$text_x = ($width - $text_width) / 2;
		$text_y = ($height - imagefontheight(10)) / 2;
		
		$text_color = ImageColorAllocate ($dst_im, 233, 14, 91);
		ImageString($dst_im, 10, $text_x, $text_y, "No Image", $text_color);
	}

	@imagedestroy($src_im);
	return $dst_im;
}

## Create by Sunmin park
// tagthumbnail(상대파일이름, 가로, 세로)
// return <img src="dir/filename.gif" width=?? height=?? >!!
function tagthumbnail($path,$filename,$width="",$height=""){
	global {$_SERVER['DOCUMENT_ROOT']};
	
	$new_width = $width;
	$new_height = $height;

	$path=rtrim($path, "/");
	$filepath = $path . "/" . $filename;

	// eregi()를 preg_match()로 변경
	if(!preg_match("/^(http:\/\/|https:\/\/|ftp:\/\/|telnet:\/\/|news:\/\/)/i", $filename)){
		$filepath = realpath($filepath);

		if(!$size=@getimagesize($filepath)) //원본 이미지사이즈를 구함
			return false;
		
		if ($size['0'] > $width || $size['1'] > $height){
			$shr_rateX = $width / $size['0'];
			$shr_rateY = $height / $size['1'];
			$base = ($shr_rateX <= $shr_rateY) ? "y" : "x";
			
			if($base == "y"){
				$new_width = round(($size['0'] * $height) / $size['1']);
				$new_height = $height;
			} else {
				$new_width = $width;
				$new_height = round(($size['1'] * $width) / $size['0']);
			}
		}

		// eregi_replace()를 str_replace()로 변경
		$webfilepath = pathinfo(str_replace($_SERVER['DOCUMENT_ROOT'], "", $filepath));
		$webfilepath = $webfilepath['dirname'] . "/" . urlencode($webfilepath['basename']);
	} else { // 파일이름이 웹링크라면
		$webfilepath=$filename;
		
		$tmp=file($webfilepath . "&mode=imagesize");
		// eregi()를 preg_match()로 변경
		if(isset($tmp['0']) && preg_match("/^[0-9]+x[0-9]+$/i",$tmp['0'])) { // 200x300 으로 이미지 사이즈 리턴이 있다면..
			$size=explode("x",$tmp['0']);

			$shr_rateX = $width / $size['0'];
			$shr_rateY = $height / $size['1'];
			$base = ($shr_rateX <= $shr_rateY) ? "y" : "x";
			if($base == "y"){
				$new_width=$width;
				$new_height=round(($size['1'] * $width)/$size['0']);
			}
			if($base == "x"){
				$new_width=round(($size['0'] * $height)/$size['1']);
				$new_height=$height;
			} // end if
		} else {
			$new_width=$width;
			$new_height=$height;
		}
	} // end if.. else ..
	return "<img src=\"{$webfilepath}\" width=$new_width height=$new_height border=0>";
} // end func.
?>
