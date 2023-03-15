### XDebug in VS Code:

•	From VS code extensions window, install the PHP extension `PHP Debug`. 

•	In the `launch.json` file in root directory, add the following:

            “configurations”:[
            {
            "name": "Listen for XDebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "log": true,
            "pathMappings": {
            "/app/": "${workspaceFolder}/"
            }
            },
            {...}

•	Create a new file named `.lando.local.yml` inside `/sites/trialcourt/` and add the below content:
            services:
              appserver:
                xdebug: true

•	Check the following in `settings.local.php`
        $config['devel.settings']['error_handlers'] = 4;
        $config['devel.settings']['devel_dumper'] = 'var_dumper';
        $config['system.logging']['error_level'] = 'verbose';

•   Place breakpoints on lines of code. XDebug adds script name/ line numbers on left section.