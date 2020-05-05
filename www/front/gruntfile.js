module.exports = function(grunt) {

    // configure grunt
    grunt.initConfig({
      pkg: grunt.file.readJSON('package.json'),

      babel: {
        jsx: {
          options: {
            presets: ['@babel/preset-react'],
          },
          expand: true,
          cwd: 'js/',
          src: ['**/*.js', '**/*.jsx'],
          dest: 'build/',
          ext: '.js'
        },
        prod: {
          options: {
            sourceMap: false,
            presets: [
              ['@babel/preset-env', {'targets': '> 0.25%, not dead'}]
            ]
          },
          src: '../static/build/main.js',
          dest: '../static/build/main.compat.js',
        }
      },

      browserify: {
        build: {
          options: {
            sourceType: 'module'
          },
          src: 'build/main.js',
          dest: '../static/build/main.js'
        }
      },

      uglify: {
        prod: {
          src: '../static/build/main.compat.js',
          dest: '../static/build/main.min.js'
        }
      },

      sass: {
        options: {
          loadPath: ['node_modules/bootstrap/scss/'],
        },
        build: {
          src: "scss/main.scss",
          dest: '../static/build/main.css'
        }
      },

      cssmin: {
        build: {
          src: "../static/build/main.css",
          dest: '../static/build/main.min.css'
        }
      },

      clean: {
        options: {force: true},
        build: ['../static/build/**', 'build/**']
      }
    });

    // Load plug-ins
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-babel');

    // define tasks
    grunt.registerTask('default', [
      'babel:jsx','browserify','sass','cssmin'
    ]);

    grunt.registerTask('prod', [
      'babel:jsx','browserify','babel:prod','uglify','sass','cssmin'
    ]);
  };