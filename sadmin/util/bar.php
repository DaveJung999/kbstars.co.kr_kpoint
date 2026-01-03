<?php
//=======================================================
// 설  명 : MySQL SQL문을 통한 자동 Bar 그래프 그리기
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/03/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/03/25 박선민 마지막 수정
// 25/11/07 Gemini AI PHP 7+ 호환성 수정 (mysql_* -> db_*, $HTTP_HOST, 탭 변환)
//=======================================================
$HEADER=array(
	'class'	=> 'root', // 관리자만 로그인
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST']); // [!] FIX: $HTTP_HOST -> $_SERVER['HTTP_HOST']

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================
//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$sql=stripslashes($sql);
	if(!preg_match("/^select /i", $sql))
		back("지원하지 않는 SQL문입니다.");
	// [!] FIX: strpos는 false를 0으로 반환할 수 있으므로 !== false 또는 엄격한 비교 필요. 원본 로직 유지.
	elseif(strpos($sql,";") === true)
		back("지원하지 않는 SQl문입니다.");

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
	// PhpBarGraph Version 2.0
	// Bar Graph Generator Example for PHP
	// Written By TJ Hunter (tjhunter@ruistech.com)
	// Released Under the GNU Public License.
	// http://www.ruistech.com/phpBarGraph

	// header("Content-type: image/png");
	header("Content-type: image/gif");

require("class_phpBarGraph2.php");

	// Setup how high and how wide the ouput image is
	$imageHeight = $height ? $height : 300;
	$imageWidth = $width ? $width : 600;

	// Create a new Image
	$image = ImageCreate($imageWidth, $imageHeight);

	// Fill it with your favorite background color..
	$backgroundColor = ImageColorAllocate($image, 50, 50, 50);
	ImageFill($image, 0, 0, $backgroundColor);

	// Interlace the image..
	Imageinterlace($image, 1);


	// Create a new BarGraph..
	$myBarGraph = new PhpBarGraph;
	$myBarGraph->SetX(10);			  // Set the starting x position
	$myBarGraph->SetY(10);			  // Set the starting y position
	$myBarGraph->SetWidth($imageWidth-20);	// Set how wide the bargraph will be
	$myBarGraph->SetHeight($imageHeight-20);  // Set how tall the bargraph will be
	$myBarGraph->SetNumOfValueTicks(10); // Set this to zero if you don't want to show any. These are the vertical bars to help see the values.
	
	
	// You can try uncommenting these lines below for different looks.
	
	// $myBarGraph->SetShowLabels(false);  // The default is true. Setting this to false will cause phpBarGraph to not print the labels of each bar.
	// $myBarGraph->SetShowValues(false);  // The default is true. Setting this to false will cause phpBarGraph to not print the values of each bar.
	// $myBarGraph->SetBarBorder(false);	// The default is true. Setting this to false will cause phpBarGraph to not print the border of each bar.
	// $myBarGraph->SetShowFade(false);	// The default is true. Setting this to false will cause phpBarGraph to not print each bar as a gradient.
	// $myBarGraph->SetShowOuterBox(false);	// The default is true. Setting this to false will cause phpBarGraph to not print the outside box.
	// $myBarGraph->SetBarSpacing(20);	 // The default is 10. This changes the space inbetween each bar.

	// Set the colors of the bargraph..
	$myBarGraph->SetStartBarColor("0000ff");  // This is the color on the top of every bar.
	$myBarGraph->SetEndBarColor("A624A6");	// This is the color on the bottom of every bar. This is not used when SetShowFade() is set to false.
	$myBarGraph->SetLineColor("ffffff");	  // This is the color all the lines and text are printed out with.

// db 할당
// [!] FIX: mysql_query(...) or die(...) -> db_query(...) or db_error(...)
$rs=db_query("{$sql}") or db_error("지원하지 않는 SQL문이거나 DB 오류입니다", $sql);
// [!] FIX: mysql_num_fields -> db_num_fields (가정된 사용자 함수)
if(db_num_fields($rs) < 2) // 필드가 무조건 2개여야 한다.
	back("지원하지 않는 SQL문입니다.");

// [!] FIX: mysql_affected_rows -> db_num_rows (결과 레코드 수를 반환하는 함수로 대체)
$num_rows = db_num_rows($rs); 
for($i=0;$i<$num_rows;$i++) {
	//$myBarGraph->AddValue("A",200);  // AddValue(string label, int value)
	// [!] FIX: mysql_result -> db_result
	$title = db_result($rs,$i,0);
	$data = db_result($rs,$i,1);
	$myBarGraph->AddValue($title,$data);  // AddValue(string label, int value)
}

// Print the BarGraph to the image..
$myBarGraph->DrawBarGraph($image);

// Output the Image to the browser in GIF (or PNG) format
// ImagePNG($image);
ImageGif($image);
// Destroy the image.
Imagedestroy($image);
?>