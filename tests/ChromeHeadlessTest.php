<?php
namespace TurboLabIt\ChromeHeadless\tests;

use PHPUnit\Framework\TestCase;
use TurboLabIt\ChromeHeadless\ChromeHeadless;
use TurboLabIt\Encryptor\Encryptor;


class ChromeHeadlessTest extends TestCase
{
    const TEST_URL = 'https://raw.githubusercontent.com/TurboLabIt/php-chrome-headless/main/tests/fetchable.html';


    protected function testCreateInstance()
    {
        $chrome = new ChromeHeadless();
        $this->assertNotEmpty($chrome);

        return $chrome;
    }


    public function testUrl()
    {
        $chrome =
            $this->testCreateInstance()
                ->browse(static::TEST_URL);

        $html = $chrome->getHtml();
        $this->assertStringContainsString('<h2>IT WORKS</h2>', $html);
    }
}
