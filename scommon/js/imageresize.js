/************************************** 
자바스크립트를 이용한 이미지 크기 재조정
04/05/20 박선민 공식 사용

출처: http://www.phpschool.com/bbs2/inc_view.html?id=10728&code=tnt2

<> 사용법 : gg(this, 400, 400, 1) 
<script LANGUAGE="JavaScript" src="/scommon/js/imageresize.js" type="Text/JavaScript"></script>
<!-- 이미지경로, 가로크기제한, 세로크기제한, 원본새창링크 -->
<img src='tt.jpg' onload='gg(this, 400, 400, 1)'>
***************************************/

function imgRsize(img, rW, rH){
	var iW = img.width;
	var iH = img.height;
	var g = new Array;
	if(iW < rW && iH < rH) { // 가로세로가 축소할 값보다 작을 경우
		g[0] = iW; 
		g[1] = iH; 
	} else {
		if(img.width > img.height) { // 원크기 가로가 세로보다 크면
			g[0] = rW;
			g[1] = Math.ceil(img.height * rW / img.width);
		} else if(img.width < img.height) { //원크기의 세로가 가로보다 크면
			g[0] = Math.ceil(img.width * rH / img.height);
			g[1] = rH;
		} else {
			g[0] = rW;
			g[1] = rH;
		}
		if(g[0] > rW) { // 구해진 가로값이 축소 가로보다 크면
			g[0] = rW;
			g[1] = Math.ceil(img.height * rW / img.width);
		}
		if(g[1] > rH) { // 구해진 세로값이 축소 세로값가로보다 크면
			g[0] = Math.ceil(img.width * rH / img.height);
			g[1] = rH;
		}
	}
	g[2] = img.width; // 원사이즈 가로
	g[3] = img.height; // 원사이즈 세로
	return g;
}

function gg(img, ww, hh, aL){
	var tt = imgRsize(img, ww, hh);
	img.width = tt[0];
	img.height = tt[1];
	if(aL){
		img.onclick = function(){
			wT = Math.ceil((screen.width - tt[2])/2.6); // 클라이언트 중앙에 이미지위치.
			wL = Math.ceil((screen.height - tt[3])/2.6);
			mm = window.open("", 'viewOrig', 'width='+tt[2]+',height='+tt[3]+',top='+wT+',left='+wL);
			var doc = mm.document;
			doc.body.style.margin = 0; // 마진제거
			doc.body.style.cursor = "hand";
			var previewimg = doc.createElement("img");
			previewimg.src = img.src;
			doc.body.appendChild(previewimg);
			doc.body.onmousedown = function(){ mm.close();}
			doc.title = 'NUX';
		}
		img.style.cursor = "hand";
	}
}