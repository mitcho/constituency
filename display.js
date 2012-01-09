var tagKeys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
var optionKeys = ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P'];
var lastTimeout = null;

$(document).ready(function() {
	var id = $('#id').val(),
	entry = $('#entry').val(),
	tables = $('#tables').val();

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
	
			var data = {entry: entry, id: id, tables: tables};
			if (theChar === 'K')
				data.next = 'yes';
			else if(theChar === 'J')
				data.previous = 'yes';
	
			if($('#random').val() === 'true')
				ata = {random: 'yes', tables: tables};
	
			$.get(url, data, function(data) {
				var link = $(data).find("link").eq(0);
	
				var queryString = $.param({
					tables: tables,
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
	
	function maybeLoadTrees() {
		if ( !$('#container').is(':visible') )
			return;
	
		$('#image, #parse').text('loading...');
		$.getJSON('display_ajax.php', {
			action: 'display_parse',
			entry: entry,
			id: id,
			type: tables
		}, function (json) {
			if (json.error)
				return console.log('error');
			$('#image, #parse').text('');
			$('<img/>')
				.attr('src', 'lib/phpsyntaxtree/stgraph.svg?data=' + json.imageData)
				.attr('alt', 'Tree: ' + json.imageData)
				.appendTo('#image');
			$('#parse').text(json.tree);
		});
	}
	maybeLoadTrees();
});