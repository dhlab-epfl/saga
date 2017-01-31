@extends('layout')

@section('content')

<div class="ui centered container" id="login">
	<div class="ui row">
		<div class="ui column segment">
			<img src="/i/saga.png" alt="Saga+" class="ui centered image" />
		</div>
	</div>

	<div class="ui three column stackable grid centered" style="margin-top:50px;">
		<div class="column">
			<form class="ui form" method="POST">
				<div class="ui header">Se connecter</div>
				<div class="field">
					<label>E-mail</label>
					<input type="text" name="email" placeholder="E-mail">
				</div>
				<div class="field">
					<label>Mot de passe</label>
					<input type="password" name="password" placeholder="Mot de passe">
				</div>
				<input name="login" class="ui button" type="submit" value="Se connecter" />
			</form>
		</div>
		<div class="column one wide"><div class="ui column divider vertical">ou</div></div>
		<div class="column" style="">
			<form class="ui form"  method="POST">
				<div class="ui header">Créer un compte</div>
				<div class="field">
					<label>E-mail</label>
					<input type="text" name="email" placeholder="E-mail">
				</div>
				<input name="register" class="ui button" type="submit" value="S'enregistrer" />
			</form>
		</div>
	</div>
</div>

@stop