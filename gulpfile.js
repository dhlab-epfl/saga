var elixir = require('laravel-elixir');
require( 'elixir-jshint' );

elixir.config.assetsPath = './';
elixir.config.publicPath = './';

elixir.config.css.sass.folder = 's/';
elixir.config.css.outputFolder = 's/';
elixir.config.sourcemaps = false;

elixir(function(mix) {
    mix.sass('app.scss');
	
	mix.scripts(['app.js'], 'js/app.min.js');
	mix.jshint(['js/app.min.js']);
});
