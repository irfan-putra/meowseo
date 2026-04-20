const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve( __dirname, 'src/index.ts' ),
		'estimated-reading-time': path.resolve(
			__dirname,
			'src/estimated-reading-time/index.ts'
		),
		'related-posts': path.resolve(
			__dirname,
			'src/related-posts/index.ts'
		),
		siblings: path.resolve( __dirname, 'src/siblings/index.ts' ),
		subpages: path.resolve( __dirname, 'src/subpages/index.ts' ),
	},
	output: {
		path: path.resolve( __dirname, '../../build/blocks' ),
		filename: '[name].js',
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.tsx?$/,
				use: 'ts-loader',
				exclude: /node_modules/,
			},
		],
	},
	resolve: {
		...defaultConfig.resolve,
		extensions: [ '.ts', '.tsx', '.js', '.jsx' ],
		alias: {
			'@': path.resolve( __dirname, 'src/' ),
		},
	},
};
