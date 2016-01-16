php btsapp.php

serve www (fe: cd www && php -S localhost:1337)

cron php btsapp.php
Like:
crontab -e
*/15 * * * * cd /path/to/site && /usr/local/bin/php btsapp.php

to let the engine generate the html file on every run
touch .DEV