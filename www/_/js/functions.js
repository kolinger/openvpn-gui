(function($){})(window.jQuery);

// nette ajax
jQuery.extend({
	nette: {
		updateSnippet: function (id, html) {
			$("#" + id).html(html);
		},

		success: function (payload) {
			// redirect
			if (payload.redirect) {
				window.location.href = payload.redirect;
				return;
			}

			// snippets
			if (payload.snippets) {
				for (var i in payload.snippets) {
					jQuery.nette.updateSnippet(i, payload.snippets[i]);
				}
			}
		}
	}
});

jQuery.ajaxSetup({
	success: jQuery.nette.success,
	dataType: "json"
});

/**
 * AJAX form plugin for jQuery
 *
 * @copyright  Copyright (c) 2009 Jan Marek
 * @license    MIT
 * @link       http://nettephp.com/cs/extras/ajax-form
 * @version    0.1
 */

jQuery.fn.extend({
	ajaxSubmit: function (callback) {
		var form;
		var sendValues = {};

		// submit button
		if (this.is(":submit")) {
			form = this.parents("form");
			sendValues[this.attr("name")] = this.val() || "";

			// form
		} else if (this.is("form")) {
			form = this;

			// invalid element, do nothing
		} else {
			return null;
		}

		// validation
		if (form.get(0).onsubmit && form.get(0).onsubmit() === false) return null;

		// get values
		var values = form.serializeArray();

		for (var i = 0; i < values.length; i++) {
			var name = values[i].name;

			// multi
			if (name in sendValues) {
				var val = sendValues[name];

				if (!(val instanceof Array)) {
					val = [val];
				}

				val.push(values[i].value);
				sendValues[name] = val;
			} else {
				sendValues[name] = values[i].value;
			}
		}

		// send ajax request
		var ajaxOptions = {
			url: form.attr("action"),
			data: sendValues,
			type: form.attr("method") || "get"
		};

		if (callback) {
			ajaxOptions.success = callback;
		}

		return jQuery.ajax(ajaxOptions);
	}
});

/* Czech initialisation for the jQuery UI date picker plugin. */
/* Written by Tomas Muller (tomas@tomas-muller.net). */
jQuery(function($){
	$.datepicker.regional['cs'] = {
		closeText: 'Zavřít',
		prevText: '&#x3C;Dříve',
		nextText: 'Později&#x3E;',
		currentText: 'Nyní',
		monthNames: ['leden','únor','březen','duben','květen','červen',
			'červenec','srpen','září','říjen','listopad','prosinec'],
		monthNamesShort: ['led','úno','bře','dub','kvě','čer',
			'čvc','srp','zář','říj','lis','pro'],
		dayNames: ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'],
		dayNamesShort: ['ne', 'po', 'út', 'st', 'čt', 'pá', 'so'],
		dayNamesMin: ['ne','po','út','st','čt','pá','so'],
		weekHeader: 'Týd',
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''};
	$.datepicker.setDefaults($.datepicker.regional['cs']);
});

// custom func's
$(document).ready(function (){
	$('.question').live('click', function (event) {
		return confirm($(this).data('question'));
	});

	$("a.ajax").live("click", function (event) {
		event.preventDefault();
		$.get(this.href);
	});

	$("form.payment").live('submit', function () {
		$(this).ajaxSubmit();
		$('#paymentInfo').modal('hide');
		return false;
	});

	$("form.payment :submit").live('click', function () {
		$(this).ajaxSubmit();
		$('#paymentInfo').modal('hide');
		return false;
	});

	$('.datepicker').datepicker({
		dateFormat: 'd. m. yy',
		regional: $.datepicker.regional['cs']
	});

	$('#paymentInfo').modal();
});


