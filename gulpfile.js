const { src, dest, series, parallel, watch } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const cleanCss = require('gulp-clean-css');
const esbuild = require('esbuild');
const del = require('del');

const theme = 'themes/coffee/assets';
const dist = `${theme}/dist`;
const production = process.env.NODE_ENV === 'production';

function clean() {
    return del([dist]);
}

function styles() {
    return src(`${theme}/src/scss/app.scss`)
        .pipe(sass().on('error', sass.logError))
        .pipe(cleanCss())
        .pipe(dest(dist));
}

// React нужен только мини-корзине, поэтому собираем его отдельным бандлом поверх jQuery-страниц
function miniCart() {
    return esbuild.build({
        entryPoints: [`${theme}/src/jsx/MiniCart.jsx`],
        outfile: `${dist}/mini-cart.js`,
        bundle: true,
        minify: true,
        target: 'es2018',
        jsx: 'automatic',
        define: { 'process.env.NODE_ENV': '"production"' },
        sourcemap: !production,
    });
}

function scripts() {
    return src(`${theme}/src/js/app.js`).pipe(dest(dist));
}

function vendor() {
    return src('node_modules/jquery/dist/jquery.min.js').pipe(dest(dist));
}

function watchFiles() {
    watch(`${theme}/src/scss/**/*.scss`, styles);
    watch(`${theme}/src/jsx/**/*.jsx`, miniCart);
    watch(`${theme}/src/js/**/*.js`, scripts);
}

const build = series(clean, parallel(styles, miniCart, scripts, vendor));

exports.build = build;
exports.watch = series(build, watchFiles);
exports.default = build;
