<?php
namespace TurboLabIt\ChromeHeadless\tests;

use PHPUnit\Framework\TestCase;
use TurboLabIt\ChromeHeadless\ChromeHeadless;
use TurboLabIt\Encryptor\Encryptor;


class ChromeHeadlessTest extends TestCase
{
    const TEST_URL = 'https://turbolabit.github.io/html-pages/fetchable.html';


    public function testCreateInstance()
    {
        $chrome = new ChromeHeadless();
        $this->assertInstanceOf('TurboLabIt\ChromeHeadless\ChromeHeadless', $chrome);
        return $chrome;
    }


    public function testBrowse()
    {
        $chrome = $this->testCreateInstance()->browse(static::TEST_URL);
        $this->assertInstanceOf('TurboLabIt\ChromeHeadless\ChromeHeadless', $chrome);
        $this->assertNotTrue($chrome->isResponseError());
        return $chrome;
    }


    public function testSelector()
    {
        $chrome = $this->testBrowse();
        $text = $chrome->selectNode('h2')->getText();
        $this->assertEquals('IT WORKS', $text);
        $this->assertNotTrue($chrome->isResponseError());
    }


    public function test404()
    {
        $url = substr(static::TEST_URL, 0, -10);
        $chrome = $this->testCreateInstance()->browse($url);
        $this->assertInstanceOf('TurboLabIt\ChromeHeadless\ChromeHeadless', $chrome);
        $this->assertTrue($chrome->isResponseError());
        return $chrome;
    }
}
