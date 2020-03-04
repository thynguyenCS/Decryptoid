<?php
/**
* @author: Thy Nguyen
* Encrypt and decrypt using double transposition algorithm
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

function double_encrypt($input_text, $key) {
/**
 * Encrypt the input_text using double transposition
 * @param: $input_text (string): user's input
 * @param: $key (int): number of columns the input is divided into
 * @return: (array): array[0]: output_text, array[1]: array of new order of rows, array[2]: array of new order of columns
 */
//fill all the characters of input into an array, # for space
$info = create_arr($key, $input_text);
$arr = $info[0];
$row = $info[1];
$col = $info[2];   
// shuffle rows and columns
$row_range = range(0, $row-1);
shuffle($row_range);
$col_range = range(0, $col-1);
shuffle($col_range);
// create new array to hold the new order of rows
$row_arr = array(array());
$counter = 0; // iterate through the string
foreach($row_range as $r) {
$row_arr[$counter] = $arr[$r];
$counter++;
} 
//the complete array with the new order of columns
$cipher_arr = $row_arr;
$counter = 0;
foreach ($col_range as $c) {
for($i=0; $i<$row; $i++) {
$cipher_arr[$i][$counter] = $row_arr[$i][$c];
}
$counter++;
}
//convert to text
$output_text = "";
for($i=0; $i<$row; $i++) {
for($j=0; $j<$col; $j++) {
$output_text .= $cipher_arr[$i][$j];
}
}
return array($output_text, $row_range, $col_range);
}

function double_decrypt($input_text, $key, $row_key, $col_key) {
/**
* Decrypt an input text using double transposition algorithm
* @param: $input_text (string): user's input
* @param: $key (int): number of columns the input is divided into
* @param: $row_key (array): the order shuffled rows
* @param: $col_key (array): the order shuffled columns
* @return: (string): if the input is valid, else return null
*/
  //fill all the characters of input into an array, # for space
  $info = create_arr($key, $input_text);
  $arr = $info[0];
  $row = $info[1];
  $col = $info[2];
  //check the validity of input, row_key and col_key
  if(max($row_key)+1 != $row or max($col_key)+1 != $col||count($arr) != count($row_key) or count($arr[0]) != count($col_key))
    return null;
  $final_arr = $arr;
  //switch the columns and rows using the row key and column key
  $row_iter = 0;
  $col_iter = 0;
  foreach ($row_key as $r) {
    foreach ($col_key as $c) {
      $final_arr[$r][$c] = $arr[$row_iter][$col_iter];
      $col_iter++;
    }
    $col_iter = 0;
    $row_iter++;
  }
  // convert to text
  $output_text = "";
  for($i=0; $i<$row; $i++) {
    for($k=0; $k<$col; $k++) {
      if ($final_arr[$i][$k] == "%")
        $output_text .= ' ';
      else
        $output_text .= $final_arr[$i][$k];
    }
  }
  return $output_text;
}

function create_arr($col, $input_text) {
  /**
  * Create a 2D array from input_text
  * @param: $col (int): number of columns the input is divided into
  * @param: $input_text (string): user's input
  * @return: (array):  array[0]: the array filled with characters of input_text, array[1]: number of rows, array[2]: number of columns
  */
  $row = floor(strlen($input_text)/$col);
  if (strlen($input_text) % $col != 0)
    $row += 1;
  // if the input_text cannot fill the array, insert %
  $arr = array_fill(0, $row, array_fill(0, $col, '%'));
  $counter = 0;//iterate a long the input_text
  for ($i=0; $i<$row; $i++) {
    for($j=0; $j<$col; $j++) {
      if($counter < strlen($input_text) and $input_text[$counter] != ' ')
        $arr[$i][$j] = $input_text[$counter];
        $counter++;
    }
  }
  //echo $col." ".$row;
  return array($arr, $row, $col);
  }

function copy_array($arr){
  $new_arr = array();
  for($i=0; $i<sizeof($arr); $i++){
    array_push($new_arr, $arr[$i]);
  }
  return $new_arr;
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
    let enkey = document.forms["encrypt"]["en-key"];
    if(plaintext.value =="" || enkey.value ==""){
      window.alert("Please fill all the fields before submitting\\n");
      return false;
    } 
    else if (!/^[a-zA-Z\s]+$|^#$/.test(plaintext.value)) {
      window.alert("Only alphanumerics and space(s) are allowed for double transposition, or enter # to submit file");
      return false;
    }
    else if(!/^\d$/.test(enkey.value)){
      window.alert("Only number greater than 0 is allowed for number of columns");
      return false;
    }
    return true;  
  }
  function validate_decrypt() {
    let cipher = document.forms["decrypt"]["de-text"];  
    let dekey = document.forms["decrypt"]["de-key"];
    let rkey = document.forms["decrypt"]["r-key"]; 
    let ckey = document.forms["decrypt"]["c-key"]; 
    if(cipher.value =="" || dekey.value =="" || rkey.value =="" || ckey.value ==""){
      window.alert("Please fill all the fields before submitting\\n");
      return false;
    }
    else if (!/^[a-zA-Z\s%]+$|^#$/.test(plaintext.value)) {
      window.alert("Only alphanumerics,space(s)  and % are allowed for double transposition cipher, or enter # to submit file");
      return false;
    }
    else if(!/^\d$/.test(dekey.value)){
      window.alert("Only number greater than 0 is allowed for number of columns");
      return false;
    }
    else if((!/^(\d,)*\d+$/.test(rkey.value)) || (!/^(\d,)*\d+$/.test(ckey.value))) {
      window.alert("Row key and column key have to match the pattern, e.g. 1,2,3");
      return false;
    }
    return true;
  }
  </script>
  <title></title></head><body>
  <h2 align="center"><font color="blue"> Double Transposition</font></h2>
  <div class="main">
    <table border="1" align=center bgcolor="#ADD8F2">
      <tr>
        <td align="center"><strong>Encrypt</strong></td>
        <td align="center"><strong>Decrypt</strong></td>
      </tr>
      <tr><td>    
        <form name="encrypt" method = 'post' action='double_trans.php' enctype='multipart/form-data' onsubmit="return validate_encrypt();">
        Enter plain text here <br> 
        <input type='text' name='en-text' placeholder='Plain text goes here or # for input file' size="50"><br><br>
        OR <br><br>
        Select File:<br> <input type = 'file' name ='en-filename' size = '20' accept='.txt'><br><br>
        Enter number of columns:<br>
        <input type='text' name='en-key' min="1" placeholder='Number of columns must be greater than 0' size="50"><br><br>
        <div align="center"><input type = 'submit' name='en' value ='Encrypt'></div>
        </form>
        </td>
        <td>      
        <form name="decrypt" method = 'post' action='double_trans.php' enctype='multipart/form-data' onsubmit="return validate_decrypt();">
        Enter plain text here <br> 
        <input type='text' name='de-text' placeholder='Cipher text goes here or # for input file' size="50"><br><br>
        OR <br><br>
        Select File: <br><input type = 'file' name ='de-filename' size = '50' accept='.txt'><br><br>
        Enter number of columns:<br>
        <input type='text' name='de-key' placeholder= 'Number of columns must be greater than 0' min="1" size="50"><br><br>
        Enter Row Key:<br>
        <input type='text' name='r-key' placeholder= 'Row key is the sequence of numbers seperated by commas' min="1" size="50"><br><br>
        Enter Column Key:<br>
        <input type='text' name='c-key' placeholder= 'Column key is the sequence of numbers seperated by commas' min="1" size="50"><br><br>
        <div align="center"><input type = 'submit' name='dec' value ='Decrypt'></div>
        </form>
        </td></tr>    
  _END;
  $input_text="";
  $output= array();
  $cipher_used = "";
  // when user choose to encrypt
  if(isset($_POST['en'])){
      $cipher_used = "Double Encryption";
    $enkey = mysql_entities_fix_string($conn, $_POST['en-key']);
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
        die("<script type='text/javascript'>alert('Only alphanumerics and spaces are allowed in double transposition');</script>");
      $input_text = mysql_entities_fix_string($conn, file_get_contents($filename));
    }
    $output = double_encrypt($input_text, $enkey);
    $row_key = "";
    $col_key = "";
    $row_array = $output[1];
    $col_array = $output[2];
    for($i =0; $i<sizeof($row_array); $i++){
      if($i != (sizeof($row_array)-1))
        $row_key .= $row_array[$i] . ",";
      else
        $row_key .= $row_array[$i];
    }
    for($i =0; $i<sizeof($col_array); $i++){
      if($i != (sizeof($col_array)-1))
        $col_key .= $col_array[$i] . ",";
      else
        $col_key .= $col_array[$i];
    }
    // Display result
    echo "<tr><td><textarea name='en-result' rows='10', cols='50'>\n";
    echo "Encrypted cipher: ".$output[0]."\n";
    echo "Row Key: ".$row_key."\n";
    echo "Column Key: ".$col_key."\n";
    echo "</textarea></td>";
    echo "<td><textarea name='de-result' rows='10', cols='50' placeholder='Decrypted plain text is displayed here' disabled>\n";
    echo "</textarea></td><tr>";
  }
  // when user choose to decrypt
  else if (isset($_POST['dec'])){
    $cipher_used="Double Decryption";
    $dekey = mysql_entities_fix_string($conn, $_POST['de-key']);
    $rkey = mysql_entities_fix_string($conn, $_POST['r-key']);
    $ckey = mysql_entities_fix_string($conn, $_POST['c-key']);
    //!/^(\d,)*\d+$/
    if(preg_match("/^[a-zA-Z\s%]+$/", $_POST['de-text']))
      $input_text = mysql_entities_fix_string($conn, $_POST['de-text']);
    if ($_FILES and $_POST['de-text'] == "#") {
      $filename = $_FILES['de-filename']['name'];
      move_uploaded_file($_FILES['de-filename']['tmp_name'], $filename);
      if (!file_exists($filename)){
        echo "<script type='text/javascript'>alert('Please submit a file before submitting');</script>";
        die("File not found");
      }
      if(!preg_match("/^[a-zA-Z\s%]+$/", file_get_contents($filename)))
        die("<script type='text/javascript'>alert('Only alphanumerics, spaces and % are allowed in double transposition');</script>");
      $input_text = mysql_entities_fix_string($conn, file_get_contents($filename));
    }
    $row_seq= explode(",", $rkey);
    $col_seq = explode(",", $ckey);
    //check if the keys contains consecutive numbers
    $rows_check = copy_array($row_seq);
    $cols_check = copy_array($col_seq);
    sort($rows_check);
    sort($cols_check);
    $is_seq = true;
    for($i=0; $i<count($rows_check); $i++){
      if ($rows_check[$i] != $i)
        $is_seq = false;
    }
    for($i=0; $i<count($cols_check); $i++){
      if ($cols_check[$i] != $i)
        $is_seq = false;
    }
    if(!$is_seq)
      die("<script type='text/javascript'>alert('The row/column key must contain consecutive numbers only');</script>");
    //check if the highest number of the ckey is equal to number of columns
    if(sizeof($col_seq) != $dekey)
      die("<script type='text/javascript'>alert('The number of columns must equal the number of elements in column key');</script>");
    $output_text= double_decrypt($input_text, $dekey, $row_seq, $col_seq);
    if($output_text==null)
      die("<script type='text/javascript'>alert('Invalid row/column key');</script>");
    // Display result
    echo "<tr><td><textarea name='en-result' rows='3', cols='50' placeholder='Encrypted cipher is displayed here' disabled></textarea></td>\n";
    echo "<td><textarea name='de-result' rows='3', cols='50'>\n";
    echo "Decrypted cipher: ".htmlspecialchars($output_text)."\n";
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
else header("location: first_page.php");
?>