ns4 = (document.layers)? true:false
ie4 = (document.all)? true:false

if (ie4) {
	if (navigator.userAgent.indexOf('MSIE')>0 && navigator.userAgent.substr(navigator.userAgent.indexOf('MSIE')+5,1)>4) {
		ie5 = true;
	} else {
		ie5 = false; }
} else { ie5 = false; }

var x = 0;
var y = 0;
var show = 0;
var sw = 0;
var cnt = 0;
var dir = 1;
if ( (ns4) || (ie4) ) {
	if (ns4) over = document.overDiv
	if (ie4) over = overDiv.style
	document.onmousemove = mouseMove
	if (ns4) document.captureEvents(Event.MOUSEMOVE)
}

function noview() {
	if ( cnt >= 1 ) { sw = 0 };
	if ( (ns4) || (ie4) ) {
		if ( sw == 0 ) {
			show = 0;
			hideObject(over);
		} else {
			cnt++;
		}
	}
}

function view(title,stime,content) {

	txt = '<table width=250 cellpadding=2 cellspacing=1 border=0 bgcolor=#aaaaaa><tr><td bgcolor=#FF9933 align=center><font color="ffffff" size=2 face=돋움>' + title + '</font></td></tr><tr><td bgcolor=FFF6ED><table width=100% cellpadding=2 cellspacing=2 border=0><tr><td align=right><font size=1 face=Arial color=red>' + stime + '</font></td></tr><tr><td height=40 valign=top><font size=2 face=돋움 color=#333333>' + content + '</font></td></tr></table></td></tr></table>';
	
	layerWrite(txt);
	txt="";
	disp();
}

function view_min(title) {
	txt = '<table width=160 cellpadding=4 cellspacing=1 border=0 bgcolor=#aaaaaa><tr><td bgcolor=#FF9933><font size=2 color="ffffff" face=돋움>' + title + '</font></td></tr></table>';

	layerWrite(txt);
	txt="";
	disp_min();
}

function disp() {
	if ( (ns4) || (ie4) ) {
		if (show == 0) 	{
			moveTo(over,x-100,y+18);
			showObject(over);
			show = 1;
		}
	}
}


function disp_min() {
	if ( (ns4) || (ie4) ) {
		if (show == 0) 	{
			showObject(over);
			moveTo(over, x-40 , y - 60);
			show = 1;
		}
	}
}

function mouseMove(e) {
	if (ns4) {x=e.pageX; y=e.pageY;}
	if (ie4) {x=event.x; y=event.y;}
	if (ie5) {x=event.x+document.body.scrollLeft; y=event.y+document.body.scrollTop;}

	if (show) { moveTo(over,x-100,y+18); }
}

function cClick() {
	hideObject(over);
	sw=0;
}

function layerWrite(txt) {
	if(ns4) {
		var lyr = document.overDiv.document
			lyr.write(txt)
			lyr.close()
	} else if(ie4) document.all["overDiv"].innerHTML = txt
}

function showObject(obj) {
	if (ns4) obj.visibility = "show"
	else if (ie4) obj.visibility = "visible"
}

function hideObject(obj) {
	if (ns4) obj.visibility = "hide"
	else if (ie4) obj.visibility = "hidden"
}

function moveTo(obj,xL,yL) {
	obj.left = xL
	obj.top = yL
}