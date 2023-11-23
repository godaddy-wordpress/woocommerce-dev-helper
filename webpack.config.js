const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const DependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const path = require('path');

module.exports = {
	...defaultConfig,
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin(),
	],
	entry: {
		'block-checkout': path.resolve(process.cwd(), 'client/js/blocks', 'block-checkout.js'),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve(process.cwd(), 'assets/js/blocks'),
	},
};
