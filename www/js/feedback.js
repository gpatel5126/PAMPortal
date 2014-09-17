$(function() {	
	$("body").append('<div id="feedback_form"><a href="#" class="close"><i class="fa fa-times-circle"></i></a><form method="post"><fieldset><label>Your PAM Portal Feedback:</label><textarea name="feedback"></textarea><input type="submit" value="Submit" /></fieldset></form><div id="formSuccess">Thanks for your feedback!</div></div><a href="#" id="feedback">feedback</a>');
	$("#mydash").append('<div class="notice2"><i class="fa fa-comments"></i>We value your feedback!  <a href="#" class="feedback_click">Click here</a> to send us your feedback on the Adobe Privileged Account Portal.</div>');
	$("a.feedback_click").click( function() {
		$("a#feedback").click();
	});
	$("a#feedback").click( function() {
		if ( $("#feedback_form").is(":visible") ) {
			$("#feedback_form a.close").click();
		} 
		else {
			$("textarea[name=feedback]").val("");
			$("#feedback_form #formSuccess").hide();
			$("#feedback_form form").show();
			$( "#feedback_form" ).css({ opacity:0 }).show();
			$( "#feedback_form" ).animate({
				opacity: 1,
				height: "250px",
				width: "400px"
			}, 200, function() {
				// Animation complete.
			});
		}
		return false;
	});
	$("#feedback_form a.close").click( function() {
		$( "#feedback_form" ).animate({
			opacity: 0,
			height: "0",
			width: "0"
		}, 200, function() {
			// Animation complete.
		});
		$("#feedback_form").hide();
		return false;
	});
	$("#feedback_form form").submit( function() {
		$(this).hide();
		$("#feedback_form #formSuccess").show();
		var feedback = $("textarea[name=feedback]").val();
		
		$.ajax({ 
			type: 'POST', 
			url: '../modules/send_feedback.php', 
			data: { feedback: feedback }, 
			dataType: 'json',
			success: function (data) { 		
				var success = data.success;
			}
		});
		return false;
	});	
});