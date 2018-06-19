<div class="container">
	<div class="row text-center my-5 pb-5">
		<div class="col-md-4 mt-3 text-md-left">
			<a href="@$pathToWebRoot" class="btn btn-primary btn-secondary -btn-main-mini -btn-success">Home</a>
		</div>
		<div class="col-md-4">
			<a href="@$pathToWebRoot@$pathToWebComponent" class="btn btn-primary btn-secondary btn-success -btn-main">COMPONENTS</a>
		</div>
		<div class="col-md-4 mt-3 text-md-right">
			<a href="{{ $this->pathToWebRoot }}dev/tinymce" class="btn btn-primary btn-secondary -btn-main-mini -btn-success">Tiny MCE</a>
		</div>
	</div>
	
	<hr>
	<div>
		<div class="py-2"><a href="{{ $this->pathToWebRoot }}dev/tinymce?v=4.0.20" class="btn btn-secondary">Tiny MCE 4.0.20</a></div>
		<div class="py-2"><a href="{{ $this->pathToWebRoot }}dev/tinymce?v=4.7.13" class="btn btn-secondary">Tiny MCE 4.7.13</a></div>
	</div>
	
	<hr>
	<pre style="font-size: 22px;">@render('rtemplater/README.md')</pre>
</div>

@renderPage( 'rtemplater/__styles' )