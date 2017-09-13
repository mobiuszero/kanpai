module.exports = {
    production: {
        options: {
            sourceMap: false,
            stripBanners: true,
            banner: '/*! mobiuszero - ' +
            '<%= grunt.template.today("yyyy-mm-dd") %> */',
            process: function (src, filepath) {
                return '// Source: ' + filepath + '\n' +
                    src.replace(/(^|\n)[ \t]*('use strict'|"use strict");?\s*/g, '$1');
            }
        },
        src: ['node_modules/jquery/dist/jquery.js', 'node_modules/jquery-validation/dist/jquery.validate.min.js', 'node_modules/jquery-validation/dist/additional-methods.js', 'node_modules/jquery-countdown/dist/jquery.countdown.min.js', 'project-src/_js/vendor/**/*.js', 'project-src/_js/custom/**/*.js'],
        dest: 'project-build/assets/js/scripts.min.js'
    },
    development: {
        options: {
            sourceMap: false,
            stripBanners: false,
            banner: '/*! mobiuszero - ' +
            '<%= grunt.template.today("yyyy-mm-dd") %> */',
            process: function (src, filepath) {
                return '// Source: ' + filepath + '\n' +
                    src.replace(/(^|\n)[ \t]*('use strict'|"use strict");?\s*/g, '$1');
            }
        },
        src: ['node_modules/jquery/dist/jquery.js', 'node_modules/jquery-validation/dist/jquery.validate.min.js', 'node_modules/jquery-validation/dist/additional-methods.js', 'node_modules/jquery-countdown/dist/jquery.countdown.min.js', 'project-src/_js/vendor/**/*.js', 'project-src/_js/custom/**/*.js'],
        dest: 'project-build/assets/js/scripts.min.js'
    }
};