#!/bin/sh
#!/bin/bash

echo 'CodeDeploy AfterInstall Hook';
sudo composer update
service httpd restart