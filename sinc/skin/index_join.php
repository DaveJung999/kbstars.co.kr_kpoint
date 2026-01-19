<?php
//=======================================================
// 설  명 : 사이트의 HTML 해더와 테일부분 예시(index_example.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/29
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/29 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?→ <?php 변환
//=======================================================
/*
<사이트 전체스킨 만드는 법>
1. 사이트의 반복되는 해더와 테일 부분을 html로 만든 이후,
본문에 들어갈 자리에 {{BODY}} 를 넣습니다.
2. 스킨의 맨 위부분에 ob_start();인 php 소스 한줄을 넣습니다.
3. 스킨의 맨 아래에 $body=ob_get_contents();부터 시작한 10줄의 php 소스를 넣습니다.
4. /skin 드렉토리 밑에 index_????.php 형태로 저장합니다..
????는 영문자로시작하여 영문자숫자로 구성되어야 하며, ????이 앞으로 사용할 사이트 스킨 이름입니다.
*/
ob_start(); // 버퍼링 시작
// 여기까지 복사하여 제작한 HTML의 맨 위에 넣으면 됨
?><html>

<head>
<link href="/style.css" rel="stylesheet" type="text/css">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>::: 천안 KB국민은행 세이버스 :::  Go! Go! Champion!</title>
<meta name="generator" content="Namo WebEditor v5.0">
<script language="JavaScript">
<!--
function namosw_init_float_layers()
{
  var name;
  var layer;
  var i;
  var j;

  j = 0;
  document._float_layers = new Array(Math.max(1, namosw_init_float_layers.arguments.length/2));
  for (i = 0; i < namosw_init_float_layers.arguments.length; i += 2) {
	name  = namosw_init_float_layers.arguments[i];
	if (name == '')
	  return;
	if (navigator.appName.indexOf('Netscape', 0) != -1) {
	  layer = document.layers[name];
	  layer._fl_pos_left = layer.left;
	  layer._fl_pos_top  = layer.top;
	} else {
	  layer = document.all[name];
	  layer._fl_pos_left = layer.style.pixelLeft;
	  layer._fl_pos_top  = layer.style.pixelTop;
	}
	layer._fl_pos = namosw_init_float_layers.arguments[i+1];
	if (layer)
	  document._float_layers[j++] = layer;
  }

  document._fl_interval = setInterval('namosw_process_float_layers()', 200);
}

function namosw_page_width()
{
  return (navigator.appName.indexOf('Netscape', 0) != -1) ? innerWidth  : document.body.clientWidth;
}

function namosw_page_height()
{
  return (navigator.appName.indexOf('Netscape', 0) != -1) ? innerHeight : document.body.clientHeight;
}

function namosw_process_float_layers()
{
  if (document._float_layers) {
	  var i;
	  var layer;
	  for (i = 0; i < document._float_layers.length; i++) {
	  layer = document._float_layers[i];
	  if (navigator.appName.indexOf('Netscape', 0) != -1) {
		if (layer._fl_pos == 1)
		  layer.left = layer._fl_pos_left + window.pageXOffset;
		else if (layer._fl_pos == 2 || layer._fl_pos == 5) 
		  layer.left = window.pageXOffset;
		else if (layer._fl_pos == 3 || layer._fl_pos == 6) 
		  layer.left = window.pageXOffset + (namosw_page_width() - layer.clip.width)/2;
		else
		  layer.left = window.pageXOffset + namosw_page_width() - layer.clip.width - 16;
		if (layer._fl_pos == 1)
		  layer.top = layer._fl_pos_top + window.pageYOffset;
		else if (layer._fl_pos == 2 || layer._fl_pos == 3 || layer._fl_pos == 4)
		  layer.top = window.pageYOffset;
		else
		  layer.top  = window.pageYOffset + namosw_page_height() - layer.clip.height;
	  } else {
		if (layer._fl_pos == 1)
		  layer.style.pixelLeft = layer._fl_pos_left + document.body.scrollLeft;
		else if (layer._fl_pos == 2 || layer._fl_pos == 5)
		  layer.style.pixelLeft = document.body.scrollLeft;
		else if (layer._fl_pos == 3 || layer._fl_pos == 6)
		  layer.style.pixelLeft = document.body.scrollLeft + (namosw_page_width() - layer.style.pixelWidth)/2;
		else
		  layer.style.pixelLeft = document.body.scrollLeft + namosw_page_width()  - layer.style.pixelWidth;
		if (layer._fl_pos == 1)
		  layer.style.pixelTop = layer._fl_pos_top + document.body.scrollTop;
		else if (layer._fl_pos == 2 || layer._fl_pos == 3 || layer._fl_pos == 4)
		  layer.style.pixelTop = document.body.scrollTop;
		else
		  layer.style.pixelTop  = document.body.scrollTop  + namosw_page_height() - layer.style.pixelHeight;
		 }
	  }
  }
}

// -->
</script>
<script language="JavaScript">
<!--
function na_restore_img_src(name, nsdoc)
{
  var img = eval((navigator.appName.indexOf('Netscape', 0) != -1) ? nsdoc+'.'+name : 'document.all.'+name);
  if (name == '')
	return;
  if (img && img.altsrc) {
	img.src	= img.altsrc;
	img.altsrc = null;
  } 
}

function na_preload_img()
{ 
  var img_list = na_preload_img.arguments;
  if (document.preloadlist == null) 
	document.preloadlist = new Array();
  var top = document.preloadlist.length;
  for (var i=0; i < img_list.length; i++) {
	document.preloadlist[top+i]	 = new Image;
	document.preloadlist[top+i].src = img_list[i+1];
  } 
}

function na_change_img_src(name, nsdoc, rpath, preload)
{ 
  var img = eval((navigator.appName.indexOf('Netscape', 0) != -1) ? nsdoc+'.'+name : 'document.all.'+name);
  if (name == '')
	return;
  if (img) {
	img.altsrc = img.src;
	img.src	= rpath;
  } 
}

// -->
</script>
</head>

<body>
<?php include("inc_head.php") ?>
<?php
//echo $_SERVER['PHP_SELF'];
switch($_SERVER['PHP_SELF']) {
	case '/sjoin/license.php' :
		$nevi = " &gt; 사이트 이용약관";
		break;
	case '/sjoin/privacy.php' :
		$nevi = ' &gt; 개인정보취급방침';
		break;
	case '/sjoin/email.php' :
		$nevi = ' &gt; 이메일주소 무단수집거부';
		break;
	case '/sjoin/copyright.php' :
		$nevi = ' &gt; 저작권 관련 안내';
		break;
	case '/sjoin/spam.php' :
		$nevi = ' &gt; 스팸정책 안내';
		break;
	case '/sboard2/list.php' :
		if($_GET['db'] == "faq") $nevi = ' &gt; FAQ';
		break;
	case '/join/7.php' :
		$nevi = ' &gt; 관리자 문의';
		break;
	case '/members/8.php' :
		$nevi = ' &gt; 관련사이트';
		break;
	case '/join/9.php' :
		$nevi = ' &gt; 티켓 안내';
		break;
	case '/sitemap/index.php' :
		$nevi = ' &gt; 사이트맵';
		break;
	default :
}
?>
<table border="0" cellpadding="0" cellspacing="0" width="1004" bgcolor="white" style="line-height:100%; margin-top:0; margin-bottom:0;">
	<tr>
		<td height="24" bgcolor="#574F43" background="/img/home-menu-title.gif">
			<p style="line-height:100%; margin-top:0; margin-bottom:0;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size:10pt; color:white">&nbsp;<a href="/"><font color="#FFFFFF">HOME</font></a> 
			&gt; 세이버스가이드 <font color="#FFFFFF"><?php echo $nevi;?></font></span></p>
		</td>
	</tr>
</table>
<table border="0" cellpadding="0" cellspacing="0" width="1004" bgcolor="white" style="line-height:100%; margin-top:0; margin-bottom:0;">
	<tr>
		<td width="874" height="208">
			<table style="line-height:100%; margin-top:0; margin-bottom:0;" border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="864">
						<p style="line-height:100%; margin-top:0; margin-bottom:0;"><span style="font-size:10pt;"><img src="/img/menu-title-top.gif" width="874" height="20" border="0"></span></p>
					</td>
				</tr>
			</table>
			<table style="line-height:100%; margin-top:0; margin-bottom:0;" border="0" cellpadding="0" cellspacing="0" width="100%" background="/img/sub-view-box-bg.gif">
				<tr>
					<td width="184">
									<p style="line-height:100%; margin-top:0; margin-bottom:0;"><span style="font-size:10pt;"><img src="/img/menu-title-10.gif" width="184" height="34" border="0"></span></p>
						<table align="right" border="0" cellpadding="0" cellspacing="0" width="158" height="100%" style="line-height:100%; margin-top:0; margin-bottom:0;">
							<tr>
								<td width="158" height="358">
									<p style="line-height:100%; margin-top:0; margin-bottom:0;">&nbsp;</p>
									<p style="line-height:100%; margin-top:0; margin-bottom:0;" align="right"><span style="font-size:10pt;"><script src="/swf/index.php?src=join&width=158&height=358"></script></span></p>
								</td>
							</tr>
							<tr>
								<td width="174">
									<p style="line-height:100%; margin-top:0; margin-bottom:0;"><span style="font-size:10pt;"><img src="/img/sub-menu-img.gif" width="158" height="94" border="0"></span></p>
								</td>
							</tr>
							<tr>
								<td width="174" height="100%">
									<p style="line-height:100%; margin-top:0; margin-bottom:0;"><span style="font-size:10pt;">&nbsp;</span></p>
								</td>
							</tr>
						</table>
					</td>
				  <td valign="top">{{BODY}}</td>
				</tr>
			</table>
			<table style="line-height:100%; margin-top:0; margin-bottom:0;" border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="864">
						<p style="line-height:100%; margin-top:0; margin-bottom:0;"><span style="font-size:10pt;"><img src="/img/sub-view-box-end.gif" width="874" height="20" border="0"></span></p>
					</td>
				</tr>
			</table>
		</td>
		<td height="208" background="/img/sub-view-bg.gif">

			<p style="line-height:100%; margin-top:0; margin-bottom:0;"><span style="font-size:10pt;">&nbsp;</span></p>
		</td>
	</tr>
</table>
<?php include("inc_tail.php") ?>
</body>
</html>
<?php
	// 여기부터 끝까지 복사하여 제작한 사이트 스킨 마지막에 넣으면 됨
	$body=ob_get_contents(); // 버퍼링된 내용을 변수로 받음
	ob_end_clean(); // 버퍼링비움
	$aBody = explode('{{BODY}}',$body,2);
	if($HEADER['html_echo']==1) echo $aBody[0];
	else $SITE['head'] = $aBody[0];
	$SITE['tail'] = $aBody[1];
	unset($body);unset($aBody);
?>