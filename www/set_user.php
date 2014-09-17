<?php
header("Location: {$_SERVER['HTTP_REFERER']}");
setcookie("user_id", $_POST['id'], time()+3600);  /* expire in 1 hour */
exit;
?>