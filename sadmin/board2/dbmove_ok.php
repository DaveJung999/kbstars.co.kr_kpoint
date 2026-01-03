<?php
//=======================================================
// 설  명 : 심플리스트
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
// 25/11/10 Gemini AI PHP 7+ 호환성 수정 (mysql_escape_string -> db_escape, 변수 중괄호 {}, 탭 변환, Short Tag)
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useApp' => 1, // cut_string()
	'useBoard2' => 1, // board2Count()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
$dbinfo_from	= array(
	// table
	'table' => 'kbsavers2.'.$_POST['from_table'],
	'upload_dir' => $_SERVER['DOCUMENT_ROOT'].'/sboard2/upload/'.$_POST['from_table'],	
);

$dbinfo_to	= array(
	// 심플리스트 제목
	'table' => 'kbsavers2.'.$_POST['to_table'],
	'upload_dir' =>	$_SERVER['DOCUMENT_ROOT'].'/sboard2/upload/'.$_POST['to_table'],	
);

if(!$_POST['mode'] == 'ok') back('dbmove.php');
else {
	// 테이블 존재하는지 체크
	if(!db_istable($dbinfo_from['table'])) back('원본 테이블이 존재하지 않습니다.');
	if(!db_istable($dbinfo_to['table'])) back('대상 테이블이 존재하지 않습니다.');
	
	// 카테고리가 있다면 해당 카테고리 존재하는지
	/*if($_POST['from_cateuid']){
		$sql = "select uid from {$dbinfo_from['table']}_cate where uid='{$_POST['from_cateuid']}'";
		if(!db_resultone($sql,0,'uid')) back('원본 카테고리가 존재하지 않습니다.');
	}
	*/
	if($_POST['to_cateuid']){
		// [!] FIX: {$dbinfo_to['table']} 변수 중괄호 {} 적용
		$sql = "select * from {$dbinfo_to['table']}_cate where uid='{$_POST['to_cateuid']}'";
		if(!$to_cateinfo = db_arrayone($sql)) back('대상 카테고리가 존재하지 않습니다.');
	}
	
	// 메모 카테고리가 있는지 체크
	if($_POST['memo']){
		// [!] FIX: {$dbinfo_to['table']} 변수 중괄호 {} 적용
		if(!db_istable($dbinfo_to['table'].'_memo')) back('대상 메모 테이블이 존재하지 않습니다.');
	}
		
	// 대상 테이블 가장 큰 num값 가져오기
	// [!] FIX: {$dbinfo_to['table']} 변수 중괄호 {} 적용
	$sql = "select max(num) as maxnum from {$dbinfo_to['table']}";
	$max_num = (int)db_resultone($sql,0,'maxnum') +1;

	$_POST['from_cateuid'] = (int)$_POST['from_cateuid'];
	// [!] FIX: {$dbinfo_from['table']}, {$_POST['from_cateuid']} 변수 중괄호 {} 적용
	$sql = "select * from {$dbinfo_from['table']} where type='docu' and cateuid='{$_POST['from_cateuid']}'";
	$rs = db_query($sql);
	while($from = db_array($rs)){
		echo "{$from['uid']} : {$from['title']} 이동하기<br>";
		
		if($_POST['memo']){
			// [!] FIX: {$dbinfo_from['table']}, {$from['uid']} 변수 중괄호 {} 적용
			$sql = "select count(*) as count from {$dbinfo_from['table']} where type='memo' and num='{$from['uid']}'";
			$from['memo_count'] = db_resultone($sql,0,'count');
			echo "메모개수:{$from['memo_count']}<br>";
		}
		
		// 파일 업로드 드렉토리
		// [!] FIX: {$dbinfo_from['upload_dir']}, {$from['bid']} 변수 중괄호 {} 적용
		$updir_from = "{$dbinfo_from['upload_dir']}/"	. (int)$from['bid'];
		$updir_to = "{$dbinfo_to['upload_dir']}/"	. (int)$from['bid'];
		@mkdir(dirname($updir_to),0777);
		@mkdir($updir_to,0777);
		
		// 기존 업로드 파일 정보 읽어오기
		if($from['upfiles']){
			$upfiles=unserialize($from['upfiles']);
			if(!is_array($upfiles)) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
				$upfiles['upfile']['name']=$from['upfiles'];
				$upfiles['upfile']['size']=(int)$from['upfiles_totalsize'];
			}
		}
		$upfiles_totalsize=(int)$from['upfiles_totalsize'];

		// 대상 파일이 이미 존재하면 파일명 변경
		if(is_array($upfiles) and count($upfiles)>0){
			foreach($upfiles as $key =>	$value){
				// [!] FIX: {$updir_from}, {$upfiles[$key]['name']} 변수 중괄호 {} 적용
				if( is_file("{$updir_from}/" . $upfiles[$key]['name']) ){
					$from_name = "{$updir_from}/" . $upfiles[$key]['name'];
					$upfiles[$key]['name'] = new_filename ($updir_to, $upfiles[$key]['name']);
					$from_to = "{$updir_to}/" . $upfiles[$key]['name'];
				}
				// [!] FIX: {$dbinfo_from['upload_dir']}, {$upfiles[$key]['name']} 변수 중괄호 {} 적용
				elseif( is_file("{$dbinfo_from['upload_dir']}/"	. $upfiles[$key]['name']) ) { // 상위드렉토리에서
					$from_name = "{$dbinfo_from['upload_dir']}/" . $upfiles[$key]['name'];
					$upfiles[$key]['name'] = new_filename ($updir_to, $upfiles[$key]['name']);
					$from_to = "{$updir_to}/" . $upfiles[$key]['name'];
				}
				if($_POST['test'])
					echo "<br>copy({$from_name},{$from_to})<br>";
				else 
					@copy($from_name,$from_to);
			}
		}
		$from['upfiles'] = serialize($upfiles);
		unset($upfiles);
		
		foreach($from as $k =>	$v){
			// [!] FIX: mysql_escape_string -> db_escape
			$from[$k] = db_escape($v);
		}
	
		// num값
		$from['num'] += $max_num;
		// [!] FIX: $dbinfo_to['table'], $from, $to_cateinfo 변수 중괄호 {} 적용
		$sql="INSERT INTO {$dbinfo_to['table']} SET
				`bid`		='{$from['bid']}',
				`userid`	='{$from['userid']}',
				`email`		='{$from['email']}',
				`passwd`	='{$from['passwd']}',
				`cateuid`	='{$to_cateinfo['uid']}',
				`catetitle`	='{$to_cateinfo['title']}',
				`num`		='{$from['num']}',
				`re`		='{$from['re']}',
				`title`		='{$from['title']}',
				`content`	='{$from['content']}',
				`data1`		='{$from['data1']}',
				`data2`		='{$from['data2']}',
				`data3`		='{$from['data3']}',
				`data4`		='{$from['data4']}',
				`data5`		='{$from['data5']}',
				`upfiles`	='{$from['upfiles']}',
				`upfiles_totalsize`	='{$from['upfiles_totalsize']}',
				`docu_type`	='{$from['docu_type']}',
				`type`		='{$from['type']}',
				`priv_read`	='{$from['priv_read']}',
				`priv_hidelevel`	='{$from['priv_level']}',
				`ip`		='{$from['ip']}',
				`hit`		='{$from['hit']}',
				`hitdownload`	='{$from['hitdownload']}',
				`hitip`		='{$from['hitip']}',
				`vote`		='{$from['vote']}',
				`voteip`	='{$from['voteip']}',
				`rdate`		='{$from['rdate']}'

		";
		if($_POST['test'])
			echo "<br><div style='overflow:scroll; height:70; width:500;'>{$sql}</div><br>";
		else {
			db_query($sql);
			$uid = db_insert_id();
			if($from['memo_count']){
				// [!] FIX: {$dbinfo_from['table']}, {$from['uid']} 변수 중괄호 {} 적용
				$sql = "select * from {$dbinfo_from['table']} where type='memo' and num='{$from['uid']}'";
				$rs_memo = db_query($sql);
				while($frommemo = db_array($rs_memo)){
					foreach($frommemo as $k =>	$v){
						// [!] FIX: mysql_escape_string -> db_escape
						$frommemo[$k] = db_escape($v);
					}
				
					// [!] FIX: {$dbinfo_to['table']}, {$frommemo}, {$uid} 변수 중괄호 {} 적용
					$sql = "INSERT INTO {$dbinfo_to['table']}_memo SET
								`bid`		='{$frommemo['bid']}',
								`userid`	='{$frommemo['userid']}',
								`email`		='{$frommemo['email']}',
								`passwd`	='{$frommemo['passwd']}',
								`pid`		='{$uid}',
								`title`		='{$frommemo['title']}',
								`content`	='{$frommemo['content']}',
								`upfiles`	='{$frommemo['upfiles']}',
								`upfiles_totalsize`	='{$frommemo['upfiles_totalsize']}',
								`docu_type`	='{$frommemo['docu_type']}',
								`vote`		='{$frommemo['vote']}',
								`priv_hidelevel`	='{$frommemo['priv_level']}',
								`ip`		='{$frommemo['ip']}',
								`rdate`		='{$frommemo['rdate']}'
							";
					db_query($sql);
					
				}
			}
		}
	}	
}
function new_filename ($updir, $filename){
	$ufile['name'] = $filename;
	// 올리려는 파일이 있을 경우 파일이름에 '_1', '_2'를 붙임
	if($tmp_count=strrpos($ufile['name'],'.')) {	// 확장자가 있을 경우
		$tmp_expend=substr($ufile['name'],$tmp_count+1);
		$tmp_file = substr($ufile['name'], 0, $tmp_count);

		$i=1;
		while (file_exists($updir.'/'.$ufile['name'])){
			$ufile['name'] = $tmp_file.'_'	. $i .'.'.$tmp_expend;
			$i++;
		}
	} else {	// 확장자가 없을 경우
		$i=1;
		while (file_exists($updir.'/'.$ufile['name'])){
			$ufile['name'] =$ufile['name'] . '_'	. $i;
			$i++;
		}		
	}
	return $ufile['name'];
}
//=======================================================
// Start.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
?>
<hr>
<br />
	board -> board2로 옮기기 <br />
<form method="post" action="dbmove_ok.php">
<input type="hidden" name="mode" value="ok">
	<p>원본 테이블 
	<input name="from_table" type="text" id="from_table" value="<?php echo $_POST['from_table'] ?>"><br>원본 카테고리 
	<input name="from_cateuid" type="text" id="from_cateuid"value="<?php echo $_POST['from_cateuid'] ?>">
	</p>
	<p>대상	테이블
	<input name="to_table" type="text" id="to_table"value="<?php echo $_POST['to_table'] ?>">	<br>대상 카테고리
	<input name="to_cateuid" type="text" id="to_cateuid"value="<?php echo $_POST['to_cateuid'] ?>">
	</p>
	<p><input name="test" type="checkbox" value="1" checked="checked">	테스트</p>
	<p><input name="memo" type="checkbox" value="1" checked="checked">	메모도 옮김</p>
	<input type="submit" value="db 옮기기">
</form>