<?php
namespace Marktjagd\LoadBalancerManager\LoadBalancer\Adapter;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractLoadBalancer
{
    const ENABLE_WEBSERVER = true;
    const DISABLE_WEBSERVER = false;

    private $httpClient;
    private $config;

    /**
     * @param ClientInterface $httpClient
     * @param array  $config
     */
    public function __construct(ClientInterface $httpClient, array $config)
    {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getLoadBalancerUrl()
    {
        return $this->httpClient->getBaseUrl();
    }

    /**
     * @return ClientInterface
     */
    protected function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getLoadBalancerStatusPart()
    {
        return $this->config['part'];
    }

    /**
     * Get array of registered workers on load balancer.
     *
     * @throws \Exception
     *
     * @return array
     */
    abstract public function getWebserverStatus();

    /**
     * Activate webserver on load balancer.
     *
     * @throws \Exception
     *
     * @param string $webserverName
     */
    abstract public function activateWebserver($webserverName);

    /**
     * Deactivate webserver on load balancer.
     *
     * @throws \Exception
     *
     * @param string $webserverName
     */
    abstract public function deactivateWebserver($webserverName);

    /**
     * Get regular expression to find worker part on load balancer manager page.
     *
     * @return string
     */
    abstract protected function getH3Regex();

    /**
     * @return Crawler
     * @throws \Exception
     */
    protected function findBalancerPart()
    {
        $request = $this->getHttpClient()->get('/balancer');
        $response = $request->send();
        $part = $this->getLoadBalancerStatusPart();

        $xPath = sprintf($this->getH3Regex(), $part);

        $crawler = new Crawler($response->getBody(true));

        // Grab the right H3 DOM element
        $headline3 = $crawler->filterXPath($xPath);

        if (0 == iterator_count($headline3)) {
            throw new \Exception(
                sprintf(
                    'Can\'t find part "%s" on load balancer',
                    $part
                )
            );
        }

        // Gather all adjacent TABLE DOM elements
        $tables = $headline3->nextAll()->filter('table');

        if (iterator_count($tables) < 2) {
            throw new \Exception(
                sprintf(
                    'Unknown page layout for load-balancer URL "%s"',
                    $request->getUrl()
                )
            );
        }

        return $tables;
    }

    /**
     * @param Crawler $balancerPart
     *
     * @return array
     */
    protected function getWorkerFromBalancerPart(Crawler $balancerPart)
    {
        $webserverStatus = $balancerPart->eq(1);
        $rows = $webserverStatus->filter('tr');

        $workers = array();
        /**
         * @var \DOMElement $row
         */
        foreach ($rows as $key => $row) {
            if ($key > 0) {
                $worker = array(
                    'worker' => $row->childNodes->item(0)->nodeValue,
                    'status' => $row->childNodes->item(5)->nodeValue,
                );
                $workers[] = $worker;
            }
        }

        return $workers;
    }

    /**
     * Find link from hostname in load balancer part.
     *
     * @param Crawler $balancerPart
     * @param string $hostName
     *
     * @throws \Exception
     *
     * @return \DOMNode
     */
    protected function findLinkFromBalancerPart(Crawler $balancerPart, $hostName)
    {
        $ip = $this->getIpForHostname($hostName);
        $link = $this->getLinkFromIp($ip, $balancerPart);

        return $link;
    }

    /**
     * @param string $hostName
     *
     * @throws \Exception
     *
     * @return string
     */
    private function getIpForHostname($hostName)
    {
        if (false === isset($this->config['hosts'][$hostName])) {
            throw new \Exception(sprintf('Worker "%s" not found in configuration', $hostName));
        }

        return $this->config['hosts'][$hostName]['host'];
    }

    /**
     * @param string  $ip
     * @param Crawler $tables
     *
     * @throws \Exception
     *
     * @return string
     */
    private function getLinkFromIp($ip, Crawler $tables)
    {
        $links = $tables->eq(1)->filter('a');

        if (0 === iterator_count($links)) {
            throw new \Exception('Links not found in load balancer part.');
        }
        /**
         * @var \DOMElement $link
         */
        foreach ($links as $link) {
            if (strstr($link->nodeValue, $ip)) {
                return $link->attributes->getNamedItem('href')->nodeValue;
            }
        }

        throw new \Exception('Link not found');
    }

    /**
     * @param string $hostLink
     *
     * @return Response
     */
    protected function getWorkerSettingsPart($hostLink)
    {
        $request = $this->getHttpClient()->get(
            $this->getHttpClient()->getBaseUrl() . $hostLink
        );

        $request->getQuery()->useUrlEncoding(false);
        $response = $request->send();

        return $response;
    }

    /**
     * @param Crawler $crawler
     *
     * @throws \Exception
     */
    protected function checkHeadline(Crawler $crawler)
    {
        $result = $crawler->filterXPath('//h3');

        foreach ($result as $node) {
            $headline = $node->nodeValue;
            if (false !== strstr($headline, 'Edit worker settings')) {
                return;
            }
        }

        throw new \Exception('Edit worker section not found.');
    }
}
