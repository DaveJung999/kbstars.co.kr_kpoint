document.writeln('<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://active.macromediacom/flash4/cabs/swflash.cab#version=4,0,0,0" width="<?=$_GET['width']
?>" height="<?=$_GET['height']
?>">');
document.writeln('<param name="movie" value="<?=$_GET['src']
?>">');
document.writeln('<param name="play" value="true">');
document.writeln('<param name="loop" value="true">');
document.writeln('<param name="quality" value="high">');
document.writeln('<embed src="<?=$_GET['src']
?>" play="true" loop="true" quality="high" pluginspage="http://www.macromediacom/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash" width="<?=$_GET['width']
?>" height="<?=$_GET['height']
?>"></embed>');
document.writeln('</object>');