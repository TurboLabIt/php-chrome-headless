# php-chrome-headless
A simple wrapper to run [chrome-php/chrome](https://github.com/chrome-php/chrome) with caching and logging.


## 💿 1. Install Chrome

````bash
sudo apt install curl -y && curl -s https://raw.githubusercontent.com/TurboLabIt/webstackup/master/script/chrome/install.sh?$(date +%s) | sudo bash

````

See: [webstackup/script/chrome/](https://github.com/TurboLabIt/webstackup/tree/master/script/chrome)


## 💿 2. (optional) Install PDF Support

If you want to use Chrome to generate PDFs, you also need this:

````bash
sudo apt install curl -y && curl -s https://raw.githubusercontent.com/TurboLabIt/webstackup/master/script/print/install-pdf.sh?$(date +%s) | sudo bash

````

See: [webstackup/script/print/](https://github.com/TurboLabIt/webstackup/blob/master/script/print)


## 📦 3. Install the package with composer

````bash
composer config repositories.turbolabit/php-chrome-headless git https://github.com/TurboLabIt/php-chrome-headless.git
composer require turbolabit/php-chrome-headless:dev-main

````


## 4. ⚙️ Symfony custom configuration (optional)

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
