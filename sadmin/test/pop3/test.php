<?php
/*
 * test_pop3.html
 *
 * @(#) $Header: /cvsroot/PHPlibrary/test_pop3.html,v 1.2 1999/06/27 19:05:49 mlemos Exp $
 *
 */ ?><HTML>
<HEAD>
<TITLE>Test for Manuel Lemos's PHP POP3 class</TITLE>
</HEAD>
<BODY>
<?php
require("pop3.php");

	$user="sponsor2";
	$password="tjsalsdl";
	$apop=0;
	$pop3_connection=new pop3_class;
	$pop3_connection->hostname="new21.com";
	if(($error=$pop3_connection->Open())=="")
	{
		echo "<PRE>Connected to the POP3 server &quot;{$pop3_connection}->hostname&quot;.</PRE>\n";
		if(($error=$pop3_connection->Login($user,$password,$apop))=="")
		{
			echo "<PRE>User &quot;{$user}&quot; logged in.</PRE>\n";
			if(($error=$pop3_connection->Statistics(&$messages,&$size))=="")
			{
				echo "<PRE>There are {$messages} messages in the mail box with a total of $size bytes.</PRE>\n";
				$result=$pop3_connection->ListMessages("",0);
				if(GetType($result)=="array")
				{
					for(Reset($result),$message=0;$message<count($result);Next($result),$message++)
						echo "<PRE>Message ",Key($result)," - ",$result[Key($result)]," bytes.</PRE>\n";
					$result=$pop3_connection->ListMessages("",1);
					if(GetType($result)=="array")
					{
						for(Reset($result),$message=0;$message<count($result);Next($result),$message++)
							echo "<PRE>Message ",Key($result),", Unique ID - \"",$result[Key($result)],"\"</PRE>\n";
						if($messages>0)
						{
							if(($error=$pop3_connection->RetrieveMessage(1,&$headers,&$body,2))=="")
							{
								echo "<PRE>Message 1:\n---Message headers starts below---</PRE>\n";
								for($line=0;$line<count($headers);$line++)
									echo "<PRE>",HtmlSpecialChars($headers[$line]),"</PRE>\n";
								echo "<PRE>---Message headers ends above---\n---Message body starts below---</PRE>\n";
								for($line=0;$line<count($body);$line++)
									echo "<PRE>",HtmlSpecialChars($body[$line]),"</PRE>\n";
								echo "<PRE>---Message body ends above---</PRE>\n";
								if(($error=$pop3_connection->DeleteMessage(1))=="")
								{
									echo "<PRE>Marked message 1 for deletion.</PRE>\n";
									if(($error=$pop3_connection->ResetDeletedMessages())=="")
									{
										echo "<PRE>Resetted the list of messages to be deleted.</PRE>\n";
									}
								}
							}
						}
						if($error==""
						&& ($error=$pop3_connection->Close())=="")
							echo "<PRE>Disconnected from the POP3 server &quot;{$pop3_connection}->hostname&quot;.</PRE>\n";
						
					}
					else
						$error=$result;
				}
				else
					$error=$result;
			}
		}
	}
	if($error!="")
		echo "<H2>Error: ",HtmlSpecialChars($error),"</H2>";
?>

</BODY>
</HTML>

