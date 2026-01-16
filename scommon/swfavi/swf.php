// <script src="/swf/index.php?src=&width=&height="></script>
document.writeln('<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://active.macromedia.com/flash4/cabs/swflash.cab#version=4,0,0,0" width="<?php echo $width;?>" height="<?php echo $height;?>">');
document.writeln('<param name="movie" value="/swf/<?php echo $src;?>.swf">');
document.writeln('<param name="play" value="true">');
document.writeln('<param name="loop" value="true">');
document.writeln('<param name="quality" value="high">');
document.writeln('<embed src="/swf/<?php echo $src;?>.swf" play="true" loop="true" quality="high" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash" width="<?php echo $width;?>" height="<?php echo $height;?>"></embed>');
document.writeln('</object>');