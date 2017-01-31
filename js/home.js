/* jshint undef: true, unused: true */
/* globals alertify:false */							// A directive for telling JSHint about global variables that are defined elsewhere. If value is false (default), JSHint will consider that variable as read-only.

var HOME;

var HOME = {
	init : function(){
		if ($('.ui.tabular.menu').length) {
			$('.ui.tabular.menu .item').tab();
		}
		if ($('.ui.dropdown').length) {
			$('.ui.dropdown').dropdown({});
		}
		$('.versionSelect').on('change', function(){
			var $tr = $(this).closest('tr');
			var selectedId = $(this).find('input[name=id]').val();
			$tr.find('.ui.button.open').attr('href', '/project.php?load='+selectedId);
			$tr.find('.ui.button.delete').attr('data-id', selectedId);
		});
		$('.button.delete').on('click touchdown', function(e){
			e.preventDefault();
			var projectId = $(this).attr('data-id');
/*
			alertify.confirm('Voulez-vous vraiment supprimer intégralement ce projet ?', function(){
				$.get('_.php', {f:'deleteSaga', id:projectId}, function(){
				});
			});
*/
			alertify.confirm('Supprimer cette version ?', function(){
				$.get('_.php', {f:'deleteVersion', id:projectId}, function(){
					location.reload();
				});
			});
		});
		$('#fSearchBooks').on('keyup', function(){
			$.get('_.php', {f:'searchBooks', q:$('#fSearchBooks').val()}, function(results){
				$('#results').html(results);
			});
		});
	}
};

$(document).ready(function(){
	HOME.init();
});