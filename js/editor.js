/* jshint undef: true, unused: true */
/* globals d3:false, alertify:false */							// A directive for telling JSHint about global variables that are defined elsewhere. If value is false (default), JSHint will consider that variable as read-only.

var GRAPHS, EDITOR, PROJECT;


// D3.js force-directed graph (credits to https://bl.ocks.org/mbostock/4062045)

var GRAPHS = {
	updateCharsGraph : function() {
		d3.selectAll('svg.charsGraph > *').remove();
		var svg = d3.select('svg.charsGraph'), width = $('#charsGraph').width(), height = $('#charsGraph').height();
		var fisheye = d3.fisheye.circular().radius(height/3).distortion(4);

		var start = $('#charsGraph .double.range .label:nth-child('+(GRAPHS.rangeStartIdx)+')').attr('data-cvalue');
		var end = $('#charsGraph .double.range .label:nth-child('+(GRAPHS.rangeEndIdx)+')').attr('data-cvalue');
		d3.json('_.php?f=getCharsGraph&includeNonColored='+$('#cbIncludeNonColored:checked').length+'&seq_start='+start+'&seq_end='+end, function(error, graph) {
			if (error) { throw error; }

			if (graph.nodes.length>1 && graph.links.length>0) {
				var k = Math.sqrt(graph.nodes.length / (width * height));		// http://stackoverflow.com/questions/9901565/charge-based-on-size-d3-force-layout
				var simulation = d3.forceSimulation()
				.force('link', d3.forceLink().id(function(d) { return d.id; }))
				.force('charge', d3.forceManyBody().strength(-(1/k)).distanceMax(50))
				.force('center', d3.forceCenter(width / 2, height / 2));


				function ticked() {
					link.attr('x1', function(d) { return d.source.x; }).attr('y1', function(d) { return d.source.y; })
						.attr('x2', function(d) { return d.target.x; }).attr('y2', function(d) { return d.target.y; });
					label.attr('x', function(d) { return d.x; }).attr('y', function(d) { return d.y; }).attr('fill', function(d) { return d.color; });
				}

				var link = svg.append('g').attr('class', 'links').selectAll('line').data(graph.links).enter().append('line').attr('stroke-width', function(d) {
					return Math.sqrt(d.value);
				});
			/*
				var node = svg.append('g').attr('class', 'nodes').selectAll('circle').data(graph.nodes).enter().append('circle').attr('r', 5).attr('fill', function(d) {
					return color(d.group);
				});//.call(d3.drag().on('start', dragstarted).on('drag', dragged).on('end', dragended));
			*/
	//			node.append('title').text(function(d) { return d.id; });


				var label = svg.selectAll(".mytext").data(graph.nodes).enter().append("text").text(function (d) { return d.id; })
						    .style("text-anchor", "middle").style("stroke", "#999").style('stroke-width', 1).style("font-weight", "bold").style("font-size", 15);

				simulation.nodes(graph.nodes).on('tick', ticked);
				simulation.force('link').links(graph.links);

				svg.on('mousemove', function() {
					fisheye.focus(d3.mouse(this));

					label.each(function(d) { d.fisheye = fisheye(d); }).attr('x', function(d) { return d.fisheye.x; }).attr('y', function(d) { return d.fisheye.y; });

					link.attr('x1', function(d) { return d.source.fisheye.x; })
					.attr('y1', function(d) { return d.source.fisheye.y; })
					.attr('x2', function(d) { return d.target.fisheye.x; })
					.attr('y2', function(d) { return d.target.fisheye.y; });
				});

				$('#charsGraph .labels>*').remove();
				graph.chapters.forEach(function(chapter){
					$('#charsGraph .labels').append('<li class="label" data-cvalue="'+chapter+'">'+Math.floor(chapter/1000)+'.'+(chapter%1000)+'</li>');
				});
				PROJECT.initRangeInputs(GRAPHS.rangeStartIdx, GRAPHS.rangeEndIdx);
			}
		});
/*
		function dragstarted(d) {
			if (!d3.event.active) { simulation.alphaTarget(0.3).restart(); }
			d.fx = d.x;
			d.fy = d.y;
		}

		function dragged(d) {
			d.fx = d3.event.x;
			d.fy = d3.event.y;
		}

		function dragended(d) {
			if (!d3.event.active) { simulation.alphaTarget(0); }
			d.fx = null;
			d.fy = null;
		}
*/

	},

	rangeStartIdx : 0,
	rangeEndIdx : -1,
	rangeInputChanged : function() {
		var needUpdate = false;
		var v1 = $('#charsGraph .double.range').range('get thumb value');
		var v2 = $('#charsGraph .double.range').range('get thumb value', 'second');
		var startIdx = Math.min(v1, v2);
		if (startIdx !== GRAPHS.rangeStartIdx) {
			GRAPHS.rangeStartIdx = startIdx;
			needUpdate = true;
		}
		var endIdx = Math.max(v1, v2);
		if (endIdx !== GRAPHS.rangeEndIdx) {
			GRAPHS.rangeEndIdx = endIdx;
			needUpdate = true;
		}
		if (needUpdate) {
			GRAPHS.updateCharsGraph();
		}
	},

	updateTimelineGraph : function() {

		d3.selectAll("svg.timelineGraph > *").remove();
		// set the dimensions and margins of the graph
		var margin = {top: 20, right: 20, bottom: 65, left: 80};
		var availWidth = $('#timelineGraph').width() - margin.left - margin.right;
		var height = $('#timelineGraph').height() - margin.top - margin.bottom;
		var chapterMarkSize = 3;
		var startEndMarkSize = 15;

		// Get the data
		d3.json('_.php?f=getTimelineGraph&includeNonColored='+$('#cbIncludeNonColored:checked').length, function(error, data) {
			if (error) { throw error; }

			var colors = data.colors, chapters = Object.keys(data.graph), places = [], characters = {}, placesCounts = {};
			var graphWidth = Math.max(chapters.length*9, availWidth);
			var svg = d3.select('svg.timelineGraph')
			.attr('width', graphWidth + margin.left + margin.right)
			.attr('height', height + margin.top + margin.bottom)
			.append('g')
			.attr('transform', 'translate('+margin.left+','+margin.top+')');

			// set the ranges
			var x = d3.scaleLinear().range([0, graphWidth]);
			var y = d3.scaleLinear().range([height, 0]);

			// define the line function
			var storyline = d3.line().curve(d3.curveCatmullRom.alpha(0.7)).x(function(row) { return x(row.chapter); }).y(function(row) { return y(row.place); });
			var valueline = d3.line().curve(d3.curveCatmullRom.alpha(0.7))
								.x(function(row) { return x(row.chapter); })
								.y(function(row) { return y(row.place+row.offset); });

			// Extract formatted data
			chapters.forEach(function(chapter){
				var row = data.graph[chapter];
				if (row.place!=='' && row.characters.length>0) {
					if (placesCounts[row.place]===undefined) { placesCounts[row.place] = 0; }
					placesCounts[row.place] += 1;
					row.characters.forEach(function(c){
						if (characters[c]===undefined) { characters[c] = []; }
						characters[c].push(row);
					});
				}
			});
			places = Object.keys(placesCounts);
			// Add the horizontal axis + grid
			x.domain([0, chapters.length+1]);
			svg.append('g').attr("class", "grid")
			.attr('transform', 'translate(0,' + height + ')')
			.call(d3.axisBottom(x).ticks(chapters.length).tickSize(-height).tickFormat(function(d) { return chapters[d-1]; }))
			.selectAll('text').attr('class','xtick').style('text-anchor', 'end').attr('dx', '-10px').attr('dy', '-5px').attr('transform', 'rotate(-90)')
			.on('click',function(d){ EDITOR.openChapter(data.chaptersMap[chapters[d-1]][0]); });

			// Add the vertical axis + grid
			y.domain([0, places.length+1]);
			svg.append('g').attr("class", "grid")
			.call(d3.axisLeft(y).ticks(places.length+1).tickSize(-graphWidth).tickFormat(function(d) { return places[d-1]; }));


			var chaptersPath = [];
			chapters.forEach(function(chapter){
				var row = data.graph[chapter];
				if (row.place!=='') {
					chaptersPath.push({'chapter':1+chapters.indexOf(chapter),'place':1+places.indexOf(row.place)});
				}
			});
			svg.append('path').data([chaptersPath]).attr('class', 'story').attr('d', storyline).attr("stroke-width", height/(places.length+2)).attr('stroke', '#000');

			// Add the valueline path.
			var offset = -0.4;
			Object.keys(characters).forEach(function(character){
				var rowValues = [];
				offset+=(0.8/(Object.keys(characters).length+1));
				chapters.forEach(function(chapter){
					if (data.graph[chapter].characters.indexOf(character)>-1) {
						rowValues.push({'chapter':1+chapters.indexOf(chapter),'place':1+places.indexOf(data.graph[chapter].place),'offset':offset});
					}
				});
				if (rowValues.length>0) {
					svg.append('path').data([rowValues])
						.attr('class', 'char').attr('d', valueline).attr('stroke', colors[character]);
					svg.selectAll('.dot').data(rowValues).enter()
						.append('circle').attr('r', chapterMarkSize).attr("cx", valueline.x()).attr("cy", valueline.y()).attr('fill', colors[character]);
					svg.selectAll('.dot').data([rowValues[0], rowValues[rowValues.length-1]]).enter()
						.append('rect').attr('width', chapterMarkSize).attr('height', startEndMarkSize).attr("y", valueline.y()).attr("x", valueline.x()).attr('transform', 'translate(-'+(chapterMarkSize/2)+',-'+(startEndMarkSize/2)+')').attr('fill', colors[character]);
				}
			});
		});
	},

	updateEntityNgram : function(ent_id, colorId) {

		function type(d) {
			d.n = +d.n;
			return d;
		}

		d3.selectAll('svg.entityNgram > *').remove();
		var svg = d3.select('svg.entityNgram'),
		margin = {top: 20, right: 20, bottom: 30, left: 40},
		width = $('#entityNgram').width() - margin.left - margin.right,
		height = 150 - margin.top - margin.bottom;

		var x = d3.scaleBand().rangeRound([0, width]).padding(0.1);
		var y = d3.scaleLinear().rangeRound([height, 0]);

		var g = svg.append('g').attr('transform', 'translate(' + margin.left + ',' + margin.top + ')');

		d3.tsv('_.php?f=getEntityNgram&id='+ent_id, type, function(error, data) {
			if (error) { throw error; }

			x.domain(data.map(function(d) { return d.chapter; }));
			y.domain([0, d3.max(data, function(d) { return d.n; })]);

			g.append('g').attr('class', 'axis axis--x').attr('transform', 'translate(0,'+height+')').call(d3.axisBottom(x))
				.selectAll('text').style('text-anchor', 'end').attr('dx', '-10px').attr('dy', '-5px').attr('transform', 'rotate(-90)');
			g.append('g').attr('class', 'axis axis--y').call(d3.axisLeft(y).ticks(10, '%'))
				.append('text').attr('transform', 'rotate(-90)').attr('y', 26).attr('text-anchor', 'end');

			g.selectAll('.bar').data(data).enter().append('rect').attr('class', 'bar color_'+colorId)
			.attr('x', function(d) { return x(d.chapter); })
			.attr('y', function(d) { return y(d.n); })
			.attr('width', x.bandwidth())
			.attr('height', function(d) { return height - y(d.n); })
			.on('click', function(d) { EDITOR.openChapter(d.cid); });
		});
	},

	updateIOGraph : function() {
		function type(d, i, columns) {
			var t;
			for (i = 1, t = 0; i < columns.length; ++i) {
				t += d[columns[i]] = +d[columns[i]];
			}
			d.total = t;
			return d;
		}
		d3.selectAll('svg.ioGraph > *').remove();
		var svg = d3.select("svg.ioGraph");
		var margin = {top: 5, right: 0, bottom: 90, left: 20};
		var minimalColumnW = 8;
		d3.tsv('_.php?f=getIOGraph&includeNonColored='+$('#cbIncludeNonColored:checked').length, type, function(error, data) {
			if (error) { throw error; }

			var headers = data.columns.slice(1);
			var graphWidth = Math.max($('#ioGraph').width(), data.length*minimalColumnW+margin.left+margin.right);
			svg.attr('width', graphWidth);
			var width = graphWidth - margin.left - margin.right;
			var height = $('#ioGraph').height() - margin.top - margin.bottom;
			var g = svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");
			var x = d3.scaleBand().rangeRound([0, width]);
			var y = d3.scaleLinear().rangeRound([height, 0]);
			var z = d3.scaleOrdinal(d3.schemeCategory10).range(["#bfbfbf", "#ed6a64", "#84ad4e"]);
			var stack = d3.stack();

			x.domain(data.map(function(d) { return d.chapter; }));
			y.domain([0, d3.max(data, function(d) { return d.total; })]).nice();
			z.domain(headers);
			var maxIO = 0;
			data.forEach(function(row){
				maxIO = Math.max(row.total, maxIO);
			});

			g.selectAll(".serie")
			.data(stack.keys(headers)(data))
			.enter().append("g")
			.attr("class", "serie")
			.attr("fill", function(d) { return z(d.key); })
			.selectAll("rect")
			.data(function(d) { return d; })
			.enter().append("rect")
			.attr("x", function(d) { return x(d.data.chapter); })
			.attr("y", function(d) { return y(d[1]); })
			.attr("height", function(d) { return y(d[0]) - y(d[1]); })
			.attr("width", x.bandwidth());

			g.append("g").attr("class", "axis axis--x").attr("transform", "translate(0," + height + ")").call(d3.axisBottom(x))
			.selectAll('text').style('text-anchor', 'end').style('font-size', '9px').attr('dx', '-8px').attr('dy', '-15px').attr('transform', 'rotate(-90)');

			g.append("g").attr("class", "axis axis--y").call(d3.axisLeft(y).ticks(maxIO, "s"))
			.selectAll("text").attr('dy', '-5px');
		});
	}
};

/*
 *
 **************************************************************************************************************************************************************/

function closeOverlay(){
	$('#overlay').fadeOut();
}

var EDITOR = {
	openChapter : function(id) {
		$.get('_.php', {'f':'getChapterOverlay', 'id':id}, function(panelContent) {
			$('#overlay>.panel>header>h1').html('Éditeur de chapitre');
			$('#overlay>.panel>.content').html(panelContent);
			$('#overlay').fadeIn();
			$('#overlay>.panel>.content .editable, #overlay>.panel>.content input[type=text]').on('blur', function(){
				EDITOR.save(id);
			});
			$('#overlay>.panel>.content .dropdown').dropdown({forceSelection:false, onChange:function(){EDITOR.save(id);}});
			$('#overlay>.panel .infos .ui.label>a').on('click touchdown', function(e){
				e.stopPropagation();
				EDITOR.openEntity($(this).closest('.label').attr('data-id'));
			});
			$('#overlay>.panel .infos .ui.label>i.delete').on('click touchdown', function(e){
				e.stopPropagation();
				var entityId = $(this).parent('.label').attr('data-id');
				var chapterId = $('#overlay').find('.content>.chapter').attr('data-id');
				alertify.confirm('Confirmer la suppression?', function(){
					$.get('_.php', {'f':'removeChapterEntity', 'entity_id':entityId, 'chapter_id':chapterId}, function(){
						EDITOR.openChapter(chapterId);
					});
				});
			});
			EDITOR.updateStats();
		});
	},
	openEntity : function(id) {
		$.get('_.php', {f: 'getEntityOverlay', entity_id: id}, function(panelContent){
			$('#overlay>.panel>header>h1').html('Détails sur l’entité');
			$('#overlay>.panel>.content').html(panelContent);
			$('#overlay').fadeIn();
			$('#overlay>.panel>.content .ui.dropdown').dropdown({on:'hover'});
			$('#overlay>.panel>.content #bAddCoref').on('click touchdown', function(){
				var other_text = $('#overlay>.panel>.content .ui.dropdown').dropdown('get text');
				var other_id = $('#overlay>.panel>.content .ui.dropdown').dropdown('get value');
				if (other_text!=='') {
					alertify.confirm('Confirmer ? L’entité «'+other_text+'» sera fusionnée avec «'+$('#overlay>.panel>.content .ui.header').text()+'».', function(){
						$.get('_.php', {f:'mergeEntities', main_id:id, other_id:other_id}, function(){
							PROJECT.updateEntities();
							PROJECT.refreshStory();
							EDITOR.openEntity(id);
						});
					});
				}
			});
			$('#overlay>.panel>.content .delete.icon').on('click touchdown', function(){
				var form = $(this).closest('.ui.label').text();
				alertify.confirm('Confirmer la suppression?', function(){
					$.get('_.php', {f:'deleteSynonym', entity_id:id, form:form}, function(){
						EDITOR.openEntity(id);
					});
				});
			});
			$('#overlay>.panel>.content .editable').on('blur', function(){
				EDITOR.save(id);
			});
			GRAPHS.updateEntityNgram(id, $('#overlay .colorSelector>.label.active').attr('data-color'));
		});
	},
	updateStats : function() {
		// Count words (www.mediacollege.com/internet/javascript/text/count-words.html)
		var text = $('.editor.editable').text();
		var wordCount = text.replace(/(^\s*)|(\s*$)/gi, '').replace(/[ ]{2,}/gi, ' ').replace(/\n /, "\n").split(' ').length;
		var sentCount = text.replace(/(?:(?:^|\n)\s+|\s+(?:$|\n))/g,'').replace(/\s+/g,' ').split('.').length - 1;
		$('.chapter #textStats').html(wordCount+' mot'+(wordCount>1?'s':'')+' / '+sentCount+' phrase'+(sentCount>1?'s':''));
	},
	delete : function(btn){
		var deleteType = '';
		var deleteId = 0;
		if ($('.content>.entity').length) {
			deleteType = 'deleteEntity';
			deleteId = btn.closest('.entity').attr('data-id');
		}
		else if ($('.content>.chapter').length) {
			deleteType = 'deleteChapter';
			deleteId = $('#overlay').find('.content>.chapter').attr('data-id');
		}
		alertify.confirm('Confirmer la suppression?', function(){
			$.get('_.php', {'f':deleteType, 'id':deleteId}, function(){
				PROJECT.updateEntities();
				PROJECT.refreshStory();
				closeOverlay();
			});
		});
	},
	nav : function(btn) {
		var $tile = $('.tile[data-id="'+$('#overlay>.panel>.content .chapter').attr('data-id')+'"]');
		var newId = $tile.attr('data-id');
		if (btn.hasClass('next') && $tile.next().length) {
			newId = $tile.next().attr('data-id');
		}
		else if (btn.hasClass('prev') && $tile.prev().length) {
			newId = $tile.prev().attr('data-id');
		}
		EDITOR.openChapter(newId);
	},
	save : function(id){
		var $content = $('#overlay>.panel>.content');
		if ($content.children('.entity').length) {
			var text = $content.find('.notice.editable').html();
			$.post('_.php', {f:'saveEntity', entity_id:id, notice:text}, function(/*d*/){
				alertify.success('Contenu modifié');
			});
		}
		else if ($content.children('.chapter').length) {
			var title = $content.find('.header.editable').text();
			var htmltext = $content.find('.editor.editable').html();
			var place = $content.find('.place .dropdown').dropdown('get value');
			var date_y = $content.find('input[name=y]').val();
			var date_m = $content.find('input[name=m]').val();
			var date_d = $content.find('input[name=d]').val();
			var date_h = $content.find('input[name=h]').val();
			var date_i = $content.find('input[name=i]').val();
			$.post('_.php', {f:'saveChapter', chapter_id:id, title:title, text:htmltext, place:place, y:date_y, m:date_m, d:date_d, h:date_h, i:date_i}, function(/*d*/){
				EDITOR.updateStats();
				alertify.success('Contenu modifié');
			});
		}

	}
};

var OPTIONS = {
	open : function(){
		$.get('_.php', {'f':'getOptionsOverlay'}, function(panelContent) {
			$('#overlay>.panel>header>h1').html('Propriétés et Versions');
			$('#overlay>.panel>.content').html(panelContent);
			$('#overlay').fadeIn();
			/*
			$('#overlay>.panel>.content input').on('blur', function(){
				OPTIONS.save();
			});
			*/
			$('#overlay').on('click touchdown','.button.deleteVersion', function(e){
				e.preventDefault();
				var deleteId = $(this).closest('a').attr('data-id');
				alertify.confirm('Supprimer cette version ?', function(){
					$.get('_.php', {'f':'deleteVersion', 'id':deleteId}, function(){
						OPTIONS.open();
					});
				});
			});
			$('#overlay #bNewVersion').on('click touchdown', function(){
				$('#overlay>.panel>.content').html('<div class="ui active dimmer"><div class="ui indeterminate text loader">Sauvegarde en cours…</div></div>');
				$.get('_.php', {'f':'newVersion'}, function(){
					OPTIONS.open();
				});
			});
			$('#overlay #bSave').on('click touchdown', function(){
				OPTIONS.save();
				closeOverlay();
			});
		});
	},
	save : function(){
		var $content = $('#overlay>.panel>.content');
		$.get('_.php', {f:'saveOptions', author:$content.find('input[name=author]').val(), title:$content.find('input[name=title]').val(), summary:$content.find('input[name=summary]').val()}, function(/*d*/) {
			alertify.success('Modifications enregistrées');
		});
	}
};
var PUBLISH = {
	open : function(){
		$.get('_.php', {'f':'getPublishOverlay'}, function(panelContent) {
			$('#overlay>.panel>header>h1').html('Options de publication');
			$('#overlay>.panel>.content').html(panelContent);
			$('#overlay .ui.accordion').accordion({exclusive:false});
			var $pictureSelector = $('#overlay .pictureSelector');
			$pictureSelector.find('td>img.ui.image').click(function(/*e*/){
				var $sel = $(this);
				var deselect = $sel.hasClass('sel');
				$pictureSelector.find('td>img.ui.image').removeClass('sel');
				if (!deselect) { $sel.addClass('sel'); }
				$('#overlay').find('input[name=icon]').val($sel.attr('src'));
			});
			$('#overlay').fadeIn();
			$('#overlay>.panel>.content .button.primary').on('click touchdown', function(){
				PUBLISH.publish();
			});
		});
	},
	publish : function(){
		var $content = $('#overlay>.panel>.content');
		var parameters = {
							f: 'publishProject',
							author: $content.find('input[name=author]').val(),
							title: $content.find('input[name=title]').val(),
							summary: $content.find('textarea[name=summary]').val()
						};
		if ($content.find('.pictureSelector .image.sel').length) {
			parameters.icon = $content.find('.pictureSelector .image.sel').attr('src');
		}
		$content.html('<div class="ui active dimmer"><div class="ui indeterminate text loader">Envoi en cours…</div></div>');
		$.post('_.php', parameters, function(panelContent){
			$('#overlay>.panel>.content').html(panelContent);
		});
	}
};

var PROJECT = {
	init : function(){
		if ($('#extractEntities').length) {
			$.ajax({
				url: '_importbook.php?file='+$('#extractEntities').attr('data-file'),
				type: 'GET',
				success: function(/*data*/){
					$('#extractEntities').fadeOut();
					PROJECT.refreshStory();
					PROJECT.updateEntities();
				},
				error: function(/*data*/) {
					alertify.error('Echec! L’analyse a échoué. Veuillez réessayer plus tard ou avec un autre livre.');
					$('#extractEntities').fadeOut();
				}
			});
		}
		else {
			PROJECT.refreshStory();
			PROJECT.updateEntities();
		}

		$('.ui.accordion').accordion({exclusive:false});
		$('.ui.dropdown').dropdown({on:'hover'});
		PROJECT.initRangeInputs(0, -1);

		$(document).on('click touchdown', '#menuOptions', function(){
			OPTIONS.open();
		});
		$(document).on('click touchdown', '#menuPublier', function(){
			PUBLISH.open();
		});
		$(document).on('click','.entityManager .ui.label',function(){
			var entity_id = parseInt($(this).attr('data-id'), 10);
			if (entity_id===0) {
				var entity_class = $(this).attr('data-class');
				alertify.prompt('Nouvelle entité', 'Nom', function(evt, entity_name) {
					$.ajax({
						url: '_.php',
						method: 'GET',
						dataType: 'html',
						async: false,
						data: {
							f: 'newEntity',
							name: entity_name,
							class: entity_class
						},
						success:function(new_entity_id){
							EDITOR.openEntity(new_entity_id);
							PROJECT.updateEntities();
						}
					});
				});
			}
			else {
				EDITOR.openEntity(entity_id);
			}
		});

		$(document).on('click','#overlay,#overlay>.panel>header .remove',function(e){
  			if (e.target !== this) { return; }
			closeOverlay();
			PROJECT.refreshStory();
		});
		$(document).on('click','#overlay .button.delete',function(/*e*/){
			EDITOR.delete($(this));
		});
		$(document).on('click','#overlay .button.prev, #overlay .button.next',function(/*e*/){
			EDITOR.nav($(this));
		});

		$(document).on('click','.colorSelector .ui.label',function(/*e*/){
			if ($(this).hasClass('active')) { return; }

  			var colorId = $(this).attr('data-color');
  			var colorClass = 'color_'+colorId;
  			var entityId = $(this).closest('.entity').attr('data-id');

  			$('.colorSelector .ui.label').removeClass('active');
  			$('.colorSelector .ui.label.'+colorClass).addClass('active');
  			$('.ui.label[data-id='+entityId+']').removeClass(
  				function (index, css) { return (css.match (/(^|\s)color_\S+/g) || []).join(' '); }
  			);
  			$('.ui.label[data-id='+entityId+']').addClass(colorClass);

  			$.ajax({
				url: '_.php',
				method: 'GET',
				dataType: 'html',
				data: {
					f: 'changeColorEntity',
					entity_id: entityId,
					color: colorId
				},
				success:function(/*d*/){
					alertify.success('Couleur changée avec succès');
					GRAPHS.updateEntityNgram(entityId, colorId);
					PROJECT.refreshStory();
				}
			});

		});

		$(document).on('click', '.buttons.type .button', function(){
			if ($(this).hasClass('black')) { return; }

			var entityId = $(this).closest('.entity').attr('data-id');
			var entityType = $(this).attr('data-type');

			$('.buttons.type .button').removeClass('black');
			$(this).addClass('black');

			$.ajax({
				url: '_.php',
				method: 'GET',
				dataType: 'html',
				data: {
					f: 'changeTypeEntity',
					entity_id: entityId,
					type: entityType
				},
				success:function(/*d*/){
					PROJECT.refreshStory();
					PROJECT.updateEntities();
					GRAPHS.updateTimelineGraph();
					alertify.success('Type changé avec succès');
				}
			});

		});

		$(document).on('change', '#cbIncludeNonColored', function(){
			GRAPHS.updateTimelineGraph();
			GRAPHS.updateCharsGraph();
			GRAPHS.updateIOGraph();
		});

	},

	initRangeInputs : function(start, end){
		$('.ui.double.range').each(function(){
			var r = $(this);
			if (r.find('.labels>.label').length) {
				var vMax = r.find('.labels>.label').length+1;
				if (end===-1) {
					end = vMax;
				}
				r.range({ min: 0, max: vMax, start: start, doubleStart: end, onChange:function(){ GRAPHS.rangeInputChanged(); } });
			}
		});
	},

	initSortableDraggable : function(){
		$(":ui-sortable").sortable("destroy");
		$(":ui-draggable").draggable("destroy");

		$('.zone .chapterList').sortable({
			items: '.tile',
			containment: "parent",
			receive : function(event,ui){
				ui.sender.remove();
				var newStory = {};
				var i = 1;
				$('.zone.structure .chapterList').each(function(){
					newStory[i++] = $(this).find('.tile').map(function(){return $(this).attr("data-id");}).get().join(',');
				});
				$.ajax({
					url: '_.php',
					method: 'GET',
					dataType: 'html',
					async: false,
					data: {
						f: 'saveStory',
						story : newStory
					},
					success:function(/*d*/){
						PROJECT.refreshStory();
					}
				});
			}
		});

		$('.tile').draggable({
			connectToSortable: '.zone .chapterList',
			helper: 'clone',
			start: function(){
				$(this).hide();
			},
			stop: function(){
				$(this).show();
			}
		});

		$('.tile').on('click touchdown', function(){
			var chapter_id = parseInt($(this).attr('data-id'), 10);
			if (chapter_id===0) {
				alertify.prompt('Nouveau chapitre', 'Titre', function(evt, chapter_name) {
					$.ajax({
						url: '_.php',
						method: 'GET',
						dataType: 'html',
						async: false,
						data: {
							f: 'newChapter',
							name: chapter_name,
							number: $('.zone.stock').find('.tile').length-1
						},
						success:function(new_chapter_id){
							EDITOR.openChapter(new_chapter_id);
							PROJECT.refreshStory();
						}
					});
				});
			}
			else {
				EDITOR.openChapter(chapter_id);
			}
		});
	},

	refreshStory : function(){
		GRAPHS.updateTimelineGraph();
		GRAPHS.updateCharsGraph();
		GRAPHS.updateIOGraph();
		$.ajax({
			url: '_.php',
			method: 'GET',
			dataType: 'html',
			async: false,
			data: {
				f: 'loadStory',
			},
			success:function(d){
				$('.zone.structure').html(d);
			}
		});

		$.ajax({
			url: '_.php',
			method: 'GET',
			dataType: 'html',
			async: false,
			data: {
				f: 'loadStock',
			},
			success:function(d){
				$('.zone.stock').html(d);
			}
		});
		PROJECT.initSortableDraggable();
	},

	updateEntities : function() {
		$.getJSON('_.php', {'f':'getEntities'}, function(d){
			var classes = ['character', 'place', 'other'];
			classes.forEach(function(entityClass){
				$('.entityManager .zone.'+entityClass).find('.label').remove();
				if (entityClass in d) {
					d[entityClass].forEach(function(e){
						var labelDetails = '<div class="labelDetails ui card"><div class="content"><i class="comment icon"></i> Cité '+e.citations+' fois</div><div class="extra content">Cliquez pour éditer...</div></div>';
						$('.entityManager .zone.'+entityClass).append('<a class="ui label entity color_'+e.color+'" data-id="'+e.id+'">'+e.name+labelDetails+'</a>');
					});
				}
				$('.entityManager .zone.'+entityClass).append('<a class="ui label color_0" data-id="0" data-class="'+entityClass+'"><i class="icon add"></i></a>');
			});
		});
	}
};

$(document).ready(function(){
	PROJECT.init();
	alertify.defaults.glossary.title = '';
	alertify.defaults.glossary.ok = 'Confirmer';
	alertify.defaults.glossary.cancel = 'Annuler';
});
$(window).resize(function() {
	$.debounce(500, function(){
		GRAPHS.updateTimelineGraph();
		GRAPHS.updateCharsGraph();
		GRAPHS.updateIOGraph();
	});
});
