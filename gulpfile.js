/* Configuration of entry points and paths for all tasks */
const config = {
  wpScripts: {
    editor: {
      src: "src/js/editor.js",
      dest: "assets/editor/"
    },
    frontend: {
      src: "src/js/frontend.js",
      dest: "assets/frontend/"
    }
  },
  styles: {
    editor: {
      src: "src/scss/editor.scss",
      dest: "assets/editor",
      file: "main.css",
      watchSrc: "src/scss/**/*.scss"
    },
    frontend: {
      src: "src/scss/frontend.scss",
      dest: "assets/frontend",
      file: "main.css",
      watchSrc: "src/scss/**/*.scss"
    }
  }
};

const gulp = require('gulp');
const plumber = require("gulp-plumber");
const concat = require("gulp-concat");
const sourcemaps = require("gulp-sourcemaps");
const sassGlob = require("gulp-sass-glob");
const sass = require("gulp-sass");
const postcss = require("gulp-postcss");
const cssnano = require("cssnano");
const autoprefixer = require("autoprefixer");
const gulpRun = require("gulp-run-command");

const sassTildeImporter = (url, prev, done) => {
  return url[0] === "~" ? { file: url.substr(1) } : null;
};

const registerStyleTasks = config => {
  // we generate normal styles tasks.
  const tasks = Object.keys(config).map(entryKey => {
    const currentConfig = config[entryKey];

    return gulp.task(`style:${entryKey}`, () => {
      return gulp
        .src(currentConfig.src)
        .pipe(sourcemaps.init())
        .pipe(
          plumber({
            errorHandler: function(err) {
              console.log(err);
              this.emit("end");
            }
          })
        )
        .pipe(sassGlob())
        .pipe(
          sass({
            includePaths: ["node_modules"],
            importer: sassTildeImporter
          })
        )
        .pipe(concat(currentConfig.file))
        .pipe(postcss([autoprefixer, cssnano]))
        .pipe(sourcemaps.write("."))
        .pipe(gulp.dest(currentConfig.dest));
    });
  });

  // now we generate watcher tasks.
  const watchTasks = Object.keys(config).map(entryKey => {
    gulp.task(`style:watch:${entryKey}`, () =>
      gulp.watch(config[entryKey].watchSrc, gulp.series(`style:${entryKey}`))
    );
  });

  return [...tasks, watchTasks];
};

registerStyleTasks(config.styles);

const registerWpScriptsTasks = config => {
  return Object.keys(config).map(entryKey => {
    const currentConfig = config[entryKey];
    return [
      gulp.task(
        `js:${entryKey}`,
        gulpRun.default(
          `npx wp-scripts build ${currentConfig.src} --output-path=./${currentConfig.dest} `
        )
      ),
      gulp.task(
        `js:watch:${entryKey}`,
        gulpRun.default(
          `npx wp-scripts start ${currentConfig.src} --output-path=./${currentConfig.dest} `
        )
      )
    ];
  });
};
registerWpScriptsTasks(config.wpScripts);

gulp.task(
  "watch",
  gulp.series(
    // gulp.parallel( Object.keys(config.styles).map(key=>`style:${key}`) ),
    // gulp.parallel(Object.keys(config.styles).map(key => `style:watch:${key}`)),
    gulp.parallel([
        ...Object.keys(config.styles).map(key => `style:watch:${key}`),
        ...Object.keys(config.wpScripts).map(key => `js:watch:${key}`)
    ])
  )
);

gulp.task(
  "default",
  gulp.parallel([
    ...Object.keys(config.styles).map(key=>`style:${key}`),
    ...Object.keys(config.wpScripts).map(key=>`js:${key}`)
  ])
);
