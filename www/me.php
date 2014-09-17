<?php
	header("Location: {$_SERVER['HTTP_REFERER']}");
	if(isset($_COOKIE['user_id'])) {
		unset($_COOKIE['user_id']);
		setcookie('user_id', '', time() - 3600); // empty value and old timestamp
	}
	exit;
?>