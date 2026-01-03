<?php
//=======================================================
// 설	명 : 리스트(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/10/01
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 02/10/01 박선민 마지막 수정
//=======================================================
$HEADER=array(		'priv' => '회원', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
		'html_echo' => 1, // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
		'html_skin' => '' // html header 파일(skin/index_$HEADER['html'].php 파일을 읽음)
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);
//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$table_logon	= $SITE['th'] . "logon";
	$table_groupinfo= $SITE['th'] . "groupinfo";

	// 해당 그룹에 가입되어 있지 않다면 볼 수 없슴
	if($_GET['gid']){
		//if(!$_SESSION['seGroup'][$_GET['gid']]) back("해당 그룹의 그룹리스트를 보실 수 없습니다.");

		$rs_groupinfo	= db_query("SELECT * from {$table_groupinfo} where uid='{$_GET['gid']}'");
		$groupinfo	= db_count() ? db_array($rs_groupinfo) : back("해당 그룹은 존재하지 않습니다.");
	} else {
		$tmp_logon_class=db_result(db_query("SELECT * from {$table_logon} where uid='{$_SESSION['seUid']}'"),0,"class");
		if($tmp_logon_class != "root") back("전체 그룹리스트를 보실 수 없습니다.");
	}
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 하위 그룹리스트 구하기
if(isset($groupinfo)){
	$rs_subgroup = db_query("SELECT * from {$table_groupinfo} WHERE num='{$groupinfo['num']}' and re like '{$groupinfo['re']}%' order by re");
}
else $rs_subgroup = db_query("SELECT * from {$table_groupinfo} order by num, re");
if(db_count()){
	// 트리메뉴 클래스 읽기
	include_once($_SERVER['DOCUMENT_ROOT'] . "/sinc/class_treemenu.php");
	$icon = 'folder.gif';
	$menu	= new HTML_TreeMenu("menuLayer", '/scommon/treemenu/images',"grouplist_member");

	while($rows=db_array($rs_subgroup)){
		$rows['url']	= "memberlist.php?gid={$rows['uid']}";

		$varRe=$rows['num'];
		for($iRe=0;$iRe<strlen($rows['re']);$iRe++){
			$varRe.= "_" . ord(substr($rows['re'],$iRe,1));
		}
		$subNode{$varRe}=new HTML_TreeNode($rows['title'],$rows['url'],$icon);
		
		if(isset($rowsPrev)){
			if($rows['num'] != $rowsPrev['num']){
				$menu->addItem($subNode{$rowsPrev['num']});
				unset($subNode);
				$subNode{$varRe}=new HTML_TreeNode($rows['title'],$rows['url'],$icon);
				$varRePrev="";
			} else {
				$varReTemp=eregi_replace("_[^_]*$","",$varRe);
				if(isset($subNode{$varReTemp}))
					$subNode{$varReTemp}->addItem($subNode{$varRe});
			}
		} else {
			if(strlen($rows['re'])>0) { // 처음 카테고리가 num, re 중간임.
				$subNode{$rows['num']}= new HTML_TreeNode('','','');
				if(db_count() == 1) $subNode{$rows['num']}->addItem($subNode{$varRe});
			}
		} // end if. . else..
		
		$rowsPrev=$rows;
		$varRePrev=$varRe;	
	} // end while
	if(isset($subNode{$rowsPrev['num']})){
		$menu->addItem($subNode{$rowsPrev['num']});
		unset($subNode);
	} 

?>
			<script src="/scommon/treemenu/sniffer.js" language="JavaScript" type="text/javascript"></script>
			<script src="/scommon/treemenu/TreeMenu.js" language="JavaScript" type="text/javascript"></script>
			
<?php
}// end if 
?>
<table width="500" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#000000">
	<tr> 
	<td width="50%" valign="top"><div id="menuLayer"></div>
<?php$menu->printMenu(); 
?>
	</td>
	<td width="50%" valign="top"><iframe src="memberlist.php?gid=<?php echo $_GET['gid'] ; ?>" width="200" border="0" frameborder="0" scrolling=no name="grouplist_member" ></iframe></td>
	</tr>
	<tr> 
	<td valign="top"><div align="center">그룹추가 수정 삭제</div></td>
	<td valign="top"><div align="center">추가 수정 삭제 이동</div></td>
	</tr>
</table>
