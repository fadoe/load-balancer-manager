<?php
namespace Marktjagd\LoadBalancerManager\LoadBalancer;

use Guzzle\Http\ClientInterface;
use Symfony\Component\DomCrawler\Crawler;

class LoadBalancerFactory
{
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
     * @param string $loadBalancer
     *
     * @throws \Exception
     *
     * @return Adapter\AbstractLoadBalancer
     */
    public function getLoadBalancerAdapter($loadBalancer)
    {
        if (false === isset($this->config[$loadBalancer])) {
            throw new \Exception(sprintf('Config for load balancer "%s" not found', $loadBalancer));
        }

        $config = $this->config[$loadBalancer];

        $requestOptions = array(
            'verify' => false,
        );
        if (isset($config['auth'])) {
            $requestOptions['auth'] = array($config['auth']['username'], $config['auth']['password'], 'Basic');
        }

        $this->httpClient->setBaseUrl($config['host']);
        $this->httpClient->setConfig(
            array(
                'redirect.disable' => true,
                'request.options' => $requestOptions,
            )
        );

        $apacheVersion = $this->getApacheVersion();
        $adapterClass = $this->convertApacheVersionToFactoryMethod($apacheVersion);

        if (false === class_exists($adapterClass)) {
            throw new \Exception('No load balancer adapter found');
        }

        return new $adapterClass($this->httpClient, $config);
    }

    /**
     * @return string
     */
    private function getApacheVersion()
    {
        $request = $this->httpClient->get('/balancer');

        $response = $request->send();

        $crawler = new Crawler($response->getBody(true));

        $serverVersion = $crawler->filterXPath('//dt[contains(text(), "Server Version")]');

        if (0 === count($serverVersion)) {
            throw new \RuntimeException('Unknown Server version');
        }

        $version = $this->extractApacheVersion($serverVersion->getNode(0)->nodeValue);

        return $version;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function extractApacheVersion($string)
    {
        $regex = '/Apache\/(?P<version>[1-9][0-9]*\.[0-9][^\s]*)/i';
        preg_match($regex, $string, $matches);

        return $matches['version'];
    }

    /**
     * @param string $version
     *
     * @return string
     */
    private function convertApacheVersionToFactoryMethod($version)
    {
        $regex = '/(\d+)\.(\d+)\.(\d+)/';
        $replace = 'Marktjagd\LoadBalancerManager\LoadBalancer\Adapter\Apache${1}${2}';
        $adapterName = preg_replace($regex, $replace, $version);

        return $adapterName;
    }
}
