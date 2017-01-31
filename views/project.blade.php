@extends('layout')

@section('content')

	@if (@$extractEntities)
	<div id="extractEntities" data-file="{{$extractEntities}}" id="wait" class="overlay">
		<h1>Texte en cours d'analyse...</h1>
		<p>Cette étape nécessite quelques minutes. Veuillez patienter.</p>
		<img src="/i/load.gif" class="load" />
	</div>
	@endif

	<div class="ui row">
		<div class="ui sixteen column wide">
			<div class="ui  menu">
				<a class="header item" href="/" style="padding-top:0;padding-bottom:0;"><img src="/i/saga.png" style="width:3.6em;" /></a>
				<a class="item" id="menuOptions">
					<div class="ui large horizontal label">{{$project_title}}</div>
					<div class="ui small tag labels" style="margin:2px 0 -2px -5px;"><p class="ui blue label">Version {{$project_version}}</p></div>
				</a>
				<div class="right menu">
					<a class="item" id="menuPublier"><div class="ui primary button">Publier</div></a>
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
	<div class="ui sixteen wide column">
		<div class="ui styled fluid accordion">
			<div class="active title"><i class="dropdown icon"></i> Entités</div>
			<div class="active content ui grid entityManager">
				<div class="ui row storyManager">

					<div class="ui six wide column">
						<h3 class="ui header">Personnages</h3>
						<div class="zone character">
						</div>
					</div>

					<div class="ui six wide column">
						<h3 class="ui header">Lieux</h3>
						<div class="zone place">
						</div>
					</div>

					<div class="ui four wide column">
						<h3 class="ui header">Autres</h3>
						<div class="zone other">
						</div>
					</div>
				</div>
			</div>

			<div class="active title"><i class="dropdown icon"></i> Histoire</div>
			<div class="active content ui grid">
				<div class="ui row storyManager">
					<div class="ui twelve wide column">
						<h3 class="ui header">Structure</h3>
						<div class="zone structure">
						</div>
					</div>

					<div class="ui four wide column">
						<h3 class="ui header">Chapitres inutilisés</h3>
						<div class="zone stock">
						</div>
					</div>
				</div>
			</div>

			<div class="active title"><i class="dropdown icon"></i> Analyse</div>
			<div class="active content ui grid" id="graphs">
				<div class="ui row">
					<div class="ui sixteen wide column">
						<div class="ui toggle checkbox">
							<input type="checkbox" id="cbIncludeNonColored" name="includeNonColored">
							<label>Inclure les personnages sans couleur</label>
						</div>
					</div>
				</div>
				<div class="ui row">
					<div class="ui sixteen wide column">
						<div id="timelineGraph"><p>Timeline</p><svg class="timelineGraph"></svg></div>
					</div>
				</div>
				<div class="ui row">
					<div class="ui eight wide column">
						<div id="charsGraph">
							<p>Réseau des personnages</p>
							<svg class="charsGraph"></svg>
							<div class="ui labeled double range" id="chars-range">
								<ul class="labels">
								</ul>
							</div>
						</div>
					</div>
					<div class="ui eight wide column">
						<div id="ioGraph"><p><span class="green">&block; Entrées</span> / <span class="red">&block; Sorties</span> des personnages</p><svg class="ioGraph"></svg></div>
					</div>
				</div>
			</div>

			<div class="title"><i class="dropdown icon"></i> Statistiques de lecture</div>
			<div class="content ui grid" id="graphs">
				<p>Données insuffisantes</p>
			</div>

		</div>
	</div>

@stop