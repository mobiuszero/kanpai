module.exports = {
    optimize: {
        files: [{
            expand: true,
            cwd: 'project-src/_image',
            src: ['**/*.{png,jpg}'],
            dest: 'project-src/_image'
        }]
    }
};