<?php
/**
 * @see https://github.com/TurboLabIt/php-chrome-headless/
 */
namespace TurboLabIt\ChromeHeadless;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;


class ChromeHeadless
{
    protected array $arrConfig = [
        "browser"   => [
            'windowSize'                => [1920, 1080],
            'ignoreCertificateErrors'   => true,
            'userAgent'                 => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.0.0 Safari/537.36',
            'sendSyncDefaultTimeout'    => 15000 
        ],
        "cache"     => [
            "ttl"   => 60 * 60
        ],
        "pdf"       => [
            "outDirFullPath"    => "/tmp/",
            "autoext"           => true,
            "lastPdfPath"       => '',
            "browser"           => [
                "printBackground"   => true,
                "marginTop"         => 0,
                "marginBottom"      => 0,
                "marginLeft"        => 0,
                "marginRight"       => 0
            ]
        ]
    ];
    protected Browser $browser;
    protected ?LoggerInterface $logger;
    protected ?AdapterInterface $cache;

    protected Page $page;
    protected int $statusCode = -1;
    protected string $statusText = '';


    public function __construct(
        array $arrConfig = [],
        ?BrowserFactory $browserFactory = null, string $cmdName = 'google-chrome',
        ?LoggerInterface $logger = null, ?AdapterInterface $cache = null)
    {
        $this->arrConfig    = array_replace_recursive($this->arrConfig, $arrConfig);
        $browserFactory     = $browserFactory ?? (new BrowserFactory($cmdName));
        $this->browser      = $browserFactory->createBrowser($this->arrConfig["browser"]);
        $this->logger       = $logger;
        $this->cache        = $cache;
    }


    public function browseAndCache(string $url) : self
    {
        $this->log("browseAndCache", "Start", $url);

        if( empty($this->cache) ) {

            $message = "Failure. Uninitialized cache!";
            $this->log("browseAndCache", $message, $url);
            throw new ChromeHeadlessException($message);
        }

        $cacheKey = "TurboLabIt_ChromeHeadless_Url_" . md5($url);
        $chrome = $this->cache->get($cacheKey, function (ItemInterface $item) use($url) {

            $this->log("browseAndCache", "Page wasn't cached, running a live request now", $url);
            $this->browse($url);
            $this->log("browseAndCache", "Back to cache management", $url);
            $item->expiresAfter($this->arrConfig["cache"]["ttl"]);
            return $this;
        });

        $this->browser      = $chrome->getBrowser();
        $this->page         = $chrome->getPage();
        $this->statusCode   = $chrome->getStatusCode();
        $this->statusText   = $chrome->getStatusText();

        return $this;
    }


    public function browse(string $url) : self
    {
        $this->log("browse", "Ready to browse with Chrome Headless", $url);
        $this->page = $this->browser->createPage();

        // https://github.com/chrome-php/chrome/issues/41#issuecomment-447047235
        $this->page->getSession()->once("method:Network.responseReceived",
            function($params) {
                $this->statusCode   = $params["response"]["status"];
                $this->statusText   = $params["response"]["statusText"];
            }
        );

        $this->page->navigate($url)->waitForNavigation();

        if( $this->isResponseError() ) {

            $this->log("browse", "Browsing KO: ##" . $this->statusText . "##", $url, $this->statusCode);

        } else {

            $this->log("browse", "Browsing OK", $url, $this->statusCode);
        }

        return $this;
    }


    public function browseToPdf(string $url, string $fileName) : self
    {
        if( $this->arrConfig["pdf"]["autoext"] && substr($fileName, -4) != '.pdf') {
            $fileName .= ".pdf";
        }

        if( $fileName[0] != DIRECTORY_SEPARATOR ) {
            $fileName = $this->arrConfig["pdf"]["outDirFullPath"] . $fileName;
        }

        if( file_exists($fileName) && time() - filemtime($fileName) < $this->arrConfig["cache"]["ttl"] ) {
            $this->arrConfig["pdf"]["lastPdfPath"] = $fileName;
            return $this;
        }
        
        $this->browse($url);
        
        if( $this->isResponseError() ) {
            return $this;
        }

        $baseDir = dirname($fileName);
        if( !is_dir($baseDir) ) {
            mkdir($baseDir, 0777, true);
        }

        $this->page->pdf($this->arrConfig["pdf"]["browser"])->saveToFile($fileName);

        $this->arrConfig["pdf"]["lastPdfPath"] = $fileName;

        return $this;
    }


    public function getLastPdfPathOnDisk() : ?string
    {
        $path = $this->arrConfig["pdf"]["lastPdfPath"];
        if( empty($path) ) {
            return null;
        }

        return $path;
    }


    public function getBrowser() : Browser
    {
        return $this->browser;
    }


    public function getPage(): Page
    {
        return $this->page;
    }


    public function selectNode(string $selector)
    {
        return $this->page->dom()->querySelector($selector);
    }


    public function selectNodes(string $selector)
    {
        return $this->page->dom()->querySelectorAll($selector);
    }


    public function getStatusCode() : int
    {
        return $this->statusCode;
    }


    public function getStatusText() : string
    {
        return $this->statusText;
    }


    public function isResponseError() : bool
    {
        return $this->statusCode >= 400;
    }


    public function log(string $origin, string $message, ?string $url = null, ?int $statusCode = null) : self
    {
        if( empty($this->logger) ) {
            return $this;
        }

        $message = $origin . ": " . $message;

        if( !empty($url) ) {
            $message .= ' -- ' . $url;
        }

        if( !empty($statusCode) ) {
            $message .= ' [' . $statusCode . ']';
        }

        $this->logger->info($message);
        return $this;
    }
}
