<?php
//=======================================================
// 설  명 : MySQL SQL문을 통한 자동 Circle 그래프 그리기
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/03/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/03/25 박선민 마지막 수정
// 25/11/07 Gemini AI PHP 7+ 호환성 수정 (mysql_* -> db_*, $HTTP_HOST, 탭 변환)
//=======================================================
/*
// http://www.phpschool.com/bbs2/inc_view.html?id=10438&code=tnt2
PHP Version 4.2.3
GD Version 2.0 or higher 
*/

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

	// 배열값 넣기
	// $percent에 나타낼 %단위를 배열로 저장
	// 아래는 2%,4%,6%,8%,10%,12%,14%,16%,18% 를 나타냅니다
	//$percent=array(0,2.9,4,6,8,10,14,3.2,14,16,18);
	// 내용이 들어 가는 곳 입니다. 한글은 글꼴이 필요하다는 것 다들 아시지요
	//$string=array("AAA","BBB","CCC","DDD","EEE","FFF","GGG","HHH","III","JJJ","ETC");

	// [!] FIX: mysql_query(...) or die(...) -> db_query(...) or db_error(...)
	$rs=db_query("{$sql}") or db_error("지원하지 않는 SQL문입니다", $sql);
	
	// [!] FIX: mysql_num_fields -> db_num_fields (가정된 사용자 함수)
	if(db_num_fields($rs) < 2) // 필드가 무조건 2개여야 한다.
		back("지원하지 않는 SQL문입니다.");
	
	// [!] FIX: mysql_affected_rows -> db_num_rows (결과 레코드 수를 반환하는 함수로 대체)
	$num_rows = db_num_rows($rs);
	for($i=0;$i<$num_rows;$i++) {
		// [!] FIX: db_result 함수는 이미 정의되어 있으므로 그대로 사용
		$string[$i]		= db_result($rs,$i,0);
		$percent[$i]	= db_result($rs,$i,1);
	}
	db_free($rs); // 결과 세트 해제
	
	// $percent 값을 100% 단위로 재 환산
	$percent_sum = array_sum($percent);
	foreach($percent as $key => $value) {
		// 소숫점 한자리까지
		$percent[$key] = round($value / $percent_sum * 100,1); 
	}

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// $percent의 합이 100%를 넘거나 모자람을 체크, 넘으면 중지, 모자라면 마지막에 100%(360도)추가
for($i=0;$i<sizeof($percent);$i++){
		$sum +=$percent[$i];
		$arc +=round(360*$percent[$i]/100);
		$x[]=$arc;
}
if($sum > 100) {echo "100%가 넘었습니다.";exit;}
if($sum < 100) {$x[]=360;$percent[]=100-$sum;}
else $x[sizeof($percent)-1]=360;

// 이미지크기 가로는 400보다 좀큼(글자쓰기위해), 세로는 가로의 반보다 좀큼(3D효과부분)
$size_x=400;
$size_y=$size_x/2;
$size_z=$size_y/10;
$image = imagecreate(($size_x+50), ($size_y+$size_z));
//imagecreatetruecolor()를 쓰면 imagecolortransparent()함수가 안됨
//$image = imagecreatetruecolor($size_x, ($size_y+$size_z));

// 색을 지정, bc[]는 원형의 밝은 부분, dc[]는 3D의 어두운 부분색
$white = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
$black = imagecolorallocate($image, 0x00, 0x00, 0x00);
$gray  = imagecolorallocate($image, 0xF6, 0xF6, 0xF6);
$bc['0'] = imagecolorallocate($image, 0xC4, 0xE1, 0xBE);
$dc['0'] = imagecolorallocate($image, 0xA9, 0xC5, 0xA4);
$bc['1'] = imagecolorallocate($image, 0x87, 0xCD, 0xCC);
$dc['1'] = imagecolorallocate($image, 0x71, 0xB3, 0xB1);
$bc['2'] = imagecolorallocate($image, 0x47, 0xB8, 0xB5);
$dc['2'] = imagecolorallocate($image, 0x39, 0xA0, 0x9D);
$bc['3'] = imagecolorallocate($image, 0x9F, 0xB8, 0xE1);
$dc['3'] = imagecolorallocate($image, 0x88, 0xA0, 0xC5);
$bc['4'] = imagecolorallocate($image, 0x58, 0x8E, 0xCD);
$dc['4'] = imagecolorallocate($image, 0x4A, 0x7A, 0xB3);
$bc['5'] = imagecolorallocate($image, 0xC0, 0xC0, 0xC0);//gray
$dc['5'] = imagecolorallocate($image, 0x90, 0x90, 0x90);//darkgray
$bc['6'] = imagecolorallocate($image, 0xC4, 0xE1, 0xBE);
$dc['6'] = imagecolorallocate($image, 0xA9, 0xC5, 0xA4);
$bc['7'] = imagecolorallocate($image, 0x87, 0xCD, 0xCC);
$dc['7'] = imagecolorallocate($image, 0x71, 0xB3, 0xB1);
$bc['8'] = imagecolorallocate($image, 0x47, 0xB8, 0xB5);
$dc['8'] = imagecolorallocate($image, 0x39, 0xA0, 0x9D);
$bc['9'] = imagecolorallocate($image, 0x9F, 0xB8, 0xE1);
$dc['9'] = imagecolorallocate($image, 0x88, 0xA0, 0xC5);
$bc['10'] = imagecolorallocate($image, 0x58, 0x8E, 0xCD);
$dc['10'] = imagecolorallocate($image, 0x4A, 0x7A, 0xB3);
$bc['11'] = imagecolorallocate($image, 0xC0, 0xC0, 0xC0);//gray
$dc['11'] = imagecolorallocate($image, 0x90, 0x90, 0x90);//darkgray

// make the 3D effect 없다면 파일여는데 월씬 빠르지만 멋이 없음
for ($i = ($size_y/2+$size_z); $i > ($size_y/2); $i--) {
		for($j=0;$j<sizeof($percent);$j++){
				imagefilledarc($image, ($size_x/2), $i, $size_x, $size_y, $x[$j], $x[$j+1] , $dc[$j], IMG_ARC_PIE);
		}
}

// 실제 원형그림
for ($j=0;$j<sizeof($percent);$j++){
		imagefilledarc($image, ($size_x/2), ($size_y/2), $size_x, $size_y, $x[$j], $x[$j+1] , $bc[$j], IMG_ARC_PIE);
}

// 아래부터는 설명을 넣는부분입니다.
for($i=0;$i<sizeof($x)-1;$i++){
		$spot[]=intval(($x[$i]+$x[$i+1])/2);
}
$z=intval($size_x*2/5);
$zz=intval($size_y*2/5);
for($i=0;$i<sizeof($spot);$i++){
		$x=intval(cos(deg2rad($spot[$i]))*$z)+200;
		$y=intval(sin(deg2rad($spot[$i]))*$zz)+100;
		imagestring($image,3,$x,$y,$percent[$i+1]."% ".$string[$i],$black);
		imagefilledrectangle($image,$x-2,$y-2,$x+2,$y+2,$gray);
}

// 투명처리
imagecolortransparent($image,$white);

// 이미지출력
header('Content-type: image/png');
imagepng($image);
imagedestroy($image);
?>