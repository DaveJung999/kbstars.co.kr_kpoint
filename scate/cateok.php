<?php
//=======================================================
// 설  명 : 게시판 카테고리 처리(cateok.php)
// 책임자 : 박선민 (), 검수: 05/01/27
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/27 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'usedb2'	=>1, // DB 커넥션 사용
	'useApp'	=>1, // 
	'useCheck'	=>1, // check_value()
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$prefix 	= 'cate';
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// table
	$table_dbinfo = $SITE['th'].$prefix.'info';

	// boardinfo 테이블 정보 가져와서 $dbinfo로 저장
	$sql = "SELECT * from {$table_dbinfo} WHERE db='{$_REQUEST['db']}' LIMIT 1";
	$dbinfo = db_arrayone($sql) or back('사용하지 않는 카테고리입니다.');
	if($dbinfo['enable_cate']!='Y') back('카테고리 기능을 지원하지 않습니다.');
	
	// 인증 체크
	if(!privAuth($dbinfo, 'priv_catemanage')) back('이용이 제한되었습니다.(레벨부족)');

	// table	
	$dbinfo['table_cate'] = $SITE['th'].$prefix;

	$sql_where_cate = " db='{$dbinfo['db']}' "; // init
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// mode값에 따른 함수 호출
switch($_REQUEST['mode']) {
	case 'catewrite' :
		$qs	= array(
				cateuid	=> 'post,trim',
				title	=> 'post,trim,notnull=' . urlencode('카테고리 제목을 입력하십시요')
			);
		if(!cateWriteOK($dbinfo,$qs)) back('처리되지 않았습니다.');
		go_url($_REQUEST['goto'] ? $_REQUEST['goto'] : "./cate.php?db={$dbinfo['db']}&cateuid={$_REQUEST['cateuid']}");
		break;
	case 'catemodify' :
		$qs	= array(
				cateuid	=> 'post,trim,notnull=' . urlencode('고유번호가 넘어오지 않았습니다'),
				title	=> 'post,trim,notnull',
			);	
		cateModifyOK($dbinfo,$qs,'uid');
		go_url($_REQUEST['goto'] ? $_REQUEST['goto'] : "cate.php?db={$dbinfo['db']}");
		break;
	case 'catedelete' :
		$goto = $_REQUEST['goto'] ? $_REQUEST['goto'] : "cate.php?db={$dbinfo['db']}";
		cateDeleteOK($dbinfo,'uid',$goto);
		go_url($goto);
		break;
	case 'catesort' :
		$qs	= array(
				db		=> 'post,trim,notnull=' . urlencode('db값이 넘어오지 않았습니다'),
				srcuid	=> 'post,trim,notnull=' . urlencode('있어야할 값이 넘어오지 않았습니다'),
				dstuid	=> 'post,trim,notnull=' . urlencode('있어야할 값이 넘어오지 않았습니다')
			);	
		cateSortOk($dbinfo,$qs);		
		echo ('
				<script language = "JavaScript">
					if(opener)
					{
						opener.location.reload();
						self.close();
					}
				</script>
		');
		exit;
	default :
		back('잘못된 웹페이지에 접근하였습니다.');
}

//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
function cateSortOk($dbinfo,$qs) {
	global $sql_where_cate;
	
	$qs=check_value($qs);
	
	$sql = "select * from {$dbinfo['table_cate']} where uid='{$qs['srcuid']}' and {$sql_where_cate}";
	$src = db_arrayone($sql) or back('해당 카테고리가 존재하지 않습니다');
	
	// 변경할 카테고리 uid 구해서 where절 uid in (..) 만듬
	$rs_srcuids=db_query("select * from {$dbinfo['table_cate']} WHERE {$sql_where_cate} and num='{$src['num']}' and re like '{$src['re']}%'");
	while( $row=db_array($rs_srcuids) )
		$srcuids[]=$row['uid'];
	$sql_where_srcuid_in = ' uid in (' . implode(',',$srcuids) . ') ';
	
	if(strlen($src['re'])) {
		if($qs['dstuid']=='first') { // 처음으로 이동한 경우 (re=ab라면 a보다 크고 ab보다 작은 범위를 1씩 증가후 re=a1으로 변경)
			$sql = "select * from {$dbinfo['table_cate']} WHERE {$sql_where_cate} and num='{$src['num']}' and re like '" . substr($src['re'],0,-1) . '_\' order by re LIMIT 1';
			$dst = db_arrayone($sql) or back('옮기고자 하는 카테고리 선택이 잘못되었습니다. err110');			
		
			$src['length']=strlen($src['re']);
			if($dst['re'] == substr($src['re'],0,-1).'1' ) db_query("UPDATE {$dbinfo['table_cate']} SET re=concat( substring(re,1,{$src['length']}-1), char(ord(substring(re,{$src['length']},1))+1 ), substring(re,{$src['length']}+1) ) where  {$sql_where_cate} and num='{$src['num']}' and strcmp(re,substring(re,1,{$src['length']}-1))>0 and strcmp(re,'{$src['re']}')< 0"); // dst[re]의 맨뒤가 '1'이 아니면 구지 1씩 증가시킬 필요 없겠지^^;;
			db_query("UPDATE {$dbinfo['table_cate']} SET re=concat( substring(re,1,{$src['length']}-1), '1', substring(re,{$src['length']}+1)) where {$sql_where_srcuid_in}"); // src[re]의 마지막은 '1'부터 시작하니 강제로 '1'로 해야겠지^^
		}
		else {
			$sql = "select * from {$dbinfo['table_cate']} where uid='{$qs['dstuid']}' and num='{$src['num']}' and re like '" . substr($src['re'],0,-1) . '_\'';
			$dst = db_arrayone($sql) or back('옮기고자 하는 카테고리 선택이 잘못되었습니다. err118');

			if( strlen($src['re'])!=strlen($dst['re']) ) back('카테고리 선택이 잘못되었습니다.');

			if( strcmp($src['re'],$dst['re']) > 0 ){ // 상위로 이동할 경우 ( 목적위치+1이상에서 본래위치 미만 범위를 1씩 증가후 본래위치는 목적위치+1
				$src['length']=strlen($src['re']);
				$dst['re_next']=substr($dst['re'],0,-1) . chr(ord(substr($dst['re'],-1))+1);
				db_query("UPDATE {$dbinfo['table_cate']} SET re=concat( substring(re,1,{$src['length']}-1), char(ord(substring(re,{$src['length']},1))+1 ), substring(re,{$src['length']}+1) ) where	{$sql_where_cate} and num='{$src['num']}' and strcmp(re,'{$dst['re_next']}')>=0 and strcmp(re,'{$src['re']}')< 0 ");
				db_query("UPDATE {$dbinfo['table_cate']} SET re=concat( substring(re,1,{$src['length']}-1), right('{$dst['re_next']}',1), substring(re,{$src['length']}+1)) where	{$sql_where_srcuid_in}");
			}
			elseif(strcmp($src['re'],$dst['re']) < 0) { // 하위로 이동할 경우 ( 본래위치+1이상에서 목적위치+1 미만 범위를 1씩 감소후 본래위치는 목적위치 
				$src['length']=strlen($src['re']);
				$src['re_next']=substr($src['re'],0,-1) . chr(ord(substr($src['re'],-1))+1);
				$dst['re_next']=substr($dst['re'],0,-1) . chr(ord(substr($dst['re'],-1))+1);
				db_query("UPDATE {$dbinfo['table_cate']} SET re=concat( substring(re,1,{$src['length']}-1), char(ord(substring(re,{$src['length']},1))-1 ), substring(re,{$src['length']}+1) ) where	{$sql_where_cate} and num='{$src['num']}' and strcmp(re,'{$src['re_next']}')>= 0 and strcmp(re,'{$dst['re_next']}')<0 ");
				db_query("UPDATE {$dbinfo['table_cate']} SET re=concat( substring(re,1,{$src['length']}-1), right('{$dst['re']}',1) , substring(re,{$src['length']}+1) ) where	 {$sql_where_srcuid_in}");
			}
		}
	}
	else { // re값이 없고 num값을 변경해야될 경우임
		if($qs['dstuid']=='first') { // 최상위로 이동할 경우
			$sql = "select * from {$dbinfo['table_cate']} WHERE {$sql_where_cate} order by num LIMIT 1";
			$dst = db_arrayone($sql) or back('옮기고자 하는 카테고리 선택이 잘못되었습니다. 4');

			if($dst['num']==1) db_query("UPDATE {$dbinfo['table_cate']} SET num=num+1 WHERE {$sql_where_cate} and num < {$src['num']}"); // dst[num]이 1일 아니라면 키울필요 없겠지^^
			db_query("UPDATE {$dbinfo['table_cate']} SET num=1 WHERE {$sql_where_srcuid_in}"); // 처음값이기때문에 dst[num]보다 1로 변경함..
		}
		else {
			$sql = "select * from {$dbinfo['table_cate']} where uid='{$qs['dstuid']}' and re=''";
			$dst = db_arrayone($sql) or back('옮기고자 하는 카테고리 선택이 잘못되었습니다. 6');
		
			if($src['num'] > $dst['num']){  // 상위로 이동할 경우 (dst[num]보다 크고 src[num] 미만범위를 1씩 증가후 src[num]=dst[num]+1로 변경
				db_query("UPDATE {$dbinfo['table_cate']} SET num=num+1 WHERE {$sql_where_cate} and num > {$dst['num']} and num < {$src['num']} ");
				db_query("UPDATE {$dbinfo['table_cate']} SET num={$dst['num']}+1 WHERE {$sql_where_srcuid_in}");
			}
			elseif($src['num'] < $dst['num']){ // 하위로 이동할 경우 (src[num]보다 크고 dst[num] 이하의 경우 1씩 감소후 본래위치는 dst[num]값으로
				db_query("UPDATE {$dbinfo['table_cate']} SET num=num-1 WHERE  {$sql_where_cate} and num > {$src['num']} and num <= {$dst['num']}");
				db_query("UPDATE {$dbinfo['table_cate']} SET num={$dst['num']} WHERE	{$sql_where_srcuid_in}");
			}
		}
	}
} // end func.


// 카테고리 추가 부분($sql_set_cate 가져오는 것 필히 확인)
function cateWriteOK($dbinfo, $qs) {
	global $sql_where_cate;
	
	$qs=check_value($qs);
	
	// num, re 값 결정
	if($qs['cateuid']){ // 서브카테고리 추가인경우
		$sql = "SELECT * FROM {$dbinfo['table_cate']} where uid='{$qs['cateuid']}' and {$sql_where_cate} ";
		$list = db_arrayone($sql) or back('해당 부모 카테고리가 없습니다.');
		$qs['num']=$list['num'];
		$qs['re'] =getCateRe($dbinfo['table_cate'],$sql_where_cate,$list['num'],$list['re']);
		if($dbinfo['cate_depth'] and $dbinfo['cate_depth'] < strlen($qs['re'])) back('더 하부의 서브카테고리를 만드실 수 없습니다');
	}
	else { // 탑카테고리 추가인경우
		$qs['num'] = db_resultone("SELECT MAX(num) as num FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} ", 0, 'num') + 1;
		$qs['re']	= '';
	}
	
	////////////////////////////////////////////
	// 추가되어 있는 테이블 필드 포함($sql_set)
	$skip_fields = array('uid' , 'upfiles' , 'upfiles_totalsize', 'hit' , 'hitip' , 'hitdownload' , 'vote' , 'voteip' , 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table_cate'],$skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) { 
				case 'zip' :
					if(!isset($_POST)) $qs['zip'] = $_POST['zip1'] + '-' + $_POST['zip2'];
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'bid' :
					$qs['bid']	= $_SESSION['seUid'];
				case 'userid' :
					if($_SESSION['seUid']) {
						switch($dbinfo['enable_userid']) {
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
					}
					break;
				case 'email' :
					if($_POST['email']) $qs['email']	= check_email($_POST['email']);
					elseif($_SESSION['seUid']) $qs['email']	= $_SESSION['seEmail'];
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) {
				if($value=='passwd') $sql_set .= ", passwd	=password('{$qs['passwd']}') ";
				else $sql_set .= ', '.$value.' ="'.$qs[$value].'"';
			}
			elseif(isset($_POST[$value])) $sql_set .= ', '.$value.' ="'.$_POST[$value]. '"';
		} // end foreach
	} // end if
	////////////////////////////////////////////

	$sql="INSERT INTO $dbinfo['table_cate'] SET
				rdate	= UNIX_TIMESTAMP()
				{$sql_set}
		";
	db_query($sql);
	
	return db_insert_id();
}

// 카테고리 수정 부분
function cateModifyOK($dbinfo,$qs,$field) {
	global $sql_where_cate;
	
	// $qs 추가,변경
	//$qs["{$field}"]	= "post,trim,notnull=" . urlencode("고유번호가 넘어오지 않았습니다");
	$qs=check_value($qs);
	$qs[$field] = $qs['cateuid'];
	
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$field}='{$qs[$field]}' and {$sql_where_cate}";
	$list = db_arrayone($sql) or back('수정하실 카테고리가 없습니다');

	////////////////////////////////////////////
	// 추가되어 있는 테이블 필드 포함($sql_set)
	$skip_fields = array('bid','uid', 'upfiles' , 'upfiles_totalsize', 'hit' , 'hitip' , 'hitdownload' , 'vote' , 'voteip' , 'rdate');
	if($fieldlist = userGetAppendFields($dbinfo['table_cate'],$skip_fields)) {
		foreach($fieldlist as $value) {
			// 해당 필드 데이터값 확정
			switch($value) { 
				/*
				case 'bid' :
				case 'num' :
				case 're' :
					continue 2; // 다음 foreach 로...
				*/
				case 'zip' :
					if(!isset($_POST)) $qs['zip'] = $_POST['zip1'] + '-' + $_POST['zip2'];
					break;
				case 'ip' :
					$qs['ip'] = remote_addr();
					break;
				case 'userid' :
					if($list['bid']==$_SESSION['seUid']) { // 관리자권한으로 수정했으면 변경불가
						switch($dbinfo['enable_userid']) {
							case 'name'		: $qs['userid'] = $_SESSION['seName']; break;
							case 'nickname'	: $qs['userid'] = $_SESSION['seNickname']; break;
							default			: $qs['userid'] = $_SESSION['seUserid']; break;
						}
					}
					break;
				case 'email' :
					if($_POST['email']) $qs['email']	= check_email($_POST['email']);
					elseif($list['bid']==$_SESSION['seUid']) // 관리자권한으로 수정했으면 변경불가
						$qs['email']	= $_SESSION['seEmail'];
					break;
			} // end switch

			// sql_set 만듦
			if(isset($qs[$value])) $sql_set .= ', '.$value.' ="'.$qs[$value]. '"';
			elseif(isset($_POST[$value])) $sql_set .= ', '.$value.' ="'.$_POST[$value]. '"';
		} // end foreach
	} // end if
	////////////////////////////////////////////
	$sql="UPDATE {$dbinfo['table_cate']} SET
				rdate	=UNIX_TIMESTAMP()
				{$sql_set}
			WHERE
				{$field}='{$qs[$field]}'
		";
	db_query($sql);
	
	return true;
}

// 카테고리 삭제부분
function cateDeleteOK($dbinfo,$field,$goto) {
	global $sql_where_cate ;
	print_r($dbinfo);
	// $qs 추가,변경
	$qs=array(
			//"$field"	=> "request,trim,notnull=" . urlencode("고유넘버가 넘어오지 않았습니다."),
			'passwd'	=> 'request,trim',
			'cateuid'	=> 'request,trim,notnull=' . urlencode('고유넘버가 넘어오지 않았습니다.'),
		);
	$qs=check_value($qs);
	$qs[$field] = $qs['cateuid'];
	
	$sql = "SELECT * FROM {$dbinfo['table_cate']} WHERE {$field}='{$qs[$field]}' and {$sql_where_cate} ";
	$list = db_arrayone($sql) or back('수정하실 카테고리가 없습니다');

	// 자신과 하위 카테고리 uid 구함($subcate_uid)
	$subcate_uid = array(); // init
	$sql="SELECT * FROM {$dbinfo['table_cate']} WHERE {$sql_where_cate} and num={$list['num']} and re like {$list['re']}%'";
	$rs2 = db_query($sql);
	for($i=0;$i<db_count();$i++) {
		$subcate_uid[] = db_result($rs2,$i,'uid');
	}
	
	// SQL문 where부분 만들기
	$sql_cate_where = ' ( uid in (' . implode(',',$subcate_uid) . ') )  ';
	$sql_where = ' ( cateuid in (' . implode(',',$subcate_uid) . ') )  ';

	// 해당 카테고리의 DB 데이터가 있다면 삭제못함
	$sql="select count(*) as count from {$dbinfo['db']} where  $sql_where ";
	if(db_resultone($sql,0,'count')) {
		back("해당 카테고리와 관련된 DB 데이터가 있습니다.\\n해당 데이터를 먼저 삭제하시기 바랍니다.");
	}

	// 해당 카테고리 삭제
	$sql="DELETE FROM {$dbinfo['table_cate']} WHERE $sql_cate_where";
	db_query($sql);
	
	// 카테고리값 시프트
	if(strlen($list['re']))
		$sql="UPDATE {$dbinfo['table_cate']}
				SET re=concat( substring(re,1,length('{$list['re']}')-1),
					char(ord(substring(re,length('{$list['re']}'),1))-1 ),
					substring(re,length('{$list['re']}')+1) )
				WHERE {$sql_where_cate} 
				AND num='{$list['num']}' 
				AND re like '" . substr($list['re'],0,-1) . "%'
				AND strcmp(re,'{$list['re']}')>= 0
			";
	else 
		$sql="UPDATE
					{$dbinfo['table_cate']}
				SET num=num-1
				WHERE {$sql_where_cate} 
				AND num > {$list['num']}
			";
	db_query($sql);
	
	return true;
}


function getCateRe($table_cate, $sql_where_cate, $num, $re){
	global $conn;

	if(trim($sql_where_cate) == "") $sql_where_cate=" 1 ";

	$sql="SELECT re, right(re,1) FROM {$table_cate} WHERE {$sql_where_cate} AND num='".db_escape($num)."' AND length(re)=length('".db_escape($re)."')+1 AND locate('".db_escape($re)."', re)=1 ORDER BY re DESC LIMIT 1";
	
	$result = db_query($sql);
	$row = $result ? db_array($result) : null;
	if($row){
		$ord_head = substr($row['re'],0,-1);
		$ord_foot = chr(ord($row['1']) + 1);
		$re = $ord_head	. $ord_foot;
	} else {
		$re .= "1";
	}
	return $re;
}

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
/**
 * 추가 입력해야할 필드를 가져옵니다. (Modernized version)
 * @param string $table The table name.
 * @param array $skip_fields Fields to exclude.
 * @return array|false List of additional fields or false on failure.
 */
function userGetAppendFields(string $table, array $skip_fields = [])
{
	if (empty($table)) {
		return false;
	}

	$result = db_query("SHOW COLUMNS FROM {$table}");

	if (!$result) {
		return false;
	}

	$fieldlist = [];
	while($row = db_array($result)) {
		if(!in_array($row['Field'], $skip_fields)){
			$fieldlist[] = $row['Field'];
		}
	}
	db_free($result); 

	return isset($fieldlist) ? $fieldlist : false;
}
?>