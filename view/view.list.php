<div class="holder">
<form>
<table>
	<tr>
		<td><lablel>Type:</lablel></td>
		<td><input type="input" name="filter[type]" value="<?php echo ((isset($_GET['filter']['type'])) ? $_GET['filter']['type'] : '')  ?>"></td>
	</tr>
	<tr>
		<td><lablel>Created:</lablel></td>
		<td><input type="input" name="filter[created]" value="<?php echo ((isset($_GET['filter']['created'])) ? $_GET['filter']['created'] : '')  ?>"></td>
	</tr>
	<tr>
		<td><lablel></lablel></td>
		<td><input type="submit" value="Filter"></td>
	</tr>
</table>



</form>
<?php
require_once('logic/logic.list.php');
?>

</div>