let webpack = require('webpack');
let UglifyJSPlugin = require('uglifyjs-webpack-plugin');
let ExtractTextPlugin = require("extract-text-webpack-plugin");
let ProgressBarPlugin = require('progress-bar-webpack-plugin');
let OptimizeCSSPlugin = require('optimize-css-assets-webpack-plugin');

module.exports = env => {
    let production = env.production === true;
    console.log('Production: ', production);

    let plugins = [
        new webpack.DefinePlugin(
            {
                'process.env': {
                    NODE_ENV: production ? '"production"':'"development"'
                }
            }
        ),
        new ExtractTextPlugin('css/[name].css'),
        new webpack.optimize.CommonsChunkPlugin({name: 'common', minChunks: Infinity})
    ];

    if (env.production) {
        plugins.push(new OptimizeCSSPlugin({cssProcessorOptions: {safe: true}}));
        plugins.push(
            new UglifyJSPlugin(
                {
                    uglifyOptions: {
                        beautify: false,
                        ecma    : 6,
                        compress: true,
                        comments: false,
                        ascii   : true
                    },
                    cache        : true,
                    parallel     : true
                }
            )
        );
        plugins.push(new ProgressBarPlugin());
    }

    return {
        entry  : {
            app     : __dirname + '/src/js/app.js',
            admin   : __dirname + '/src/js/admin.js',
            personal: __dirname + '/src/js/personal.js'
        },
        output : {
            path    : __dirname + '/src/',
            filename: "js/Static/[name].js"
        },
        resolve: {
            modules   : ['node_modules', 'src'],
            extensions: ['.js', '.vue', '.scss'],
            alias     : {
                'vue$' : 'vue/dist/vue.esm.js',
                '@'    : __dirname + '/src',
                '@js'  : __dirname + '/src/js',
                '@vue' : __dirname + '/src/vue',
                '@scss': __dirname + '/src/scss',
                '@vc'  : __dirname + '/src/vue/Components'
            }
        },
        module : {
            loaders: [
                {
                    test   : /\.vue$/,
                    loader : 'vue-loader',
                    options: {
                        extractCSS: true,
                        loaders   : {
                            scss: ExtractTextPlugin.extract(
                                {
                                    use     : [
                                        {
                                            loader : 'css-loader',
                                            options: {minimize: production}
                                        }, {
                                            loader : 'sass-loader',
                                            options: {minimize: production}
                                        }, {
                                            loader : 'sass-resources-loader',
                                            options: {resources: 'src/scss/Partials/_variables.scss'}
                                        }
                                    ],
                                    fallback: 'vue-style-loader'
                                }
                            )
                        }
                    }
                }, {
                    test: /\.scss$/,
                    use : ExtractTextPlugin.extract(
                        {
                            use: [
                                {
                                    loader : 'css-loader',
                                    options: {minimize: production}
                                }, {
                                    loader : 'sass-loader',
                                    options: {minimize: production}
                                }, {
                                    loader : 'sass-resources-loader',
                                    options: {resources: 'src/scss/Partials/_variables.scss'}
                                }
                            ]
                        }
                    )
                }, {
                    test   : /\.(png|jpg|gif|svg|eot|ttf|woff|woff2)$/,
                    loader : 'url-loader',
                    options: {
                        limit          : 2048,
                        outputPath     : 'css/',
                        publicPath     : '../',
                        useRelativePath: false
                    }
                }
            ]
        },
        plugins: plugins
    };
};