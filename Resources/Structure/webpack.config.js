const path = require("path");
const webpack = require('webpack');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const merge = require('webpack-merge');
const uglifyJS = require('./webpack/js.uglify');
const devtool = require('./webpack/devtool');
// вендроный css и компилируемый scss проекта двумя отдельными файлами
const extractCss = new ExtractTextPlugin('css/vendor.css');
const extractScss = new ExtractTextPlugin('css/style.css');

const paths = {
  build: path.join(__dirname, "../public"),
  sass: path.join(__dirname, "sass")
};

module.exports = env => {

  // Получаем окуржение
  let testEnv = (function (env) {
    if (env === 'prod') {
      console.log('-------- Production');
      return true;
    } else {
      console.log('-------- Development');
      return false;
    }
  })(env);

  // Основные настройки
  let common = merge(
    [{
      entry: {
        // bundle.js, style.css попадают в placeholder name
        // пути указанные в свойстве актуальны для компиляции,
        // т.е. собранный [name].{js,css} будет доступен по
        // paths.build + path/to/entry + [name].{js,css,etc.}
        'bundle/bundle.js': './bundle/bundle.js',
        'css/style.css': './sass/style.scss'
      },
      output: {
        path: paths.build,
        filename: "[name]"
      },
      resolve: {
        alias: {
          views: path.resolve(__dirname, 'bundle/views/')
        },
      },
      // опции лоадеров на генерацию source-map не влияют
      plugins: [
        new webpack.ProvidePlugin({
          $: 'jquery',
          jQuery: 'jquery'
        }),
        // можно задать путь и расширение, но будет путаница
        // new ExtractTextPlugin('vendor/app.css')
        extractCss,
        extractScss
      ],
      module: {
        rules: [
          //js loader
          {
            test: /\.js$/,
            exclude: /node_modules/,
            use: {
              loader: 'babel-loader',
              options: {
                presets: ['@babel/preset-env']
              }
            }
          },
          //image, file-loader
          {
            test: /\.(jpg|gif|png|svg)$/,
            loader: 'file-loader',
            options: {
              name: '../img/[name].[ext]'
            },
          },
          //loader для компиляции scss в отдельный сss
          {
            test: /\.scss$/,
            include: [
              paths.sass,
            ],
            exclude: /node_modules/,
            use: extractScss.extract({
              fallback: 'style-loader',
              use:
                [{
                  loader: 'css-loader',
                  options: {
                    //url: false потому что иначе не заработало...
                    url: false,
                    minimize: testEnv,
                  }
                },
                  {
                    loader: 'sass-loader',
                  }],
            })
          },
          //css в отдельный файл
          {
            test: /\.css$/,
            //include: [
            // paths.css возможно понадобиться указать папку где лежат css
            //],
            // exclude: /node_modules/,
            /*
            use: [{
                loader: 'style-loader'
              },
              {
                loader: 'css-loader',
                options: {
                  // root: './../img/'
                }
              }],
            */
            use: extractCss.extract({
             fallback: 'style-loader',
             use: 'css-loader',
            })
          },
          // шаблонизатор
          {
            test: /\.hbs$/,
            loader: 'handlebars-loader',
            exclude: /node_modules/,
            options: {
              helperDirs: path.join(__dirname, 'bundle/helpers'),
              precompileOptions: {
                knownHelpersOnly: false,
              },
              // extensions: '.hbs',
              // knownHelpersOnly: false,
              // runtimePath: 'handlebars/runtime',
              // helperDirs: './bundle/helpers/',
            }
          }
        ]
      }
    }
    ]);

  // возвращаем настройки
  if (!testEnv) {
    return merge([
      common,
      devtool()
    ]);
  } else {
    return merge([
      common,
      uglifyJS()
    ]);
  }
};
