/**
 * @package theme
 * @subpackage shoelace
 * @author Niels Seidel niels.seidel@fernuni-hagen.de
 * @license MIT
 */

module.exports = function (grunt) { // jshint ignore:line

    // Import modules.
    var path = require('path');
    var moodleroot = path.dirname(path.dirname(__dirname)); // jshint ignore:line
    
    grunt.initConfig({
        jshint: {
            options: { jshintrc: './.jshintrc' },
            files: ['./amd/src/**/*.js']
        },
        terser: {
            lib: {
                options: {
                    sourceMap: false,
                },
                files: [{
                    expand: true,
                    src: ['**/*.js', '!**/*.min.js'],
                    dest: './lib/build',
                    cwd: './lib/src',
                    rename: function (dst, src) {
                        return dst + '/' + src.replace('.js', '.min.js');
                    }
                }]
            },
            amd: {
                options: {
                    sourceMap: false,
                },
                files: [{
                    expand: true,
                    src: ['**/*.js', '!**/*.min.js'],
                    dest: './amd/build',
                    cwd: './amd/src',
                    rename: function (dst, src) {
                        return dst + '/' + src.replace('.js', '.min.js');
                    }
                }]
            }
        },
        cssmin: {
            minify: {
                files: [{
                    expand: true,
                    cwd: './css',
                    src: ['**/*.css', '!**/*.min.css'],
                    dest: './css/min',
                    ext: '.min.css'
                }]
            },
            options: {
                shorthandCompacting: false,
                roundingPrecision: -1
            },
            combine: {
                files: {
                    './styles.css': ['./css/min/*.css']
                }
            }
        }
    });

    // Load core tasks.
    //grunt.loadNpmTasks("grunt-ts");
    grunt.loadNpmTasks('grunt-terser');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-cssmin');

    grunt.registerTask("plugin-build", ["terser"]);
    grunt.registerTask("plugin-b", ["terser:amd"]);
    grunt.registerTask("plugin-check", ["jshint"]);
    grunt.registerTask("plugin-css", ["cssmin"]);
    grunt.registerTask("plugin-all", ["terser", "cssmin"]);

};
