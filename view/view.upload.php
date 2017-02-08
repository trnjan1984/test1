<div class="holder">
<?php
require_once('logic/logic.upload.php');
?>
	<form action="" method="post" enctype="multipart/form-data">
		<fieldset><label>Select .xlsx file:</label></fieldset>
		<fieldset><input name="file" type="file" required></fieldset>
		<fieldset><input type="submit" value="Upload" required></fieldset>
		<input type="hidden" name="action" value="upload-data">
	</form>
</div>