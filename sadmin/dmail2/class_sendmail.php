
<?php
#######################################################################################
##																					##
##								일반 사용자 정의 Class 모음					##
##																					##
#######################################################################################

/****************************************************************************************
 클래스		: mime_mail
 설명		: 파일 첨부포함 메일 보내기
 Source		: Professional PHP Programming - 581p.
 작성일		: 2000 . 7 . 20

 수정		: 2000 . 7 . 20
			HTML형식으로 메일 보내는 부분 추가

 예제 - 첨부파일 메일 보내기
 -----------------------------------------------------------------------------------
	include("mime_mail.php");
	$mail = new mime_mail;

	$mail->from		= $from;
	$mail->to		= $to;
	$mail->name		= "박선민";
	$mail->subject	= $subject;
	$mail->body		= $body;
	$mail->html		= 1;

	if($userfile_name && $userfile_size){
		$filename=basename($userfile_name);
		$fd = fopen($userfile, "r");
		$data = fread($fd, $userfile_size);
		fclose($fd);

		$mail->add_attachment($data, $filename, $userfile_type);
	}	

	if($userfile2_name && $userfile2_size){
		$filename=basename($userfile2_name);
		$fd = fopen($userfile2, "r");
		$data = fread($fd, $userfile2_size);
		fclose($fd);

		$mail->add_attachment($data, $filename, $userfile2_type);
	}	

	if($userfile3_name && $userfile3_size){
		$filename=basename($userfile3_name);
		$fd = fopen($userfile3, "r");
		$data = fread($fd, $userfile3_size);
		fclose($fd);

		$mail->add_attachment($data, $filename, $userfile3_type);
	}	
	$mail->send();
	exit;
*****************************************************************************************/

Class mime_mail
{
	var $parts;
	var $to;
	var $from;
	var $name;
	var $headers;
	var $subject;
	var $body;
	var $html;

	/****************************************************************************************
	함수명		: MimeMail()
	인자		: -
	설명		: 생성자 함수 . Class 생성과 동시에 실행
				변수를 초기화 한다.
	반환값		: -
	*****************************************************************************************/
	Function mime_mail() 
	{
		$this->parts = array();
		$this->to = "";
		$this->from = "";
		$this->name = "";
		$this->subject = "";
		$this->body = "";
		$this->headers = "";
		$this->html = 0;
	}

	/****************************************************************************************
	함수명		: add_attachment()
	인자		: $message, $name="", $ctype="application/octet-stream"
	설명		: 파일 첨부를 한다.
	반환값		: -
	*****************************************************************************************/
	Function add_attachment($message, $name="", $ctype="application/octet-stream")
	{
		$this->parts[] = array(
							"ctype" =>	$ctype,
							"message" =>	$message,
							"encode" =>	$encode,
							"name" =>	$name
							);	
	}

	/****************************************************************************************
	함수명		: build_message()
	인자		: $part
	설명		: 본문 내용을 생성한다.
	반환값		: 
	*****************************************************************************************/
	Function build_message($part) 
	{
		$message = $part['message'];
		$message = chunk_split(base64_encode($message));
		$encoding = "base64";

		return "Content-Type: ".$part['ctype'].($part['name']? "; name = \"".$part['name']."\"" : "")
 . "\nContent-Transfer-Encoding: {$encoding}\n\n{$message}\n";
	}
	
	/****************************************************************************************
	함수명		: build_multipart()
	인자		: -
	설명		: 
	반환값		: -
	*****************************************************************************************/
	Function build_multipart() 
	{
		$boundary = "b".md5(uniqid(time()));
		$multipart = "Content-Type: multipart/mixed; boundary = {$boundary}\n\n";
		$multipart .= "This is a MIME encoded message.\n\n--{$boundary}";

		for($i=sizeof($this->parts)-1; $i>=0; $i--){
			$multipart .= "\n".$this->build_message($this->parts[$i]) . "--$boundary";
		}

		return $multipart .= "--\n";
	}

	/****************************************************************************************
	함수명		: get_mail()
	인자		: $complete=true
	설명		: 
	반환값		: -
	*****************************************************************************************/
	Function get_mail($complete=true)
	{
		$mime = "";

		if(isset($this->from)){
			$mime .= "From: ".$this->name." <".$this->from . ">\n";
		}

		if(isset($this->headers)){
			$mime .= $this->headers . "\n";
		}

		if($complete){
			if(isset($this->to)){
				$mime .= "To: {$this}->to\n";
			}

			if(isset($this->subject)){
				$mime .= "Subject: {$this}->subject\n";
			}
		}

		if(isset($this->body)){
			if($this->html){
				$ctype = "text/html";
			} else {
				$ctype = "text/plain";
			}
			$this->add_attachment($this->body, "", $ctype);
			$mime .= "MIME-Version: 1.0\n".$this->build_multipart();

			return $mime;
		}
	}

	/****************************************************************************************
	함수명		: send()
	인자		: -
	설명		: 메일 보내는 함수
	반환값		: -
	*****************************************************************************************/
	Function send()
	{
		$mime = $this->get_mail(false);
		mail($this->to, $this->subject, "", $mime);
	}
} 

?>
