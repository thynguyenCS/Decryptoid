<?php
/**
* @author: Thy Nguyen
Simple substitution: replace each plaintext letter with another
Encrypting/decrypting by shift right/left by a desired number of shifting
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
define('SIZE', 26); //The size of the alphabet
define('ALPHABET','abcdefghijklmnopqrstuvwxyz'); 
function substitution_encrypt($plaintext, $shamt) {
  /**
  Encrypting the input plaintext by shift right a shamt in the alphabet
  */  
  while($shamt > SIZE) //trim down any cycle shifting
    $shamt -= SIZE;
  $ciphertext ="";
  for($i = 0; $i < strlen($plaintext); ++$i){
      $pos = stripos(ALPHABET, $plaintext[$i]) + $shamt;
      if($pos >'Z')
          $pos = $pos % SIZE;
      $ciphertext .= ALPHABET[$pos];
  }
  return strtoupper($ciphertext);
}

function substitution_decrypt($plaintext, $shamt) {
  /**
  Decrypting the input plaintext by shift left a shamt in the alphabet
  */
  while($shamt > SIZE) //trim down any cycle shifting
    $shamt -= SIZE;
  $ciphertext ="";
  for($i = 0; $i < strlen($plaintext); ++$i){
      $pos = stripos(ALPHABET, $plaintext[$i]) - $shamt;
      if($pos >'Z')
          $pos = $pos % SIZE;
      $ciphertext .= ALPHABET[$pos];
  }
  return strtoupper($ciphertext);
}
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
  let shift_amt = document.forms["encrypt"]["en-shamt"];       
  validate(plaintext, shift_amt);
}
function validate_decrypt() {
  let plaintext = document.forms["decrypt"]["de-text"];  
  let shift_amt = document.forms["decrypt"]["de-shamt"];       
  validate(plaintext, shift_amt);
}
function validate(plaintext, shift_amt) {
  if(plaintext.value =="" || shift_amt.value ==""){
      window.alert("Please fill all the fields before submitting\\n");
      return false;
  } 
  else if (!/^[a-zA-Z\s]+$|^#$/.test(plaintext.value)) {
      window.alert("Only alphabets and space(s) are allowed for simple substitution, or enter # to submit file");
      return false;
  }
  else if(!/^\d$/.test(shift_amt.value)){
    window.alert("Only number greater than or equal 0 is allowed for shift amount");
    return false;
  }
  return true;   
}
</script>
<title>Simple Substitution</title></head><body>
<h2 align="center"><font color="blue"> Simple Substitution</font></h2>
<div class="main">
  <table border="1" align=center bgcolor="#ADD8E6">
    <tr>
    <td align="center"><strong>Encrypt</strong></td>
     <td align="center"><strong>Decrypt</strong></td>
    </tr>
    <tr> 
      <td>    
      <form name="encrypt" method = 'post' action='substitution.php'enctype='multipart/form-data' onsubmit="return validate_encrypt();">
        Enter plain text here <br> 
        <input type='text' name='en-text' placeholder='Plain text goes here or # for input file' size="50"><br><br>
        OR <br><br>
        Select File:<br> <input type = 'file' name ='en-filename' size = '20' accept='.txt'><br><br>
        Shift amount:<br>
        <input type='text' name='en-shamt' min="0" placeholder='Shift amount should be greater or equal 0' size="50"><br><br>
        <div align="center"><input type = 'submit' name='en' value ='Encrypt'></div>
      </form>
      </td>
      <td>      
      <form name="decrypt" method = 'post' action='substitution.php'enctype='multipart/form-data' onsubmit="return validate_decrypt();">
        Enter cipher here <br> 
        <input type='text' name='de-text' placeholder='Cipher text goes here or # for input file' size="50"><br><br>
        OR <br><br>
        Select File: <br><input type = 'file' name ='de-filename' size = '50' accept='.txt'><br><br>
        Shift amount:<br>
        <input type='text' name='de-shamt' placeholder= 'Shift amount should be greater or equal 0' min="0" size="50"><br><br>
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
  $cipher_used = "Substitution Encryption";
  $shift_amt = mysql_entities_fix_string($conn, $_POST['en-shamt']);
  if(preg_match("/^[a-zA-Z\s]+$/", $_POST['en-text']))
    $input_text = mysql_entities_fix_string($conn, $_POST['en-text']);
  if ($_FILES and $_POST['en-text'] == "#") {
    $filename = $_FILES['en-filename']['name'];
    move_uploaded_file($_FILES['en-filename']['tmp_name'], $filename);
    if (!file_exists($filename)){
        echo "<script type='text/javascript'>alert('Please submit a file before submitting');</script>";
        die("File not found");
    }
  if(!preg_match("/^[a-zA-Z\s]+$/", file_get_contents($filename)))
      die("<script type='text/javascript'>alert('Only space(s) and alphabets are allowed in simple substitution');</script>");
  $input_text = mysql_entities_fix_string($conn, file_get_contents($filename));
  }
$output_text = substitution_encrypt($input_text, $shift_amt);
// Display result
echo "<tr><td><textarea name='en-result' rows='3', cols='50'>\n";
echo "Encrypted cipher: ".htmlspecialchars($output_text)."\n";
echo "</textarea></td>";
echo "<td><textarea name='de-result' rows='3', cols='50' placeholder='Decrypted plain text is displayed here' disabled>\n";
echo "</textarea></td><tr>";
}
// when user choose to decrypt
else if (isset($_POST['dec'])){
  $cipher_used="Substitution Descryption";
  $shift_amt = mysql_entities_fix_string($conn, $_POST['de-shamt']);
  if(preg_match("/^[a-zA-Z\s]+$/", $_POST['de-text']))
  $input_text = mysql_entities_fix_string($conn, $_POST['de-text']);
  if ($_FILES and $_POST['de-text'] == "#") {
  $filename = $_FILES['de-filename']['name'];
  move_uploaded_file($_FILES['de-filename']['tmp_name'], $filename);
  if (!file_exists($filename)){
      echo "<script type='text/javascript'>alert('Please submit a file before submitting');</script>";
      die("File not found");
  }
  if(!preg_match("/^[a-zA-Z\s]+$/", file_get_contents($filename)))
      die("<script type='text/javascript'>alert('Only space(s) and alphabets are allowed in simple substitution');</script>");
  $input_text = mysql_entities_fix_string($conn, file_get_contents($filename));
}
$output_text = substitution_decrypt($input_text, $shift_amt);
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