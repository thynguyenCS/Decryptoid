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
function is_registered($result, $username, $email){
    $is_registered = false;
    $num_of_rows = $result->num_rows;
    for($i=0; $i<$num_of_rows; $i++){
        $result->data_seek($i);
        $each_tuple = $result->fetch_array(MYSQLI_ASSOC);
        if($username == $each_tuple['username'])
            $is_registered = true;
        }
    return $is_registered;
}

function create_salt(){
    $salt = random_bytes(22);
    $password = "hihihi112";
    $token = hash("ripemd128", "$salt$password$salt");
   // print($token);
}

echo <<<_END
        <html><head>
        <script>
        function validate(form) {
            fail = validate_username(form.usrname.value);
            fail += validate_password(form.pass.value);
            fail += validate_email(form.email.value);
            //console.log(form.usrname.value);
            if(fail == "") return true;
            else {
                alert(fail);
                return false;
            }
        }
        function validate_username(field){
            if (field=="") return "Username field cannot be blank\\n";
            else{
                if(!/^[a-zA-Z][\w-]+$/.test(field)) 
                    return "The username must start with a character and can contain alphanumerics, -(dash) and _(underscore) only\\n";
            }
            return "";
        }

        function validate_password(field){
            if (field=="") return "Password field cannot be blank\\n";
            else if(field.length< 6)
                return "Password must be at least 6 characters";
            else if(!/[a-z]/.test(field)|| !/[A-Z]/.test(field)|| !/[0-9]/.test(field))
                return "Password must contain at least one lowercase, one uppercase and one number\\n";
            return "";
        }

        function validate_email(field){
            if (field=="") return "Email field cannot be blank\\n";
            else{
                let email_reg=/^[a-zA-Z][\w.-]+@[a-zA-Z]+\.[a-zA-Z]+$/;
                if(!email_reg.test(field)) return "The email address is invalid\\n";
            }
            return "";
        }
        </script>
        <title>Sign Up</title></head><body>  
        <table border="0" cellpadding="2" cellspacing="5" bgcolor=rgba(150, 25, 0, 0.5)>
        <th><td></td><td colspan="2" align="center"><strong>Sign Up Form</strong></td></th>     
        <form method="post" action="signup.php" enctype="multipart/form-data" onsubmit="return validate(this)">
        <tr>
        <td colspan="2" align="center">Username: </td>
        <td colspan="2" align="center"><input type="text" name="usrname" placeholder="Enter username"> </td>
        </tr>
        <tr>
        <td colspan="2" align="center">Password</td>
        <td colspan="2" align="center"><input type="password" name="pass" placeholder="Enter password"> </td>
        </tr>
        <td colspan="2" align="center">Email</td>
        <td colspan="2" align="center"><input type="text" name="email" placeholder="example@domain.com"> </td>
        </tr>
        <tr><td></td><td colspan="2" align="center"><font size ="-5">I agree the terms and conditions </font>
            <input id= "cb" type="checkbox" name="checkbox" required>
            </td>
        </tr>
        <tr colspan="2" align="center">
        <td></td>
        <td colspan="2" align="center"><input type="submit" name="reg" value="Register"></td>             
        </tr>
        </form>                 
        </table>             
_END;
$registered= false;
$signed_up = false;
if(isset($_POST['reg'])){
    if($_POST['usrname'] and $_POST['pass'] and $_POST['email']){
        //if table does not exist, create a table, the first user, not need to check duplicate
        $conn = new mysqli($hn, $un, $pw, $db);
        if($conn->connect_error)
            die("Cannot connect to database S001");
        $table = "users";
        $query = "SELECT * FROM $table";
        $result = $conn->query($query);
        if(!$result){
            $query = "CREATE TABLE $table(
                              username VARCHAR(32) NOT NULL PRIMARY KEY,
                              password VARCHAR (64) NOT NULL,
                              email VARCHAR(32) NOT NULL,
                              salt VARCHAR(32) NOT NULL
                              )";
            $result = $conn->query($query);
            if (!$result) die("Cannot connect to database S002");
            //create user here, say successful
            $signed_up = true;
            //$result->close();
        }
        //step 1: check if the new username and email are already registered before
            $temp_usrname = mysql_entities_fix_string($conn, $_POST['usrname']);
            $temp_pass = mysql_entities_fix_string($conn, $_POST['pass']);
            $temp_email = mysql_entities_fix_string($conn, $_POST['email']);
            //$query = "SELECT * FROM $table";
            // $result = $conn->query($query);
            // if (!$result) die(mysql_fatal_error("Cannot connect to database S003"));
            //step 2: if not registered yet:  insert new user into database
            $registered = is_registered($result, $temp_usrname, $temp_pass);
            if(!$registered){
                //echo "NOT REGISTED";
                $salt = random_bytes(22);
                //echo $salt;
                $password = hash("ripemd128", "$salt$temp_pass$salt");
                $query = "INSERT INTO $table VALUES('$temp_usrname', '$password', '$temp_email', '$salt')";
                $result = $conn->query($query);
                if (!$result) die("Cannot connect to database S003");
                $signed_up = true;   
                //$result->close();                                               
        } 
        $conn->close();          
    }       
    else echo '<font color="red"><h2>Please fill all the fields before submitting<h2></font>'; 
}

echo "</body></html>";
if($registered){
echo <<<_END
    <html><head></head><body>
    <font color="red"> Cannot create an account since this username has already registered.<br> Go to main page or log in.</font>
    <p><a href=first_page.php>Main Page</a></p>
    <p><a href=login.php>Log In</a></p>
    </body></html>
_END; 
}

if($signed_up){
echo <<<_END
    <html><head></head><body>
    <p> You have successfully registered. Go to Main Page or Log in </p>
    <p><a href=first_page.php>Main Page</a></p>
    <p><a href=login.php>Log In</a></p>
    </body></html>
_END;    
}

?>