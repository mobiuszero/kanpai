module.exports = {
    php_scripts_files: {
        expand: true,
        cwd: 'project-src/_php_scripts/',
        src: ['**/*', '!commands', '!commands/*', '!phpunit-6.2.4.phar', '!composer.json'],
        dest: 'project-build/assets/inc/'
    },
    images: {
        expand: true,
        cwd: 'project-src/_image/',
        src: '**/*',
        dest: 'project-build/assets/img/'
    }
};