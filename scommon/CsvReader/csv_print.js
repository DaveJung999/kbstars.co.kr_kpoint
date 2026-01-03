
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}

function printWindow() 
{ 
   //factory.printing.header          = "This is MeadCo"; // 머리말을 설정합니다.
   //factory.printing.footer          = "Printing by ScriptX 5.x"; // 꼬리말을 설정합니다.
   //factory.printing.portrait        = false; // 세로로 출력할것인지 가로로 출력할것인지 설정합니다. true:세로 false:가로
   //factory.printing.leftMargin      = 1.0;   // 좌측여백
   //factory.printing.topMargin       = 1.0;   // 상단여백
   //factory.printing.rightMargin     = 1.0;   // 우측여백
   //factory.printing.bottomMargin    = 1.0;   // 하단여백
   //factory.printing.copies          = 1;     한장만 출력하라는뜻
   factory.printing.printBackground = true;  // 백그라운드까지 출력
   factory.printing.Print(true, window);     // 현재윈도를 프린트하는뜻(window대신에 frame을 지정해주면 해당 프레임을 출력합니다.)
} 

function content_print() {
	var obj = null;
	var html = "<html xmlns='http://www.w3.org/1999/xhtml' lang='ko'><head><title>인쇄하기</title>";
	var obj_styles = document.styleSheets;
//	var odiv = document.getElementsByTagName("div");
	var odiv = document.getElementsByTagName("td");
	var oscript = document.getElementsByTagName("script");
	var shref, shtml;

	html += "<base href='http://"+ document.location.host +"' />";
	html += "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";

	for (var i = 0; i < oscript.length; i++) {
		shref = oscript.item(i).getAttribute("src");
		shtml = oscript.item(i).innerHTML;
		if(shref == "" && shtml.length > 100)
			html += "<script language='javascript' type='text/javascript' CHARSET='euc-kr'>"+ shtml +"</script>";
		else if(shref != "")
			html += "<script language='javascript' type='text/javascript' CHARSET='euc-kr' src='http://"+ document.location.host + shref + "'></script>";
	}
	html += "<script language='javascript' type='text/javascript' CHARSET='euc-kr'>function content_print() {document.body.style.background='url(\"\")';window.print();}</script>";
	for (var i = 0; i < obj_styles.length; i++ )
		html += "<link rel='stylesheet' type='text/css' href='"+ obj_styles.item(i).href + "' />";
	
	////////////////////////////////////////////////////////////////////////////////////
	//		opa95 [2009-09-24]
	//		- xscript를 이용하여 프린트하기위해 객체를 생성합니다.
	//		- smsx.cab 파일을 해당경로에 넣어줘야 합니다.
	html += "<object id='factory' style='display:none' codeBase='/smsx.cab#Version=6,2,433,14' classid='clsid:1663ed61-23eb-11d2-b92f-008048fdd814' viewastext></object>";
	html += "</head><body>"
	for(i = 0; i < odiv.length; i++) {
		if(odiv.item(i).className == "content")
			obj = odiv.item(i);
	}
	if(obj == null) {
		alert('메인페이지는 인쇄 할 수 없습니다.');
		return false;
//		obj = document.body;
	}
	html += obj.innerHTML;
	html += "</body></html>";
	html += "<script language='javascript'>printPage();</script>";
//	html += "<script language='javascript'>document.location.reload();< /script>";
	var win = window.open("about:blank");//, "", "width=680, scrollbars=yes");
	win.document.write(html);
	win.document.location.reload();
//	else win.onload = function() { win.print(); win.close(); };
}

////////////////////////////////////////////////////////////////////////////////////
//		opa95 [2009-09-24]
//		- 생성된 페이지를 출력합니다.

function printPage(){
	document.getElementById("factory").printing.header = ""; // 머릿말설정
	document.getElementById("factory").printing.footer = ""; // 꼬릿말설정
	document.getElementById("factory").printing.portrait = true; // 출력방향 설정 : true-가로, false-세로
	document.getElementById("factory").printing.leftMargin = 7.0; // 왼쪽 여백 설정
	document.getElementById("factory").printing.topMargin = 5.0; // 위쪽 여백 설정
	document.getElementById("factory").printing.rightMargin = 10.0; // 오른쪽 여백 설정
	document.getElementById("factory").printing.bottomMargin = 0.0; // 머릿말설정
	//document.getElementById("factory").printing.printBackground = true;  // 백그라운드까지 출력
	document.getElementById("factory").printing.Print(true, window);

}

