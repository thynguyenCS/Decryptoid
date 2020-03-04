<?php
/**
* @author: Thy Nguyen
* Fist page to let the user either logs in or signs up
*/
echo <<<_END
    <html><head><title> Main page </title></head><body>
    <div align='center'><h1> DECRYPTOID WEBSITE</h1> <div>    
    <form method = 'post' action='login.php'enctype='multipart/form-data'>       
        <div align='center'><input type='submit' value='Log in'></div>
    </form> 
    <div align='center'> <h3> OR </h3></div>
    <form method = 'post' action='signup.php'enctype='multipart/form-data'>     
        <input type ='submit' value ='Sign Up'></div>
    </form></body></html>
_END;
?>