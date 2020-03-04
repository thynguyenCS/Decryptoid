<?php 
/**
* @author: Thy Nguyen
*/
require_once 'connect_database.php';

function mysql_entities_fix_string($conn, $string) {
    return htmlentities(mysql_fix_string($conn, $string));
}
function mysql_fix_string($conn, $string) {
    if (get_magic_quotes_gpc()) $string = stripslashes($string);
        return $conn->real_escape_string($string);
}
session_start();
if (isset($_SESSION['on']))
    if ($_SESSION['on'] != hash('ripemd128', $_SERVER['REMOTE_ADDR'] .$_SERVER['HTTP_USER_AGENT'])) {
        header("location: first_page.php");
        exit;
    }
$conn = new mysqli($hn, $un, $pw, $db);
if($conn->connect_error) die('Cannot connect to database 001');

$query = "SELECT * FROM users";
$result = $conn->query($query);
if(isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])) {
    $username = mysql_entities_fix_string($conn, $_SERVER['PHP_AUTH_USER']);
    $password = mysql_entities_fix_string($conn, $_SERVER['PHP_AUTH_PW']);
    // look for the user in the database
    $query = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($query);
    if (!$result) die("Cannot connect to database 003");
    elseif($result->num_rows) {
        $row = $result->fetch_array(MYSQLI_NUM);
        $result->close();
        $salt = $row[3];
        $token = hash("ripemd128", "$salt$password$salt");
        //echo $salt."<br>".$token;
        if($token == $row[1]) {
            //session_start();
            $_SESSION['on'] = hash('ripemd128', $_SERVER['REMOTE_ADDR'] .$_SERVER['HTTP_USER_AGENT']);
            $_SESSION['username'] = $username;
            $_SESSION['password'] = $password;
            $_SESSION['email'] = $row[2];
            echo "You are now log in as $username<br>";
            header("Location: decryptoid1.php");
        }
        else die("Invalid username/password combination<br><br> <p align='center'><a href=first_page.php> Main Page</a></p>");
    }
    else die("Invalid username/password combination<br><br> <p align='center'><a href=first_page.php>Main Page</a></p>");
    $conn->close();
}
else {
    header("WWW-Authenticate: Basic realm='Restricted Section");
    header("HTTP/1.0 401 Unauthorized");
    die("<p> Please enter your username and password<br>
        <a href=first_page.php>Main Page</a><br><a href=login.php>Log In</a></p>");
}

?>
