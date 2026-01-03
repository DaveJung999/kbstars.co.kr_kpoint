<?
	function str_date_diff($arg_format, $arg_date1, $arg_date2)
	{
		$arg_date1  =  strtotime($arg_date1);
		$arg_date2  =  strtotime($arg_date2);

		$arg_date1  =  mktime(@date('H', $arg_date1),@date('i', $arg_date1),@date('s', $arg_date1),@date('m', $arg_date1),@date('d', $arg_date1),@date('Y', $arg_date1));

		$arg_date2  =  mktime(@date('H', $arg_date2),@date('i', $arg_date2),@date('s', $arg_date2),@date('m', $arg_date2),@date('d', $arg_date2),@date('Y', $arg_date2));

		$arg_date  =  $arg_date2 - $arg_date1;

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
	
	function year($arg_date)
	{

		$arg_date = strtotime($arg_date);
		$arg_date  =  mktime(@date('H', $arg_date),@date('i', $arg_date),@date('s', $arg_date),@date('m', $arg_date),@date('d', $arg_date),@date('Y', $arg_date));

		$str = date('Y', $arg_date);

		return $str;
	}
	
	function month($arg_date)
	{

		$arg_date = strtotime($arg_date);
		$arg_date  =  mktime(@date('H', $arg_date),@date('i', $arg_date),@date('s', $arg_date),@date('m', $arg_date),@date('d', $arg_date),@date('Y', $arg_date));

		$str = date('n', $arg_date);

		return $str;
	}
	
	function day($arg_date)
	{

		$arg_date = strtotime($arg_date);
		$arg_date  =  mktime(@date('H', $arg_date),@date('i', $arg_date),@date('s', $arg_date),@date('m', $arg_date),@date('d', $arg_date),@date('Y', $arg_date));

		$str = date('j', $arg_date);

		return $str;
	}
	
	function Weekday($arg_date)
	{

		$arg_date = strtotime($arg_date);
		$arg_date  =  mktime(@date('H', $arg_date),@date('i', $arg_date),@date('s', $arg_date),@date('m', $arg_date),@date('d', $arg_date),@date('Y', $arg_date));

		$str = @date('w', $arg_date);

		return $str;
	}
	

	function now_datetime(){

		 //get CurrentDate
		$current = getdate(); 

		// format 
		if ($current["hours"] < 10) { 
			$current["hours"] = "0" . $current["hours"]; 
		} 
		if ($current["minutes"] < 10) { 
			$current["minutes"] = "0" . $current["minutes"]; 
		} 
		if ($current["seconds"] < 10) { 
			$current["seconds"] = "0" . $current["seconds"]; 
		} 
		if ($current["mon"] <10 ){
			$current["mon"] = "0".$current["mon"];
		}
		if ($current["mday"] < 10) { 
			$current["mday"] = "0" . $current["mday"]; 
		} 

		// turn it into strings 
		$current_time =  $current["hours"] . ":" . $current["minutes"] . ":" . $current ["seconds"]; 
		$current_date = $current["year"] . "-" . $current["mon"] . "-" . $current["mday"];

		return $current_date . $current_time;
	}

	function exec_LastDay($datMonth,$datYear) 
	{
		if ($datMonth==4 || $datMonth==6 || $datMonth==9 || $datMonth==11 ) //4월 6월 9월 11월이면 월말값은 30일
		{
			$datLastDay=30;
		}
		elseif ($datMonth==2 &&  ($datYear%4) != 0 )          //2월이고  년도를 4로 나눈 값이 0이 아니면 28일
		{
			$datLastDay=28;
		}
		elseif ($datMonth==2 && ($datYear%4) == 0 )         //윤달 계산
		{
			if (($datYear%100) == 0 )
			{
				if (($datYear%400) == 0 )
					$datLastDay=29;
				else
					$datLastDay=28;
			}
			else
			{
				$datLastDay=29;
			}
		}
		else
		{
			$datLastDay=31;
		} 

		return $datLastDay;
	}

	function daysafter($arg_date, $arg_after_day)
	{

		$arg_date = strtotime($arg_date);
		$arg_date  =  mktime(@date('H', $arg_date),@date('i', $arg_date),@date('s', $arg_date),@date('m', $arg_date),@date('d', $arg_date),@date('Y', $arg_date));

		$arg_after_day_mktime = $arg_after_day*86400;

		$date_diff = $arg_date + $arg_after_day_mktime;

		$str = @date('Y-n-j H:i:s', $date_diff);

		return $str;

	}

?>