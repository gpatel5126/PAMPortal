<?php
	require_once("includes/dbconn.php");
	require_once("includes/access.php");
	
	if ( isset($_COOKIE['user_id']) ) {
		$target = $_COOKIE['user_id'];
	} else {
		$target = $_SESSION['uid'];
	}
	
	$i = 0;
	if ($stmt = $mysqli->prepare("
		SELECT FullName 
		FROM ad
		WHERE UID = ?
	")) {
		$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$i++;
			$fullName = $row['FullName'];
		}
	}
	
	if ($stmt = $mysqli->prepare("
		SELECT COUNT(`CI Name`) AS unclaimed
		FROM cmdb_dump
		WHERE `Owner Name` = 'Not Available'
	")) {
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$unclaimed = $row['unclaimed'];
		}
	}
	
	$j = 0;
	if ($stmt = $mysqli->prepare("
		SELECT UID 
		FROM server_user_compliance_history
		WHERE UID = ?
		ORDER BY date DESC
		LIMIT 1
	")) {
		$stmt->bind_param("s", $target);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$j++;
		}
	}
	
	if ($i != 0) {
	
		// Thresholds
		$yellow_threshold = 30;
		$green_threshold = 70;
		
		if ($stmt = $mysqli->prepare("
			SELECT FullName 
			FROM ad
			WHERE UID = ?
		")) {
			$stmt->bind_param("s", $target);
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$fullName = $row['FullName'];
			}
		}
		
		// Get the number of uncompliant servers
		$uncompliant_servers_array = array();
		
		if ($stmt = $mysqli->prepare("
			SELECT * 
			FROM server_user_compliance_history
			WHERE UID = ?
			ORDER BY date DESC
			LIMIT 1
		")) {
			$stmt->bind_param("s", $target);
			$stmt->execute();
			$res = $stmt->get_result();
			while ($row = $res->fetch_assoc()) {
				$total_uncompliant = $row['o_uncompliant'] + $row['m_uncompliant'];
				array_push($uncompliant_servers_array, $total_uncompliant);
			}
		}
		
		$uncompliant_servers_data = implode(",", $uncompliant_servers_array);
		
		require_once('includes/getStats.php');
	
	}
	
?>
<!DOCTYPE html>
<html>
<head>
	<title>PAM Portal</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta charset="UTF-8"/>
	<!--[if IE]><script src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7/html5shiv.min.js"></script><![endif]-->
	<meta name="viewport" content="width=device-width"/>
	<link rel="stylesheet" href="style.css"/>
	<link rel="stylesheet" href="protect_account.css"/>
	<script src="//code.jquery.com/jquery-2.1.1.min.js"></script>
	<script src="js/feedback.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
	<script src="js/highlight.js"></script>
	<link href='//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic,700italic' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
	
	<!-- Add fancyBox main JS and CSS files -->
	<script type="text/javascript" src="source/jquery.fancybox.js?v=2.1.5"></script>
	<link rel="stylesheet" type="text/css" href="source/jquery.fancybox.css?v=2.1.5" media="screen" />
	
	
	<script>
	$(function() {
// Initial variables
		var geo = $('select#geoSelect').val();
		var nickname = '';
		var env_code = 'GEN';
		inv_limit = 12;
		safe_limit = 12;
		var xhr;
		
		inv_search_value = '';
		safeName = '';
		accountName = '';
		
		
		safeQuery = '';
		currentSafe = 'none';
		inputFlag = true;
		passwordFlag = true;
		
		search_value = "";
		page = 1;
		pages = 1;
		limit = 25;
		prot = "all";
	// Safe listing pages
		safePage = 1;
		safePages = 1;
		
		whatUsage = "";
		safeName = "";
	

	    search_value = "";
		page = 1;
		pages = 1;
		limit = 25;
		prot = "all";
		first_time = true;
		roles = "all";
		
		$('.fancybox').fancybox({
			autoSize: false,
            autoDimensions: false,
			width: '1000px',
			height: '90%',
		
			preload   : true
		});
		
		$( document ).on( "click", ".pages a", function() {
			var go = $(this).attr('href');
			$('#loading').show();
			$("#load_container").load(go, function() {
				$('#loading').hide();
			});
			$('#content').animate({
			   scrollTop: $("#content").offset().top
			});
			
			return false;
		});
		
		var itemRow = $('table.team tr.s_row');
		itemRow.click(function(e) {
			e.preventDefault();
			$.fancybox({
				'href': $(this).find('td.server a').attr('href'),
				'autoSize': false,
				'autoDimensions': false,
				'width': '1000px',
				'height': '90%',
				'preload' : true,
				'transitionIn': 'none',
				'transitionOut': 'none',
				'type': 'iframe'
			});
		});
		
		$( document ).on( "click", "a.unclaim", function() {
			var server_name = $(this).attr('rel');
			var $a = $(this);
			
			$.ajax({ 
				type: 'POST', 
				url: 'unclaim.php', 
				data: { server_name: server_name }, 
				dataType: 'json',
				success: function (data) { 					
					if ( data['status'] == 'Success' ) {
						$a.parent().parent().addClass('success');
						$a.parent().html("<span class='msg'><strong>Successfully Unclaimed</strong></span>");
					}
					else {
						$a.parent().parent().addClass('error');
						$a.parent().html("<span class='msg'><strong>Error:</strong> "+ data['message'] +"</span>");
					}
					
				}
			});
			
			return false;
		});
		
		$( document ).on( "click", "a.transfer", function() {
			var server_name = $(this).attr('rel');
			var $t = $(this);
			
			$(this).parent().html("<form method='post' class='tt'><label>Transfer to:</label><span><b></b>Enter UID</span><input type='text' value='' name='transfer_to' /><input type='hidden' name='server_name' value='"+server_name+"' /><input type='submit' value='Go!'/></form><a href='#' class='unclaim' rel='"+server_name+"'>Unclaim server</a>");
			
			return false;
		});
		
		$( document ).on( "focus", "form.tt input[type=text]", function() {
			$(this).parent().children('span').fadeIn().css("display","inline-block");
			return false;
		});
		$( document ).on( "focusout", "form.tt input[type=text]", function() {
			$(this).parent().children('span').fadeOut();
			return false;
		});
		
		
		$( document ).on( "click", "a.reset", function() {
			var server_name = $(this).attr('rel');
			$('.errorMsg').fadeOut();
			$(this).parent().parent().parent().parent().removeClass("error");
			$(this).parent().parent().parent().html("<a class='transfer' href='#' rel='"+server_name+"'><i class='fa fa-chevron-circle-right'></i>Transfer Ownership</a>");
			return false;
		});
		
		$( document ).on( "submit", "form.tt", function() {
			var $a = $(this);
			
			$.ajax({ 
				type: 'POST', 
				url: 'transfer.php', 
				data: $($a).serialize(),
				dataType: 'json',
				success: function (data) { 					
					if ( data['status'] == 'Success' ) {
						$a.parent().parent().addClass('success');
						$a.parent().html("<span class='msg'><strong>Successfully Transferred</strong></span>");
					}
					else {
						$a.parent().parent().addClass('error');
						$a.parent().html("<span class='msg'><strong>Error</strong><div class='errorMsg'><b></b>"+ data['message'] +"<br /><a href='#' class='reset' rel='"+data['server_name']+"'>Go back</a></div>");
					}
					
				}
			});
			
			return false;
		});
// Password input
		$(document).on("keyup paste", "input[name=currentPassword]", function() {
			var passwordVal = $(this).val();
			if ( passwordVal != "" ) { $("#continue").removeClass("inactive"); }
			else { $("#continue").addClass("inactive"); }
		});
		
		$("#protectButton").click( function() {
			if ( $(this).hasClass('inactive') ) {
			










			}
			else {
				$("#protectForm").submit();
			}
		});
		
		// When you search for a safe (keyup is when a key is released. 
		$( document ).on( "keyup", "input#safe_search", function() {
			safePage = 1;
			safeQuery = $(this).val();
			if (safeQuery.length > 0) { $(this).removeClass("inactive"); }
			loadChooseSafes();
		});
		
		
		$('input#safe_search').on('blur', function(){
		   if ( $(this).val() == '' ) { $(this).addClass('inactive'); $(this).val('start typing to search your safes...'); }
		   
		}).on('focus', function(){
			$(this).removeClass('inactive');
			if ( $(this).val() == "start typing to search your safes..." ) { $(this).val(''); }
		});
		
		// When you click to select a safe
		$( document ).on( "click", "#safe_list ul li a", function() {
			if ( $(this).hasClass("active") ) {
				$(this).removeClass("active");
				safeName = "";
			}
			else {




				$("#safe_list a").removeClass("active");
				$(this).addClass("active");
				safeName = $(this).attr('rel');
				if (safeName != "" && whatUsage != "") {
					$("a#use_this_safe").removeClass("inactive");
				}


			}
			return false;
		});
		
		// When you click to select a safe
		$( document ).on( "click", "#what_usage ul li a", function() {




			if ( $(this).hasClass("active") ) {
				$(this).removeClass("active");
				whatUsage = "";
			}
			else {
				$("#what_usage a").removeClass("active");
				$(this).addClass("active");
				whatUsage = $(this).attr('rel');
				if (safeName != "" && whatUsage != "") {
					$("a#use_this_safe").removeClass("inactive");
				}
			}
			
			return false;
		});
		

		//<!-- Gunjan- code line 365-497  To open "Choose a safe and account usage type" popup
		function openChooseSafe() {
				safePage = 1;
				
				whatUsage = "";
				$("#what_usage a").removeClass("active");
				safeName = "";
				
				/*$("#disclaimer").animate({
					'margin-left': '520px'
				}, 400);
				
				var disclaimerHeight = $("#disclaimer").height();
				$("#disclaimer .shadow").css({
					'height': disclaimerHeight + 40
				});
				
				$("#disclaimer .shadow").fadeTo(400,0.5);	*/
				loadChooseSafes();
				
				$(".one-col").fadeTo(400, .1); /*gunjan code to chane the opacity of window when dialog closed */
				$(".one-col").removeClass("active");
				
				$("#choose_a_safe").css({
					opacity: 0
				});
				$("#choose_a_safe").show();
				$("#choose_a_safe").animate({
					'opacity': 1,
					'margin-left': '200px',
					'z-index':1000,
				}, 400);
		}
		
		/* to load then inner content of popup of "Choose a safe and account usage type" */
		function loadChooseSafes() {
			$.ajax({ 
				type: 'POST', 
				url: '../modules/my_safes.php', 
				data: { safe_query: safeQuery, page: safePage, limit: 4 }, 
				dataType: 'json',
				success: function (data) { 		
					$("#safe_list ul").empty();
					
					ObjNewSafeName = "";
					$("a#use_this_safe").addClass("inactive");
					
					safePages = Math.ceil(data.num_results/4);
					
					// Make the previous button inactive if we need to
					if ( safePages == 1) { $(".prv[rel=safe]").addClass("inactive"); }
					if ( safePages <= 1 || safePage == safePages) { $(".nxt[rel=safe]").addClass("inactive"); } else { $(".nxt[rel=safe]").removeClass("inactive"); }
					
					if (data.num_results == 0) {
						$("#safe_pages .num_results").text(data.num_results + " safes");
						if (safeQuery == "") {
							$("#safe_list ul").append("<li class='none'>You don't have access to any safes.</li>");
						}
						else {
							$("#safe_list ul").append("<li class='none'>No matching safes.</li>");
						}		
						$("span.safePages").text(1);
						$("span.safePage").text(1);
					}
					else {
						$("#safe_pages .num_results").text(data.num_results + " safes");
						$("span.safePages").text(safePages);
						$("span.safePage").text(safePage);
						$.each(data.results, function(i, item) {		
							$("#safe_list ul").append("<li><a href='#' rel='"+item.name+"'><i class='fa fa-check-square'></i>"+ item.name +"</a></li>");
						});
						if (safeQuery.length > 0) {
							$("#safe_list ul li a").highlight(safeQuery);
						}
					}
				}
			});
		}
		/* to close popup of "Choose a safe and account usage type" */
		function closeChooseSafe() {
			$(".one-col").fadeTo(400, 1.0);
			$(".one-col").removeClass("active");
			//windows.opacity=1;
			/*$("#disclaimer").css({
				opacity: 0
			});
			$("#disclaimer").show();
			
			$("#disclaimer .shadow").fadeOut();
			
			$("#disclaimer").animate({
				'opacity': 1,
				'margin-left': '520px'
			}, 400);
			*/
			$("#choose_a_safe").animate({
				'opacity': 0,
				'margin-left': '200px'
			}, 200, function() {
				$("#choose_a_safe").hide();
			});
			
		}
		
		/* click event of "Protect this account" */
		$("#use_this_safe").click( function() {
		//Gunjan Code for  checking verify password to proceed further 471-474
			if($(this).hasClass('inactive')){
				alert('Please either varify password or select Reconcile Account Name to protect this account.');
				return false;
			}
			var password = $('input[name=currentPassword]').val();
			
			var formHeight = $("#protectFull").height() + 5;
			$("#formLoading").css('height',formHeight);
			$("#formLoading").html('<img src="images/large_loader.gif" alt="" /><br />Protecting your account!<span>This should take about five seconds.</span>');
			$("#formLoading").fadeIn(700);
			
			$.ajax({
			   type: "POST",
			   url: 'copyaccount_handler1.php',
			   data: { action: 'Protect', safeName: safeName, account_name: accountName, password: password, policyType: whatUsage },
			   dataType: 'json',
			   success: function(data)
			   {
					if ( data.status == 'Success') {
						$("#formLoading").html("<i class='fa fa-check-circle'></i><br />Your account has been protected!<span>Object created with name '"+data.objectName+"'</span><a href='#' class='formReset fButton' id='back_after_create'><i class='fa fa-chevron-circle-left'></i>Back to my accounts!</a>");
					}
					else if ( data.status == 'Failure') {
						var errors = '<ul>';
						for ( var i = 0; i < data.messages.length; i++ ) {
							errors += "<li>" + data.messages[i] + "</li>";
						}
						errors += "</ul>";
						
						$("#formLoading").html("<i class='fa fa-times-circle red'></i><br />There were errors with your request:<span class='errors'>"+ errors + "</span><a href='#' class='formGoBack fButton' id='back_after_error'><i class='fa fa-chevron-circle-left'></i>Go back and fix errors</a>");
					}
			   }
			 });
			 return false;
		});
			/*
			$("#use_this_safe").click( function() {
				var formHeight = $(this).height() + 5;
				$("#formLoading").css('height',formHeight);
				$("#formLoading").html('<img src="images/large_loader.gif" alt="" /><br />Adding your object!<span>This should take about ten seconds.</span>');
				$("#formLoading").fadeIn(700);
				
				$.ajax({
				   type: "POST",
				   url: 'copyaccount_handler.php',
				   data: $("form#cs").serialize(), // serializes the form's elements.
				   dataType: 'json',
				   success: function(data)
				   {
						if ( data.status == 'Success') {
							$("#formLoading").html("<i class='fa fa-check-circle'></i><br />Your object has been added!<span>Object created with name '"+data.objectName+"'</span><a href='#' class='formReset fButton' id='back_after_create'><i class='fa fa-chevron-circle-left'></i>Back to safe inventory!</a>");
							
						}
						else if ( data.status == 'Failure') {
							var errors = '<ul>';
							for ( var i = 0; i < data.messages.length; i++ ) {
								errors += "<li>" + data.messages[i] + "</li>";
							}
							errors += "</ul>";
							
							$("#formLoading").html("<i class='fa fa-times-circle red'></i><br />There were errors with your request:<span class='errors'>"+ errors + "</span><a href='#' class='formGoBack fButton' id='back_after_error'><i class='fa fa-chevron-circle-left'></i>Go back and fix errors</a>");
						}
				   }
			 });
			
			return false;

			}*/
				
			/*$("#use_this_safe").click( function() {
			
;			var formHeight = $(this).height() + 5;
			$("#formLoading").css('height',formHeight);
			/*$("#formLoading").html('<img src="images/large_loader.gif" alt="" /><br />Adding your object!<span>This should take about ten seconds.</span>');
			$("#formLoading").fadeIn(700);*/
			/*
			$.ajax({
			   type: "POST",
			   url: 'copyaccount_handler1.php',
			   data: $("form#cs").serialize(), // serializes the form's elements.
			   dataType: 'json',
			   success: function(data)
			   {
					if ( data.status == 'Success') {
						$("#formLoading").html("<i class='fa fa-check-circle'></i><br />Your object has been added!<span>Object created with name '"+data.objectName+"'</span><a href='#' class='formReset fButton' id='back_after_create'><i class='fa fa-chevron-circle-left'></i>Back to safe inventory!</a>");
						
					}
					else if ( data.status == 'Failure') {
						var errors = '<ul>';
						for ( var i = 0; i < data.messages.length; i++ ) {
							errors += "<li>" + data.messages[i] + "</li>";
						}
						errors += "</ul>";
						
						$("#formLoading").html("<i class='fa fa-times-circle red'></i><br />There were errors with your request:<span class='errors'>"+ errors + "</span><a href='#' class='formGoBack fButton' id='back_after_error'><i class='fa fa-chevron-circle-left'></i>Go back and fix errors</a>");
					}
			   }
			 });
			
			return false;
		});
		*/
//----------------------------------------------------------
/* Gunjan code line 503-537 Reconcile window */
		//$(document).on("click","#uni",function(){ alert('uni'); getreconcile();});
		//$(document).on("click","#win",function(){ alert('win'); getreconcile();});
		// fill data for reconcile using ajax 570-603
		$(document).on("click","a#reconcile",function(){
			//openReconcile();
			$("#txtReconcile").toggle(200);
			getreconcile();
			return false;
			
		});
		$("#uni").click(function(){
			var osname = $('#os_name').val();
			if(osname!='unix'){
		     	$('#txtReconcile').css('display','none');
		       }
		 });
		$("#win").click(function(){
			var osname = $('#os_name').val();
			if(osname!='windows'){
			$('#txtReconcile').css('display','none');
			}
		});		
		
		function getreconcile(){
		    var osname=($('#os_name').val());
			
		    $.ajax({
				   type: "GET",
				   url: 'reconcile.php',
				   data: 'osname='+osname, // serializes the form's elements.
				   dataType: 'html',
				   success: function(html)
				   { $('#txtReconcile').html(html);
				   }
			   });
		}
		// Close reconcile on click
		/*$(document).on("click", "a#closeReconcile", function() {			
			closeReconcile();
			return false;
		});
		
	function openReconcile() {	
		
		$("#txtReconcile").css({
			opacity: 0
		});
		$("#txtReconcile").show();
		
		$("#txtReconcile").animate({
			'opacity': 1,
			'margin-left': '200px',
			'z-index':2000,
			'margin-top': '347px',			
		}, 400);
		

	}
	/*closeReconcile */
/*	function closeReconcile() {				
		$("#txtReconcile").animate({
			'opacity': 0,
			'margin-left': '200px'
		}, 200, function() {
			$("#txtReconcile").hide();
		});
	}*/
		
		// Open disclaimer dialogue box when Protect button is selected. 
		$(document).on("click", "a.protect2", function() {
			accountName = $(this).attr('rel');<!--.attr('rel') looks like it is being used to set/send account name to next file, maybe handler.-->
			//alert(accountName);
			//alert('call');
			//openDisclaimer();
			 openChooseSafe();
			//alert('end');
			return false;
		});


		
		// Open disclaimer on protect
		$(document).on("click", "a#continue", function() {
			if ( $(this).hasClass("inactive") ) { }
			else {
				$("#formLoading").hide();
				
				$("#currentPasswordLoader").show();
				var currentPassword = $("input[name=currentPassword]").val();
				// Authenticate password
				$.ajax({
				   type: "POST",
				   url: 'modules/verify_account.php',
				   data: { accountName: accountName, password: currentPassword },
				   dataType: 'json',
				   success: function(data)
				   {
						if ( data.status == 'Success') {
							// If successful, open the next screen
							openChooseSafe();
							$("div#currentPasswordError").hide();
							$("#currentPasswordLoader").hide();
						}
						else if ( data.status == 'Failure') {
							// If failure, show error							
							$("div#currentPasswordError").show();
							$("#currentPasswordLoader").hide();
						}
				   }
				 });
			}
			return false;
		});
		
		// Close disclaimer on click
		$(document).on("click", "a.closeDisclaimer", function() {
			accountName = '';
			closeDisclaimer();
			return false;
		});
		





		// Close disclaimer on click
		$(document).on("click", "a#closeChooseSafe", function() {
			closeChooseSafe();
			return false;
		});
		//Gunjan Code for after verify password enable/disable protect account 701-709
		$(document).on("change","#reconcilename",function(){
			if($(this).val()!= "-1"){
				$("a#use_this_safe").removeClass("inactive");
				$("a#use_this_safe").addClass("active");
			}else{
			$("a#use_this_safe").removeClass("active");
				$("a#use_this_safe").addClass("inactive");
			}
		});
		//Gunjan Code for API CALL
		$(document).on("click","#pwdSubmit",function() {
			var formHeight = $(this).height() + 5;
			$("#formLoading").css('height',formHeight);
			/*$("#formLoading").html('<img src="images/large_loader.gif" alt="" /><br />Adding your object!<span>This should take about ten seconds.</span>');
			$("#formLoading").fadeIn(700);*/
			var str ='task=verify&'+$("form#cs").serialize();
			$.ajax({
			   type: "POST",
			   url: 'copyaccount_handler1.php',
			   data: str, // serializes the form's elements.
			   dataType: 'json',
			   success: function(data) {
					//alert(data);
					//return false;
					if ( data.status == 'Success') {
						$("#formLoading").html("<i class='fa fa-check-circle'></i><br />Your object has been added!<span>Object created with name '"+data.objectName+"'</span><a href='#' class='formReset fButton' id='back_after_create'><i class='fa fa-chevron-circle-left'></i>Back to safe inventory!</a>");
						//Gunjan Code for after verify password enable/disable protect account 729-730
						$("a#use_this_safe").removeClass("inactive");
						$("a#use_this_safe").addClass("active");
					}
					else if ( data.status == 'Failure') {
						var errors = '<ul>';
						for ( var i = 0; i < data.messages.length; i++ ) {
							errors += "<li>" + data.messages[i] + "</li>";
						}
						errors += "</ul>";
						
						$("#formLoading").html("<i class='fa fa-times-circle red'></i><br />There were errors with your request:<span class='errors'>"+ errors + "</span><a href='#' class='formGoBack fButton' id='back_after_error'><i class='fa fa-chevron-circle-left'></i>Go back and fix errors</a>");
						//Gunjan Code for after verify password enable/disable protect account 740-742
						$("a#use_this_safe").removeClass("active");
						$("a#use_this_safe").addClass("inactive");
					}
			   }
			 });
			
			return false;
		});
		
		$( document ).on( "click", "a#back_after_create", function() {

			$("#disclaimer").fadeOut(200);
			$("#choose_a_safe").fadeOut(200);
			/* Gunjan code line 608 Reconcile window */
			$("#txtReconcile").fadeOut(200);

			//loadAccounts(1);
			loadServers(1);

			$(".one-col").fadeTo(200, 1);
			$(".one-col").addClass("active");
			
			return false;
		});
		$( document ).on( "click", "a#back_after_error", function() {
			$("#formLoading").fadeOut();
			return false;
		});
		
		$(".pages_bar .prot > a").click( function() {
			var rel = $(this).attr('rel');
			$(this).siblings('a').removeClass("active");
			$(this).addClass("active");
			prot = rel;

			//loadAccounts(1);
			loadServers(1);
			return false;	
		});
		






		


		// ---------- PREVIOUS AND NEXT --------------
		$('a.nxt').click( function() {
			var type = $(this).attr('rel');







			if (type == 'safe') {
				if (safePage >= safePages) {
					// Do nothing
				} else {



					safeName = "";
					if (safePage == 1) { $(this).siblings('.prv').removeClass('inactive'); }









					safePage = safePage + 1;
					loadChooseSafes();
					
					if (safePage == safePages) {
						$(this).addClass("inactive");
					}
				}
			}
			else if (type == 'search') {


				if (page >= pages) {
					// Do nothing
				} else {
					if (page == 1) { $(this).siblings('.prv').removeClass('inactive'); }
					page = page + 1;
					//loadAccounts(page);
					loadServers(page);
					if (page == pages) {
						$(this).addClass("inactive");
					}
				}
			}




			return false;
		});










		$('a.prv').click( function() {
			var type = $(this).attr('rel');
			if (type == 'safe') {
				if (safePage <= 1) {
					
				} else {
					if (safePage == safePages) { $(this).siblings('.nxt').removeClass('inactive'); }
					safePage = safePage - 1;
					loadChooseSafes();
					
					if (page == 1) {
						$(this).addClass("inactive");
					}
				}
			}



			else if (type == "search") {
				if (page <= 1) {

					
				} else {
					if (page == pages) { $(this).siblings('.nxt').removeClass('inactive'); }
					page = page - 1;

					//loadAccounts(page);
					loadServers(page);
					
					if (page == 1) {
						$(this).addClass("inactive");
					}
				}
			}
			return false;
		});
		
		$(".pages_bar .prot > a").click( function() {
			var rel = $(this).attr('rel');
			$(this).siblings('a').removeClass("active");
			$(this).addClass("active");
			prot = rel;
			loadServers(1);	
			return false;			
		});
		
		// System roles
		$(".pages_bar .roles > a").click( function() {
			$(this).siblings('ul').fadeToggle(300);
			$(this).toggleClass("active");
			return false;
		});
		
		// System roles
		$( document ).on( "click", ".pages_bar .roles > ul a", function() {
			var rel = $(this).attr("rel");
			if (rel == "all") {
				$(".pages_bar .roles > ul a").removeClass('active');
				$(".pages_bar .roles > a").html("All system roles<i class='fa fa-chevron-circle-down'></i>");
				$(this).addClass('active');
				$(".pages_bar .roles > select option").prop('selected', false);
				$(".pages_bar .roles > select option[value='all']").prop('selected', true);
			}
			else {
				$(".pages_bar .roles > select option[value='all']").prop('selected', false);
				$(".pages_bar .roles > ul a[rel=all]").removeClass('active');
				if ( $(this).hasClass('active') ) {
					$(this).removeClass('active');
					$(".pages_bar .roles > select option[value='"+rel+"']").prop('selected', false);
				}
				else {
					$(this).addClass('active');
					$(".pages_bar .roles > select option[value='"+rel+"']").prop('selected', true);
				}
				var how_many = $(".pages_bar .roles > ul a.active").length;
				if (how_many == 0) {
					$(".pages_bar .roles > ul a[rel=all]").click();
				}
				else {
					if (how_many == 1) { var how_many_s = ""; } else { how_many_s = "s"; }
					$(".pages_bar .roles > a").html(how_many + " system role"+ how_many_s +" selected<i class='fa fa-chevron-circle-down'></i>");
				}
			}
			
			roles = $(".pages_bar .roles > select").val().join(",");
			
			loadServers(1);
			return false;
		});
		
		// Hide roles list when we click outside roles box
		$(document).mouseup(function (e)
		{
			var container = $(".pages_bar .roles > ul");
			if (!container.is(e.target) // if the target of the click isn't the container...
				&& container.has(e.target).length === 0) // ... nor a descendant of the container
			{
				$(".pages_bar .roles > a").removeClass("active");
				container.fadeOut();
			}
		});
		
		$('a.nxt').click( function() {
			if (page >= pages) {
				// Do nothing
			} else {
				if (page == 1) { $(this).siblings('.prv').removeClass('inactive'); }
				page = page + 1;
				loadServers(page);
				if (page == pages) {
					$(this).addClass("inactive");
				}
			}
			return false;
		});
		$('a.prv').click( function() {
			if (page <= 1) {
				
			} else {
				if (page == pages) { $(this).siblings('.nxt').removeClass('inactive'); }
				page = page - 1;
				loadServers(page);
				
				if (page == 1) {
					$(this).addClass("inactive");
				}
			}
			return false;
		});
		
		// Load the initial list of safes
		loadServers(1);
		
		$( document ).on( "keyup", "#searchServers", function() {
			search_value = $(this).val();
			loadServers(1);
			
		});
		
		$('#searchServers').on('blur', function(){
		   if ( $(this).val() == '' ) { $(this).addClass('inactive'); $(this).val('search your servers...'); }
		   
		}).on('focus', function(){
			$(this).removeClass('inactive');
			if ( $(this).val() == "search your servers..." ) { $(this).val(''); }
		});
		
		
		
	});

	/* Gunjan code line 823-846 load  Protect your account data */
	function openDisclaimer() {
	//alert('abd');
		$("input[name=currentPassword]").val("");
		$("#continue").addClass("inactive");
		$("div#currentPasswordError").hide();
		
		$(".one-col").fadeTo(400, .3);
		$(".one-col").removeClass("active");
		
		$("#disclaimer .shadow").hide();
		$("#choose_a_safe .shadow").hide();
		
		$("#disclaimer").css({
			opacity: 0
		});
		$("#disclaimer").show();
		
		$("#disclaimer").animate({
			'opacity': 1,
			'margin-left': '500px',
			'margin-top':'205px',
		}, 400);
	}
	//close Protect your account data */
	function closeDisclaimer() {
		$(".one-col").fadeTo(200, 1);
		$(".one-col").addClass("active");
		
		$("#disclaimer").animate({
			'opacity': 0,
			'margin-left': '400px'
		}, 200, function() {
			$("#disclaimer").hide();
		});
	}


	function loadServers(current_page) {
		$.ajax({ 
			type: 'POST', 
			url: 'modules/my_servers.php', 
			data: { server_query: search_value, page: current_page, prot: prot, roles: roles }, 
			dataType: 'json',
			success: function (data) { 					
				$("table#my_servers tbody").empty();
				
				pages = Math.ceil(data.num_results/limit);
				page = current_page;
				
				if (data.num_results != 1) { var ess = "s"; } else { var ess = ""; }
				$(".num_results").text(data.num_results+" server"+ ess);
				
				// Make the previous button inactive if we need to
				if ( page == 1) { $(".prv").addClass("inactive"); }
				if ( pages <= 1 || page == pages) { $(".nxt").addClass("inactive"); } else { $(".nxt").removeClass("inactive"); }
				
				if (first_time == true) {
					$.each(data.roles, function(i, item) {
						$(".pages_bar .roles > ul").append("<li><a href='#' rel='"+item.role_name+"'><i class='fa fa-check-square'></i>"+item.role_name+"</a></li>");
						$(".pages_bar .roles > select").append("<option value='"+item.role_name+"'>"+item.role_name+"</option>");
					});
					first_time = false;
				}
				
				if (data.num_results == 0) {
					if (search_value == "" && prot == "all" && roles == "all") {
						$("table#my_servers tbody").append("<tr><td></td><td colspan='6'>You don't own any servers.</td></tr>");
					}
					else {
						$("table#my_servers tbody").append("<tr><td></td><td colspan='6'>No matching servers.</td></tr>");
					}
				}
				else {
					$("#temp_container").hide();
					$("#results_container").slideDown(300);
					$(".pages").text(pages);
					$(".page").text(page);
					
					$.each(data.results, function(i, item) {
					
						if (item.implemented == "y") {
							var status_class = "pro";
							var status_name = "Yes";
						}
						else {
							var status_class = "un";
							var status_name = "No";
						}
						
						<!--lineItem = "<tr class='"+status_class+"'><td class='status'><span></span></td><td class='search'>"+item.account+"</td><td>"+item.displayName+"</td><td>"+status_name+"</td>";-->
						lineItem = "<tr class='"+status_class+"'><td class='status'><span></span></td><td class='search'>"+item.address+"</td><td>"+item.primary_contact+"</td><td>"+status_name+"</td><td>"+item.confidentiality+"</td><td>"+item.role+"</td><td><a class='transfer' href='#' rel='"+item.ci+"'><i class='fa fa-chevron-circle-right'></i>Transfer Ownership</a></td>";
						if (item.implemented == "n") {// gunjan code line no 915
							lineItem += "<td class='actions'><a class='protect2' rel='"+item.address+"'><i class='fa fa-lock'></i>Protect</a></td>";
						}
						else {// gunjan code line no 918
							//lineItem += "<td class='actions'><a class='protected' rel='"+item.protected+"'><i class='fa fa-lock'></i>Protected</a></td>";
							lineItem += "<td class='actions'><a class='protected' rel='"+item.address+"'><i class='fa fa-lock'></i>Protected</a></td>";
						}
						lineItem += "</tr>";
						
						$("table#my_servers tbody").append(lineItem);
						<!--$("table#my_servers tbody").append("<tr class='"+status_class+"'><td class='status'><span></span></td><td class='search'>"+item.address+"</td><td>"+item.primary_contact+"</td><td>"+status_name+"</td><td>"+item.confidentiality+"</td><td>"+item.role+"</td><td><a class='transfer' href='#' rel='"+item.ci+"'><i class='fa fa-chevron-circle-right'></i>Transfer Ownership</a></td><td><a href='#' class='protect2'><i class='fa fa-lock'></i>Protect</a></td></tr>");-->
						
						if (search_value.length > 0) {
							$("table#my_servers tbody tr td.search").highlight(search_value);
						}
					});
				}
			}
		});
		
	}
	
	
	
	</script>
	<!--Gunjan code 939-941 Page specific files -->
	<script type="text/javascript" src="js/jquery.mockjax.js"></script>
    <script type="text/javascript" src="js/jquery.autocomplete.js"></script>
    <script type="text/javascript" src="js/manage_accounts1.js"></script>
	
	<!--Gunjan code 984-999 css -->
	<style>
	.btncustom {		
		padding: 3px !important;
		width: 64px !important;
	}
	.passwords span.p_error {		
		float: left;		
		margin-top: 20px;
	}
	#txtReconcile{	      
    text-align: center;	   
	width:360px !important;
	}
	#reconcile{
	width:150px;
	}

	</style>
</head>
<body>
<aside id="sidebar">
	<div id="logo">
		<img src="images/logo.png" alt="" /><span>Privileged Account Management Portal</span>
	</div>
	<nav>
		<ul>
			<li><a href="dashboard.php"><b></b><i class="fa fa-tachometer"></i>My Dashboard</a></li>
		</ul>
		
		<?php if ($priv) { ?>
		<h3>Executive Reports</h3>
		<ul>
			<li><a href="server_compliance.php"><b></b><i class="fa fa-laptop"></i>Server Compliance</a></li>
			<li><a href="account_compliance.php"><b></b><i class="fa fa-users"></i>Account Compliance</a></li>
			<li><a href="server_lookup.php"><b></b><i class="fa fa-search"></i>Server Lookup &amp; Reports</a></li>
			<?php if ($core) { ?><li><a href="login_logs.php"><b></b><i class="fa fa-file-text"></i>Log of User Logins</a></li> <?php } ?>
			<?php if ($core) { ?><li><a href="feedback_log.php"><b></b><i class="fa fa-file-text"></i>Feedback Log</a></li> <?php } ?>
			<?php if ($core) { ?><li><a href="acct_policy_updates.php"><b></b><i class="fa fa-file-text"></i>Account Policy Updates</a></li> <?php } ?>
		</ul>
		<?php } ?>
		
		<h3>My Reports</h3>
		<ul>
			<li><a href="my_servers.php"class="active"><b></b><i class="fa fa-desktop"></i>My Server Accounts<?php if ($i != 0 && $j != 0) { echo "<span>{$total_uncompliant}</span>"; } ?></a></li>
			<li><a href="my_accounts.php"><b></b><i class="fa fa-user"></i>My Accounts</a></li>
		</ul>
		
		<h3>Security Actions</h3>
		<ul>
			<li><a href="create_safe.php"><b></b><i class="fa fa-lock"></i>Create/Manage Safes</a></li>
			<li><a href="create_account.php"><b></b><i class="fa fa-briefcase"></i>Add/Manage Accounts</a></li>
			<!--<li><a href="#"><b></b><i class="fa fa-briefcase"></i>Pending Approvals<span>7</span></a></li>-->
			<li><a href="logout.php"><b></b><i class="fa fa-sign-out"></i>Logout</a></li>
		</ul>
	</nav>
</aside>
<h1 id="main"><i class="fa fa-desktop"></i>My Servers
<?php if ($priv) { ?>
<div id="choose"><form method="post" action="set_user.php">Active Directory UID: &nbsp;<input type="text" name="id" value="<?php echo $target; ?>" /><input type="submit" value="Go" /><a href="me.php" id="link_me">Back to me</a></form><span>(Available during beta testing only)</span></div>
<?php } ?>
</h1>
<div id="content">
	<div id="mydash">
		<?php if ($i==0) { ?>
		<div id="welcome">
			<h2>This user isn't in Active Directory, please choose another user.</h2>
		</div>
		<?php } else {?>
		<div class="notice">
			<i class="fa fa-exclamation-triangle"></i>Don't see your server listed?  See if it's one of the <a href="server_report.php?type=not_available"><?php echo $unclaimed; ?> unclaimed servers in the CMDB</a>!
		</div>
		
		<!-- Gunjan code line 992-1023  choose_a_safe html -->	
		<form method="post" id="cs">
				<div id="choose_a_safe" class="window cf">
			<div id="formLoading"><img src="images/large_loader.gif" alt="" /><br />Adding your object!<span>This should take about ten seconds.</span></div>	
			<!--Gunjan code line 994-1002 operating system -->
			<div class="os">
				<label>Which operating system?</label>
				<ul id="os_type" class="acc_picker">
					<li><a href="#" class="active" id="win" rel="windows"><i class="fa fa-check-square"></i>Windows</a></li>
					<li><a href="#" rel="unix" id="uni" ><i class="fa fa-check-square"></i>Unix/Linux/Mac (SSH)</a></li>
				</ul>
				<input type="hidden" name="os_type" id="os_name" value="windows" />
			</div>
			
			<h2><i class="fa fa-list"></i><span>Choose a safe and account usage type</span></h2>
			<div id="protectFull" class="cf">
				<p>
					To protect your account, you must first choose a CyberArk safe to store it in or <a href="create_safe.php">create a new safe</a>:
				</p>
				<div id="safe_list">
					<input type="text" name="safe_search" id="safe_search" class="inactive" value="start typing to search your safes..." />
					<ul>
		
					</ul>
				</div>
				<input type="hidden" name="safeName" />
				
				<!--Gunjan code line 1019-1038 password -->
				<div class="passwords cf">
					<div>
						<label>Current password:</label>
						<input  type="password" name="password" autocomplete="off" /> <span class="req">*</span>
					</div>
					<div>
						<label>Current password (confirm):</label>
						<input   type="password" name="password2" autocomplete="off" /> <span class="req">*</span>
						<input class="btncustom1" type="button" value="Verify" id="pwdSubmit" />						
					</div>					
					<span class="p_error">Your passwords do not match.  Please type them in again.</span>
					
					<label class="extra" style="clear:both">Password type</label>
					<ul id="passwordType" class="acc_picker">
						<li style="width:150px;"><a href="#" class="active" rel="reconcile" id="reconcile"><i class="fa fa-check-square"></i>Reconcile</a></li>		
						<!--gunjan code for setting ajax value 1229 to 1236-->
						<div id="txtReconcile" style="display:none" >
						  
							<div>	Reconcile Account Name :			
								 
							</div>							
						</div>
						
					</ul>
					<input type="hidden" name="passwordType" value="reconcile" />
				</div>
				
				<!--Gunjan code line 1041-1038 password change policy -->
				<label>Password Change Interval Policy</label>
				<ul id="policy_length" class="acc_picker">
					<li><a href="#"  rel="7"><i class="fa fa-check-square"></i>7 days</a></li>
					<li><a href="#" class="active" rel="30"><i class="fa fa-check-square"></i>30 days</a></li>
				</ul>
				<input type="hidden" name="policy_length" value="30" />		
				<div id="formError">
					Please fill out all the required fields before submitting.
				</div>
				<input type="hidden" name="action" value="Add" />
				
				<div class="pages_bar all" id="safe_pages">
					<div class="filters">
						<span class="num_results">0 safes</span>
					</div>
					<div class="page_num">Page <span class="safePage">1</span> of <span class="safePages">1</span></div><a href="#" class="prv inactive" rel="safe"><i class='fa fa-chevron-left'></i></a><a href="#" class="nxt" rel="safe"><i class='fa fa-chevron-right'></i></a>
				</div>
				
				<!--<div id="what_usage">
					<p>How is this account's password used?</p>
					<ul>
						<li><a href="#" rel="manual"><i class="fa fa-check-square"></i><span>Application/Script Account - Password to be changed manually</span></a></li>
						<li><a href="#" rel="automatic"><i class="fa fa-check-square"></i><span>Interactive Account - Password to be changed automatically</span></a></li>
					</ul>
				</div>-->
				
				<a href="#" class="button" id="closeChooseSafe">Go back</a>
				<a href="#" class="button right inactive" id="use_this_safe">Protect this account!</a>
			</div>
		</div>
		</form>
		
		<div class="one-col cf">
			<div>
				<h2><i class="fa fa-user"></i>Servers I own</h2>
				<div id="load_container">
					<div class="pages_bar" style="margin-bottom: 10px;">
						<div class="filters">
							<span class="num_results">300 results</span>
							<input type="text" name="search_query" id="searchServers" class="inactive" value="search your servers..." />
							<div class="prot">
								<a href="#" class="active" rel="all">All</a><a href="#" rel="protected">Protected</a><a href="#" rel="unprotected">Unprotected</a>
							</div>
							<div class="roles">
								<a href="#">All system roles<i class="fa fa-chevron-circle-down"></i></a>
								<ul class="roles_dd">
									<li><a href="#" rel="all" class="active"><i class="fa fa-check-square"></i>All</a></li>
								</ul>
								<select multiple name="roles[]">
									<option value="all">all</option>
								</select>
							</div>
						</div>
						<div class="page_num">Page <span class="page">0</span> of <span class="pages">0</span></div><a href="#" class="prv inactive" rel="search"><i class='fa fa-chevron-left'></i></a><a href="#" class="nxt" rel="search"><i class='fa fa-chevron-right'></i></a>
					</div>
					<table class="my_servers" id="my_servers">
						<thead>
							<tr>
								<th class="status"></th>
								<th>Server name</th>
								<th>Primary Contact</th>
								<th>Protected?</th>
								<th>Confidentiality</th>
								<th>System Role</th>
								<th class='action'>Transfer/Unclaim</th>
								<th>Protect</th>
							</tr>
						</thead>
						<tbody>
						
						</tbody>
					</table>
				</div>
			</div>
			<div>
				<h2><i class="fa fa-users"></i>My team's servers</h2>
				<table class="my_servers team">
					<thead>
						<tr>
							<th class="status"></th>
							<th>Team Member</th>
							<th class='stat'>Owned</th>
							<th class='stat'>In Team</th>
							<th class='stat'>Unprotected</th>
							<th class='comp'>Compliance</th>
							<!-- <th>Action</th> -->
						</tr>
					</thead>
					<tbody>
						<?php
						$users = 0;
						// List of people under you and their compliance
						if ($stmt = $mysqli->prepare("
							SELECT c_id, ad.FullName, ad.UID, server_user_compliance_history.o_compliant, server_user_compliance_history.o_uncompliant, server_user_compliance_history.m_compliant, server_user_compliance_history.m_uncompliant
							FROM server_user_compliance_history
							LEFT JOIN ad ON server_user_compliance_history.UID = ad.UID
							WHERE ad.Manager = ? AND c_id IN (SELECT max(c_id) FROM server_user_compliance_history GROUP BY UID) AND (o_compliant > 0 OR o_uncompliant > 0 OR m_compliant > 0 OR m_uncompliant > 0)
							GROUP BY ad.FullName
							ORDER BY date DESC, ad.FullName ASC
						")) {
							$stmt->bind_param("s", $target);
							$stmt->execute();
							$res = $stmt->get_result();
							while ($row = $res->fetch_assoc()) {
								$user_server_total_compliance = round(100*($row['o_compliant'] + $row['m_compliant'])/($row['o_compliant'] + $row['o_uncompliant'] + $row['m_compliant'] + $row['m_uncompliant']), 1);
								$total_servers_owned = $row['o_compliant'] + $row['o_uncompliant'];
								$total_servers_managed = $row['m_compliant'] + $row['m_uncompliant'];
								$total_unprotected = $row['o_uncompliant'] + $row['m_uncompliant'];
								echo "<tr class='s_row'>";
									echo "<td></td>";
									echo "<td class='server'><a class='fancybox fancybox.iframe nm' href='server_user_report.php?uid={$row['UID']}'>";
										echo $row['FullName'];
									echo "<i class='fa fa-external-link-square'></i></a></td>";
									echo "<td class='stat'>{$total_servers_owned}</td>";
									echo "<td class='stat'>{$total_servers_managed}</td>";
									echo "<td class='stat'>{$total_unprotected}</td>";
									echo "<td class='comp'>";
										echo "<span class='comp'>";
											echo $user_server_total_compliance;
											echo "%";
											if ($user_server_total_compliance == 0) { $user_server_total_compliance = 5; }
										echo "</span>";
										echo "<span class='bar "; if ($user_server_total_compliance >= $green_threshold) { echo 'green'; } else if ($user_server_total_compliance >= $yellow_threshold) { echo 'yellow'; } echo "'>";
											echo "<em style='width: {$user_server_total_compliance}%'></em>";
										echo "</span>";
									echo "</td>";
									//echo "<td class='act'><a class='protect' href='#'><i class='fa fa-chevron-circle-right'></i>Contact</a></td>";
								echo "</tr>";
								$users++;
							}
						}
						if ($users == 0) {
							echo "<tr>";
								echo "<td></td>";
								echo "<td colspan='6'>You don't manage any team members that own servers.</td>";
							echo "</tr>";
						}
					
					?>
					
					</tbody>
				</table>	
			</div>
		</div>
		
		<?php } ?>
	</div>
	
</div>
</body>
</html>


<!-- Gunjan code line 1147-1164 protect your data html-->
<div id="disclaimer" class="window cf">
	<div class="shadow"></div>
	<h2><i class="fa fa-lock"></i><span>Protect your account</span></h2>
	<p>
		If you choose to protect your account, CyberArk will store the password and typically take control over it and change it on a regular interval.
	</p>
	<p>
		If you wish to continue, enter the current account password to verify ownership.
	</p>
	<div id="currentPasswordCont">
		<label id="currentPasswordLabel">Current Password:</label><input type="password" name="currentPassword" id="currentPassword" /><img src="images/loader.gif" alt="" id="currentPasswordLoader" />
	</div>
	<div id="currentPasswordError">
		The password is incorrect.
	</div>
	<a href="#" class="button closeDisclaimer">Go back</a>
	<a href="#" class="button right inactive" id="continue">Authenticate account</a>
</div>
