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
    protected ?LoggerInterface $logger;
    protected ?AdapterInterface $cache;
    protected int $cacheTtl;

    protected Browser $browser;

    protected Page $page;
    protected int $statusCode = -1;
    protected string $statusText = '';


    public function __construct(?BrowserFactory $browserFactory = null, string $cmdName = 'google-chrome', ?LoggerInterface $logger = null, ?AdapterInterface $cache = null, ?int $cacheTtl = null)
    {
        $browserFactory     = $browserFactory ?? (new BrowserFactory($cmdName));
        $this->browser      = $browserFactory->createBrowser([
            'windowSize'                => [1920, 1000],
            'ignoreCertificateErrors'   => true,
            'userAgent'                 => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.34 Safari/537.36'
        ]);
        $this->logger       = $logger;
        $this->cache        = $cache;
        $this->cacheTtl     = $cacheTtl === null ? 60 * 60 : $cacheTtl;
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
        $this->cache->get($cacheKey, function (ItemInterface $item) use($url) {

            $this->log("browseAndCache", "Page wasn't cached, running a live request now", $url);
            $this->browse($url);
            $this->log("browseAndCache", "Back to cache management", $url);
            $item->expiresAfter($this->cacheTtl);
        });

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



    public function browseToPdf(string $url, string $pdfPath) : self
    {
        if( file_exists($pdfPath) && time() - filemtime($pdfPath) < $this->cacheTtl ) {
            return $this;
        }

        if( empty($this->cache) ) {

            $this->browse($url);

        } else {

            $this->browseAndCache($url);
        }

        if( $this->isResponseError() ) {
            return $this;
        }

        $this->page->pdf(['printBackground' => false])->saveToFile($pdfPath);

        return $this;
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

        $this->logger->log($message);
        return $this;
    }
}
