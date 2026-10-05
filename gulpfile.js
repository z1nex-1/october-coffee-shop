const { src, dest, series, parallel, watch } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const cleanCss = require('gulp-clean-css');
const esbuild = require('esbuild');
const del = require('del');
const fs = require('fs');
const path = require('path');

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

function fonts() {
    return src([
        'node_modules/@fontsource-variable/manrope/files/manrope-{cyrillic,latin}-wght-normal.woff2',
        'node_modules/@fontsource-variable/playfair-display/files/playfair-display-{cyrillic,latin}-wght-{normal,italic}.woff2',
    ]).pipe(dest(`${dist}/fonts`));
}

const icons = ['truck', 'credit-card', 'flame', 'calendar-days', 'coffee', 'package', 'map-pin', 'leaf', 'arrow-right', 'shopping-bag'];

// один спрайт вместо иконочного шрифта: в шаблонах <use href="icons.svg#truck">, цвет берётся из currentColor
async function sprite() {
    const symbols = icons.map((name) => {
        const svg = fs.readFileSync(`node_modules/lucide-static/icons/${name}.svg`, 'utf8');
        const body = svg.slice(svg.indexOf('>', svg.indexOf('<svg')) + 1, svg.lastIndexOf('</svg>')).trim();
        return `<symbol id="${name}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">${body}</symbol>`;
    });
    fs.mkdirSync(dist, { recursive: true });
    fs.writeFileSync(path.join(dist, 'icons.svg'), `<svg xmlns="http://www.w3.org/2000/svg">${symbols.join('')}</svg>`);
}

function watchFiles() {
    watch(`${theme}/src/scss/**/*.scss`, styles);
    watch(`${theme}/src/jsx/**/*.jsx`, miniCart);
    watch(`${theme}/src/js/**/*.js`, scripts);
}

const build = series(clean, parallel(styles, miniCart, scripts, vendor, fonts, sprite));

exports.build = build;
exports.watch = series(build, watchFiles);
exports.default = build;
