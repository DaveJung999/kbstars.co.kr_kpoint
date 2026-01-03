<?php
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 예시로 데이터베이스에서 특정 게시글을 조회합니다.
// 실제 사용 시에는 db_arrayone 함수와 테이블 이름이 유효해야 합니다.
$sql = "select * from new21_board2_contents_2016 where uid = 59 ";
$list = db_arrayone($sql);
echo isset($list['content']) ? $list['content'] : ''; 
?>
