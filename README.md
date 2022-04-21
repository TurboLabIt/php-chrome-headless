# php-chrome-headless
A simple wrapper to run [chrome-php/chrome](https://github.com/chrome-php/chrome) with caching and logging.


## 💿 1. Install Chrome

````bash
sudo apt install curl -y && curl -s https://raw.githubusercontent.com/TurboLabIt/webstackup/master/script/chrome/install.sh?$(date +%s) | sudo bash

````

## 📦 2. Install the package with composer

````bash
composer config repositories.TurboLabIt/php-chrome-headless git https://github.com/TurboLabIt/php-chrome-headless.git
composer require turbolabit/php-chrome-headless:dev-main

````


## 🕸 3. Scrape a page

````php
<?php
 
?>
````

See: [ChromeHeadlessTest](https://github.com/TurboLabIt/php-chrome-headless/blob/main/tests/ChromeHeadlessTest.php#L33)


## 🕸 4. HTML to PDF

````php
<?php
 
?>
````

See: [ChromeHeadlessTest](https://github.com/TurboLabIt/php-chrome-headless/blob/main/tests/ChromeHeadlessTest.php#L52)


## 🧪 Test it

````bash
git clone git@github.com:TurboLabIt/php-chrome-headless.git
cd php-chrome-headless
clear && bash script/test_runner.sh

````
