@extends('layout')

@section('content')
	<div>
		<div class="ui vertical compact sticky menu">
			<a href="/" class="ui item"><img class="ui small image" src="/i/saga.png" /></a>
		</div>
	</div>

	<div id="top"></div>
	<section id="flow" class="ui text container">
		<div class="page ui column centered">
			<h1>{{$story['title']}}</h1><h2>{{$story['author']}}</h2>
			<article><h1></h1><h2></h2><div class="text hyphenate"></div></article>
		</div>
	</section>
@stop