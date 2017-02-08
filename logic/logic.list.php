<?php

	$whereQuery = '';
	if(isset($_GET['filter']))
	{
		$filterArray = array();
		foreach($_GET['filter'] as $column => $value)
		{
			if($value)
				$filterArray[] = $column . " LIKE '%" . $value . "%'";
		}
		if(!empty($filterArray))
			$whereQuery = " WHERE " . join(' AND ', $filterArray);
	}
		

	$dataSelect = new Data;
	$query = "
		SELECT SQL_CALC_FOUND_ROWS stock_id as `Stock ID`, product_id as `Product ID`, product_name as `Product name`, quantity as `Quantity`, type as `Type`, created  as `Created`
		FROM `stock`
		" . $whereQuery . "
		ORDER BY created DESC
	";
	$limit = 50;
	if(isset($_GET['page']) && $_GET['page'] && is_numeric($_GET['page']))
		$page = $_GET['page'];
	else
		$page = 1;
	$dataSelect->createPagination($limit, $query, $page);
	/*
	*/

?>