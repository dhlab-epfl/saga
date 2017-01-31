<div class="options ui row">
	<div class="ui column eight wide">
		<div class="ui labeled fluid input">
			<div class="ui label">Titre</div>
			<input type="text" name="title" value="{{$project_title}}"/>
		</div>
	</div>
	<div class="ui column eight wide">
		<div class="ui labeled fluid input">
			<div class="ui label">Auteur</div>
			<input type="text" name="author" value="{{$project_author}}"/>
		</div>
	</div>
	<div class="ui column sixteen wide">
		<div class="ui labeled fluid input">
			<div class="ui label">Résumé</div>
			<input type="text" name="summary" value="{{$summary}}"/>
		</div>
	</div>
</div>

<div class="ui row">
	<div class="ui sixteen wide column">
		<button class="ui primary button" id="bSave">Enregistrer</button>
	</div>
</div>

<div class="versions ui row">
	<div class="ui sixteen wide column">
		<h5>Versions</h5>
		<a class="ui button" id="bNewVersion"><i class="ui icon add"></i>Nouvelle copie de la version courante</a>
		<table class="ui compact table">
			<thead><tr><th>Numéro</th><th>Dernière modification</th><th>Résumé</th><th></th></tr>
			<tbody>
			@foreach($projects as $p_idx => $project)
				<tr>
					<td class="nobreak">{{$project['version']}}
						@if ($project_id==$project['id'])
							<div class="ui small blue tag label" style="margin-left:10px;">En cours</div>
						@endif
						@if ($project['status']=='published')
							<div class="ui small green tag label" style="margin-left:10px;">Publié</div>
						@endif
					</td>
					<td>{{$project['date_modif']}}</td>
					<td>{{$project['summary']}}</td>
					<td>
						@if ($project_id!=$project['id'])
							<a href="" data-id="{{$project['id']}}" class="ui tiny red button deleteVersion">Supprimer</a>
							@if ($project['status']!='published')
								<a href="project.php?load={{$project['id']}}" class="ui tiny button">Ouvrir</a>
							@endif
						@endif
					</td>
				</tr>
			@endforeach
			</tbody>
		</table>
	</div>
	<div class="ui eight wide column">
	</div>
</div>

<!--
<div class="export ui row">
	<div class="infos ui sixteen wide column">
		<div class="">
			<h2>Format</h2>
			<select name="format">
			@foreach($fileFormats as $f)
				<option value="{{$f['id']}}">{{$f['label']}}</option>
			@endforeach
			</select>
		</div>
	</div>
</div>
-->