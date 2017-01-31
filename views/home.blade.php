@extends('layout')

@section('content')

<div class="ui centered container" id="home">
	<div class="ui grid segment">
		<div class="ui row">
			<img src="/i/saga.png" class="ui image centered " alt="Saga+" />
		</div>
		<div class="ui row">
			<div class="ui attached blendedin menu">
				<a href="/" class="item">À Propos</a>
				<a class="item disabled">Documentation</a>
				<div class="right menu">
					<div class="ui dropdown item">
						<i class="user icon"></i> <i class="dropdown icon"></i>
						<div class="menu">
							<a class="item" href="/home.php">Mes Projets</a>
							<a class="item" href="/?logout=">Logout</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="ui two column doubling grid">
		<div class="column">
			<h5 class="ui header">Nouveau projet</h5>
			<div class="ui top attached tabular menu">
				<a class="item active" data-tab="first">Exemples</a>
				<a class="item" data-tab="second">Importer</a>
				<a class="item" data-tab="third">Projet vide</a>
			</div>
			<div class="ui bottom attached tab segment active" data-tab="first">
				<form class="ui form" method="post" action="project.php">
					<input type="hidden" name="new" value="import" />
					<p>
						<select name="example">
							@foreach ($examples as $file => $title) {
								<option value="{{$file}}">{{$title}}</option>';
							@endforeach
						</select>
					</p>
					<input type="submit" value="»"/>
				</form>
			</div>
			<div class="ui bottom attached tab segment" data-tab="second">
				<form class="ui form" method="post" action="project.php" enctype="multipart/form-data">
					<input type="hidden" name="new" value="import" />
					<p>Vous pouvez importer vos livres sous forme de texte brut (format ".txt"). Les chapitres seront automatiquement détectés.</p>
					<p>
						<input type="file" name="book" />
					</p>
					<input type="submit" value="»"/>
				</form>
			</div>
			<div class="ui bottom attached tab segment" data-tab="third">
				<form class="ui form" method="post" action="project.php">
					<div class="ui labeled fluid input">
						<div class="ui label">Titre</div>
						<input type="text" name="title" value="" placeholder="Nom du livre ou du projet" />
					</div>
					<input type="hidden" name="new" value="empty" />
					<p></p>
					<input type="submit" value="»"/>
				</form>
			</div>
		</div>
		@if (count($projects)>0)
		<div class="column">
			<h5 class="ui header">Mes projets</h5>
			<table id="projects" class="ui celled table">
				<thead><tr><th>Nom</th><th>Version</th><th></th></tr>
				<tbody>
				@foreach($projects as $project_id => $project)
					<tr>
						<td>{{$project['title']}}</td>
						<td>
							<div class="ui inline dropdown versionSelect">
								<input type="hidden" name="id" value="{{ $project['ids'][0][0] }}">
								<div class="default text">{{ $project['ids'][0][1] }}</div>
								<i class="dropdown icon"></i>
								<div class="menu">
								@foreach ($project['ids'] as $idVersionPair)
									<div class="item" data-value="{{ $idVersionPair[0] }}">{{ $idVersionPair[1] }}</div>
								@endforeach
								</div>
							</div>
						</td>
						<td><nobr>
							<a href="" class="ui red basic button delete tiny" data-id="{{ $project['ids'][0][0] }}"><i class="ui medium icon delete"></i></a>
							<a href="project.php?load={{ $project['ids'][0][0] }}" class="ui tiny button open">Ouvrir</a>
						</nobr></td>
					</tr>
				@endforeach
				</tbody>
			</table>
		</div>
		@endif
	</div>

</div>

@stop