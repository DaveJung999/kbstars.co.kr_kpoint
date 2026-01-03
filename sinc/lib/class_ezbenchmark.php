<?php
// 
// $Id: ezbenchmark.php,v 1.1 2001/02/07 18:45:24 bf Exp $ 
// 
// Definition of eZTextTool class 
// 
// Bard Farstad <bf@ez.no> 
// Created on: <23-Jan-2001 12:34:54 bf> 
// 
// Copyright (C) 1999-2001 eZ Systems.	All rights reserved. 
// 
// This source file is part of eZ publish, publishing software. 
// Copyright (C) 1999-2001 eZ systems as 
// 
// This program is free software; you can redistribute it and/or 
// modify it under the terms of the GNU General Public License 
// as published by the Free Software Foundation; either version 2 
// of the License, or (at your option) any later version. 
// 
// This program is distributed in the hope that it will be useful, 
// but WITHOUT ANY WARRANTY; without even the implied warranty of 
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the 
// GNU General Public License for more details. 
// 
// You should have received a copy of the GNU General Public License 
// along with this program; if not, write to the Free Software 
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA	02111-1307, US 
// 

//!! eZCommon 
//! Provied utility functions for http. 
/*! 
*/ 

class benchmark 
{ 
	// 시작 시각 저장
	function start() 
	{ 
		$this->StartTime = microtime(true); 
	} 

	// 종료 시각 저장
	function stop() 
	{ 
		$this->StopTime = microtime(true); 
	} 

	// 초단위 float 반환 (microtime(true) 사용으로 불필요, 필요 시 외부에서 쓰세요)
	function microtime_float($time)
	{
		list($usec, $sec) = explode(" ", $time);
		return ((float)$usec + (float)$sec);
	}

	// 실행 시간 표시 (초단위, 소수점 2자리)
	function view() 
	{ 
		if (!isset($this->StartTime) || !isset($this->StopTime)){
			return "Benchmark not started/stopped properly.";
		}
		$elapsed = $this->StopTime - $this->StartTime; 
		$result = number_format($elapsed, 2) . " seconds"; 
		return $result; 
	} 
	
	// 실행 시간 float 초단위 반환
	function result()
	{
		if (!isset($this->StartTime) || !isset($this->StopTime)){
			return false;
		}
		return $this->StopTime - $this->StartTime; 
	}
} // end class

?>