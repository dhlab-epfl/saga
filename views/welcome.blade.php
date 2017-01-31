@extends('layout')

@section('content')

<div class="ui centered container" id="home">
	<div class="ui grid segment">
		<div class="ui row">
			<img src="/i/saga.png" class="ui image centered " alt="Saga+" />
		</div>
		<div class="ui row">
			<div class="ui attached blendedin menu">
				<a href="/" class="active item">À Propos</a>
				<a href="/library.php" class="item">Bibliothèque</a>
				<a class="item disabled">Documentation</a>
				<div class="right menu">
					<div class="item"><a class="ui primary button" href="/home.php">Mon compte</a></div>
				</div>
			</div>
		</div>
	</div>

	<div class="ui two column doubling grid">
		<div class="column">
			<h5 class="ui header">Le projet <em>Saga+</em></h5>
			<div class="ui segment">
				<p><em>Saga+</em> est un éditeur de texte visant à explorer les possibilités de réorganisation d'œuvres littéraires longues et complexes.</p>
				<p>Le code source est disponible sur <a href="http://github.com/"><i class="icon github"></i>GitHub</a></p>
			</div>
		</div>
		<div class="column">
			<h5 class="ui header">Derniers ouvrages publiés</h5>
			<div class="ui segment">
				<div class="ui link items">
					@each('partials.bookItem', $lastBooks, 'book')
				</div>
			</div>
		</div>
	</div>

</div>

@stop