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
    protected array $arrConfig;
    protected Browser $browser;
    protected ?LoggerInterface $logger;
    protected ?AdapterInterface $cache;

    protected Page $page;
    protected int $statusCode       = -1;
    protected string $statusText    = '';
    protected ?string $lastPdfPath  = null;


    public function __construct(
        array $arrConfig,
        ?LoggerInterface $logger, ?AdapterInterface $cache,
        ?BrowserFactory $browserFactory = null
    )
    {
        $this->arrConfig    = $arrConfig;
        $this->logger       = $logger;
        $this->cache        = $cache;
        $browserFactory     = $browserFactory ?? (new BrowserFactory($arrConfig["chrome-exe"]));
        $this->browser      = $browserFactory->createBrowser($this->arrConfig["browser"]);
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

        // reset
        $this->statusCode   = -1;
        $this->statusText   = '';

        $this->page = $this->browser->createPage();

        // https://github.com/chrome-php/chrome/issues/41#issuecomment-447047235
        $this->page->getSession()->once("method:Network.responseReceived",
            function($params) {
                $this->statusCode   = $params["response"]["status"];
                $this->statusText   = $params["response"]["statusText"];
            }
        );

        $this->page->navigate($url)->waitForNavigation();

        if( $this->statusCode == "-1" && empty($this->statusText) ) {
            $this->statusText = "FAILURE! Unable to connect to ##" . $url . "## Network error or domain unresolvable!";
        }

        if( $this->isResponseError() ) {

            $this->log("browse", "Browsing KO: ##" . $this->statusText . "##", $url, $this->statusCode);
            throw new ChromeHeadlessException($this->statusText);

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
            $this->lastPdfPath = $fileName;
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

        $this->page->pdf($this->arrConfig["pdf"]["browser"])->saveToFile($fileName, $this->arrConfig["pdf"]["timeout"]);
        $this->lastPdfPath = $fileName;

        $this->pdfCompress($fileName);

        return $this;
    }


    public function getLastPdfPathOnDisk() : ?string
    {
        return $this->lastPdfPath;
    }


    public function pdfCompress(string $originalPdfPath) : self
    {
        if( empty($this->arrConfig["pdf"]["compression"]) ) {
            return $this;
        }

        $compressedPdfPath = $originalPdfPath . "_compressed.pdf";

        // https://www.adobe.com/acrobat/hub/how-to/how-to-compress-pdf-in-linux
        $cmd = 'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/' . $this->arrConfig["pdf"]["compression"] .
                    ' -dNOPAUSE -dQUIET -dBATCH -sOutputFile="' . $compressedPdfPath . '" "' . $originalPdfPath . '"';

        if( file_exists($compressedPdfPath) ) {
            unlink($compressedPdfPath);
        }

        $arrOutput  = [];
        $exitCode   = null;
        $exeResult  = exec($cmd, $arrOutput, $exitCode);

        // if ghostscript is not installed, we get an error code, but no output is captured (???)
        if( $exitCode == 127 && empty($arrOutput) ) {
            $arrOutput[] = "Command 'gs' not found, but can be installed with 'ghostscript'";
        }

        if( $exitCode != 0 ) {
            $txtOutput = implode(PHP_EOL, $arrOutput);
            throw new ChromeHeadlessException("PDF compression FAILED! " . $txtOutput);
        }

        $originalPdfPathFileSize    = filesize($originalPdfPath);
        $compressedPdfFileSize      = filesize($compressedPdfPath);

        if( $compressedPdfFileSize < $originalPdfPathFileSize ) {

            unlink($originalPdfPath);
            rename($compressedPdfPath, $originalPdfPath);

        } else {

            unlink($compressedPdfPath);
        }

        return $this;
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
        return $this->statusCode == -1 || $this->statusCode >= 400;
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
