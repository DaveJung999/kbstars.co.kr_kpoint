<?php
#######################################################################################
##																					##
##								일반 사용자 정의 Class 모음							##
##																					##
##																					##
#######################################################################################

/****************************************************************************************
 클래스		: mime_mail
 설명		 : 파일 첨부포함 메일 보내기
 Source		: Professional PHP Programming - 581p.
 작성일		: 2000. 7. 20

 수정		 : 2000. 7. 20
				HTML형식으로 메일 보내는 부분 추가

 예제 - 첨부파일 메일 보내기
 -----------------------------------------------------------------------------------
	include("mime_mail.php");
	$mail = new mime_mail;

	$mail->from	 = $from;
	$mail->to		= $to;
	$mail->name	 = "박선민";
	$mail->subject	= $subject;
	$mail->body	 = $body;
	$mail->html	 = 1;

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

class mime_mail
{
	public $parts;
	public $to;
	public $from;
	public $name;
	public $headers;
	public $subject;
	public $body;
	public $html;



	/****************************************************************************************
	 함수명		: __construct()
	 인자		 : -
	 설명		 : 생성자 함수. Class 생성과 동시에 실행
					변수를 초기화 한다.
	 반환값		: -
	*****************************************************************************************/
	public function __construct()
	{
		$this->parts = [];
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
	 인자		 : $message, $name="", {$ctype}="application/octet-stream"
	 설명		 : 파일 첨부를 한다.
	 반환값		: -
	*****************************************************************************************/
	public function add_attachment($message, $name = "", $ctype = "application/octet-stream")
	{
		$this->parts[] = [
							"ctype" =>  $ctype,
							"message" =>  $message,
							"name" =>  $name
						];
	}

	/****************************************************************************************
	 함수명		: build_message()
	 인자		 : $part
	 설명		 : 본문 내용을 생성한다.
	 반환값		:
	*****************************************************************************************/
	public function build_message($part)
	{
		$message = $part['message'];
		$message = chunk_split(base64_encode($message));
		$encoding = "base64";

		$output = "Content-Type: " . $part['ctype'] . ($part['name'] ? "; name = \"" . $part['name'] . "\"" : "") . "\n";
		$output .= "Content-Transfer-Encoding: {$encoding}\n\n{$message}\n";

		return $output;
	}
	
	/****************************************************************************************
	 함수명		: build_multipart()
	 인자		 : -
	 설명		 :
	 반환값		: -
	*****************************************************************************************/
	public function build_multipart()
	{
		$boundary = "b" . md5(uniqid(time()));
		$multipart = "MIME-Version: 1.0\n";
		$multipart .= "Content-Type: multipart/mixed; boundary = \"{$boundary}\"\n\n";
		$multipart .= "This is a MIME encoded message.\n\n--{$boundary}";

		for($i=sizeof($this->parts)-1; $i>=0; $i--){
			$multipart .= "\n" . $this->build_message($this->parts[$i]) . "--$boundary";
		}

		return $multipart .= "--\n";
	}

	/****************************************************************************************
	 함수명		: get_mail()
	 인자		 : $complete=true
	 설명		 :
	 반환값		: -
	*****************************************************************************************/
	public function get_mail()
	{
		// 이 함수는 send()에서만 사용되므로, 메일 본문과 헤더를 조합한 문자열만 반환하도록 수정
	$HEADERs = '';
		
		if (isset($this->name)){
		$HEADERs .= "From: =?UTF-8?B?".base64_encode($this->name) . "?= <".$this->from. ">\n";
		} else {
		$HEADERs .= "From: ".$this->from."\n";
		}

		if (isset($this->headers)){
		$HEADERs .= $this->headers . "\n";
		}
		
		if (isset($this->body)){
			$ctype = $this->html ? "text/html; charset=UTF-8" : "text/plain; charset=UTF-8";
			$this->add_attachment($this->body, "", $ctype);
			
			$mime_body = $this->build_multipart();
		$HEADERs .= $mime_body;

			return $headers;
		}

		return $headers;
	}

	/****************************************************************************************
	 함수명		: send()
	 인자		 : -
	 설명		 : 메일 보내는 함수
	 반환값		: -
	*****************************************************************************************/
	public function send()
	{
	$HEADERs = $this->get_mail();
		
		// subject에 UTF-8 인코딩 적용
		$encoded_subject = "=?UTF-8?B?".base64_encode($this->subject) . "?=";
		
		// mail 함수 호출. 본문은 빈 문자열로, MIME 본문은 추가 헤더에 포함
		return mail($this->to, $encoded_subject, "", $headers);
	}
}
?>
