<!DOCTYPE html>
<html lang="fr">
<head>
	<!-- Standard Meta -->
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
	<link rel="icon" href="/favicon.ico" type="image/x-icon"/>

	<!-- Site Properties -->
	<title>Saga</title>
	<link media="all" type="text/css" rel="stylesheet" href="/semantic/semantic.min.css"/>
	<link media="all" type="text/css" rel="stylesheet" href="/s/app.css"/>
	@if(count(@$styles))
		@foreach($styles as $style)
			<link media="all" type="text/css" rel="stylesheet" href="/s/{{$style}}.css"/>
		@endforeach
	@endif
	<script src="/js/jquery.min.js"></script>
	<script src="/js/jquery-ui.min.js"></script>
	<script src="/js/jquery.scrollTo.min.js"></script>
	<script src="/js/jquery.appear.min.js"></script>
	<script src="/js/jquery.ba-throttle-debounce.min.js"></script>
	<script src="/js/alertify.min.js"></script>
	<script src="/js/d3.v4.min.js"></script>
	<script src="/js/d3.fisheye.min.js"></script>
	<script src="/semantic/semantic.min.js"></script>
	<script src="/js/app.min.js"></script>
	@if(count(@$scripts))
		@foreach($scripts as $script)
			<script src="/js/{{$script}}.min.js"></script>
		@endforeach
	@endif
</head>
<body>
	<div id="overlay" class="overlay">
		<div class="panel">
			<header>
				<h1></h1>
				<i class="remove icon"></i>
			</header>
			<div class="content ui padded grid">
			</div>
		</div>
	</div>
	<div id="wrapper">
		<div class="ui grid">
			@if(count(@$msg_errors)||count(@$msg_success))
			<div class="ui row">
				<div class="ui sixteen column wide">
					@if(count($msg_errors))
						<div class="ui negative fluid message">
						  <ul class="list">
						  	@foreach($msg_errors as $m)
							    <li>{{$m}}</li>
							@endforeach
						  </ul>
						</div>
					@endif
					@if(count($msg_success))
						<div class="ui positive message">
						  <ul class="list">
						  	@foreach($msg_success as $m)
							    <li>{{$m}}</li>
							@endforeach
						  </ul>
						</div>
					@endif
				</div>
			</div>
			@endif
   	 		@yield('content')
		</div>
	</div>
</body>
</html>