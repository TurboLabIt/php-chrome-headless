#!/usr/bin/env bash
SCRIPT_NAME=test_runner

if [ ! -d "vendor" ]; then
  composer install
fi

${PHP_CLI} ./vendor/bin/phpunit tests
PHPUNIT_RETVAL=$?

if [ ${PHPUNIT_RETVAL} -eq 0 ]; then

  echo -e "\e[1;42m 👍🏻 SUCCESS \e[0m"

else

  echo -e "\e[1;41m 👎🏻 FAILURE \e[0m"
fi

echo ""
