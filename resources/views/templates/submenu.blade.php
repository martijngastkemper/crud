<div class="col-sm-2">

	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Options</h3>
		</div>
		<div class="list-group">@foreach ($submenu as $item)
			<a class="list-group-item" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
		@endforeach </div>
	</div>

</div>
