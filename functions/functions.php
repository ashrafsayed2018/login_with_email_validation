<?php

/* ************** helper functions ************** */
// clean the html entities 
function clean($string) {
    return htmlentities($string);
}

function redirect($location) {
   return header("Location: $location");
}

function set_messages($message) {
    if(!empty($message)) {
        $_SESSION['message'] = $message;
    } else {
        $message = '';
    }
}

function display_message() {
    if(isset($_SESSION['message'])) {
        echo $_SESSION['message'];
        unset($_SESSION['message']);
    }
}

// token generator function for making our forms secure

function token_generator() {
   $token = $_SESSION['token'] =  md5(uniqid(mt_rand(),true));

   return $token;
}

// validation errors 

function validation_errors($error) {

    return "<div class='alert alert-danger alert-dismissible' ><button type='button' class='close' data-dismiss='alert' area-label='close'>x</button>" . $error . "</div>";

}

// check if email exist

function email_exist($email) {

    $sql = "SELECT id FROM users WHERE email = '$email'";

    $result = query($sql);
   
    if(row_count($result) == 1) {
        return true;
    } else {
        return false;
    }
}

// check if username exist

function username_exist($username) {

    $sql = "SELECT id FROM users WHERE username = '$username'";

    $result = query($sql);
    if(row_count($result) == 1) {
        return true;
    } else {
        return false;
    }
}

function send_email($email,$subject,$msg,$headers) {
     return mail($email,$subject,$msg,$headers);
}
//  validatins function 

function validate_user_registeration() {
    $min = 3;
    $max = 20;
    $errors = [];
    if($_SERVER['REQUEST_METHOD'] == "POST") {
        
        $first_name = clean($_POST['first_name']);
        $last_name = clean($_POST['last_name']);
        $username = clean($_POST['username']);
        $email = clean($_POST['email']);
        $password = clean($_POST['password']);
        $confirm_password = clean($_POST['confirm_password']);
    
        if(empty($first_name)) {
            $errors[] =  "Your first name can not be empty ";
        }

        if(!empty($first_name) && mb_strlen($first_name) < $min) {
            $errors[] =  "Your first name can not be less than $min characters";
        }
        if(!empty($first_name) && mb_strlen($first_name) > $max) {
            $errors[] =  "Your first name can not be more than $max characters";
        }
        if(empty($last_name)) {
            $errors[] =  "Your last name can not be empty ";
        }
        if(!empty($last_name) && mb_strlen($last_name) < $min) {
            $errors[] =  "Your last name can not be less than $min characters";
        }

        if(!empty($last_name) && mb_strlen($last_name) > $max) {
            $errors[] =  "Your last name can not be more than $max characters";
        }

        if(empty($username)) {
            $errors[] =  "Your username can not be empty ";
        }
        if(!empty($username) && mb_strlen($username) < $min) {
            $errors[] =  "Your  username can not be less than $min characters";
        }

        if(!empty($username) && mb_strlen($username) > $max) {
            $errors[] =  "Your user name can not be more than $max characters";
        } 
        if(username_exist($username)) {
            $errors[] =  "The username is already token";

        }
        if(empty($email)) {
            $errors[] =  "Your email can not be empty ";
        }

        if(email_exist($email)) {
            $errors[] =  "The email is already exists";

        }
        if(!empty($password) && mb_strlen($password) < 8) {
            $errors[] =  "Your password can not be more than 8 characters";
        } 
        if(empty($password)) {
            $errors[] =  "Your password can not be empty ";
        }
        
        if(!empty($password) && mb_strlen($password) > 8 && $password !== $confirm_password) {
             $errors[] = "Your password not matching ";
        }

      

        // check if errors array is empty 

        if(!empty($errors)) {
          foreach($errors as $error) {
              echo validation_errors($error);
          }
        } else {
            if(register_user($first_name,$last_name,$username,$email,$password)) {

            set_messages("<p class='bg-success text-center' > please check your email or spam folder for an activation link </p>");
            redirect('index.php');
              
            }
           
        }
    }
}

// register user function 

function register_user($first_name,$last_name,$username,$email,$password) {
    $user_details = [$first_name,$last_name,$username,$email,$password];
    foreach($user_details as $detail) {
        escape($detail);
    }

    if(email_exist($email)) {
        return false;
    } else if (username_exist($username)) {
        return false;
    } else {
        // encrypt password 
        $password = md5($password);

        $validation_code = token_generator();

        // inserting the user datails into the user table 

        $sql = "INSERT INTO users (first_name,last_name,username,password,email,validation_code,active) VALUES('$first_name','$last_name','$username','$password','$email','$validation_code',0)";

        $result = query($sql);
        confirm($result);

        $subject = "activate account";
        $msg     = "
        Please click the link to activate the account 
        http://login.local/activate.php?email=$email&code=$validation_code
        ";
        $headers = "";
        send_email($email,$subject,$msg,$headers);
        return true;
    }

}
?>