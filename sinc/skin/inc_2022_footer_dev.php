			<div id="copyright_bg" class="clearfix">
				<div id="copyright_cont" class="clearfix">
					<div id="copylogo" class="clearfix">
						<img id="image" src="/images/2021/new/copyright_logo.png" class="image" />
					</div>
					<div id="copyright_menu" class="clearfix">
						<p id="copyright_txt"><a href="/kbstars/2022/d07/06.php?mNum=0706" id="0706" class="white">사이트맵</a><br /></p>
						<p id="copyright_txt"><a href="/kbstars/2022/d07/02.php?mNum=0702" id="0702" class="white">개인정보처리방침</a><br /></p>
						<p id="copyright_txt"><a href="/kbstars/2022/d07/01.php?mNum=0701" id="0701" class="white">이용약관</a><br /></p>
						<p id="copyright_txt"><a href="/kbstars/2022/d07/04.php?mNum=0704" id="0704" class="white">저작권관련안내</a><br /></p>
						<p id="copyright_txt2">
							<a href="http://www.kbstarsvc.co.kr" target="_blank" class="white" id="0704">
								<img src="/images/2018/f_banner/copy_rights_banner1.png" alt="KB손해보험 배구단" width="48" height="34" title="KB손해보험 배구단"/>
							</a><br />
						</p>
						<p id="copyright_txt2">
							<a href="https://omoney.kbstar.com/quics?page=C018596#loading" target="_blank" class="white" id="0704">
								<img src="/images/2021/new/copy_rights_banner2.png" alt="KB국민은행 스타즈 사격단" width="48" height="34" title="KB국민은행 스타즈 사격단"/>
							</a><br />
						</p>
					</div>
					<div id="copyright" class="clearfix">
						<p id="copyright_txt1">
<?php
						//=======================================================
						// Start... (DB 작업 및 display)
						//=======================================================
						$sql_addr = "select * from new21_board2_contents_2016 where uid = 71 ";
						$list_addr = db_arrayone($sql_addr);
						// 변수 존재 여부 확인 후 출력
						echo isset($list_addr['content']) ? $list_addr['content'] : '';
?>
						</p>
					</div>
				</div>
			</div>
		</div>
	</body>
	<!-- 네비게이션 -->
	<script type="text/javascript" src="/share/js/2016/navigation.js"></script>
	<!-- 네비게이션 -->
</html>
