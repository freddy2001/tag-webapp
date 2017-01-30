<?php
sleep("30");
echo shell_exec("cd /var/www/test_html/webapp && /usr/bin/git pull 2>&1");
?>