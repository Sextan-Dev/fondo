const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const webpack = require('webpack');
const { VueLoaderPlugin } = require('vue-loader');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const MinimizerPlugin = require('minimizer-webpack-plugin');

const listFiles = dir => fs.readdirSync(dir, { withFileTypes: true }).flatMap(entry => {
    const file = path.join(dir, entry.name);
    return entry.isDirectory() ? listFiles(file) : [file];
});

// Copies static files into public/. Replaces copy-webpack-plugin, whose Node 18-compatible
// releases depend on a vulnerable serialize-javascript.
class CopyAssetsPlugin {
    constructor(patterns) {
        this.patterns = patterns;
    }

    apply(compiler) {
        compiler.hooks.thisCompilation.tap('CopyAssetsPlugin', compilation => {
            compilation.hooks.processAssets.tap({ name: 'CopyAssetsPlugin', stage: webpack.Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL }, () => {
                for (const { from, to, noErrorOnMissing } of this.patterns) {
                    const source = path.resolve(__dirname, from);
                    if (!fs.existsSync(source)) {
                        if (!noErrorOnMissing) compilation.errors.push(new webpack.WebpackError(`CopyAssetsPlugin: ${from} not found`));
                        continue;
                    }
                    const isDirectory = fs.statSync(source).isDirectory();
                    if (isDirectory) compilation.contextDependencies.add(source);
                    for (const file of isDirectory ? listFiles(source) : [source]) {
                        compilation.fileDependencies.add(file);
                        const name = isDirectory ? path.posix.join(to, path.relative(source, file).split(path.sep).join('/')) : to;
                        compilation.emitAsset(name, new webpack.sources.RawSource(fs.readFileSync(file)), { copied: true, sourceFilename: path.relative(__dirname, file) });
                    }
                }
            });
        });
    }
}

class MixManifestPlugin {
    apply(compiler) {
        compiler.hooks.thisCompilation.tap('MixManifestPlugin', compilation => {
            compilation.hooks.processAssets.tap({ name: 'MixManifestPlugin', stage: webpack.Compilation.PROCESS_ASSETS_STAGE_REPORT }, () => {
                const manifest = {};
                for (const name of ['js/app.js', 'css/app.css']) {
                    const asset = compilation.getAsset(name);
                    if (!asset) throw new Error(`Missing required asset: ${name}`);
                    const hash = crypto.createHash('sha256').update(asset.source.buffer()).digest('hex').slice(0, 20);
                    manifest[`/${name}`] = `/${name}?id=${hash}`;
                }
                compilation.emitAsset('mix-manifest.json', new webpack.sources.RawSource(JSON.stringify(manifest, null, 2)));
            });
        });
    }
}

module.exports = {
    entry: { app: ['./resources/vue/app.js', './resources/assets/sass/app.scss'] },
    output: {
        path: path.resolve(__dirname, 'public'),
        filename: 'js/[name].js',
        publicPath: '/',
        // Never clean public/: it also holds users' attachments.
        clean: false
    },
    resolve: {
        extensions: ['.js', '.vue', '.json'],
        alias: { 'vue$': 'vue/dist/vue.runtime.esm.js' }
    },
    module: { rules: [
        { test: /\.vue$/, loader: 'vue-loader' },
        { test: /\.css$/, use: [{ loader: MiniCssExtractPlugin.loader, options: { defaultExport: true } }, 'css-loader'] },
        { test: /\.s[ac]ss$/, use: [{ loader: MiniCssExtractPlugin.loader, options: { defaultExport: true } }, 'css-loader', 'resolve-url-loader', {
            loader: 'sass-loader', options: { sourceMap: true, sassOptions: { quietDeps: true, silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'legacy-js-api', 'slash-div'] } }
        }] },
        { test: /\.(png|jpe?g|gif|svg|woff2?|ttf|eot|otf)(\?.*)?$/, type: 'asset/resource', generator: { filename: 'assets/[name].[contenthash:12][ext]' } }
    ] },
    plugins: [
        new VueLoaderPlugin(),
        new MiniCssExtractPlugin({ filename: 'css/[name].css' }),
        new webpack.ProvidePlugin({ $: 'jquery', jQuery: 'jquery', 'window.jQuery': 'jquery' }),
        // moment otherwise bundles its ~140 locales; the app only offers es_ES and fr_FR.
        new webpack.ContextReplacementPlugin(/moment[/\\]locale$/, /^\.\/(es|fr)(\.js)?$/),
        new CopyAssetsPlugin([
            ...['files', 'images', 'sounds', 'nu', 'icon_fonts_assets'].map(name => ({ from: `resources/assets/${name}`, to: name, noErrorOnMissing: true })),
            ...['apple-touch-icon-144.png', 'apple-touch-icon.png', 'manifest.json', 'favicon.png'].map(name => ({ from: `resources/assets/${name}`, to: name }))
        ]),
        new MixManifestPlugin()
    ],
    // esbuild instead of webpack's default terser: terser needs a 512 MB heap for app.js and
    // crashes on small servers; esbuild also minifies the CSS terser never touched.
    // The silenced checks only flag third-party code: vue-gettext's plural rules repeat `case 'zh'`, and
    // element-ui's theme brings an @charset that browsers ignore once it is no longer the first rule.
    optimization: { minimizer: [
        new MinimizerPlugin({ minify: MinimizerPlugin.esbuildMinify, minimizerOptions: { logOverride: { 'duplicate-case': 'silent' } } }),
        new MinimizerPlugin({ test: /\.css$/i, minify: MinimizerPlugin.esbuildMinifyCss, minimizerOptions: { logOverride: { 'invalid-@charset': 'silent' } } })
    ] },
    // vue-loader 15 re-exports each <style> block with `export *`, which never carries the default
    // export its component imports. That import is only read for CSS modules, which the app doesn't use.
    ignoreWarnings: [{ module: /\.vue$/, message: /export 'default' \(imported as 'style\d+'\) was not found/ }],
    devtool: false,
    performance: { hints: false },
    stats: 'errors-warnings'
};
