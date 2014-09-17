$(function() {
	
		var geo = $('select#geoSelect').val();
		var nickname = '';
		var env_code = 'GEN';
		list_limit = 6;
		var xhr;
		
		var owned_page = 1;
		var access_page = 1;
				
		
		$("form").bind("keypress", function (e) {
			if (e.keyCode == 13) {
				return false;
			}
		});
		
		$("#safe_access").load('../modules/safe_access.php?limit='+list_limit, function() {
			var safe_access_height = $("#safe_access").height();
			var safe_access_width = $("#safe_access").width();
			
			$('.pages_loading#access_loading').css({
				'height' : safe_access_height,
				'line-height' : safe_access_height+'px',
				'margin-top' : -safe_access_height,
				'width' : safe_access_width
			});
		});
		$("#safe_owner").load('../modules/safe_owner.php?limit='+list_limit, function() {
			var safe_owner_height = $("#safe_owner").height();
			var safe_owner_width = $("#safe_owner").width();
			
			$('.pages_loading#owned_loading').css({
				'height' : safe_owner_height,
				'line-height' : safe_owner_height+'px',
				'margin-top' : -safe_owner_height,
				'width' : safe_owner_width
			});
		});
		
		$("a.who").click( function() {
			var optionPicked = $(this).attr('id');
			
			if ( $(this).hasClass('active') ) {
			
			} else {
				$('input[name="who"]').val(optionPicked);
				$('a.who').removeClass("active");
				$(this).addClass('active');
				
				if (optionPicked == 'just_me') {
					$('#members_fields').slideUp();
				}
				else {
					$('#members_fields').slideDown();
				}
			}
			return false;			
		});
		
		$('#nickname').unbind('keyup change input paste').bind('keyup change input paste',function(e){
			if (this.value.match(/[^a-zA-Z0-9 _]/g)) { 
				this.value = this.value.replace(/[^a-zA-Z0-9 _]/g, ''); 
			}
			else {
				var $this = $(this);
				var val = $this.val();
				var valLength = val.length;
				var maxCount = 21;
				if(valLength>maxCount){
					$this.val($this.val().substring(0,maxCount));
				}
				else {
					$(".length_error").hide();
					nickname = $(this).val();
					updateSafeName(nickname, geo, env_code);
				}
			}
		});
		
		$("a.where").click( function() {
			var geoToSelect = $(this).attr('id');
			$('a.where').removeClass("active");
			$(this).addClass('active');
			
			$('select#geoSelect').val(geoToSelect);
			
			geo = $('select#geoSelect').val();
			
			updateSafeName(nickname, geo, env_code);
			
			return false;			
		});
		
		$( document ).on( "click", "a.remove", function() {
			var idToRemove = $(this).attr('rel');
			idToRemove = idToRemove.replace('u_', '');
			
			$("#members table tbody tr#u_"+idToRemove).fadeOut().remove();
			$("#membersSelect option#u_"+idToRemove).remove();
			$("#controllersSelect option#c_"+idToRemove).remove();
			
			if ( $('#members table tbody').children(':visible').length == 0) {
				$('tr.initial').fadeIn();
			}
			
			return false;
		});
		
		$( document ).on( "click", "a.formGoBack", function() {
			$("#formLoading").fadeOut();
			
			return false;
		});
		
		// Open environments window
		$(".envs > a").click( function() {
			$('#environments').fadeIn().css("display","inline-block");
			return false;
		});
		
		// Select environment on click
		$("#environments a").click( function() {
			env_code = $(this).attr('rel');
			var text = $(this).text();
			
			$("#env_name").text(text);
			$("#env_input").val(env_code);
			$("#environments a").removeClass('active');
			$(this).addClass('active');
			
			$("#environments").hide();
			
			updateSafeName(nickname, geo, env_code);
		});
		
		// Hide environments div when we click outside
		$(document).mouseup(function (e)
		{
			var container = $("#environments");
			if (!container.is(e.target) // if the target of the click isn't the container...
				&& container.has(e.target).length === 0) // ... nor a descendant of the container
			{
				container.fadeOut();
			}
		});
		
		$( document ).on( "click", "a.formReset", function() {
			$("#formLoading").fadeOut();
			
			// Clear out the safe name
			$("#nickname").val('');
			$("#safeNameInput").val('');
			nickname = '';
			
			// Go back to just me
			$("form#cs input[name=who]").val('just_me');
			$(".who").removeClass('active');
			$(".who#just_me").addClass('active');
			
			// Clear the environment
			$("#env_input").val('GEN');
			$("#environments a").removeClass("active");
			$("#environments a").first().addClass("active");
			$("#env_name").text("General Environment");
			
			// Clear the description
			$('textarea').val('');
			
			// Clear out the selected members
			$('#membersSelect').find('option').remove().end();
			$('#controllersSelect').find('option').remove().end();
			$('#members table tbody tr').not('.initial').remove();
			$('#members table tbody tr.initial').fadeIn();
			$('#members_fields').slideUp();
			$('#autocomplete').val('Search for an ActiveDirectory user');
			
			// Back to SJC
			$("select#geoSelect").val('SJ');
			$("a.where").removeClass("active");
			$("a.where#SJ").addClass("active");
			geo = $("#geoSelect").val();
			
			// Close the safe name field
			$("#safeNameCont").slideUp();
			
			return false;
		});
		
		$('#autocomplete').focus( function() {
			if ( $(this).val() == 'Search for an ActiveDirectory user') {
				$(this).val('');
			}
		});
		
		$( document ).on( "click", "a.m_type", function() {
			var idToRemove = $(this).attr('rel');
			idToRemove = idToRemove.replace('u_', '');
			
			if ( $(this).hasClass('active') ) {
				
			}
			else {
				$(this).parent().children('a').removeClass('active');
				$(this).addClass('active');
				
				// If we clicked on "controllers"
				if ( $(this).hasClass('controllers') ) {
					$("#membersSelect option#u_"+idToRemove).remove();
					$("#controllersSelect").append("<option id='c_"+idToRemove+"' value='"+idToRemove+"' selected>"+idToRemove+"</option>");
				}
				// If we clicked on "members"
				else {
					$("#controllersSelect option#c_"+idToRemove).remove();
					$("#membersSelect").append("<option id='u_"+idToRemove+"' value='"+idToRemove+"' selected>"+idToRemove+"</option>");
				}
				
				
			}
			
			
			return false;
		});
		
		$('#autocomplete').autocomplete({
			serviceUrl: '/autocomplete/uid.php',
			lookupLimit: 10,
			minChars: 2,
			//triggerSelectOnValidInput: false,
			onSelect: function (suggestion) {
				//alert('You selected: ' + suggestion.value + ', ' + suggestion.data);
				$(this).val('Search for an ActiveDirectory user');
				$(this).blur();
				
				if ( $('tr.initial').is(':visible') ) {
					$('tr.initial').hide();
				}
				
				// If the user doesn't already exist
				if( $("tr#u_" + suggestion.data).length == 0) {
					$("#members table tbody").append($('<tr id="u_'+suggestion.data+'"><td>'+ suggestion.value +'</td><td>'+ suggestion.data +'</td><td><a href="#" rel="u_'+suggestion.data+'" class="active m_type members">Member</a><a href="#" rel="u_'+suggestion.data+'" class="m_type controllers">Controller</a></td><td><a href="#" rel="'+suggestion.data+'" class="remove"><i class="fa fa-times-circle red"></i></a></td></tr>').hide().fadeIn(500));
					$("#membersSelect").append("<option id='u_"+suggestion.data+"' value='"+suggestion.data+"' selected>"+suggestion.value+"</option>");
				}
				
				
			}
		});
		
		
		
		$('form#cs').submit( function() {
			var formHeight = $(this).height() + 5;
			$("#formLoading").css('height',formHeight);
			$("#formLoading").html('<img src="images/large_loader.gif" alt="" /><br />Creating your safe!<span>This should take about ten seconds.</span>');
			$("#formLoading").fadeIn(700);
			
			$.ajax({
			   type: "POST",
			   url: 'add_safe.php',
			   data: $("form#cs").serialize(), // serializes the form's elements.
			   dataType: 'json',
			   success: function(data)
			   {
					if ( data.status == 'Success') {
						$("#formLoading").html("<i class='fa fa-check-circle'></i><br />Your safe has been created!<span>Safe created with name '"+data.safeName+"'</span><a href='#' class='formReset fButton'><i class='fa fa-chevron-circle-right'></i>Create another safe!</a>");
						// Update the owned and member tables
						
						$("#safe_access").load('../modules/safe_access.php?limit='+list_limit);
						$("#safe_owner").load('../modules/safe_owner.php?limit='+list_limit);
						loadPageNums();
						
					}
					else if ( data.status == 'Failure') {
						var errors = '<ul>';
						for ( var i = 0; i < data.messages.length; i++ ) {
							errors += "<li>" + data.messages[i] + "</li>";
						}
						errors += "</ul>";
						
						$("#formLoading").html("<i class='fa fa-times-circle red'></i><br />There were errors with your request:<span class='errors'>"+ errors + "</span><a href='#' class='formGoBack fButton'><i class='fa fa-chevron-circle-left'></i>Go back and fix errors</a>");
					}
			   }
			 });
			
			return false;
		});
		
		$('a.nxt').click( function() {
			var type = $(this).attr('rel');
			if (type == 'owned') {
				if (owned_page >= owned_pages) {
					// Do nothing
				} else {
					if (owned_page == 1) { $(this).siblings('.prv').removeClass('inactive'); }
					owned_page = owned_page + 1;
					changePage(type, owned_page, owned_pages);
					
					if (owned_page == owned_pages) {
						$(this).addClass("inactive");
					}
				}
			}
			else if (type == 'access') {
				if (access_page >= access_pages) {
					// Do Nothing
				} else {
					if (access_page == 1) { $(this).siblings('.prv').removeClass('inactive'); }
					access_page = access_page + 1;
					changePage(type, access_page, access_pages);
					if (access_page == access_pages) {
						$(this).addClass("inactive");
					}
				}
			}
			return false;
		});
		$('a.prv').click( function() {
			var type = $(this).attr('rel');
			if (type == 'owned') {
				if (owned_page <= 1) {
					
				} else {
					if (owned_page == owned_pages) { $(this).siblings('.nxt').removeClass('inactive'); }
					owned_page = owned_page - 1;
					changePage(type, owned_page, owned_pages);
					
					if (owned_page == 1) {
						$(this).addClass("inactive");
					}
				}
			}
			else if (type == 'access') {
				if (access_page <= 1) {
					// Do nothing
				} else {
					if (access_page == access_pages) { $(this).siblings('.nxt').removeClass('inactive'); }
					access_page = access_page - 1;
					changePage(type, access_page, access_pages);
					
					if (access_page == 1) {
						$(this).addClass("inactive");
					}
				}
			}
			return false;
		});
		
		loadPageNums();
	});
	
	function updateSafeName(nickname, geo, env) {
		var currentRequest;
		var cleansed_nickname = nickname.replace(/ /g,"_");
		cleansed_nickname = cleansed_nickname.toLowerCase();
		
		var finalName = geo+"_"+cleansed_nickname+"_"+env;
		
		$('#safeName span').text(finalName);
		$('#safeNameInput').val(cleansed_nickname);
		
		$("#safeNameCont").slideDown();
		
		$("#safeLoader").show();
			
		currentRequest = $.ajax({
			dataType: "json",
			url: '../modules/check_safe_names.php?safe='+finalName,
			beforeSend : function()    {           
				if(currentRequest != null) {
					currentRequest.abort();
				}
			},
			success: function(data) {
				if (data.greenLight == "false") {
					$("#nameError").text("This safe name already exists.").show();
					$('input[type="submit"]').attr('disabled','disabled');
				}
				else {
					if (cleansed_nickname.length == 0) {
						$('input[type="submit"]').attr('disabled','disabled');
						$("#nameError").text("You must enter a safe nickname.").show();
					}
					else {
						$("#nameError").hide();
						$('input[type="submit"]').removeAttr('disabled');
					}
				}
				
				$("#safeLoader").hide();
			}
		});
			/*
			xhr = $.getJSON( "../modules/check_safe_names.php?safe="+finalName).done( function( data ) {
				$("#safeLoader").show();
				if (data.greenLight == "false") {
					$("#nameError").text("This safe name already exists.").show();
					$('input[type="submit"]').attr('disabled','disabled');
				}
				else {
					$("#nameError").hide();
					$('input[type="submit"]').removeAttr('disabled');
				}
				
				$("#safeLoader").hide();
			});
			*/
	}
	
	// Function to change the pages of the lists
	function changePage(list, page, pages) {
		if (list == 'owned') {
			var safe_owner_height = $("#safe_owner").height();
			var safe_owner_width = $("#safe_owner").width();
			$('.pages_loading#owned_loading').css({
				'height' : safe_owner_height,
				'line-height' : safe_owner_height+'px',
				'margin-top' : -safe_owner_height,
				'width' : safe_owner_width
			});
			
			$("#owned_page").text(page);
			$("#owned_pages").text(pages);
			$("#owned_loading.pages_loading").show();
			$("#safe_owner").load('../modules/safe_owner.php?limit='+list_limit+'&page='+page, function() {
				$("#owned_loading.pages_loading").hide();
			});
		}
		else {
			var safe_access_height = $("#safe_access").height();
			var safe_access_width = $("#safe_access").width();
			$('.pages_loading#access_loading').css({
				'height' : safe_access_height,
				'line-height' : safe_access_height+'px',
				'margin-top' : -safe_access_height,
				'width' : safe_access_width
			});
			
			$("#access_page").text(page);
			$("#access_pages").text(pages);
			$("#access_loading.pages_loading").show();
			$("#safe_access").load('../modules/safe_access.php?limit='+list_limit+'&page='+page, function() {
				$("#access_loading.pages_loading").hide();
			});
		}		
	}
	
	// Function to refresh the lists of safes owned and having access to after a safe creation request
	function loadPageNums() {
		$.getJSON( "../modules/get_safe_pages.php?limit="+list_limit, function( data ) {
			owned_pages = data.owner_pages;
			access_pages = data.access_pages;
			
			if (owned_pages < 2) {
				$(".pages_bar.owned").hide();
			}
			else {
				$("#owned_pages").text(owned_pages);
			}
			
			if (access_pages < 2) {
				$(".pages_bar.access").hide();
			}
			else {
				$("#access_pages").text(access_pages);
			}
			
		});
		
	}