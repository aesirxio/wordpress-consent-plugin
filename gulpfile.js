const zip = require('gulp-zip');
const gulp = require('gulp');
const rename = require('gulp-rename');
const composer = require('gulp-composer');
const webpack = require('webpack-stream');
const { watch, series } = require('gulp');
const _ = require('lodash');

require('dotenv').config();

var dist = './dist';
process.env.DIST = dist;

async function cleanTask() {
  const del = await import('del');
  return del.deleteAsync(`${dist}/plugins/aesirx-consent/**`, { force: true });
}

function movePluginFolderTask() {
  return gulp
    .src(['./wp-content/plugins/aesirx-consent/**'])
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent`));
}

function moveAnalyticJSTask() {
  return gulp
    .src(['./node_modules/aesirx-consent/dist/consent.js'])
    .pipe(rename('consent.js'))
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent/assets/vendor`));
}
function moveAnalyticVerifyJSTask() {
  return gulp
    .src(['./node_modules/aesirx-consent/dist/consent-verify.js'])
    .pipe(rename('consent-verify.js'))
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent/assets/vendor`));
}
function moveAnalyticSimpleJSTask() {
  return gulp
    .src(['./node_modules/aesirx-consent/dist/consent-simple.js'])
    .pipe(rename('consent-simple.js'))
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent/assets/vendor`));
}

function moveRepeatableFieldsJSTask() {
  return gulp
    .src(['./wp-content/plugins/aesirx-consent/aesirx-consent-repeatable-fields.js'])
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent/assets/vendor`));
}

function moveGeoJSTask() {
  return gulp
    .src(['./wp-content/plugins/aesirx-consent/aesirx-consent-geo.js'])
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent/assets/vendor`));
}

function moveVerifyJSTask() {
  return gulp
    .src(['./wp-content/plugins/aesirx-consent/aesirx-consent-verify.js'])
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent/assets/vendor`));
}

function moveAiJSTask() {
  return gulp
    .src(['./wp-content/plugins/aesirx-consent/aesirx-consent-ai.js'])
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent/assets/vendor`));
}

function moveMarkedJSTask() {
  return gulp
    .src(['./wp-content/plugins/aesirx-consent/aesirx-consent-marked.js'])
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent/assets/vendor`));
}

function moveCKeditorJSTask() {
  return gulp
    .src(['./wp-content/plugins/aesirx-consent/aesirx-consent-ckeditor.js'])
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent/assets/vendor`));
}

function moveSelect2JSTask() {
  return gulp
    .src(['./wp-content/plugins/aesirx-consent/aesirx-consent-select2.js'])
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent/assets/vendor`));
}

function webpackBIApp() {
  return gulp
    .src('./assets/bi/index.tsx')
    .pipe(webpack(require('./webpack.config.js')))
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent`));
}

function webpackBIAppWatch() {
  return gulp
    .src('./assets/bi/index.tsx')
    .pipe(webpack(_.merge(require('./webpack.config.js'), { watch: true })))
    .pipe(gulp.dest(`${dist}/plugins/aesirx-consent`));
}

function compressTask() {
  return gulp
    .src(`${dist}/plugins/**`)
    .pipe(zip('plg_aesirx_consent.zip'))
    .pipe(gulp.dest('./dist'));
}

function composerTask() {
  return composer({
    'working-dir': `${dist}/plugins/aesirx-consent`,
    'no-dev': true,
  });
}

async function cleanComposerTask() {
  const del = await import('del');
  return del.deleteAsync(`${dist}/plugins/aesirx-consent/composer.lock`, {
    force: true,
  });
}

exports.zip = series(
  cleanTask,
  movePluginFolderTask,
  moveAnalyticJSTask,
  moveAnalyticVerifyJSTask,
  moveAnalyticSimpleJSTask,
  moveRepeatableFieldsJSTask,
  moveGeoJSTask,
  moveVerifyJSTask,
  moveAiJSTask,
  moveMarkedJSTask,
  moveCKeditorJSTask,
  moveSelect2JSTask,
  webpackBIApp,
  composerTask,
  cleanComposerTask,
  compressTask,
  cleanTask
);

exports.watch = function () {
  dist = process.env.WWW;
  process.env.DIST = dist;
  watch('./assets/**', series(webpackBIAppWatch));
  watch(
    './wp-content/plugins/aesirx-consent/**',
    series(
      movePluginFolderTask,
      moveAnalyticJSTask,
      moveAnalyticVerifyJSTask,
      moveAnalyticSimpleJSTask,
      moveRepeatableFieldsJSTask,
      moveGeoJSTask,
      moveVerifyJSTask,
      moveAiJSTask,
      moveMarkedJSTask,
      moveCKeditorJSTask,
      moveSelect2JSTask,
      composerTask
    )
  );
};
