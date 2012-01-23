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
	// make sure we can scroll enough to hide next/prev:
	$('#container').css('min-height',$(document).height() - 20);
	// always hide next/prev immediately.
	$(document).scrollTop($('#entry').offset().top - 60);

	$('.tabs').tabs();
	$('.topbar').dropdown();
	$('[rel=twipsy]').twipsy();
	
	id = $('input#id').val(),
	entry = $('input#entry').val();

	var tags_header = $('#tags').siblings('h4');
	var toggles = $('.inputs-list label:not(.disabled)');
	toggles.find('input').change(function() {
		// remove any 'saved tags!' labels
		tags_header.find('.label').remove();
	});
	// add accelerator labels to 1-9.
	toggles.slice(0,9).each(function(i) {
		$(this).children('span').prepend('<span class="accelerator">(' + (i + 1) + ')</span> ');
	})

	$(document.body).keyup(function onkeyup(e) {
		var theChar = String.fromCharCode(e.keyCode);
	
		// Don't do anything if we were in a textarea or something.
		var focused = $('input[type=text]:focus, textarea:focus');
		if (focused.length)
			return;

		// Tags
		if (theChar === (parseInt(theChar) + '')) {
			var toggle = toggles.eq(parseInt(theChar) - 1).find('input');
			toggle.attr('checked', !toggle.attr('checked'));

			// remove any 'saved tags!' labels
			tags_header.find('.label').remove();

			return;
		}
		
		if (theChar == 'R')
			return window.location = $('#random-link').attr('href');
	
		if (theChar == 'C') {
			submit('constituent', moveForward);
			//return $('#constituent').click();
		}

		if (theChar == 'N') {
			submit('not_constituent', moveForward);
			//return $('#not_constituent').click();
		}

		if( theChar == 'J' )
			return window.location = $('#prev').attr('href');
	
		if( theChar == 'K' )
			return window.location = $('#next').attr('href');
	});
	
	function moveForward() {
		window.location = ($('#random').val() == 'true') ?
			$('#random-link').attr('href') :
			$('#next').attr('href');
	}

	function submit(constituency, callback) {
		if ($('#spinner').is(':visible'))
			return;

		$('#spinner').show();
		// remove 'saved tags!' labels
		tags_header.find('.label').remove();

		data = { action: 'save', id: id, entry: entry };

		if (constituency)
			data.constituency = constituency;
		// the constituency answer is the id of the button:
		else if (this.id)
			data.constituency = this.id;
	
		tags = {};
		$('#tags input:not(.disabled)').each(function() {
			var input = $(this);
			tags[input.attr('data-tag')] = !!input.attr('checked');
		})
		data.tags = tags;
			
		$.post('display_ajax.php', data, function(json) {
			$('#spinner').hide();
			if (json.modified) {
				$('#last_modified small').text(json.modified);
				$('.submit').removeClass('success danger selected');
				$('#' + json.constituency).addClass('selected');
			}
			if (json.tags)
				tags_header.append(' <span class="label success fade in" style="margin-left: 5px;">saved tags!</span>');
			if ( typeof callback === 'function' )
				callback();
		}, 'json');
	}
	$('.submit').click(submit);

	// ignore the form
	$('form').submit(function(e) {
		e.preventDefault();
		return false;
	});

	$('#parse-control li:not(.divider)').click(chooseParseType);
	maybeLoadTrees();
	
	$('#toggleWidth').click(toggleWidth);
});