<div class="entity ui grid" data-id="{{$e['id']}}">

	<div class="ui row">
		<div class="ui four wide column">
			<div class="ui header">{{$e['name']}}</div>
		</div>
		<div class="ui ten wide column">
			<div class="ui small buttons type">
				<button class="ui button {{$e['class'] == 'character' ? 'black' :''}}" data-type="character">Personnage</button>
				<div class="or"></div>
				<button class="ui button {{$e['class'] == 'place' ? 'black' :''}}" data-type="place">Lieu</button>
				<div class="or"></div>
				<button class="ui button {{$e['class'] == 'other' ? 'black' :''}}" data-type="other">Autre</button>
			</div>
		</div>
		<div class="ui two wide column">
			<button class="ui right floated red basic button delete">Supprimer</button>
		</div>
	</div>

	<div class="ui row">
		<div class="ui four wide column">
			<h4>Couleur</h4>
			<div class="colorSelector">
				@for ($i = 0; $i < 25; $i++)
				<a data-color="{{$i}}" class="ui label color_{{$i}} {{($i==$e['color']) ? 'active' : ''}}"></a>
				@endfor
			</div>
		</div>
		<div class="ui six wide column">
			<h4>Mémo</h4>
			<div class="notice editable" contenteditable="true">{{$e['notice']}}</div>
		</div>
		<div class="ui six wide column">
			<h4>Coréférences</h4>
			<div id="coreferences">
				@foreach ($e['synonyms'] as $synonym)
					<a class="ui large label">{{@$synonym}}<i class="delete icon"></i></a>
				@endforeach
			</div>
			<div class="ui floating dropdown labeled search icon button">
				<i class="add icon"></i>
				<span class="text">Ajouter...</span>
				<div class="menu">
					@foreach ($otherEntities as $other)
						<div class="item" data-value="{{$other['id']}}">{{$other['name']}}</div>
					@endforeach
				</div>
			</div>
			<a class="ui button" id="bAddCoref">OK</a>
		</div>
	</div>

	<div class="ui row">
		<div id="entityNgram" class="ui sixteen wide column">
			<svg class="entityNgram" style="width:100%; height:100%;"></svg>
		</div>
	</div>

	<div class="ui row">
		<h4>Citations</h4>
		<table id="excerpts" class="ui celled padded table">
		<tr><th>Chapitre</th><th>Extrait</th></tr>
		@foreach($excerpts as $e)
			<tr><td>{{$e['ref']}}</td><td>{!! $e['text'] !!}</td></tr>
		@endforeach
		</table>
	</div>
</div>