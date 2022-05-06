# php-chrome-headless
A simple wrapper to run [chrome-php/chrome](https://github.com/chrome-php/chrome) with caching and logging.


## 💿 1. Install Chrome

````bash
sudo apt install curl -y && curl -s https://raw.githubusercontent.com/TurboLabIt/webstackup/master/script/chrome/install.sh?$(date +%s) | sudo bash

````

See: [webstackup/script/chrome/](https://github.com/TurboLabIt/webstackup/tree/master/script/chrome)


## 📦 2. Install the package with composer

````bash
composer config repositories.TurboLabIt/php-chrome-headless git https://github.com/TurboLabIt/php-chrome-headless.git
composer require turbolabit/php-chrome-headless:dev-main

````


## 3. ⚙️ Symfony custom configuration (optional)

````yaml
# config/packages/chromeheadless.yaml
turbo_lab_it_chrome_headless:
  $arrConfig:
    pdf:
      browser:
        marginTop: 1.5
        marginBottom: 1.5
````

See: [services.yaml](https://github.com/TurboLabIt/php-chrome-headless/blob/main/src/Resources/config/services.yaml)


## 🕸 4. Scrape a page

````php
<?php
 
?>
````

See: [ChromeHeadlessTest](https://github.com/TurboLabIt/php-chrome-headless/blob/main/tests/ChromeHeadlessTest.php#L33)


## 🕸 5. HTML to PDF

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
