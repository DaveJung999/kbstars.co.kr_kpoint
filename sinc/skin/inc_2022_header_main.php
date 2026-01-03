<?php
include("inc_2022_header.php"); 
?>
	<link rel="stylesheet" type="text/css" href="/share/css/2017/index.css" />
	<link rel="stylesheet" type="text/css" href="/share/css/2017/imgslider.css" />
	<!-- slide_2(선수 슬라이드 -->

	<!-- bxSlider Javascript file -->
	<script type="text/javascript" src="/share/js/2016/jquery.bxslider.min.2017.js"></script>
	<!-- bxSlider CSS file -->
	<script type="text/javascript">
		$(document).ready(function(){
			$('.bxslider').bxSlider({
				auto: true,
				autoControls:	false,
				randomStart : true,
				pagerCustom: '#bx-pager'
				
			});
		});
	</script>
	<!-- /////////slide_2(선수 슬라이드) -->

	<div id="primaryContainer" class="primaryContainer clearfix" style="overflow:auto;">
		<div id="main" class="clearfix">
			<div id="main_top" class="clearfix">
				<?php
				include("inc_2022_header_kbsns.php"); 
				?>
			</div>
			<div id="navi" class="clearfix">
				<?php
				include("inc_2022_topmenu.php"); 
				?>
			</div>
			<div id="visual" class="clearfix">
				<?php
				include("inc_2022_visual_main.php"); 
				?>
			</div>
		</div>
		