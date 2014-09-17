<?php
	require_once("includes/dbconn.php");
	
	$user = $_GET['id'];
			$mysqli->real_query("
				SELECT c_id, ad.FullName, ad.UID, server_user_compliance_history.o_compliant, server_user_compliance_history.o_uncompliant, server_user_compliance_history.m_compliant, server_user_compliance_history.m_uncompliant
				FROM server_user_compliance_history
				LEFT JOIN ad ON server_user_compliance_history.UID = ad.UID
				WHERE ad.Manager = '$user' AND c_id IN (SELECT max(c_id) FROM server_user_compliance_history GROUP BY UID)
				GROUP BY ad.FullName
				ORDER BY date DESC, ad.FullName ASC
			");
			echo mysqli_error($mysqli);
			$res = $mysqli->use_result();
			while ($row = $res->fetch_assoc()) {
				$fullName2 = $row['FullName'];
				
				$total = $row['m_uncompliant'] + $row['o_uncompliant'];
				$total_managed = $row['m_uncompliant'] + $row['m_compliant'];
				
				echo "
				<div>
					<a href='#{$row['UID']}'"; if ($total_managed > 0) { echo " class='below'"; } else { echo " class='end'"; } echo ">";
						echo "<span><i class='fa fa-chevron-right'></i>{$fullName2}</span><em class='num'>{$total}</em>
					</a>
					<div class='sub'>
							
					</div>
				</div>";
			}
		?>