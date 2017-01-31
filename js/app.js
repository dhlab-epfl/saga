/* jshint undef: true, unused: true */
/* globals alertify:false */							// A directive for telling JSHint about global variables that are defined elsewhere. If value is false (default), JSHint will consider that variable as read-only.

$(document).ready(function(){
	alertify.defaults.glossary.title = '';
	alertify.defaults.glossary.ok = 'Confirmer';
	alertify.defaults.glossary.cancel = 'Annuler';
});