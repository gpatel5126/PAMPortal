 <?php 
 require_once("includes/dbconn.php");
 require_once("includes/access.php");
 $nm=$_GET['osname']; 
 if($nm=='windows'){$id='WinDomain';}
 if($nm=='unix'){$id='UnixSSH';} 
$sel="SELECT `Safe`,`Target system user name` FROM `ca_dump` where `Policy ID`='$id'";
$result=$mysqli->query($sel); ?>
<div>	Reconcile Account Name :			
	 <select name="txt_reconcile" id="reconcilename" required>
		<option value='-1'> -- Select Name -- </option>
		<?php while($findresult=$result->fetch_assoc()){
		  echo '<option value="'.$findresult['Target system user name'].'">'.$findresult['Target system user name'].'</option>';
		}?>
	
	</select>
</div>	  
	