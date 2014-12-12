<?
/* @var $submit_url string Address that this form should post. */
/* @var $rows array Array of rows which contains arrays of columns. */
/* @var $columns array of columns whcih contain the column data. */
/* @var $foreign_key_values array Array of all the inputted foreign keys. */
/* @var $validation array Form validation array. */
$validation = ifsetor($validation, false);
?>

<table>
	<thead>
		<tr>
			<? foreach($columns as $column): ?> 
				<td><?= $column["formal_name"] ?></td>
			<? endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<? foreach($rows as $row) : ?>
		<form method="post" action="<?= $submit_url ?>/<?= $row["id"] ?>">
			<tr>
				<? foreach($columns as $column): ?>
					<? if($column["type"] == "text") : ?> 
						<td><?php \Core\FormHelper::inputText($column["name"], $validation, $row[$column["name"]]); ?></td>
					<? elseif($column["type"] == "foreign_key") : ?> 
						<td><?php \Core\FormHelper::inputSelect($column["name"], $foreign_key_values[$column["name"]], $validation, $row[$column["name"]]); ?></td>
					<? elseif($column["type"] == "date") : ?> 
						<td><?php \Core\FormHelper::inputText($column["name"], $validation, ($row[$column["name"]] === null)? "" : date("m-d-Y", $row[$column["name"]])); ?></td>
					<? endif; ?>
				<? endforeach; ?>
				<td><input type="submit" value="Save" /></td>
			</tr>
		</form>
	<? endforeach; ?>
</tbody>
</table>