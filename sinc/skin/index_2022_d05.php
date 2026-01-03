<?php
//=======================================================
// 설 명 : 사이트의 HTML 해더와 테일부분 예시 (index_example.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/29
// Project: sitePHPbasic
// ChangeLog
//		DATE	수정인		수정 내용
// -------- ------ --------------------------------------
// 05/01/29 박선민 마지막 수정
//=======================================================
/*
<사이트 전체스킨 만드는 법>
1. 사이트의 반복되는 해더와 테일 부분을 HTML로 만든 이후,
본문에 들어갈 자리에 {{BODY}} 를 넣습니다.
2. 스킨의 맨 위부분에 ob_start();인 php 소스 한줄을 넣습니다.
3. 스킨의 맨 아래에 $body=ob_get_contents();부터 시작한 10줄의 php 소스를 넣습니다.
4. /skin 드렉토리 밑에 index_????.php 형태로 저장합니다.
????는 영문자로시작하여 영문자숫자로 구성되어야 하며, ????이 앞으로 사용할 사이트 스킨 이름입니다.
*/

// PHP 출력 버퍼링을 시작합니다.
// 이 이후의 모든 출력 내용은 버퍼에 저장됩니다.
ob_start();

// 상단 헤더와 서브메뉴 파일을 포함합니다.
// 파일이 없을 경우 오류가 발생할 수 있습니다.
include("inc_2022_header_d05.php");
include("inc_2022_submenu_d05.php"); 
?>
<!-- 메인 콘텐츠 영역을 감싸는 새로운 HTML 구조 -->
		<div style="width:100%; text-align:center;">
			<div style="width:100%;align-content:center;margin:0 auto;display:inline-block;">
				<div id="sub_contents_bg" class="clearfix">
					<div id="sub_contents" class="clearfix">
{{BODY}}
					</div>
				</div>
			</div>
		</div>
<?php
// 하단 푸터 파일을 포함합니다.
// 파일이 없을 경우 오류가 발생할 수 있습니다.
include("inc_2022_footer.php");

// 여기부터 끝까지 복사하여 제작한 사이트 스킨 마지막에 넣으면 됨
// 버퍼링된 내용을 $body 변수에 저장합니다.
$body = ob_get_contents();

// 버퍼링을 끝내고 버퍼의 내용을 비웁니다.
ob_end_clean();

// $body 내용을 {{BODY}}를 기준으로 두 부분으로 나눕니다.
// $aBody['0']에는 헤더 부분, $aBody['1']에는 푸터 부분이 저장됩니다.
$aBody = explode('{{BODY}}', $body, 2);

// $HEADER['html_echo']가 1이면 헤더 부분을 바로 출력하고,
// 그렇지 않으면 $SITE['head'] 변수에 저장합니다.
// 이 부분은 사용자 정의 변수에 따라 동작이 달라질 수 있습니다.
if (isset($HEADER['html_echo']) && $HEADER['html_echo'] == 1){
	echo $aBody['0'];
} else {
	$SITE['head'] = $aBody['0'];
}

// 푸터 부분을 $SITE['tail'] 변수에 저장합니다.
$SITE['tail'] = $aBody['1'];

// 사용된 변수를 메모리에서 해제합니다.
unset($body);
unset($aBody);
?>
