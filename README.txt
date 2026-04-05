Upload these files to your website root or a secure folder under htdocs:
- config.php
- firebase_auth.php
- send-command.php
- firebase-service-account.json

Then update config.php:
- project_id => your Firebase project id
- app_secret => the same secret as BuildConfig.COMMAND_WEBHOOK_SECRET in the Android app

Recommended final URL:
https://apknox.online/send-command.php
