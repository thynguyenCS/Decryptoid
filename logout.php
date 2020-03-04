<?php
/**
* @author: Thy Nguyen
*/
function destroy_session_and_data() {
    $_SESSION = array();
    setcookie(session_name(), '', time() - 2592000, '/');
    session_destroy();
}
session_start();
if(isset($_SESSION['username'])) {
    destroy_session_and_data();
    echo "<div align='center'>";
    echo "<h2><strong>You are logging off...See you soon</strong></h2><br><br>";
    echo "<img src='http://img1.wikia.nocookie.net/__cb20140328191525/pokemon/images/thumb/3/39/007Squirtle.png/200px-007Squirtle.png'>";
    echo "<p align='center'><a href=first_page.php>Main Page</a></div>";
}
//even session is not on, go to main page
else header("location: first_page.php");
?>