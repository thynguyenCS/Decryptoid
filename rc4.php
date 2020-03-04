<?php
/**
* @author: Thy Nguyen
RC4 is a stream cipher and variable length key algorithm. This algorithm encrypts one byte at a time (or larger units on a time).
Ref: https://www.researchgate.net/figure/RC4s-processing-flowchart_fig1_261455297
*/
session_start(); 
require 'connect_database.php';
function mysql_entities_fix_string($conn, $string) {
    return htmlentities(mysql_fix_string($conn, $string));
}
function mysql_fix_string($conn, $string) {
    if (get_magic_quotes_gpc()) $string = stripslashes($string);
        return $conn->real_escape_string($string);
}
define('SIZE', 256);
function rc4_encrypt($input_text, $key){
	$S = range(0, SIZE-1);
	$key_arr = array();
	$input_arr = array();
	// convert each character in key/input into ASCII value
	for ($i = 0; $i < strlen($key); $i++) {
		$key_arr[] = ord($key{$i});
	}
	for ( $i = 0; $i < strlen($input_text); $i++ ) {
		$input_arr[] = ord($input_text{$i});
	}
	//KSA: Key-Scheduling Algorithm Initialization
	$len = strlen($key);
	$i = $j = 0;
	for($iter = 0; $iter <SIZE; $iter++ ){
		$j = ($j+ $key_arr[$i] + $S[$iter]) % SIZE;
		//swap
		$tmp = $S[$iter];
		$S[$iter] = $S[$j];
		$S[$j] = $tmp;
		$i = ($i + 1) % $len;
	}
	//PRGA: Pseudo random generation algorithm (Stream Generation)
	$len = strlen($input_text);
	$i = $j = 0;
	for ($iter = 0; $iter < $len; $iter++) {
		$i = ($i + 1) % SIZE;
		$j = ($j + $S[$i]) % SIZE;
		//swap
		$tmp = $S[$i];
		$S[$i] = $S[$j];
		$S[$j] = $tmp;
		$input_arr[$iter] ^= $S[($S[$i] + $S[$j]) % SIZE];
	}
// convert to string
	$output_text = "";
	for ( $i = 0; $i < $len; $i++ ) {
		$output_text .= chr($input_arr[$i]);
}
	return bin2hex($output_text);
}

function rc4_decrypt($input_text, $key){
	return hex2bin(rc4_encrypt(hex2bin($input_text), $key));
}
// echo (rc4_encrypt("attac1canvas* tonight", "1f"))."<br>";
// echo rc4_decrypt("f3c6a3e4680d11d1512dd4c7f0fbce5da8142b633c", '1f');

$conn = new mysqli($hn, $un, $pw, $db);
if($conn->connect_error) die('Cannot connect to database');
$table = "ciphers";
$query = "SELECT * FROM $table";
$result = $conn->query($query);
if(isset($_SESSION['username'])) {
  $username = $_SESSION['username'];
  $password = $_SESSION['password'];
  $email = $_SESSION['email'];
  
echo <<<_END
<html><head><script>
function validate_encrypt() {
  let plaintext = document.forms["encrypt"]["en-text"];  
  let key = document.forms["encrypt"]["en-key"];       
  validate(plaintext, key);
}
function validate_decrypt() {
  let plaintext = document.forms["decrypt"]["de-text"];  
  let key = document.forms["decrypt"]["de-key"];       
  validate(plaintext, key);
}
function validate(plaintext, key) {
  if(plaintext.value =="" || key.value ==""){
      window.alert("Please fill all the fields before submitting\\n");
      return false;
  } 
  else if ((!/^[a-zA-Z0-9\s]+$|^#$/.test(plaintext.value)) || (!/^[a-zA-Z0-9\s]+$|^#$/.test(key.value))) {
      window.alert("Only alphanumberics and space(s) are allowed for RC4, or enter # to submit file");
      return false;
  }
  return true;   
}
</script>
<title>RC4</title></head><body>
<h2 align="center"><font color="blue"> RC4</font></h2>
<div class="main">
  <table border="1" align=center bgcolor="#ADD8E6">
    <tr>
    <td align="center"><strong>Encrypt</strong></td>
     <td align="center"><strong>Decrypt</strong></td>
    </tr>
    <tr> 
      <td>    
      <form name="encrypt" method = 'post' action='rc4.php'enctype='multipart/form-data' onsubmit="return validate_encrypt();">
        Enter plain text here <br> 
        <input type='text' name='en-text' placeholder='Plain text goes here or # for input file' size="50"><br><br>
        OR <br><br>
        Select File:<br> <input type = 'file' name ='en-filename' size = '20' accept='.txt'><br><br>
        Key:<br>
        <input type='text' name='en-key' placeholder='Alphanumerics only' size="50"><br><br>
        <div align="center"><input type = 'submit' name='en' value ='Encrypt'></div>
      </form>
      </td>
      <td>      
      <form name="decrypt" method = 'post' action='rc4.php'enctype='multipart/form-data' onsubmit="return validate_decrypt();">
        Enter cipher here <br> 
        <input type='text' name='de-text' placeholder='Cipher text goes here or # for input file' size="50"><br><br>
        OR <br><br>
        Select File: <br><input type = 'file' name ='de-filename' size = '50' accept='.txt'><br><br>
        Key:<br>
        <input type='text' name='de-key' placeholder= 'Alphanumerics only' size="50"><br><br>
        <div align="center"><input type = 'submit' name='dec' value ='Decrypt'></div>
      </form>
      </td>
    </tr>    
_END;
$input_text="";
$output_text = "";
$cipher_used = "";
// when user choose to encrypt
if(isset($_POST['en'])){
  $cipher_used = "RC4 Encryption";
  $enkey = mysql_entities_fix_string($conn, $_POST['en-key']);
  if(preg_match("/^[a-zA-Z0-9\s]+$/", $_POST['en-text']))
    $input_text = mysql_entities_fix_string($conn, $_POST['en-text']);
  if ($_FILES and $_POST['en-text'] == "#") {
    $filename = $_FILES['en-filename']['name'];
    move_uploaded_file($_FILES['en-filename']['tmp_name'], $filename);
    if (!file_exists($filename)){
        echo "<script type='text/javascript'>alert('Please submit a file before submitting');</script>";
        die("File not found");
    }
  if(!preg_match("/^[a-zA-Z0-9\s]+$/", file_get_contents($filename)))
      die("<script type='text/javascript'>alert('Only space(s) and alphanumerics are allowed in RC4');</script>");
  $input_text = mysql_entities_fix_string($conn, file_get_contents($filename));
  }
$output_text = rc4_encrypt($input_text, $enkey);
// Display result
echo "<tr><td><textarea name='en-result' rows='3', cols='50'>\n";
echo "Encrypted cipher: ".htmlspecialchars($output_text)."\n";
echo "</textarea></td>";
echo "<td><textarea name='de-result' rows='3', cols='50' placeholder='Decrypted plain text is displayed here' disabled>\n";
echo "</textarea></td><tr>";
}
// when user choose to decrypt
else if (isset($_POST['dec'])){
  $cipher_used="RC4 Descryption";
  $dekey = mysql_entities_fix_string($conn, $_POST['de-key']);
  if(preg_match("/^[a-zA-Z0-9\s]+$/", $_POST['de-text']))
    $input_text = mysql_entities_fix_string($conn, $_POST['de-text']);
  if ($_FILES and $_POST['de-text'] == "#") {
    $filename = $_FILES['de-filename']['name'];
    move_uploaded_file($_FILES['de-filename']['tmp_name'], $filename);
    if (!file_exists($filename)){
      echo "<script type='text/javascript'>alert('Please submit a file before submitting');</script>";
      die("File not found");
    }
if(!preg_match("/^[a-zA-Z0-9\s]+$/", file_get_contents($filename)))
    die("<script type='text/javascript'>alert('Only space(s) and alphanumerics are allowed in RC4');</script>");
  $input_text = mysql_entities_fix_string($conn, file_get_contents($filename));
}
$output_text = rc4_decrypt($input_text, $dekey);
// Display result
echo "<tr><td><textarea name='en-result' rows='3', cols='50' placeholder='Encrypted cipher is displayed here' disabled></textarea></td>\n";
echo "<td><textarea name='de-result' rows='3', cols='50'>\n";
echo "Decrypted plaintext: ".htmlspecialchars($output_text)."\n";
echo "</textarea></td><tr>";
}
echo <<<_END
</table></div><br>
<div align="center">
<a href=first_page.php>Go Back To Main Page </a><br><br>
<a href=decryptoid1.php>Choice of Ciphers</a><br><br>
<a href=logout.php>Log Out</a></div></body></html>
_END;
//add the data into the database
//username, input text, cipher used, and timestamp
//if table not exist, create table
if(!$result){
  $query = "CREATE TABLE $table(
                          username VARCHAR(32) NOT NULL,
                          input_text LONGTEXT NOT NULL,
                          cipher_used VARCHAR(32) NOT NULL,
                          timesp TIMESTAMP NOT NULL
                          )";
$result = $conn->query($query);
if (!$result) die("Cannot connect to database 002");  
}
if($input_text != "" and $cipher_used != ""){
  $query="INSERT INTO $table VALUES ('$username', '$input_text', '$cipher_used', NULL)";
  $result = $conn->query($query);
  if (!$result) die("Cannot connect to database 003"); 
}

}//session is set
// if not, lead to main page
else header("location: first_page.php");

?>