<?php
//=======================================================
// 설	명 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/10/09
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 02/10/09 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		=>'운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

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
	// 관리자페이지 환경파일 읽어드림
	$rs=db_query("select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
	$page = db_count() ? db_array($rs) : back("관리자페이지 환경파일을 읽을 수가 없습니다");

	$table = "urls";
	
	// 카테고리 테이블 구함
	$sql_where=" 1 ";
	switch( $dbinfo['cate_table'] ) {
		case "" :
			$table_cate=$table;
			break;
		case "this" :
			$table_cate=$table;
			$sql_where=" type='cate' ";
			break;
		default :
			$table_cate=$table . "_" . $dbinfo['cate_table'];
	}

	// mode값에 따른 처리
	if($mode == "change"){
		// 넘어온 값 체크
		if(!$srcuid || !$dstuid) back("있어야할 값이 넘어오지 않았습니다.");

		$rs_src=db_query("SELECT * from {$table_cate} WHERE $sql_where and uid='{$srcuid}'");
		$src= db_count() ? db_array($rs_src) : back("해당 카테고리가 존재하지 않습니다");
		
		// 변경할 카테고리 uid 구해서 where절 uid in (..) 만듬
		$rs_srcuids=db_query("SELECT * from {$table_cate} WHERE $sql_where and num='{$src['num']}' and re like '{$src['re']}%'");
		while( $row=db_array($rs_srcuids) )
			$srcuids[]=$row['uid'];
		$sql_where_srcuid_in = " uid in (" . implode(",",$srcuids) . ") ";
		
		if(strlen($src['re'])) {
			if($dstuid=="first") { // 처음으로 이동한 경우 (re=ab라면 a보다 크고 ab보다 작은 범위를 1씩 증가후 re=a1으로 변경)
				$rs_dst=db_query("SELECT * from {$table_cate} WHERE $sql_where and num='{$src['num']}' and re like '" . substr($src['re'],0,-1) . "_' order by re LIMIT 1");
				$dst= db_count() ? db_array($rs_dst) : back("옮기고자 하는 카테고리 선택이 잘못되었습니다. 3");			
			
				$src['length']=strlen($src['re']);
				if($dst['re'] == substr($src['re'],0,-1)."1" ) db_query("update {$table_cate} SET re=concat( substring(re,1,{$src['length']}-1), char(ord(substring(re,{$src['length']},1))+1 ), substring(re,{$src['length']}+1) ) WHERE $sql_where and num='{$src['num']}' and strcmp(re,substring(re,1,{$src['length']}-1))>0 and strcmp(re,'{$src['re']}')< 0"); // dst[re]의 맨뒤가 '1'이 아니면 구지 1씩 증가시킬 필요 없겠지^^;;
				db_query("update {$table_cate} SET re=concat( substring(re,1,{$src['length']}-1), '1', substring(re,{$src['length']}+1)) WHERE $sql_where and {$sql_where_srcuid_in}"); // src[re]의 마지막은 '1'부터 시작하니 강제로 '1'로 해야겠지^^
			}
			else {			
				$rs_dst=db_query("SELECT * from {$table_cate} WHERE $sql_where and uid='{$dstuid}' and num='{$src['num']}' and re like '" . substr($src['re'],0,-1) . "_'");
				$dst= db_count() ? db_array($rs_dst) : back("옮기고자 하는 카테고리 선택이 잘못되었습니다. 3");

				if( strlen($src['re'])!=strlen($dst['re']) ) back("카테고리 선택이 잘못되었습니다.");
	
				if( strcmp($src['re'],$dst['re']) > 0 ){ // 상위로 이동할 경우 ( 목적위치+1이상에서 본래위치 미만 범위를 1씩 증가후 본래위치는 목적위치+1
					$src['length']=strlen($src['re']);
					$dst['re_next']=substr($dst['re'],0,-1) . chr(ord(substr($dst['re'],-1))+1);
					db_query("update {$table_cate} SET re=concat( substring(re,1,{$src['length']}-1), char(ord(substring(re,{$src['length']},1))+1 ), substring(re,{$src['length']}+1) ) WHERE $sql_where and num='{$src['num']}' and strcmp(re,'{$dst['re_next']}')>=0 and strcmp(re,'{$src['re']}')< 0 ");
					db_query("update {$table_cate} SET re=concat( substring(re,1,{$src['length']}-1), right('{$dst['re_next']}',1), substring(re,{$src['length']}+1)) WHERE $sql_where and {$sql_where_srcuid_in}");
				}
				elseif(strcmp($src['re'],$dst['re']) < 0) { // 하위로 이동할 경우 ( 본래위치+1이상에서 목적위치+1 미만 범위를 1씩 감소후 본래위치는 목적위치 
					$src['length']=strlen($src['re']);
					$src['re_next']=substr($src['re'],0,-1) . chr(ord(substr($src['re'],-1))+1);
					$dst['re_next']=substr($dst['re'],0,-1) . chr(ord(substr($dst['re'],-1))+1);
					db_query("update {$table_cate} SET re=concat( substring(re,1,{$src['length']}-1), char(ord(substring(re,{$src['length']},1))-1 ), substring(re,{$src['length']}+1) ) WHERE $sql_where and num='{$src['num']}' and strcmp(re,'{$src['re_next']}')>= 0 and strcmp(re,'{$dst['re_next']}')<0 ");
					db_query("update {$table_cate} SET re=concat( substring(re,1,{$src['length']}-1), right('{$dst['re']}',1) , substring(re,{$src['length']}+1) ) WHERE $sql_where and {$sql_where_srcuid_in}");
				}
			}
		}
		else { // re값이 없고 num값을 변경해야될 경우임
			if($dstuid=="first") { // 최상위로 이동할 경우
				$rs_dst=db_query("SELECT * from {$table_cate} WHERE $sql_where order by num LIMIT 1");
				$dst= db_count() ? db_array($rs_dst) : back("옮기고자 하는 카테고리 선택이 잘못되었습니다. 4");

				if($dst['num']==1) db_query("update {$table_cate} SET num=num+1 WHERE $sql_where and num < {$src['num']}"); // dst[num]이 1일 아니라면 키울필요 없겠지^^
				db_query("update {$table_cate} SET num=1 WHERE $sql_where and {$sql_where_srcuid_in}"); // 처음값이기때문에 dst[num]보다 1로 변경함..
			}
			else {
				$rs_dst=db_query("SELECT * from {$table_cate} WHERE $sql_where and uid='{$dstuid}' and re=''");
				$dst= db_count() ? db_array($rs_dst) : back("옮기고자 하는 카테고리 선택이 잘못되었습니다. 6");
			
				if($src['num'] > $dst['num']){	// 상위로 이동할 경우 (dst[num]보다 크고 src[num] 미만범위를 1씩 증가후 src[num]=dst[num]+1로 변경
					db_query("update {$table_cate} SET num=num+1 WHERE $sql_where and num > {$dst['num']} and num < {$src['num']} ");
					db_query("update {$table_cate} SET num={$dst['num']}+1 WHERE $sql_where and {$sql_where_srcuid_in}");
				}
				elseif($src['num'] < $dst['num']){ // 하위로 이동할 경우 (src[num]보다 크고 dst[num] 이하의 경우 1씩 감소후 본래위치는 dst[num]값으로
					db_query("update {$table_cate} SET num=num-1 WHERE $sql_where and num > {$src['num']} and num <= {$dst['num']}");
					db_query("update {$table_cate} SET num={$dst['num']} WHERE $sql_where and {$sql_where_srcuid_in}");
				}
			}
		}
		echo ("
				<script language = 'JavaScript'>
					if(opener)
					{
						opener.location.reload();
						self.close();
					}
				</script>
		");
		exit();
	}

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
echo $page['html_header'] ;	// 스타일시트
$rs_cateinfo = db_query("SELECT * from {$table_cate} WHERE $sql_where and uid='{$cateuid}'");
$cateinfo = db_count() ? db_array($rs_cateinfo) : back_close("카테고리가 선택되지 않았습니다.");

// 상위메뉴 순서 변경
?>
	<form name="form1" method="post" action="<?=$PHP_SELF
?>" style="margin:0px">
	<input type="hidden" name="mode" value="change">
	<input type="hidden" name="srcuid" value="<?php 
echo $cateinfo['uid'] 
?>">
	<table border=0 cellspacing='<?=$page['table_cellspacing']
?>' cellpadding='<?=$page['table_cellpadding']
?>' bgcolor='<?=$page['table_linecolor']
?>' width=350 height=100>
		<tr>
			<td bgcolor='<?=$page['table_titlecolor']
?>' ><b>메뉴순서변경</b></td>
		</tr>
		
		<tr>
			<td bgcolor='<?=$page['table_thcolor']
?>'>현재 <b><?php 
echo $cateinfo['title']  ?></b> 메뉴입니다.</td>
		</tr>
		<tr>
			<td bgcolor='<?=$page['table_tdcolor']
?>'><?php 
echo $list['menu'] 
?> 메뉴를 
			<select name="dstuid">
<?php
				if(strlen($cateinfo['re']))
					$rs_menus= db_query("SELECT * from {$table_cate} WHERE $sql_where and num='{$cateinfo['num']}' and length(re) = length('{$cateinfo['re']}') and locate('" . substr($cateinfo['re'],0,-1) . "',re)=1 order by re");
				else 
					$rs_menus= db_query("SELECT * from {$table_cate} WHERE $sql_where and	re='' order by num");
				$count=db_count();
				if($count <=1) back_close("순서변경이 필요없습니다.");

				// 처음으로, ??다음으로 출력
				$html_option="<option value='first'>처음으로</option>";
				for($i=0; $i<$count; $i++){
					$list_menus=db_array($rs_menus);
					if($list_menus['uid']==$cateinfo['uid'])
						$html_option="";
					elseif($i==$count-1) { // 마지막이면
						echo $html_option;
						echo "<option value='{$list_menus['uid']}'>마지막으로</option>";
					}
					else {
						echo $html_option;
						$html_option="<option value='{$list_menus['uid']}'>{$list_menus['title']} 다음으로</option>";
					}
				} // end for
				
?>
			</select><input type="submit" name="Submit" value="::	변경	::">
			</td>
		</tr>
		</table>
	</form>
</body>
</html>