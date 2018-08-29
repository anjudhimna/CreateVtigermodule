<?php
if(isset($_GET['action']) && $_GET['action']=="1")
{
	echo '<div style="color:red;font-style:bold;text-align:center">Module Craeted SuccessFully. You can view You Module From menu under Tools Tab.</div>';
	?>
	<script type="text/javascript">
		var url ="createcustom_module_view.php";
	    window.setTimeout(function(){

		// Move to a new location or you can do something else
		window.location.href = <?php echo $_SERVER['host_name']; ?>"createcustom_module_view.php";

	    }, 1000);
	</script>

<?php
}elseif(isset($_GET['action']) && $_GET['action']=="0")
{
echo '<div style="color:red;font-style:bold;text-align:center">Some error Occured.Please contact. </div>';

}
?>

</script>
<!DOCTYPE html>
<html>
<body>
<div style="text-align:center;font-size:25px;font-style:bold">
<form action="createcustom_module.php" id="login-form">
  <div class="heading">Enter Module Name</div>
  <div class="left">
    <input type="text" name="module_name" id="module_name" value="" placeholder="Module Name" required=required/> <br />
    <input type="submit" value="Submit" />
  </div>
  
</form>  
</div>
</body>
</html>
