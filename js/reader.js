var kMinArticleHeight = 300;
var kNextChapterSpacer = '<p class="spacer"><a class="back" href="/library.php">Choisir un autre livre</a><a class="next">Chapitre suivant</a></p>';

function nl2p(str) {
    return (str + '').replace(/([^>\r\n]+)(\r\n|\n\r|\r|\n)/g, '<p>$1</p>');
}

function showInfoBox(word, anchor) {
	$('#infoMask').hide();
	var box = $('<div id="infoBox"/>').html('<img src="/i/loading.gif" />');
	var mask = $('<div id="infoMask"/>').append(box).hide(100);
	mask.bind('click touchdown', function(){
		$(this).fadeOut();
	});
	$('body').append(mask);
	mask.fadeIn(100);
	$.get('_.php', {'f':'getInfo','k':word}, function(data){
		box.html(data);
	});
}

function orientationChange() {
	$.waypoints('refresh');
}

  // Initial execution if needed

function showChapter(container, chapter) {
	container.removeClass('loading');
	$('.text>p').waypoint('destroy');
	container.children('h1').html('T. '+chapter.tome);
	container.children('h2').html('§ '+chapter.chapter);
	var chapterText = nl2p(chapter.text+'\n') + kNextChapterSpacer;
	container.children('.text').html(chapterText);
	if (chapter.more == 0) {
		container.children('.text').find('.spacer').addClass('last');
	}
	else {
		container.children('.text').find('.spacer .next').bind('click touchdown', function(){
			$('body,html').animate({
				scrollTop: $(this).offset().top + $(this).closest('.spacer').height()
			});
		});
	}
	Hyphenator.run();
	container.children('.text').find('>p:not(.spacer)').waypoint(function(/*direction*/){
		$(this).addClass('read');
	}, {offset:'100%', triggerOnce:true});
	container.children('.text').find('a.nomenclature').bind('click touchdown', function(e){
		e.preventDefault();
		e.stopPropagation();
		showInfoBox($(this).attr('data-word'), $(this));
	});
	container.children('.text').find('>p.spacer:not(.last)').css({'height':($.waypoints('viewportHeight')/* - kMinArticleHeight*/)}).waypoint(function(/*direction*/){
		$(this).addClass('read');
		loadNextChapter();
		$(this).waypoint('destroy');
	});
	$.waypoints('refresh');
	$('.text p:in-viewport').addClass('read').css({'opacity':'1'});
}

function loadNextChapter() {
	var $page = $('#flow>div.page');
	var $container = $page.find('article').last();
	if ($.trim($container.text()) !== '') {
		$container = $container.clone();
		$container.children('*').html('');
		$page.find('.spacer .next').remove();
		$page.append($container);
	}
	$container.addClass('loading');
	window.scrollTo(0,document.body.scrollHeight);
	$.getJSON('_.php', {'f':'getNextChapter'}, function(chapter){
		setTimeout(function(){showChapter($container, chapter);}, 500);
	});
}

$(window).ready(function(){
	/*
	if ($('#toc').length>0) {
		$('#toc').stellar({
			horizontalScrolling: true,
			verticalScrolling: false,
			hideElement: function($elem) { $elem.fadeOut(200); },
			showElement: function($elem) { $elem.fadeIn(200); }
		});
	}
	var reads = $('#toc').find('a.read');
	var dx = 0;
	for (var i = 0; i < reads.length-1; i++) {
		var r = [$('#toc').find('a.read[data-order='+i+']'), $('#toc').find('a.read[data-order='+(i+1)+']')];
		var p = [(r[0].position().top<r[1].position().top)?0:1, (r[0].position().left<r[1].position().left)?0:1];
		var line = $('<div class="line '+((p[0]!==p[1])?'tlbr':'trbl')+'"/>');
		dx++;
		line.css({
					'top':r[p[0]].position().top+r[p[0]].closest('nav').position().top+80+dx,
					'left':r[p[1]].position().left+r[p[1]].closest('nav').position().left+20,
					'height':r[(p[0]+1)%2].closest('nav').position().top-r[p[0]].closest('nav').position().top+1,
					'width':r[(p[1]+1)%2].position().left-r[p[1]].position().left+1
				});
//		line.html(r[0].text()+'-'+r[1].text());
		$('#toc').append(line);
	}
	*/
	if ($('#flow').length) {
		Hyphenator.config({
			displaytogglebox : false
			/*minwordlength : 4*/
		});
		loadNextChapter();
	}

	if ($('#bInfo').length>0) {
		$('#bInfo').click(function(){
			$('#about').fadeToggle();
			$('#bInfo').toggleClass('active');
		});
	}

	$('.ui.sticky').sticky({
		context: '#wrapper'
	});

	window.addEventListener('orientationchange', orientationChange);
	orientationChange();

	document.body.style.webkitTouchCallout='none';
	document.body.style.KhtmlUserSelect='none';
//	$.mobile.allowCrossDomainPages = true; 	//https://groups.google.com/forum/#!topic/phonegap/oyizAiRsv8k
});