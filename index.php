<?php 
require_once "includes/header.php";
require_once "includes/nav.php";
?>
	<div class="jumbotron">
		<h1 class="text-center"> 
		welcome to Home page 
		<?php  
		
		 display_message(); 
		 ?></h1>
	</div>
<?php

require_once "includes/footer.php"; 
$sql = "SELECT * FROM users";

$result = query($sql);
confirm($result);
$row = fetch_array($result);
// print_r($row['username']);
// echo row_count($result);


?>


	
