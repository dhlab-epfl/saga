@extends('layout')

@section('content')

<div class="ui centered container" id="library">
	<div class="ui grid segment">
		<div class="ui row">
			<img src="/i/saga.png" class="ui image centered " alt="Saga+" />
		</div>
		<div class="ui row">
			<div class="ui attached blendedin menu">
				<a href="/" class="item">À Propos</a>
				<a href="/library.php" class="active item">Bibliothèque</a>
				<a class="item disabled">Documentation</a>
				<div class="right menu">
					<div class="item"><a class="ui primary button" href="/home.php">Mon compte</a></div>
				</div>
			</div>
		</div>
	</div>

	<div class="ui grid segment">
		<div class="ui row">
			<div class="ui column six wide centered">
				<div class="ui huge fluid icon input">
					<input type="text" id="fSearchBooks" placeholder="Rechercher...">
					<i class="search icon"></i>
				</div>
			</div>
		</div>
		<div class="ui row">
			<div class="ui column sixteen wide link items" id="results">
			</div>
		</div>
	</div>

</div>

@stop