# Yii with HTML5 Boilerplate,Responsive Bootstrap,YiiBooster and BootSwatch.
 © 2012  [Spiros Kabasakalis](http://www.reverbnation.com/spiroskabasakalis)
 Licensed under the Apache License v2.0  
 http://www.apache.org/licenses/LICENSE-2.0  

A starter project template for Yii  built with HTML5 Boilerplate,Responsive Bootstrap,YiiBooster and BootSwatch.
Tested with version 1.1.12

##[Live Demo](http://yiiapp.kabasakalis.tk/)

## Set it up
- Clone the git repo - `git clone git://github.com/drumaddict/Yii_Html5.git` - or [download it](https://github.com/drumaddict/Yii_Html5/zipball/master)
- Hook up your Yii framework path in index.php.
- Fill in database info in config/main.php and config/console.php
- Fill in your recaptcha private and public keys,if you want to use [recaptcha](http://www.google.com/recaptcha).
- Fill in myEmail and gmail_password  parameters in config/main.php in order to use Gmail SMPT server
  for email.To set up your localhost (in my case XAMPP stack) to send emails with Gmail SMPT Server,(for testing purposes),
  see this [article](http://expertester.wordpress.com/2010/07/07/how-to-send-email-from-xampp-php/).
- Run the migration in migrations folder,or use the sql dump in data folder to create the user table and a couple of test users.
  (Manually set the status active (1) and remove the activation_key entry for test users.)

## Features (so far)

### Authentication,Registration,Password Reset.

- Usage of [Password Strategy Extension](http://www.yiiframework.com/extension/yii-password-strategies/).
  Note that this extension has some enviroment requirements,for example bcrypt will not work in old PHP versions.
- Password Reset with email verification.
- Registration with email  activation.
- Delete inactive Users (users that registered but did not activate their account) within a configured period of time,
   default is one day.This is done with a console command,which can be ran as cron job.See commands folder.
- [ReCaptcha](http://www.google.com/recaptcha) for antispam,(optionally).
- [Input Extension](http://www.yiiframework.com/extension/input/),for data sanitation.

### [YiiBooster](http://yii-booster.clevertech.biz/) with [Bootswatch](http://bootswatch.com/)

YiiBooster is configured to load assets straight from a webroot folder,to avoid publishing assets.
Also,based on configuration parameter,YiiBooster will load a bootswatch theme,or just the default Bootstrap design.



## RESOURCES

- [HTML5 Boilerplate](http://html5boilerplate.com/)
- [Bootstrap](http://twitter.github.com/bootstrap/)
- [Yii Bootstrap Extension](http://www.yiiframework.com/extension/bootstrap/)
- [YiiBooster](http://yii-booster.clevertech.biz/)
- [Bootswatch](http://bootswatch.com/)
- [mailer](http://www.yiiframework.com/extension/mailer/)
- [Yii Boilerplate](https://github.com/clevertech/YiiBoilerplate)
- [LESS]( http://lesscss.org/)
- [WinLess](http://winless.org/)

