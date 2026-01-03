// 04/04/10 박선민 공식사용
// <script LANGUAGE="JavaScript" src="/scommon/js/formatnum.js" type="Text/JavaScript"></script>
// <SCRIPT LANGUAGE="JavaScript">document.write(formatNum('22.293'));</SCRIPT>
function formatNum(str){
	var tmpStr = ""+str;
	var vReturn = "";

	// 숫자가 아니라면 그냥 리턴
	var regNum =/[0-9]+$/; 
	if(!regNum.test(str)){
		return str;
	}

	while(1){
		if(tmpStr.length < 4){
			vReturn = vReturn + tmpStr;
			break;
		}else{
			if(tmpStr.length%3 == 0){
				vReturn = vReturn + tmpStr.substring(0,3) + ",";
				tmpStr = tmpStr.substring(3,tmpStr.length);
			}else{
				vReturn = vReturn + tmpStr.substring(0,tmpStr.length%3) + ",";
				tmpStr = tmpStr.substring(tmpStr.length%3,tmpStr.length);
			}
		}
	}

	return vReturn;
}