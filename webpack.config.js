
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		frontend: path.resolve( process.cwd(), 'src/js/', 'frontend.js' ),
        admin: path.resolve( process.cwd(), 'src/js/', 'admin.js' ),
		front_style: path.resolve( process.cwd(), 'src/scss/', 'frontend.scss' ),
		admin_style: path.resolve( process.cwd(), 'src/scss/', 'admin.scss' ),
	}
};