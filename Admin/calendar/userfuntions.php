
<?php
// 03/09/16 
function userLastDay($datMonth,$datYear){
	if ($datMonth == 4 || $datMonth == 6 || $datMonth == 9 || $datMonth == 11 ) { //4월 6월 9월 11월이면 월말값은 30일
		$datLastDay=30;
	} elseif ($datMonth == 2 && 	($datYear%4) != 0 ) { //2월이고	년도를 4로 나눈 값이 0이 아니면 28일
		$datLastDay=28;
	} elseif ($datMonth == 2 && ($datYear%4) == 0 ) {	//윤달 계산
		if (($datYear%100) == 0 ){
			if (($datYear%400) == 0 ) $datLastDay=29;
			else $datLastDay=28;
		}
		else $datLastDay=29;
	}
	else $datLastDay=31;

	return $datLastDay;
}
// 두날짜간 얼마의 차이가 있는지 체크
// 03/09/16
function userDateDiff($arg_format, $arg_date1, $arg_date2){
	$arg_date1	=	strtotime($arg_date1);
	$arg_date2	=	strtotime($arg_date2);

	//$arg_date1	=	mktime(@date('H', $arg_date1),@date('i', $arg_date1),@date('s', $arg_date1),@date('m', $arg_date1),@date('d', $arg_date1),@date('Y', $arg_date1));
	//$arg_date2	=	mktime(@date('H', $arg_date2),@date('i', $arg_date2),@date('s', $arg_date2),@date('m', $arg_date2),@date('d', $arg_date2),@date('Y', $arg_date2));

	$arg_date	=	$arg_date2 - $arg_date1;

	if ($arg_format == 'd' || $arg_format == 'j')
		$str = intval($arg_date/86400) + 1;
	elseif ($arg_format == 'm' || $arg_format == 'n')
		$str = intval(($arg_date/86400)/30) + 1;
	elseif ($arg_format == 'Y')
		$str = intval(($arg_date/86400)/365) + 1;
	elseif ($arg_format == 's')
		$str = intval($arg_date);

	return $str;
} 

?>
