module.exports = {
    production: {
        options: {
            dest: 'project-production-build/change-log-<%= grunt.template.today("yyyymmdd") %>.txt',
            template: '{{date}}\n\n{{> features}}{{> fixes}}'
        }
    }
};