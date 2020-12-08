<?php

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require "vendor/autoload.php";

/* ************** helper functions ************** */
// clean the html entities 
function clean($string) {
    return html_entity_decode($string);
    
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

function send_email($email = null,$subject  = null,$msg  = null,$headers  = null) {
    // Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {

    //Server settings
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
    // $mail->SMTPDebug = 1; 
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = Config::SMTP_HOST;                    // Set the SMTP server to send through
    $mail->Username   = Config::SMTP_USER;                     // SMTP username
    $mail->Password   = Config::SMTP_PASSWORD;                     // SMTP password
                                  

    $mail->Port       = Config::SMTP_PORT; 


    $mail->SMTPAuth = TRUE;      // enable smtp authantication 
    $mail->SMTPSecure ="tls";  
    // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    $mail->isHTML = true;
    $mail->CharSet = "UTF-8";

    //Recipients
    $mail->setFrom('from@ashrafsayed.com', 'ashraf sayed');
    $mail->addAddress($email,'ashraf sayed');     // Add a recipient


    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $msg;
    $mail->AltBody = $msg;

    $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
    //  return mail($email,$subject,$msg,$headers);
}
//  validatins user registeration function 

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

             set_messages("<p class='alert alert-success text-center'> please check your email or spam folder for an activation link </p>");
             redirect('index.php');

              
            } else {
                set_messages("<p class='alert alert-danger text-center' > Sorry we could not register the user </p>");
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
        $password = password_hash($password,PASSWORD_BCRYPT,array('const' =>12));

        $validation_code = token_generator();

        // inserting the user datails into the user table 

        $sql = "INSERT INTO users (first_name,last_name,username,password,email,validation_code,active) VALUES('$first_name','$last_name','$username','$password','$email','$validation_code',0)";

        $result = query($sql);

        $subject = "ashraf@gmail.com";
        $msg     = "
        Please click the link to activate the account 
        <a href='".Config::DEVELOPMENT_URL ."/activate.php?email=$email&code=$validation_code'>Activate your account </a>";
        $headers = "from : ashraf@e3lanat.com";
        send_email($email,$subject,$msg,$headers);
         return true;
    }

}


// activate user function 

function activate_user() {
    if($_SERVER['REQUEST_METHOD'] == 'GET') {

        if(isset($_GET['email'])) {

           $email = escape(clean($_GET['email']));
           $validation_code = escape(clean($_GET['code']));
           
           // check if we have a row in the database 

           $sql = "SELECT id from users where email = '$email' and validation_code='$validation_code'";

           $result = query($sql);
           if(row_count($result) == 1) {
               // upadte the active state in users table to 1 

               $sql2 = "UPDATE users set validation_code = 0 , active = 1 where email = '$email' and validation_code = '$validation_code'";
               $result2 = query($sql2);

              set_messages("<p class='alert alert-success'> Your account has been activated please login </p>");
              redirect('login.php');
           } else {
                 set_messages("<p class='alert alert-danger'> Your account has not been activated !!! </p>");
                 redirect('register.php');

           }
        }
    }
}

//  validatins user login function 

function validate_user_login() {
    $min = 3;
    $max = 20;
    $errors = [];
    if($_SERVER['REQUEST_METHOD'] == "POST") {
        $email    = clean($_POST['email']);
        $password = clean($_POST['password']);
        $remember = isset($_POST['remember']);
        $email = escape($email);
        $password = escape($password);
        $errors = [];
        

        if(empty($email)) {
            $errors[] = "the email field could not be empty";
        }

        if(empty($password)) {
            $errors[] = "the password field could not be empty";
        }

        // check if errors array is empty 


        if(!empty($errors)) {
            foreach($errors as $error) {
                echo validation_errors($error);
            }
        } else {
           
          if(login_user($email,$password,$remember)) {
              redirect('admin.php');
          } else {
              echo validation_errors('<p class="alert alert-danger text-center">Your credentials are not correct</p>');
          }
        }
  
    }
}

// login user function 

function login_user($email,$password,$remember) {
    // check if the user with the email is exists in the users table 

    $sql = "SELECT id,password FROM users WHERE email = '$email' AND active = 1";

    $result = query($sql);
   
    if(row_count($result) == 1) {

            $row = fetch_array($result);
            $db_password = $row['password'];
          

            if(password_verify($password,$db_password)) {

                // check if the remember check box is checked 

               if($remember == "on") {
                   setcookie('email',$email, time() + 86400);
               }

                $_SESSION['email'] = $email;

                return true;
            } else {
                return false;
            }
       
    } else {
        return false;
    }
}

// logged in function 

function logged_in() {

    if(isset($_SESSION['email']) || isset($_COOKIE['email'])) {
        return true;
    } else {
        return false;
    }
}

// recover password function 

function recover_password() {
    if($_SERVER['REQUEST_METHOD'] == "POST") {

        if(isset($_SESSION['token']) && $_POST['token'] == $_SESSION['token']) {
           // check if email exists 

           $email = escape($_POST['email']);
         
          

           if(email_exist($email)) {
              // send email with the varification code 

              $validation_code =  md5(uniqid(mt_rand(),true));
              ;

              setcookie('temp_access_code', $validation_code, time() + 300);

              // updating the validatin code inside the users table where email = email

              $sql = "UPDATE users set validation_code = '$validation_code' where email = '$email'";
              $result = query($sql);
              if(!$result) {
                  echo "no updates";
              }

              $subject = "ashraf@gmail.com";
              $msg     = "Here is your passowrd reset code
              <strong style='color:green'>$validation_code</strong> 

               Click her to reset  your password 
               <a href='".Config::DEVELOPMENT_URL ."/code.php?email=$email&code=$validation_code'> click to rest your password </a>";

              $headers = "from : ashraf@e3lanat.com";

             send_email($email,$subject,$msg,$headers);

              // set message 

              set_messages("<p class='alert alert-success text-center'> Please check your email or spam folder for a password reset </p>");

            //    redirect('index.php');


           } else {
               echo validation_errors("<p class='alert alert-danger text-center'> this email   <strong>$email</strong> does not exists </p>");
           } 

      
        } else {
            redirect('index.php');
        }
    }

    // if the user click on cancel button 

    if(isset($_POST['cancel_submit'])) {
        redirect('login.php');
    }
}

// code validation 

function validate_code () {

    // check if there is cookies for temp code 
    if(isset($_COOKIE['temp_access_code'])) {

        // check if is set get email and is set get code 

        if(!isset($_GET['email']) && !isset($_GET['code'])) {
            
        redirect('index.php');
        } else if (empty($_GET['email']) || empty($_GET['code'])) {
            redirect('index.php');
        } else {
            // check if post code is set

            if(isset($_POST['code'])) {
                $email = clean($_GET['email']);
                $validation_code = clean($_POST['code']);
            

                $sql = "SELECT id from users where validation_code = '$validation_code' and email = '$email'";
                $result = query($sql);
                // check if the row count is == 1

                if(row_count($result) == 1) {


                    setcookie('temp_access_code', $validation_code, time() + 900);

                    redirect("reset.php?email=$email&code=$validation_code");

                } else {
                    echo validation_errors("sorry worng validation code ");
                }
                
            }
        }


    } else {

       // set message 

        set_messages("<p class='alert alert-danger text-center'> Sorry your validation code is wrong </p>");

        redirect('recover.php');
    }
}

// password reset function 

function password_reset() {

    // check if there is cookies for temp code 
    if(isset($_COOKIE['temp_access_code'])) {
        if(isset($_GET['email']) && isset($_GET['code'])) {


            $email = clean($_GET['email']);
            $email = escape($email);
                if(isset($_SESSION['token']) && isset($_POST['token'])) {
            

                    if($_POST['token'] == $_SESSION['token']) {

                      $errors = [];
                        
                        $password = $_POST['password'];
                        $confirm_password = $_POST['confirm_password'];
                        if(mb_strlen($password) < 8) {
                          $errors[] = "password length should be more than 8 chars ";
                        }
                        if(!empty($password) && mb_strlen($password) > 7 && $password != $confirm_password) {
                            $errors[] = "the two passwords not identicals ";
                        }

                    // check if errors array is empty 

                    if(!empty($errors)) {
                        foreach($errors as $error) {
                            echo validation_errors($error);
                        }
                    } else {
                        $updated_password = md5($password);
                        echo $updated_password;

                        $sql = "UPDATE users SET password ='$updated_password',validation_code = 0, active = 1  WHERE email='$email'";

                        $result = query($sql);
                        confirm($result);

                        set_messages("<p class='alert alert-success text-center' > Your password has been update please login </p>");

                        redirect('login.php');


                    }

                    }
                    
                }
            }
    } else {

        set_messages("<p class='alert alert-danger text-center' > Sorry the time has expired</p>");

        redirect('recover.php');
    }
}
?>