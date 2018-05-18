const webpack = require('webpack')
const Promise = require('es6-promise').polyfill()
const path = require('path');
const ExtractTextPlugin = require("extract-text-webpack-plugin")
const sourceDir = path.resolve(__dirname, 'js')
const destDir = path.resolve(__dirname, 'dist')
const sourceSassDir = path.resolve(__dirname, 'scss')
const OptimizeCssAssetsPlugin = require('optimize-css-assets-webpack-plugin')

module.exports = (env, argv) => {
  const inProduction = argv.mode === 'production'
  const inDevelopment = argv.mode === 'development'
  
  const settings = {
    entry: {
      'js/custom.js': sourceDir + '/index.js',
      'js/base.js': sourceDir + '/base.js',
      'css/custom.css': sourceSassDir + '/main.scss'
    },
    output: {
      path: destDir,
      filename: "[name]",
    },
    resolve: {
      extensions: ['.js']
    },
    plugins: [
      new ExtractTextPlugin('css/custom.css', {allChunks: true}),
      new webpack.ProvidePlugin({$: "jquery", jQuery: "jquery",}),
    ],
    externals: {
      $: 'jQuery'
    },
    module: {
      rules: [
        {
          test: require.resolve('jquery'),
          use: [
            {
              loader: 'expose-loader',
              options: 'jQuery'
            }, {
              loader: 'expose-loader',
              options: '$'
            },
          ]
        }, {
          test: /\.scss$/,
          use: ExtractTextPlugin.extract({
            fallback: 'style-loader',
            use: [
              {
                loader: 'css-loader',
                options: {
                  sourceMap: true
                },
              }, {
                loader: 'sass-loader',
                options: {
                  sourceMap: true
                },
              },
            ]
          })
        }, {
          test: /\.(png|woff|woff2|eot|ttf|svg)$/,
          exclude: '/node_modules/',
          loader: 'url-loader?limit=100000'
        },
      ]
    }
  }
  
  if (inDevelopment) {
    settings.devtool = 'inline-source-map'
  }
  
  if (inProduction) {
    settings.plugins.push(new OptimizeCssAssetsPlugin({
      assetNameRegExp: /css\/custom\.css$/g,
      cssProcessor: require('cssnano'),
      cssProcessorOptions: {
        discardComments: {
          removeAll: true
        }
      },
      canPrint: true,
    }))
  }
  return settings
}
