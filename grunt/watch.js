module.exports = {
    development: {
        files: ['project-src/_scss/**/*.scss', 'project-src/_js/**/*.js', 'project-src/_php_scripts/**/*', 'project-src/_image/**/*', 'project-src/_build_files/**/*.hbs'],
        tasks: ['sass:development', 'concat:development', 'copy:php_scripts_files', 'copy:images', 'assemble']
    },
    production: {
        files: ['project-src/_scss/**/*.scss', 'project-src/_js/**/*.js', 'project-src/_php_scripts/**/*', 'project-src/_image/**/*', 'project-src/_build_files/**/*.hbs'],
        tasks: ['sass:production', 'concat:production', 'copy:php_scripts_files', 'copy:images', 'assemble']
    }
};