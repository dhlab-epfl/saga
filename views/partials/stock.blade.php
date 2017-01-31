<p class="chapterList">
	@foreach($_SESSION['story']['stock'] as $n => $c)
		<span class="tile" data-id="{{$c['id']}}" data-number="{{$n}}" title="{{$c['title']}}">
			@if(isset($entitiesByChapter[$c['id']]))
				@foreach($entitiesByChapter[$c['id']] as $entity_id => $count)
					<span class="char color_{{$coloredEntities[$entity_id]}}" style="width:{{100*$count/$totalOccurences[$c['id']]}}%;"></span>
				@endforeach
			@endif
			@if(isset($placeOfChapter[$c['id']]))
				<span class="place color_{{$coloredEntities[$placeOfChapter[$c['id']]]}}"></span>
			@endif
		</span>
	@endforeach
	<a class="tile" data-id="0" data-number="0"><i class="icon add"></i></a>
</p>
