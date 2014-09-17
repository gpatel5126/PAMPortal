$(function() {

		
		// Initial variables
		var geo = $('select#geoSelect').val();
		var nickname = '';
		var env_code = 'GEN';
		inv_limit = 12;
		safe_limit = 12;
		search_limit = 6;
		var xhr;
		
		inv_search_value = '';
		safe_name = '';
		search_value = '';
		currentSafe = 'none';
		inputFlag = true;
		passwordFlag = true;
		
		// Default object properties
		obj_account_name = "";
		obj_server_name = "";
		obj_account_type = "";
		
		// Safe listing pages
		page = 1;
		pages = 1;
		movePage = 1;
		movePages = 1;
		
		var all_page = 1;	
		inv_page = 1;
		inv_pages = 1;
		
		// ---------------- DO WE HAVE A HASH? ------------------------------------
		if(window.location.hash) {
			safe_name = window.location.hash.substring(1); //Puts hash in variable, and removes the # character			
			openInventory();
		}
		
		// ---------------- OBJECT CREATE PROCESS ------------------------------------
		
		// If you want to create an object 
		$( document ).on( "click", "a.add_new_account", function() {
			openCreate();
			resetForm();
			return false;
		});
		
		// What type of account is this?
		$( document ).on( "click", "a.who", function() {
			var rel = $(this).attr('rel');
			$("a.who").removeClass('active');
			$(this).addClass('active');
			$("input[name=account_type]").val(rel).trigger('change');			
			if (rel == 'db') {
				$("label#sl").text("Database server name");
				$(".os").slideUp();
				$(".db").slideDown();
			}
			else if (rel == 'os') {
				$("label#sl").text("Server name");
				$(".db").slideUp();
				$(".os").slideDown();
			}
			return false;
		});
		
		// Which operating system / policy ID / password type
		$( document ).on( "click", "ul.acc_picker li a", function() {
			var rel = $(this).attr('rel');
			var id = $(this).parent().parent().attr('id');
			$(this).parent().siblings().children('a').removeClass("active");
			$(this).addClass('active');
			$("input[name="+id+"]").val(rel);
			
			return false;
		});
		
		// corp.adobe.com switcher
		$( document ).on( "click", "a#not_corp", function() {
			if ( $(this).hasClass('active') ) {
				$("input[name=not_corp]").val('false');
				$(".corp").show();
				$("input[name=os_server_name], input[name=db_server_name]").animate({
					'width' : '170px'
				});
				$("a#not_corp").removeClass('active');
				$("a#not_corp").text("Not .corp.adobe.com?");
			}
			else {
				$("input[name=not_corp]").val('true');
				$(".corp").hide();
				$("input[name=os_server_name], input[name=db_server_name]").animate({
					'width' : '240px'
				});
				$("a#not_corp").addClass('active');
				$("a#not_corp").text("Back to .corp.adobe.com");
			}
			return false;
		});
		
		// When you submit the form
		$('form#cs').submit( function() {
;			var formHeight = $(this).height() + 5;
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
		});
		
		// Remove/add labels from fields in grey boxes
		$('.grey input').on('blur', function(){
		   var name = $(this).attr('name');
		   var fieldText = getFieldText(name);
		   if ( $(this).attr("name") == "databaseName" && $("input[name=database_type]").val() == 'oracle' ) {
				if ( $(this).val() == '' ) { $(this).addClass('inactive'); $(this).val( "Service name" ); }
		   }
		   else {
				if ( $(this).val() == '' ) { $(this).addClass('inactive'); $(this).val( fieldText ); }
		   }
		}).on('focus', function(){
			var name = $(this).attr('name');
			var fieldText = getFieldText(name);
			if ( $(this).attr("name") == "databaseName" ) {
				if ( $(this).val() == fieldText || $(this).val() == "Service name" ) { $(this).val(''); }
			}
			else {
				if ( $(this).val() == fieldText ) { $(this).val(''); }
			}
			$(this).removeClass('inactive');
		});
		
		// What to do after successful creation
		$( document ).on( "click", "a#back_after_create", function() {
			loadInventory();
			closeCreate();
			return false;
		});
		
		// What to do after an error
		$( document ).on( "click", "a#back_after_error", function() {
			$("#formLoading").fadeOut();
			$("#updateFormLoading").fadeOut();
			return false;
		});
		
		// Match the password fields
		$(document).on("keyup paste", "input[type=password]", function() {
			var passwordVal = $("input[name=password]").val();
			var password2Val = $("input[name=password2]").val();
			
			if ( passwordVal != "" && password2Val != "" ) {
				if ( passwordVal != password2Val ) {
					$(".passwords .p_error").slideDown();
					passwordFlag = true;
				}
				else {
					$(".passwords .p_error").slideUp();
					passwordFlag = false;
				}
			}
		});
		
		// Account for all the required fields
		$(document).on("change keyup paste", "form :input", function() {
			checkForm();
		});
		
		
		// ---------------- END OBJECT CREATE PROCESS --------------------------------
		
		// ------- UPDATE OBJECT PROCESS ------------
		// If you click on the update button
		$( document ).on( "click", "a#update_button", function() {
			objectHeight = $("#object").height();
			if (obj_account_type == "Operating System") { updateHeight = 423; }
			if (obj_account_type == "Database") { updateHeight = 560; }
			
			$("#object").animate({
				'height' : updateHeight
			});
			if (obj_account_type == "Operating System") {
				$("#update_div .db").hide();
				$("#update_div .os").show();
				$("#new_account_name").val(obj_account_name);
				$("#new_os_server_name").val(obj_server_name);
			}
			else if (obj_account_type == "Database") {
				$("#update_div .db").show();
				$("#update_div .os").hide();
				
				$("#new_account_name").val(obj_account_name);
				$("#new_db_server_name").val(obj_server_name);
				$("#new_port").val(obj_port);
				$("#new_database_name").val(obj_database);
			}
			$("#update_div").fadeIn();
			
			
			return false;
		});
		$( document ).on( "click", "a#update_final", function() {
			new_account_name = $("#new_account_name").val();
			
			if (obj_account_type == "Operating System") {
				new_server_name = $("#new_os_server_name").val();
			}
			else {
				new_server_name = $("#new_db_server_name").val();
			}
			new_policy_length = $("#new_policy_length").val();
			
			if (obj_account_type == "Operating System") {
				new_port = "";
				new_database_name = "";
			}
			else {
				new_database_name = $("#new_database_name").val();
				new_port = $("#new_port").val();
			}
			
			var formHeight = $("#object").height() + 5;
			$("#updateFormLoading").css('height',formHeight);
			$("#updateFormLoading").html('<img src="images/large_loader.gif" alt="" /><br />Updating your object!<span>This should take a few seconds.</span>');
			$("#updateFormLoading").fadeIn(700);
			
			$.ajax({
			   type: "POST",
			   url: '../updateObject.php',
			   data: { accountUniqueName: object_name, safeName: safe_name, accountType: obj_account_type, accountName: new_account_name, address: new_server_name, policyID: obj_policy_id, policy_length: new_policy_length, new_database_name: new_database_name, port: new_port },
			   dataType: 'json',
			   success: function(data)
			   {
					if ( data.status == 'Success') {
						$("#updateFormLoading").html("<i class='fa fa-check-circle'></i><br />Your object has been updated!<span>Updates may take a few minutes to fully process.</span><a href='#' class='formReset fButton' id='back_after_update'><i class='fa fa-chevron-circle-left'></i>Back to object!</a>");
						object_name = data.new_object_name;
						
						$("#object h2 span").text(object_name);
					}
					else if ( data.status == 'Failure') {
						var errors = '<ul>';
						for ( var i = 0; i < data.messages.length; i++ ) {
							errors += "<li>" + data.messages[i] + "</li>";
						}
						errors += "</ul>";
						
						$("#updateFormLoading").html("<i class='fa fa-times-circle red'></i><br />There were errors with your request:<span class='errors'>"+ errors + "</span><a href='#' class='formGoBack fButton' id='back_after_error'><i class='fa fa-chevron-circle-left'></i>Go back and fix errors</a>");
					}
			   }
			 });
		});
		
		$( document ).on( "click", "a#back_after_update", function() {
			loadInventory();
			loadObject();
			$("#updateFormLoading").fadeOut();
			$("#update_div").fadeOut();
			return false;
		});
		
		// ------- END UPDATE OBJECT PROCESS ------------
		
		
		
		
		
		// ---------------- START SAFE AND OBJECT TRAVERSAL PROCESSES ----------------
		// If you click on a safe link
		$( document ).on( "click", "table#my_safes tbody tr td a", function() {
			var rel = $(this).attr('rel');
			if (safe_name == "") {
				// Set or reset the inventory variables
				safe_name = rel;
				openInventory();
				$(this).parent().parent().addClass("active");
			}
			return false;
		});
		
		// If you click on a safe row, not the link
		$( document ).on( "click", "#safes.active table#my_safes tbody tr.entry", function() {
			$(this).children("td").children("a").trigger("click");
		});		
		
		// If you click on an object link
		$( document ).on( "click", "table#my_inventory tbody tr td a", function() {
			$(this).parent().parent().trigger("click");
			return false;
		});
		// If you click on an object row
		$( document ).on( "click", "#inventory.active table#my_inventory tbody tr.entry", function() {
			var rel = $(this).children("td").children("a").attr("rel");
			object_name = rel;
			$("#object h2 span").text(object_name);
			openObject();
			$(this).addClass("active");
		});
		
		// If you click to delete an object
		$( document ).on( "click", "a#delete_button", function() {
			objectHeight = $("#object").height();
			$("#object").animate({
				'height' : '270px'
			});
			$("#delete_div h3").text("Are you sure?");
			$("#delete_errors").hide();
			$("#delete_spinner").hide();
			$("#delete_success").hide();
			$("#delete_buttons").show();
			$("#delete_div").fadeIn();
			return false;
		});
		
		// If you click on delete final
		$( document ).on( "click", "a#delete_final", function() {
			
			$("#delete_buttons").hide();
			$("#delete_div h3").text("Deleting this object");
			$("#delete_spinner").fadeIn();
		
			$.ajax({
			   type: "POST",
			   url: '../copyaccount_handler.php',
			   data: { action: 'Delete', safeName: safe_name, objectName: object_name },
			   dataType: 'json',
			   success: function(data)
			   {
					if ( data.status == 'Success') {
						$("#delete_spinner").hide();
						$("#delete_div h3").text("Object Deleted!");
						$("#delete_success").fadeIn();
						
					}
					else if ( data.status == 'Failure') {
						var errors = '<ul>';
						for ( var i = 0; i < data.messages.length; i++ ) {
							errors += "<li>" + data.messages[i] + "</li>";
						}
						errors += "</ul>";
						
						$("#delete_div h3").text("Errors with deletion");
						
						$("#delete_spinner").hide();
						$("#delete_errors div").html(errors);
						$("#delete_errors").fadeIn();
					}
			   }
			 });
			
			return false;
		});
		
		// Go back after delete
		$( document ).on( "click", "a#back_after_delete", function() {
			loadInventory();
			closeObject();
			return false;
		});
		
		// If you want to go back from deleting or moving an object
		$( document ).on( "click", "a.no", function() {
			$("#delete_div").fadeOut();
			$("#move_div").fadeOut();
			$("#update_div").fadeOut();
			$("#object").animate({
				'height' : objectHeight + 42
			});
			return false;
		});
		
		// If you click to move an object
		$( document ).on( "click", "a#move_button", function() {
			objectHeight = $("#object").height();
			$("#object").animate({
				'height' : '500px'
			});
			$("#move_div").fadeIn();
			
			movePage = 1;
			moveSafeQuery = "";
			$("input#moveSafe").addClass("inactive");
			$("input#moveSafe").val("start typing to search your safes...");
			
			
			$("#move_errors").hide();
			$("#move_spinner").hide();
			$("#move_success").hide();
			$("#move_cont").show();
			
			loadMoveSafes();
			
			return false;
		});
		
		// Search your safes that you can move to
		$( document ).on( "keyup", "input#moveSafe", function() {
			movePage = 1;
			moveSafeQuery = $(this).val();
			if (moveSafeQuery.length > 0) { $(this).removeClass("inactive"); }
			loadMoveSafes();
		});
		
		// Search your safes that you can move to
		$('input#moveSafe').on('blur', function(){
		   if ( $(this).val() == '' ) { $(this).addClass('inactive'); $(this).val('start typing to search your safes...'); }
		   
		}).on('focus', function(){
			$(this).removeClass('inactive');
			if ( $(this).val() == "start typing to search your safes..." ) { $(this).val(''); }
		});		
		
		$( document ).on( "click", "#move_safes ul li a", function() {
			$("#move_safes a").removeClass("active");
			$(this).addClass("active");
			
			$("a#move_final").removeClass("inactive");
			
			ObjNewSafeName = $(this).attr('rel');
			return false;
		});
		
		
		$( document ).on( "click", "a#move_final", function() {
			if (ObjNewSafeName != "") {
				$("#move_cont").hide();
				$("#move_div h3").text("Moving object");
				$("#move_spinner").fadeIn();
				$.ajax({
				   type: "POST",
				   url: '../copyaccount_handler.php',
				   data: { action: 'Move', safeName: safe_name, objectName: object_name, newSafeName: ObjNewSafeName },
				   dataType: 'json',
				   success: function(data)
				   {
						if ( data.status == 'Success') {
							$("#move_spinner").hide();
							$("#move_div h3").text("Object moved!");
							$("#move_success").fadeIn();
						}
						else if ( data.status == 'Failure') {
							var errors = '<ul>';
							for ( var i = 0; i < data.messages.length; i++ ) {
								errors += "<li>" + data.messages[i] + "</li>";
							}
							errors += "</ul>";
							$("#move_spinner").hide();
							$("#move_errors div").html(errors);
							$("#move_errors").fadeIn();
						}
				   }
				 });
			}
			return false;
		});
		
		// ------ BEGIN SEARCH ---------
		// Search box
		$( document ).on( "keyup paste", "input[name=objectSearchQuery]", function() {
			objectSearchPage = 1;
			searchObjectQuery = $(this).val();
			if (searchObjectQuery.length > 1) { 
				loadSearchObjects(); 
			}
			else {
				$("ul#searchResults").empty();
			}
		});
		
		// Search box click action
		$('input[name=objectSearchQuery]').on('blur', function(){
		   if ( $(this).val() == '' ) { $(this).addClass('inactive'); $(this).val('search individual objects...'); }
		   
		}).on('focus', function(){
			$(this).removeClass('inactive');
			if ( $(this).val() == "search individual objects..." ) { $(this).val(''); }
		});	
		
		// If you click on a search result
		$( document ).on( "click", "a.search_result", function() {
			object_name = $(this).attr('rel');
			safe_name = $(this).attr('href').substring(1);
			$("#inventory h2 span").text(safe_name);
			openObjectFromSearch();
			$("#object h2 span").text(object_name);
			return false;		
		});
		// ------ END SEARCH ---------
		
		// Only allow numbers in the port box
		$('input[name=port]').keyup(function () {
			if (this.value != this.value.replace(/[^0-9\.]/g, '')) {
			   this.value = this.value.replace(/[^0-9\.]/g, '');
			}
		});
		
		// If you want to go back to the safe list
		$( document ).on( "click", "a.backToSafes", function() {
			openSafeList();
		});
		
		// The drivers for all the previous and next buttons
		$('a.nxt').click( function() {
			var type = $(this).attr('rel');
			// Page through safes
			if (type == 'safes') {
				if (page >= pages) {
					// Do nothing
				} else {
					if (page == 1) { $(this).siblings('.prv').removeClass('inactive'); }
					page = page + 1;
					loadSafes();
					
					if (page == pages) {
						$(this).addClass("inactive");
					}
				}
			}
			// Page through safe inventory
			else if (type == 'inv') {
				if (inv_page >= inv_pages) {
					// Do nothing
				} else {
					if (inv_page == 1) { $(this).siblings('.prv').removeClass('inactive'); }
					inv_page = inv_page + 1;
					loadInventory();
					
					if (inv_page == inv_pages) {
						$(this).addClass("inactive");
					}
				}
			}
			// Page through the lists of safes you can move to
			else if (type == 'move') {
				if (movePage >= movePages) {
					// Do nothing
				} else {
					if (movePage == 1) { $(this).siblings('.prv').removeClass('inactive'); }
					movePage = movePage + 1;
					loadMoveSafes();
					
					if (movePage == movePages) {
						$(this).addClass("inactive");
					}
				}
			}
			return false;
		});
		$('a.prv').click( function() {
			var type = $(this).attr('rel');
			// Page through safes
			if (type == 'safes') {
				if (page <= 1) {
					
				} else {
					if (page == pages) { $(this).siblings('.nxt').removeClass('inactive'); }
					page = page - 1;
					loadSafes();
					
					if (page == 1) {
						$(this).addClass("inactive");
					}
				}
			}
			// Page through safe inventory
			else if (type == 'inv') {
				if (inv_page <= 1) {
					
				} else {
					if (inv_page == inv_pages) { $(this).siblings('.nxt').removeClass('inactive'); }
					inv_page = inv_page - 1;
					loadInventory();
					
					if (inv_page == 1) {
						$(this).addClass("inactive");
					}
				}
			}
			// Page through the lists of safes you can move to
			else if (type == 'move') {
				if (movePage <= 1) {
					
				} else {
					if (movePage == movePages) { $(this).siblings('.nxt').removeClass('inactive'); }
					movePage = movePage - 1;
					loadMoveSafes();
					
					if (movePage == 1) {
						$(this).addClass("inactive");
					}
				}
			}
			
			return false;
		});
		
		// Load safes intially when loading the page
		loadSafes();
		
		$("ul#database_type a").click( function() {
			if ( $("input[name=databaseName]").val() == "Service name" || $("input[name=databaseName]").val() == "Database name" ) {
				if ( $(this).attr('rel') == 'oracle' ) {
					$("input[name=databaseName]").addClass("inactive").val("Service name");
				}
				else {
					$("input[name=databaseName]").addClass("inactive").val("Database name");
				}
			}
		});
		
		// Close a safe's inventory when clicked
		$(".closeInventory").click( function() {
			closeInventory();
			return false;
		});
		// Close an object when clicked
		$(".closeObject").click( function() {
			closeObject();
			return false;
		});
		// Close the create form when clicked
		$(".closeCreate").click( function() {
			closeCreate();
			resetForm();
			return false;
		});
	});
	
	function openObjectFromSearch() {
		loadInventory();
		$("#safes").removeClass("active");
		$("#inventory").css({
			opacity: 0
		});
		$("#inventory").show();
		$("#inventory").animate({
			'opacity': 1,
			'margin-left': '100px'
		}, 400);
		$("#safes").fadeTo(400, .1);
		$("#inventory").css({
			'border' : '1px solid #f0f0f0'
		});
		
		var height = $("#inventory").height() + 40;
		$(".shader").css({
			'height' : height
		});
		$(".shader").fadeIn(400);
		
		$("#object").css({
			opacity: 0
		});
		$("#object").show();
		
		$("#object").animate({
			'opacity': 1,
			'margin-left': '300px'
		}, 400);
		
		$("#delete_div").hide();
		
		$("#move_div").hide();
		$("#move_div h3").text("Where do you want to move this?");
		$("#move_errors").hide();
		$("#move_spinner").hide();
		$("#move_success").hide();
		$("#move_cont").show();
		
		
		$("#object").css("height", "auto");
		
	}
	
	function openInventory() {
		// Reset variables
		inv_page = 1;
		inv_search_value = "";
		$("#inventory h2 span").text(safe_name);
		
		$("#inventory").addClass("active");  // Make the inventory window the only active one
		loadInventory();  // Load the safe's inventory
		
		// Fade out the safe lists
		$("#safes").fadeTo(400, .3);
		$("#safes").removeClass("active");
		
		// Bring the inventory window to the front
		$("#inventory").css({ opacity: 0 });
		$("#inventory").show();
		$("#inventory").animate({
			'opacity': 1,
			'margin-left': '200px'
		}, 400);
	}
	function closeInventory() {
		$("#safes").fadeTo(200, 1);
		$("#safes").addClass("active");
		$("#inventory").removeClass("active");
		
		$("#inventory").animate({
			'opacity': 0,
			'margin-left': '300px'
		}, 200, function() {
			$("#inventory").hide();
		});
		
		$("#my_safes tr").removeClass("active");
		
		safe_name = "";
	}
	function openCreate() {
	
		$("#safes").fadeTo(400, .1);
		$("#inventory").css({
			'border' : '1px solid #f0f0f0'
		});
		var height = $("#inventory").height() + 40;
		$(".shader").css({
			'height' : height
		});
		$(".shader").fadeIn(400);
		$("#inventory").animate({
			'margin-left': '100px'
		}, 400);
		
		$("#safe_to_store").text(safe_name);
		$("input[name=safeName]").val(safe_name);
		
		$("#createObject").css({
			opacity: 0
		});
		$("#createObject").show();
		
		$("#createObject").animate({
			'opacity': 1,
			'margin-left': '300px'
		}, 400);		
		
		$("#createObject").css("height", "auto");
	
	}
	function openObject() {
		$("#safes").fadeTo(400, .1);
		$("#inventory").css({
			'border' : '1px solid #f0f0f0'
		});
		var height = $("#inventory").height() + 40;
		$(".shader").css({
			'height' : height
		});
		$(".shader").fadeIn(400);
		$("#inventory").animate({
			'margin-left': '100px'
		}, 400);
		
		$("#object").css({
			opacity: 0
		});
		$("#object").show();
		
		$("#object").animate({
			'opacity': 1,
			'margin-left': '300px'
		}, 400);
		
		$("#delete_div").hide();
		
		$("#move_div").hide();
		$("#move_div h3").text("Where do you want to move this?");
		$("#move_errors").hide();
		$("#move_spinner").hide();
		$("#move_success").hide();
		$("#move_cont").show();
		
		loadObject();
	
	}
	function loadObject() {
		$("#object").css("height", "auto");
		$("#objectLoader").show();
		$("#objectDetails").empty();
		$(".object_actions").hide();
		
		$.ajax({ 
			type: 'POST', 
			url: 'displayObject.php', 
			data: { accountUniqueName: object_name, safeName: safe_name }, 
			dataType: 'json',
			success: function (data) { 	
				$("#objectLoader").hide();
				if (data.status == "Success") {				
					obj_account_name = data.results.accountName.value;
					obj_server_name = data.results.address.value;
					obj_account_type = data.results.deviceType.value;
					obj_port = data.results.port.value;
					obj_database = data.results.database.value;
					obj_policy_id = data.results.policyId.value;
					
					
					// Change button view based on access
					if (data.access == "Yes") { $(".object_actions").show(); }
					else { $(".object_actions").hide(); }
					
					$.each(data.results, function(i, item) {
						if (item.show == "yes" && (item.value != "" && item.value != "error")) {
							$("#objectDetails").append("<tr><td>"+ item.name +"</td><td>"+ item.value +"</td></tr>");
						}
					});
				}
				else {
					$("#objectDetails").append("<tr><td class='wide'>Couldn't find the object.  Try again?</td></tr>");
				}
			}
		});
	}
	function closeObject() {
		$("#safes").fadeTo(200, .3);
		$("#inventory").css({
			'border' : '1px solid #e8e8e8'
		});
		$(".shader").fadeOut(200);
		$("#inventory").animate({
			'opacity': 1,
			'margin-left': '200px'
		}, 200);
		
		$("#object").animate({
			'opacity': 0,
			'margin-left': '500px'
		}, 200, function() {
			$("#object").hide();
		});
		
		$("#my_inventory tr").removeClass("active");
	}
	function closeCreate() {
		$("#formLoading").hide();
		$("#safes").fadeTo(200, .3);
		$("#inventory").css({
			'border' : '1px solid #e8e8e8'
		});
		$(".shader").fadeOut(200);
		$("#inventory").animate({
			'opacity': 1,
			'margin-left': '200px'
		}, 200);
		
		$("#createObject").animate({
			'opacity': 0,
			'margin-left': '500px'
		}, 200, function() {
			$("#createObject").hide();
		});
		
		$("#my_inventory tr").removeClass("active");
	}
	
	function checkForm() {
		var account_type = $("input[name=account_type]").val();
		var account_name = $("input[name=account_name]").val();
		var os_server_name = $("input[name=os_server_name]").val();
		var db_server_name = $("input[name=db_server_name]").val();
		var port = $("input[name=port]").val();
		var databaseName = $("input[name=databaseName]").val();
		var password = $("input[name=password]").val();
		var password2 = $("input[name=password2]").val();
		
		if (account_name == "" || password == "" || password2 == "") {
			inputFlag = true;
		}
		else {
			inputFlag = false;
			if (account_type == "os") {
				if (os_server_name == "" || os_server_name == "Server name") { inputFlag = true; }
			}
			else if (account_type == "db") {
				if (db_server_name == "" || db_server_name == "Database server name" || port == "" || port == "Port #" || databaseName == "" || databaseName == "Database name" || databaseName == "Service name") { inputFlag = true; }
			}
		}
		
		if (inputFlag == true || passwordFlag == true) {
			$('form#cs input[type="submit"]').attr('disabled','disabled');
			$('#formError').show();
		}
		else {
			$('form#cs input[type="submit"]').removeAttr('disabled');
			$('#formError').hide();
		}
	}
	
	function resetForm() {
		$(".db").hide();
		$(".os").show();
		
		$(".who").removeClass('active');
		$(".who[rel=os]").addClass('active');
		$("input[name=account_type]").val("os");
		
		$("#os_type a").removeClass("active");
		$("#os_type a[rel=windows]").addClass("active");
		$("input[name=os_type]").val("windows");
		
		$("#database_type a").removeClass("active");
		$("#database_type a[rel=mssql]").addClass("active");
		$("input[name=database_type]").val("mssql");
		
		$("input[name=account_name]").val('');
		
		$("input[name=not_corp]").val('false');
		$(".corp").show();
		$("input[name=os_server_name], input[name=db_server_name]").css({
			'width' : '170px'
		});
		$("a#not_corp").removeClass('active');
		$("a#not_corp").text("Not .corp.adobe.com?");
		
		$("input[name=os_server_name]").addClass("inactive").val("Server name");
		$("input[name=db_server_name]").addClass("inactive").val("Database server name");
		$("input[name=port]").addClass("inactive").val("Port #");
		$("input[name=databaseName]").addClass("inactive").val("Database name");
		
		$("#policy_length a").removeClass("active");
		$("#policy_length a[rel=7]").addClass("active");
		$("input[name=policy_length]").val("7");
		
		$("input[name=password]").val('');
		$("input[name=password2]").val('');
		
		$("#passwordType a").removeClass("active");
		$("#passwordType a[rel=keep]").addClass("active");
		$("input[name=passwordType]").val("keep");	
		
	}
	
	// Function to get the field text
	function getFieldText(name) {
		if (name == "db_server_name") { return "Database server name"; }
		else if (name == "os_server_name") { return "Server name"; }
		else if (name == "port") { return "Port #"; }
		else if (name == "databaseName") { return "Database name"; }
	}
	
	// Function to load the safes
	function loadSafes() {
		$.ajax({ 
			type: 'POST', 
			url: '../modules/my_safes.php', 
			data: { safe_query: search_value, page: page, limit: safe_limit }, 
			dataType: 'json',
			success: function (data) { 		
				
				$("table#my_safes tbody").empty();
				
				pages = Math.ceil(data.num_results/safe_limit);
				
				if (data.num_results != 1) { var ess = "s"; } else { var ess = ""; }
				$(".num_results").text(data.num_results+" safe"+ ess);
				
				// Make the previous button inactive if we need to
				if ( page == 1) { $(".prv").addClass("inactive"); }
				if ( pages <= 1 || page == pages) { $(".nxt[rel=safes]").addClass("inactive"); } else { $(".nxt[rel=safes]").removeClass("inactive"); }
				
				if (data.num_results == 0) {
					if (search_value == "") {
						$("table#my_safes tbody").append("<tr><td colspan='4'>You don't have access to any safes.</td></tr>");
					}
					else {
						$("table#my_safes tbody").append("</td><td colspan='4'>No matching safes.</td></tr>");
					}
					$(".pages").text(1);
					$(".page").text(1);
					
				}
				else {
					$(".pages").text(pages);
					$(".page").text(page);
					
					$.each(data.results, function(i, item) {
					
						$("table#my_safes tbody").append("<tr class='entry'><td class='search'>"+item.name+"</td><td>"+item.created+"</td><td>"+item.role+"</td><td><a href='#' class='advance' rel='"+item.name+"'><i class='fa fa-chevron-circle-right'></i>Open</a></td></tr>");
						
						if (search_value.length > 0) {
							$("table#my_safes tbody tr td.search").highlight(search_value);
						}
					});
				}
			}
		});
	}
	
	function loadMoveSafes() {
		$.ajax({ 
			type: 'POST', 
			url: '../modules/my_safes.php', 
			data: { safe_query: moveSafeQuery, page: movePage, limit: 4, currentSafe: safe_name, access: 'limited' }, 
			dataType: 'json',
			success: function (data) { 		
				$("#move_safes ul").empty();
				
				ObjNewSafeName = "";
				$("a#move_final").addClass("inactive");
				
				movePages = Math.ceil(data.num_results/4);
				
				// Make the previous button inactive if we need to
				if ( movePage == 1) { $(".prv[rel=move]").addClass("inactive"); }
				if ( movePages <= 1 || movePage == movePages) { $(".nxt[rel=move]").addClass("inactive"); } else { $(".nxt[rel=move]").removeClass("inactive"); }
				
				if (data.num_results == 0) {
					if (moveSafeQuery == "") {
						$("#move_safes ul").append("<li>You don't have access to any safes.</li>");
					}
					else {
						$("#move_safes ul").append("<li>No matching safes.</li>");
					}		
					$("em.movePages").text("1");
					$("em.movePage").text("1");
				}
				else {
					$("#move_pages .num_results").text(data.num_results + " safes");
					$("em.movePages").text(movePages);
					$("em.movePage").text(movePage);
					$.each(data.results, function(i, item) {		
						$("#move_safes ul").append("<li><a href='#' rel='"+item.name+"'><i class='fa fa-check-square'></i>"+ item.name +"</a></li>");
					});
				}
			}
		});
	}
	
	function loadInventory() {
		$.ajax({ 
			type: 'POST', 
			url: '../modules/safe_inventory.php', 
			data: { safe_name: safe_name, inventory_query: inv_search_value, page: inv_page, limit: inv_limit }, 
			dataType: 'json',
			success: function (data) { 		
				$("table#my_inventory tbody").empty();  // Empty the inventory table
				
				// If we have owner/controller rights, show the "Add" button
				if (data.can_create == "YES") { $(".add_new_account").show(); }
				else { $(".add_new_account").hide(); }
				
				inv_pages = Math.ceil(data.num_results/safe_limit); // Calculate the number of pages
				
				if (data.num_results != 1) { var ess = "s"; } else { var ess = ""; }
				$(".inv_num_results").text(data.num_results+" object"+ ess);
				
				// Make the previous and next buttons inactive if we need to
				if ( inv_page == 1) { $(".prv[rel=inv]").addClass("inactive"); }
				if ( inv_pages <= 1 || inv_page == inv_pages) { $(".nxt[rel=inv]").addClass("inactive"); } else { $(".nxt[rel=inv]").removeClass("inactive"); }
				
				// If there's no results
				if (data.num_results == 0) {
					if (inv_search_value == "") {
						$("table#my_inventory tbody").append("<tr><td colspan='2'>This safe is empty!</td></tr>");
					}
					else {
						$("table#my_inventory tbody").append("<tr><td colspan='2'>No matching objects.</td></tr>");
					}
				}
				else {
					$(".inv_pages").text(inv_pages);
					$(".inv_page").text(inv_page);
					$.each(data.results, function(i, item) {
						$("table#my_inventory tbody").append("<tr class='entry'><td class='search'>"+item.name+"</td><td><a href='#' class='advance' rel='"+item.name+"'><i class='fa fa-search'></i>View</a></td></tr>");
						if (inv_search_value.length > 0) {
							$("table#my_inventory tbody tr td.search").highlight(search_value);
						}
					});
				}
			}
		});
	}
	
	function loadSearchObjects() {
		$.ajax({ 
			type: 'POST', 
			url: '../modules/search_objects.php', 
			data: { query: searchObjectQuery }, 
			dataType: 'json',
			success: function (data) { 		
				
				$("ul#searchResults").empty();
				
				can_create = data.can_create;
				
				objectSearchPages = Math.ceil(data.num_results/search_limit);
				
				//if (data.num_results != 1) { var ess = "s"; } else { var ess = ""; }
				var ess = "s";
				$(".search_num_results").text(data.num_results+" object"+ ess);
				
				// Make the previous button inactive if we need to
				if ( objectSearchPage == 1) { $(".prv[rel=inv]").addClass("inactive"); }
				if ( objectSearchPages <= 1 || objectSearchPages == objectSearchPage) { $(".nxt[rel=objSearch]").addClass("inactive"); } else { $(".nxt[rel=objSearch]").removeClass("inactive"); }
				
				if (data.num_results == 0) {
					$("ul#searchResults").append("<li>No matching objects.</li>");					
				}
				else {
					//$(".inv_pages").text(inv_pages);
					//$(".inv_page").text(inv_page);
					
					$.each(data.results, function(i, item) {
						$("ul#searchResults").append("<li><a href='#"+item.safeName+"' class='search_result' rel='"+item.objectName+"'><div class='objectSearchName'>"+item.objectName+"</div><div class='safeSearchName'><label>Safe:</label>"+item.safeName+"</div></a></li>");
						
						$("ul#searchResults li a .objectSearchName").highlight(searchObjectQuery);
					});
				}
			}
		});
	}