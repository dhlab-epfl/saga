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