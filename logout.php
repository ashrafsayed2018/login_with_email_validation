<?php 

session_start();
session_unset();
session_destroy();

if(isset($_COOKIE['email'])) {
    unset($_COOKIE['email']);
    setcookie('email','', time() - 86400);
}

header('Location:index.php');
?>