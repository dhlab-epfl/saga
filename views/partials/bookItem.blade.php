<div class="item" onclick="window.location='/reader.php?id={{$book['id']}}'">
	<div class="ui small image">
		<img src="{{$book['icon']}}"/>
	</div>
	<div class="content">
		<a class="header" href="/reader.php?id={{$book['id']}}">{{$book['title']}}</a>
		<div class="meta">
			<span>{{$book['author']}}</span>
		</div>
		<div class="description">
			<p>{{$book['summary']}}</p>
		</div>
	</div>
</div>