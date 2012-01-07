var tagKeys = new Array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
var optionKeys = new Array('Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P');

function submitData(e) {
	if(typeof FormData == "undefined")
		return true;
	else {
		e.preventDefault();
		var req = new XMLHttpRequest();
		req.open("POST", theForm.action, true);

		var allOptions = document.getElementsByTagName("option");
		var selectedOptions = new Array();
		for(var i = 0; i < allOptions.length; i++) {
			o = allOptions[i];
			if(o.selected)
				selectedOptions.push(o);
		}
		req.selectedOptions = selectedOptions;

		beforeSubmit.innerHTML = "";

		req.onreadystatechange = function(e2) {
			if (req.readyState == 4 && req.status == 200) {
				beforeSubmit.innerHTML = "Submitted! ";

				var selects = document.getElementsByTagName("select");
				for(var i = 0; i < selects.length; i++) {
					s = selects[i];
					var options = s.childNodes;
					for(var j = 0; j < options.length; j++) {
						o = options[j];
						if(o instanceof HTMLOptionElement && o.innerHTML.charAt(0) === "*")
							o.innerHTML = o.innerHTML.substr(1);
					}
				}
				for(var i = 0; i < req.selectedOptions.length; i++) {
					o = req.selectedOptions[i];
					o.innerHTML = '*' + o.innerHTML;
				}

				window.clearTimeout(lastTimeout);
				lastTimeout = window.setTimeout(function() {beforeSubmit.innerHTML = "";}, 3000);
			}
		};

		var fd = new FormData(theForm);
		fd.append('async', true);
		req.send(fd);
	}
}

// From http://stackoverflow.com/questions/647259/javascript-query-string
function getQueryString() {
	var result = {};
	var queryString = window.location.search.substring(1);
    var re = /([^&=]+)=([^&]*)/g;
	var m;

	while(m = re.exec(queryString)) {
		result[decodeURIComponent(m[1])] = decodeURIComponent(m[2]);
	}

	return result;
}

function makeQueryString(vars) {
	var queryString = '?';

	for(var name in vars) {
		queryString += encodeURIComponent(name) + '=' + encodeURIComponent(vars[name]) + '&';
	}

	// strip last &
	queryString = queryString.substring(0, queryString.length - 1);
	return queryString;
}

function onkeyup(e) {
	// Don't do anything if we were in a textarea or something.
	var ae = document.activeElement;
	if(!((ae instanceof HTMLInputElement && ae.type ==='text') || ae instanceof HTMLTextAreaElement)) {
		var theChar = String.fromCharCode(e.keyCode);
		var i = tagKeys.indexOf(theChar);
		if(i > -1) {
			var tagBox = document.getElementById('tag-' + i);
			tagBox.click();
		}

		i = optionKeys.indexOf(theChar);
		if(i > -1) {
			var option = document.getElementById('option-' + theChar);
			var options = option.parentNode.childNodes;
			for(var i = 0; i < options.length; i++) {
				options[i].selected = false;
			}
			option.selected = true;
		}

		if(theChar == 'K' || theChar == 'J') {
			submit.click();

			var id = document.getElementById('id').value;
			var entry = document.getElementById('entry').value;
			var tables = document.getElementById('tables').value;

			var l = window.location;
			var base = l.protocol + '//' + l.host + l.pathname;
			var lastSlash = base.lastIndexOf('/');
			base = base.substring(0, lastSlash);
			var url = base + '/get_link.xml.php';

			var urlSuffix = "?entry=" + entry + "&id=" + id + "&tables=" + tables;
			if(theChar === 'K')
				urlSuffix += "&next=yes";
			else if(theChar === 'J')
				urlSuffix += "&previous=yes";

			if(document.getElementById('random').value === 'true')
				urlSuffix = "?random=yes";

			var req = new XMLHttpRequest();
			req.open("GET", url + urlSuffix, true);
			req.onreadystatechange = function(e2) {
				if(req.readyState == 4 && req.status == 200) {
					var doc = req.responseXML;
					var link = doc.getElementsByTagName("link")[0];
					var entry = link.getAttribute("entry");
					var id = link.getAttribute("id");

					var query = getQueryString();
					query['entry'] = entry;
					query['id'] = id;
					var queryString = makeQueryString(query);
					window.location = window.location.href.replace(window.location.search, queryString);
				}
			};

			req.send();
		}

		if(theChar == 'M')
			window.location = document.getElementById('random-link').href;
	}
}

var lastTimeout = null;

document.body.addEventListener('onkeyup', onkeyup, false);

var submit = document.getElementById("submit");
submit.focus();
var beforeSubmit = document.getElementById("before-submit");
theForm = document.forms[0];
theForm.addEventListener('submit', submitData, false);
