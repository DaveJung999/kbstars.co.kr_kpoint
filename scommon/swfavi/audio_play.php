document.writeln('<audio controls>');
document.writeln('  <source src="<?php echo $_GET['src'];
?>" >');
document.writeln('Your browser does not support the audio element.');
document.writeln('</audio>');
