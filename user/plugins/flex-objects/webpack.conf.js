var webpack = require('webpack'),
    path = require('path'),
    UglifyJsPlugin = require('uglifyjs-webpack-plugin'),
    isProd = process.env.NODE_ENV === 'production' || process.env.NODE_ENV === 'production-wip',
    VueLoaderPlugin = require('vue-loader/lib/plugin');

module.exports = {
    entry: {
        'flex-objects': './app/main.js'
    },
    devtool: isProd ? false : 'eval-source-map',
    target: 'web',
    output: {
        path: path.resolve(__dirname, 'js'),
        filename: '[name].js',
        chunkFilename: 'flex-objects-vendor.js'
    },
    optimization: {
        minimize: isProd,
        minimizer: [
            new UglifyJsPlugin({
                uglifyOptions: {
                    compress: {
                        drop_console: true
                    },
                    dead_code: true
                }
            })
        ]
        /* , splitChunks: {
            cacheGroups: {
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    priority: 1,
                    name: 'vendor',
                    enforce: true,
                    chunks: 'all'
                }
            }
        }*/
    },
    plugins: [
        new webpack.ProvidePlugin({
            'fetch': 'imports-loader?this=>global!exports-loader?global.fetch!whatwg-fetch'
        }),
        new VueLoaderPlugin()
    ],
    externals: {
        jquery: 'jQuery',
        'grav-config': 'GravConfig'
    },
    module: {
        rules: [
            { enforce: 'pre', test: /\.json$/, loader: 'json-loader' },
            { enforce: 'pre', test: /\.js$/, loader: 'eslint-loader', exclude: /node_modules/ },
            { test: /\.css$/, loader: 'vue-style-loader!style-loader!css-loader' },
            {
                test: /\.js$/,
                loader: 'babel-loader',
                query: {
                    presets: ['@babel/preset-env'],
                    plugins: ['@babel/plugin-proposal-object-rest-spread']
                }
            },
            { test: /\.vue$/, loader: 'vue-loader', options: {} },
            { test: /\.(png|jpg|gif|svg)$/, loader: 'file', options: { name: '[name].[ext]?[hash]' } }
        ]
    }
};
