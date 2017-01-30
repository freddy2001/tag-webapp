<?php
sleep("5");
echo shell_exec("cd /var/www/test_html/webapp && /usr/bin/git pull 2>&1");
?>