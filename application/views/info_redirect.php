<?
/* @var $text string Text to output. */
/* @var $location string Location to redirect the user to. */
/* @var $seconds int Number of seconds to delay the client from redirecting. */

\Core\View::output("info", array("text" => $text));
?>
<script type="text/JavaScript">
	setTimeout("location.href = '<?= $location ?>';",<?= $seconds * 1000 ?>);
</script>