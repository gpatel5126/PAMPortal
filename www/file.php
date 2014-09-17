<?php
	if ( isset($_POST['members'])) {
		$members = $_POST['members'];
	}
	else {
		$members = "hello";
	}
	
	$owner_id = "me";
	
	if ( $members == "" || !is_array($members) || count($members) == 0 ) { $members = $owner_id; }
	else {
		$members = implode(",", $members);
	}
	
	echo $members;
	echo "test";

?>
<form method="post" action="file.php">
	<select name="members[]">
	</select>
	<input type="submit" />
</form>