<div id="kb_sns" >
<?php
	//=======================================================
	// Start.. . (DB 작업 및 display)
	//=======================================================
	$sql = "select * from new21_board2_contents_2016 where uid = 69 ";
	$list = db_arrayone($sql);
	// 변수 존재 여부 확인 후 출력
	echo isset($list['content']) ? $list['content'] : '';
?>
</div>
<div id="nav_logo"> <img src="/images/2017/2nd/kbbank.png"/></div>
