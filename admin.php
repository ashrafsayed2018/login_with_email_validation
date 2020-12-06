<?php 
require_once "includes/header.php";
require_once "includes/nav.php";
?>
	<div class="jumbotron">
		<h1 class="text-center">
		<?php 
		if(logged_in()) {
			echo "logged in";
		} else {
			redirect('login.php');
		}
		?>
		</h1>
	</div>
<?php require_once "includes/footer.php"; ?>

