var tagKeys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
var optionKeys = ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P'];
var lastTimeout = null;
var id, entry;

function toggleWidth() {
	if ($('.container-maybe-fluid.container-fluid').length) {
		$('.container-maybe-fluid').removeClass('container-fluid').addClass('container');
		$('#toggleWidth').text('Wide');
	} else {
		$('.container-maybe-fluid').removeClass('container').addClass('container-fluid');
		$('#toggleWidth').text('Narrow');
	}
	return false;
}

function chooseParseType() {
	$('#parse-control li').removeClass('active');
	$(this).addClass('active');

	var	parsetype = $('#parse-control li.active').attr('data-parse_type');
	$('#parse-control .dropdown-toggle').text('Parse: ' + (parsetype || 'None'));

	maybeLoadTrees();
}

function maybeLoadTrees() {
	var	parsetype = $('#parse-control li.active').attr('data-parse_type');
	if ( !parsetype ) {
		$('#parse-container').hide();
		return;
	}

	$('#image, #parse').text('loading...');
	$.getJSON('display_ajax.php', {
		action: 'display_parse',
		entry: entry,
		id: id,
		type: parsetype
	}, function (json) {
		$('#image, #parse').text('');
		if (json.error) {
			return $('<div class="alert-message warning fade in"><a class="close" href="#">×</a><p>No ' + parsetype + ' parse data available.</p></div>')
				.prependTo('#container')
				.alert();
		}

		$('#parse-container').show();
		$('#image').empty();
		$('<img/>')
			.attr('src', 'lib/phpsyntaxtree/stgraph.svg?data=' + json.imageData)
			.attr('alt', 'Tree: ' + json.imageData)
			.appendTo('#image');
		$('#parse').text(json.tree);
		
		if ( json.link_parse ) {
			var classification = '<h2>' + json.link_parse.constituency + '</h2>';
			if ( json.link_parse.error ) {
				classification += '<p><strong>Error:</strong> ' + json.link_parse.error + '</p>';
			}
			if ( json.link_parse.failure_type ) {
				classification += '<p><strong>Failure type:</strong> ' + json.link_parse.failure_type;
				if ( json.link_parse.missing_node )
					classification += ': ' + json.link_parse.missing_node;
				classification += '</p>';
			}
			classification += '<p><strong>Immediately dominating node:</strong> ' + json.link_parse.immediate_node + '</p>';
			classification += '<p><strong>Punctuation pass:</strong> ' + ( json.link_parse.punctuation_pass == '1' ? 'yes' : 'no' ) + '</p>';
			classification += '<p><strong>Almost:</strong> ' + ( json.link_parse.almost == '1' ? 'yes' : 'no' ) + '</p>';
			$('#classification').html(classification);
		}
	});
}

$(document).ready(function() {
	$('.tabs').tabs();
	$('.topbar').dropdown();
	
	id = $('input#id').val(),
	entry = $('input#entry').val();

	$(document.body).keyup(function onkeyup(e) {
		var theChar = String.fromCharCode(e.keyCode);
	
		// Don't do anything if we were in a textarea or something.
		var focused = $('input[type=text]:focus, textarea:focus');
		if (focused.length)
			return;
		
		var i = tagKeys.indexOf(theChar);
		if(i > -1)
			$('#tag-' + i).click();
	
		if( optionKeys.indexOf(theChar) > -1 ) {
			var option = $('#option-' + theChar);
			option.siblings().attr('selected',false);
			option.attr('selected',true);
		}
	
		if( theChar == 'K' || theChar == 'J' ) {
			submit.click();
	
			var l = window.location.toString();
			// @todo get_link.xml doesn't actually exist right now
			var url = loc.replace(/display.php(.*)$/,'/get_link.xml.php');
	
			var	parsetype = $('#parse-control li.active').attr('data-parse_type');
	
			var data = {entry: entry, id: id, parse_type: parsetype};
			if (theChar === 'K')
				json.next = 'yes';
			else if(theChar === 'J')
				json.previous = 'yes';
	
			if($('#random').val() === 'true')
				ata = {random: 'yes', parse_type: parsetype};
	
			$.get(url, data, function(data) {
				var link = $(data).find("link").eq(0);
	
				var queryString = $.param({
					parse_type: parsetype,
					entry: link.attr("entry"),
					id: link.attr("id")
				});
				window.location = 'display.php?' + queryString;
			}, 'xml');
		}
	
		if(theChar == 'M')
			window.location = $('#random-link').attr('href');
	});
	$('submit').focus();

	function submitData(e) {
		e.preventDefault();
	
		var beforeSubmit = $("#before-submit");
		beforeSubmit.text("");
	
		var selectedOptions = $('option:selected').map(function(x,e) {return e.id});
		// @todo data format is not currently correct.
		var data = {
			selectedOptions: selectedOptions
		}
	
		$.post($('form').attr('action'), data, function() {
			beforeSubmit.text("Submitted! ");
	
			$("select option").each( function() {
				var that = $(this),
				label = that.text();
				
				if (label.charAt(0) === '*')
					that.text(label.substr(1));
				
				if (that.is(':selected'))
					that.text('*' + that.text());				
			} );
	
			window.clearTimeout(lastTimeout);
			lastTimeout = window.setTimeout(function() {beforeSubmit.text("");}, 3000);
		});
	}
	$('form').submit(submitData);

	$('#parse-control li:not(.divider)').click(chooseParseType);
	maybeLoadTrees();
	
	$('#toggleWidth').click(toggleWidth);
});