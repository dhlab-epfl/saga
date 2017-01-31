<div class="publish ui row form">
	<div class="ui eight wide column">
		<h5>Titre</h5>
		<input type="text" name="title" value="{{$project_title}}"/>
		<h5>Auteur</h5>
		<input type="text" name="author" value="{{$project_author}}"/>
		<h5>Résumé</h5>
		<textarea name="summary">{{$summary}}</textarea>
	</div>
	<div class="ui eight wide column">
		<h5>Couverture</h5>

		<div class="ui styled fluid accordion">
			<div class="title"><i class="dropdown icon"></i>Images prédéfinies</div>
			<div class="content">
				<input type="hidden" name="icon" value="" />
				<table class="pictureSelector">
				@for ($i = 0; $i < 8; $i++)
					<tr>
					@for ($j = 0; $j < 8; $j++)
						<td><img class="ui small image" src="/i/icons/{{$i*8+$j+1}}.jpg" /></td>
					@endfor
					</tr>
				@endfor
				</table>
			</div>
		</div>
	</div>
	<div class="ui sixteen wide column">
		<div class="ui right floated primary button">Publier</div>
	</div>
</div>