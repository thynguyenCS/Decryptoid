<?php
/*
 * Author: Thy Nguyen
 * The first page after the user log in, display the choices of ciphers
 */
echo <<<_END
<html><head><title>Decryptoid Page</title></head>
<body>
<h2>Select a cipher</h2>
		<input id = "simple" type="radio" name="cipherChoice" value="SIMPLE_SUBSTITUTION" onclick="window.location.href='substitution.php';">
		<label for="simple">Simple Substitution</label><br>
	<input id = "double" type="radio" name="cipherChoice" value="DOUBLE_TRANSPOSITION" onclick="window.location.href='double_trans.php';">
	<label for="double">Double Transposition</label><br>
	<input id = "rc4" type="radio" name="cipherChoice" value="RC4" onclick="window.location.href='rc4.php';">
	<label for="rc4">RC4</label><br><br>
	<a href='first_page.php'>Main Page</a><br><br>
	<a href='logout.php'>Log Out</a>
</body></html>
_END;
?>