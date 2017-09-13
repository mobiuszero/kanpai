module.exports = {
    options: {
        limit: 5
    },
    copy_files: [
        'copy:php_scripts_files',
        'copy:images'
    ],
    optimize_images: [
        'tinyimg:optimize'
    ]
};