	@foreach($_SESSION['story']['tomes'] as $tome => $list)
			<p class="chapterList">
				@foreach($list as $n => $c)
					<span class="tile" data-id="{{$c['id']}}" data-number="{{$n}}" title="{{$c['title']}}">
						@if(isset($entitiesByChapter[$c['id']]))
							@foreach($entitiesByChapter[$c['id']] as $entity_id => $count)
								<span class="char color_{{$coloredEntities[$entity_id]}}" style="width:{{100*$count/$totalOccurences[$c['id']]}}%;"></span>
							@endforeach
						@endif
						@if(isset($placeOfChapter[$c['id']]))
							<span class="place color_{{@$coloredEntities[$placeOfChapter[$c['id']]]}}"></span>
						@endif
					</span>
				@endforeach
			</p>
	@endforeach
		<p class="chapterList"></p>
