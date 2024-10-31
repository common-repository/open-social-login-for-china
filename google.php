<?php
include_once( 'setting.php' );
if (isset($_GET['code'])) {
	header('Location:/?connect=google&action=callback&'.http_build_query($_GET));
}
exit();
?>