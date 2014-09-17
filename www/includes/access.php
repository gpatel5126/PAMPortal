<?php
	session_start();

	if (!isset($_SESSION['logged']) || !isset($_SESSION['uid'])) {
		header("Location: index.php");
		exit;
	}
	else {
	
		$full_access = array('gmtrans','barkin','gerri','paulette','den','sgary','atgupta','cberube','pri26646','mfarynia','dahart','tkhuu','aune','kportie','lyendler','vanninat','drmello','cyb58135','pri76183','vsagarch','mankad','higginsb','casoto','jpowell','marcfern','petern','namathur','mudsharm','stwagner','swatson','vanninat','dacrompto','lilytran','acastro','adakik','adaud','amkumarg','anbhusha','anmittal','apreet','ashishg','atgupta','banka','bannur','bansal','bgarner','bhattach','bnoel','bodong','brulotte','cantao','cjoanino','dahart','debarshi','dezion','dgopinat','dlenoe','emonson','erobeson','estes','eswaramo','fdierolf','ggadbois','golani','goldberg','irichter','jacobi','jakolafa','jeclark','jifitzge','jingwang','jsudhaka','kahamed','karunaka','kportie','lakshmig','lessard','levu','mbadhe','mchinnat','mcross','mhendric','mjanko','mlarsen','nchowdar','ockers','osurke','pav','pcabibi','petschol','poje','prverma','psingeet','pwagner','rdaquiga','rdevaraj','rtodd','ruchir','sakumarc','saurabhm','sbodela','schivuku','sebyt','sgangine','shgoel','shorning','skumarmu','soyang','sstevens','surkumar','tan','tpowers','umal','vmuriki','vpachaur','vsagarch','vvishnu','whiteson','wiljohns');
		$limited_access = array('aomar','brawhite','smishra','celestes','rtodd','rdaquiga');
		
		if ($stmt = $mysqli->prepare("
			SELECT uid 
			FROM ad
			WHERE UID = 'gerri' OR Manager = 'gerri' OR Manager2 = 'gerri' OR Manager3 = 'gerri' OR Manager4 = 'gerri' OR Manager5 = 'gerri' OR Manager6 = 'gerri' OR Manager7 = 'gerri' OR Manager8 = 'gerri' OR Manager9 = 'gerri' OR Manager10 = 'gerri'
		")) {
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$user_id = $row['uid'];
				array_push($full_access, $user_id);
			}
		}
		
		if ($stmt = $mysqli->prepare("
			SELECT uid 
			FROM ad
			WHERE UID = 'sgary' OR Manager = 'sgary' OR Manager2 = 'sgary' OR Manager3 = 'sgary' OR Manager4 = 'sgary' OR Manager5 = 'sgary' OR Manager6 = 'sgary' OR Manager7 = 'sgary' OR Manager8 = 'sgary' OR Manager9 = 'sgary' OR Manager10 = 'sgary'
		")) {
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$user_id = $row['uid'];
				array_push($limited_access, $user_id);
			}
		}
	
		if (!in_array($_SESSION['uid'],$full_access) && !in_array($_SESSION['uid'],$limited_access)) {
			header("Location: beta.php");
			exit;
		}
		
		$priv = in_array($_SESSION['uid'],$full_access);
		$core = in_array($_SESSION['uid'],array('den','jpowell','aune','lyendler'));
	
	}
	
?>