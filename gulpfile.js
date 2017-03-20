var gulp = require('gulp');
var sass = require('gulp-sass');

gulp.task('default', function() {
    console.log('processing assets...');
    gulp.src('app/Resources/assets/sass/**/*.scss')
        .pipe(sass())
        .pipe(gulp.dest('web/css'));
});