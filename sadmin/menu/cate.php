<?php
##	설명
##	Ver 1.31 2002/5/21 By Sunmin Park(sponsor@new21.com)
##	
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp'	 => 1,
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

##Ready... (변수 초기화 및 넘어온값 필터링)
	// 관리자페이지 환경파일 읽어드림
	$rs=db_query("select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
	$page = db_count() ? db_array($rs) : back("관리자페이지 환경파일을 읽을 수가 없습니다");

	$table = $SITE['th'] . "admin_menu";
	// 테이블 정보 가져와서 $dbinfo로 저장
	if($db) {
		$rs_dbinfo=db_query("SELECT * FROM {$SITE['th']}shopinfo WHERE table_name='{$db}'");
		$dbinfo=db_count() ? db_array($rs_dbinfo) : back("생성되지 않은 DB입니다.");
	}
	//else back("DB 값이 없습니다");
	
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

##Start... (DB 작업 및 display)
if($cateuid) {
	$cate_nevi = "<a href='{$PHP_SELF}?db={$db}'>Top</a> > ";
	$rs_cateinfo = db_query("SELECT * from {$table_cate} WHERE uid={$cateuid}");
	if(db_count()) {
		$cateinfo = db_array($rs_cateinfo);
		if(strlen($cateinfo['re'])) {
			// ( re='' or re='a' or re='ac' ) 만들기, re='aca"일때
			$sql_where_cate = " (re='' ";
			for($i=0;$i<strlen($cateinfo['re'])-1;$i++) {
					$sql_where_cate .= " or re='" . substr($cateinfo['re'],0,$i+1) ."' ";			
			}
			$sql_where_cate .= " ) ";
			// 	카테고리 네비게이션 만들기
			$rs = db_query("SELECT * from {$table_cate} WHERE $sql_where and num={$cateinfo['num']} and {$sql_where_cate} order by re");
			while($row=db_array($rs)) {
				$cate_nevi .= $row['title'] . " > ";
			}
		} // end if	
		if($mode == "catemodify"){
			$list=$cateinfo;
			$html['submitvalue']="수정";
		}
		else {
			$cate_nevi .= "$cateinfo['title'] > ";		
			$html['submitvalue']="추가";
		}
	} // end if(db_count)
} // end if($cateuid)

$mode = $mode ? $mode : "catewrite";
?>
<?=$page['html_header']	 // 스타일시트
?>
<body bgcolor="<?=$page['right_bgcolor']?>" background="<?=$page['right_background']?>">
<form name="form1" method="post" action="cateok.php">
	<input type="hidden" name="mode" value="<?=$mode?>">
	<input type="hidden" name="db" value="<?php echo $db; ?>">
	<input type="hidden" name="cateuid" value="<?=$cateuid?>">

<table border=0 cellspacing='<?=$page['table_cellspacing']?>' cellpadding='<?=$page['table_cellpadding']?>' bgcolor='<?=$page['table_linecolor']?>'>
		<tr> 
			<td bgcolor='<?=$page['table_titlecolor']?>' colspan=2>메뉴 <?=$html['submitvalue']?></td>
		</tr>
		<tr> 
			<td bgcolor='<?=$page['table_thcolor']?>'>메뉴 이름</td>
			<td bgcolor='<?=$page['table_tdcolor']?>'>
				<?=$cate_nevi
?>
			<input type="text" name="title" value="<?=htmlspecialchars($list['title'],ENT_QUOTES) ?>">
			</td>
		</tr>
		<tr> 
			<td bgcolor='<?=$page['table_thcolor']?>'>URL</td>
			<td bgcolor='<?=$page['table_tdcolor']?>'>
			<input type="text" name="url" size="40" value="<?=htmlspecialchars($list['url'],ENT_QUOTES) ?>">
			</td>
		</tr>
		<tr>
			<td bgcolor='<?=$page['table_thcolor']?>'>&nbsp;</td>
			<td bgcolor='<?=$page['table_tdcolor']?>'>
			<input type="submit" name="Submit" value=" 메뉴 <?=$html['submitvalue']?> ">
			</td>
		</tr>
		</table>
		<br>
</form>



<form name="form3" method="post" action="">
	<table border=0 cellspacing='<?=$page['table_cellspacing']?>' cellpadding='<?=$page['table_cellpadding']?>' bgcolor='<?=$page['table_linecolor']?>'>
	<tr> 
		<td bgcolor='<?=$page['table_titlecolor']?>' colspan=7><b> <?php echo $title; ?></b> 
		메뉴 현황</td>
	</tr>
	<tr bgcolor='<?=$page['table_thcolor']?>'> 
		<td width="48" > <div align="center">NO.</div></td>
		<td width="145" > <div align="center">상위메뉴 제목</div></td>
		<td	colspan=4 nowrap> <div align="center">메뉴</div></td>
		<td nowrap >메뉴수</td>
	</tr>
<?php
$rs_catelist = db_query("SELECT * from {$table_cate} ORDER BY num, re");		
$total = db_count();
for($i=0; $i<$total; $i++){
	$list = db_array($rs_catelist);
	$list['rede']=strlen($list['re']);
	if($list['rede'])
		$list['title']= str_repeat("&nbsp;&nbsp;", $list['rede']) . " ↘ " . $list['title']; 

	// URL Link..
	$href['catewrite']="{$PHP_SELF}?db={$db}";
	$href['catereply']="{$PHP_SELP}?db={$db}&cateuid={$list['uid']}";
	$href['catemodify']="{$PHP_SELP}?db={$db}&mode=catemodify&cateuid={$list['uid']}";
	$href['catesort']="./catesort.php?db={$db}&cateuid={$list['uid']}";
	$href['catedirectmodify']="/admin/myadmin224/tbl_change.php?table={$table_cate}&pos=0&session_max_rows=30&disp_direction=horizontal&repeat_cells=100&dontlimitchars=&primary_key=+%60uid%60+%3D+%27{$list['uid']}%27&goto=" . urlencode($PHP_SELF . "?" . $QUERY_STRING);
	$href['catedelete']="./cateok.php?db={$db}&mode=catedelete&cateuid={$list['uid']}";
	$href["list"]="./shop.php?db={$db}&cateuid={$list['uid']}";
?>
	<tr bgcolor='<?=$page['table_tdcolor']?>'> 
		<TD align="center" nowrap > 
		<?=$list['rede'] ? "" : $list['num']?>
		</TD>
		<TD nowrap> 
		<?=$list['title']?>(<?=$list['re']?>)
		</TD>
		<td nowrap> <div align="center"><a href="javascript: return false" onClick="window.open('<?=$href['catesort']?>','_blank','toolbar=no,location=no,status=no,menubar=no,scrollbars=auto,resizable=no,width=350,height=100,top=30 left=30')">순서변경</a></div></td>
		<td align="center" nowrap><a href="<?=$href['catereply']?>">서브추가</a></td>
		<td align="center" nowrap> <a href="<?=$href['catemodify']?>">수정</a> </td>
		<td align=center nowrap> <a href="<?=$href['catedelete']?>" onClick="return confirm('서브카테고리있다면 서비카테고리까지 삭제됩니다.\n정말 삭제 하시겠습니까?')">삭제</a> 
		</td>
		<td align="center" nowrap> 
		<?=$menucount?>
		</td>
	</tr>
<?php
} // end for
?>
	</table>
</form>
</body>
</html>