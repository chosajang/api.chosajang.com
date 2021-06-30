#!/bin/sh
#!/bin/bash

echo 'CodeDeploy AfterInstall Hook';
sudo composer update
service httpd restart
mkdir test
mkdir /svc/web/api-laravel/scripts/test222