/*http://mattbailey.io/a-beginners-guide-to-grunt-redux.html*/
// Gruntfile.js Config file
module.exports = function (grunt) {
    // To show the time period each task takes to complete
    require('time-grunt')(grunt);
    // Loads the tasks configs and loads them
    require('load-grunt-config')(grunt, {
        jitGrunt: true
    });
};