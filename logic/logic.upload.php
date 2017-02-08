<?php

if(isset($_POST['action']) && $_POST['action'] == 'upload-data')
{

	if ($_FILES["file"]["size"] > 200000) {
		echo '<p class="error">Sorry, your file is too large.</p>';
	}
	else
	{
		
		$dataColumns = array(
			0 => 'stock_id',
			1 => 'product_id',
			2 => 'product_name',
			3 => 'quantity',
			4 => 'type',
			5 => 'created'
		);
		
		if( preg_match('/openxmlformats\-officedocument/', $_FILES['file']['type']))
		{

			$xlsx = new SimpleXLSX( $_FILES['file']['tmp_name']);
			$data = $xlsx->rows();
			unset($data[0]);

			$updateInsertTable = '';

			$updateInsertTable .= '<table class="updateinsert-data">';
			
			list($cols,) = $xlsx->dimension();
			
			foreach( $data as $index => $vals) {
				if(!isset($vals[0]) || !$vals[0])
					continue;
				//Dump::liveDump($vals);
				$data = array();
				$updateInsertTable .= '<tr>';
				//for( $i = 0; $i < $cols; $i++)
				foreach($vals as $key => $val)
				{
					$updateInsertTable .= '<td>'.( ($val) ?  $val : '&nbsp;' ).'</td>';
					$data[$dataColumns[$key]] = $val;
					
				}

//Dump::liveDump($data);
				$checkStockId = new Data;
				$checkStockId->selectData("
					SELECT stock_id FROM `stock` WHERE stock_id = '" . $data['stock_id'] . "'
				");
				$insertUpdateData = new Data;
				$insertUpdateData->selectTable('stock');
				$insertUpdateData->setData($data);

				if($checkStockId->hasData())
				{
					$insertUpdateData->updateData(array('stock_id'));
					$updateInsertTable .= '<td>UPDATED</td>';
				}
				else
				{
					$insertUpdateData->insertData();
					$updateInsertTable .= '<td>INSERTED</td>';
				}

				$updateInsertTable .= '</tr>';
			}
			$updateInsertTable .= '</table>';
			echo $updateInsertTable;
		}
		
	}
	
}

?>