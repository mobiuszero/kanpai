module.exports = {
    options: {
        layoutdir: 'project-src/_build_files/layout/',
        layout: "*.hbs",
        flatten: true,
        partials: 'project-src/_build_files/components/**/*.hbs',
        ext: '.html'
    },
    pages: {
        options: {
            layout: 'main.hbs'
        },
        files: {
            'project-build/': ['project-src/_build_files/pages/**/*.hbs']
        }
    }
};