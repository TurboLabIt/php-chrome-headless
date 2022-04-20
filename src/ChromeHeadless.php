<?php
/**
 * @see https://github.com/TurboLabIt/php-chrome-headless/
 */
namespace TurboLabIt\ChromeHeadless;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;


class ChromeHeadless
{
    protected string $chromeCmd;
    protected ?LoggerInterface $logger;
    protected ?AdapterInterface $cache;
    protected int $cacheTtl;

    protected string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.83 Safari/537.36';

    protected string $retVal    = '';
    protected string $html      = '';
    protected int $statusCode   = -1;


    public function __construct(?string $chromeCmd = null, ?LoggerInterface $logger = null, ?AdapterInterface $cache = null, ?int $cacheTtl = null)
    {
        $this->chromeCmd    = $chromeCmd === null ?  '/usr/bin/google-chrome' : $chromeCmd;
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

        $cacheKey = md5($url);
        $arrResponse =
            $this->cache->get($cacheKey, function (ItemInterface $item) use($url) {

                $this->log("browseAndCache", "Page wasn't cached, running a live request now", $url);
                $this->browse($url);
                $code = $this->getStatusCode();
                $this->log("browseAndCache", "Request done", $url, $code);

                $item->expiresAfter($this->cacheTtl);
                return [
                    "retVal"        => $this->getRetVal(),
                    "html"          => $this->getHtml(),
                    "statusCode"    => $this->getStatusCode()
                ];
        });

        $this->retVal       = $arrResponse["retVal"];
        $this->html         = $arrResponse["html"];
        $this->statusCode   = $arrResponse["statusCode"];

        return $this;
    }


    public function browse(string $url) : self
    {
        $this->log("browse", "Start", $url);

        $chromeCmd  = $this->chromeCmd . " --headless --user-agent='" . $this->userAgent . "' --dump-dom '" . $url . "'";
        $this->log("browse", "Ready to browse with Chrome Headless. Command is: ##" . $chromeCmd . "##", $url);

        $arrOutput = [];
        $this->retVal = exec($chromeCmd, $arrOutput);
        $this->html   = implode(PHP_EOL, $arrOutput);

        if( empty($this->html) ) {

            $this->log("browse", "Fetch KO (empty HTML)", $url);

        } else {

            $this->log("browse", "Fetch OK (got some HTML)", $url);
        }

        return $this;
    }


    public function getRetVal(): string
    {
        return $this->retVal;
    }


    public function getHtml(): string
    {
        return $this->html;
    }


    public function getStatusCode(): int
    {
        return $this->statusCode;
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
