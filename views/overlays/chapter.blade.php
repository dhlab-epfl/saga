<div class="ui eight column row">
	<div class="ui one wide column">
	</div>
	<div class="ui eight wide column">
		<div class="ui header editable" contenteditable="true">{{$c['title']}}</div>
	</div>
	<div class="ui one wide column">
	</div>
	<div class="ui two wide column">
		<button class="ui basic button prev right floated" style="white-space:nowrap;">Précédent</button>
	</div>
	<div class="ui two wide column">
		<button class="ui basic button next left floated" style="white-space:nowrap;">Suivant</button>
	</div>
	<div class="ui two wide column">
		<button class="ui red basic button delete">Supprimer</button>
	</div>
</div>
<div class="chapter ui row" data-id="{{$c['id']}}">
	<div id="stickycontext" class="ui ten wide column">
		<div class="editor editable" contenteditable="true">{!! $c['textHTML'] !!}</div>
		<div class="legend"></div>
	</div>
	<div class="infos ui rail six wide column">
		<div class="ui sticky">
			<div class="field place">
				<h2>Localisation</h2>
				<div class="ui floating dropdown labeled search icon button color_{{$allPlaces[$c['place']]['color']}}">
					<i class="world icon"></i>
					<input type="hidden" name="id" value="{{ $c['place'] }}">
					<span class="default text">{{$allPlaces[$c['place']]['name']}}</span>
					<div class="menu">
						@foreach($allPlaces as $e)
							<div class="item" data-value="{{$e['id']}}">{{$e['name']}}</div>
						@endforeach
					</div>
				</div>
	<!--
				<select name="place">
				@foreach($allPlaces as $e)
					<option{{($e['id']==$c['place']?' selected="selected"':'')}} value="{{$e['id']}}">{{$e['name']}}</option>
				@endforeach
				</select>
	-->
			</div>
			<hr/>
			<div class="">
				<h2>Temporalité</h2>
				<div class="ui right labeled input">
					<input type="text" name="y" value="{{$c['y']}}" size="4" />
					<div class="ui basic label">A</div>
				</div> -
				<div class="ui right labeled input">
					<input type="text" name="m" value="{{$c['m']}}" size="4" />
					<div class="ui basic label">M</div>
				</div> -
				<div class="ui right labeled input">
					<input type="text" name="d" value="{{$c['d']}}" size="4" />
					<div class="ui basic label">J</div>
				</div>,
				<div class="ui right labeled input">
					<input type="text" name="h" value="{{$c['h']}}" size="4" />
					<div class="ui basic label">H</div>
				</div> :
				<div class="ui right labeled input">
					<input type="text" name="i" value="{{$c['i']}}" size="4" />
					<div class="ui basic label">M</div>
				</div>
			</div>
			<hr/>
			<div class="character">
				<h2>Personnages</h2>
				@foreach($chapterChars as $e)
					<div class="ui label color_{{$e['color']}}" data-id="{{$e['id']}}"><a>{{$e['name']}}</a><i class="delete icon"></i></div>
				@endforeach
			</div>
			<hr/>
			<div class="character">
				<h2>Écriture</h2>
				<p id="textStats"></p>
			</div>
			<hr/>
		</div>
	</div>
</div>