module.exports = {
    development: {
        files: {
            'project-build/assets/css/main.css': 'project-src/_scss/main.scss'
        },
        options: {
            outputStyle: 'expanded',
            sourceComments: true
        }
    },
    production: {
        options: {
            outputStyle: 'compressed',
            sourcemap: 'none',
            sourceComments: false
        },
        files: {
            'project-build/assets/css/main.css': 'project-src/_scss/main.scss'
        }
    }

};