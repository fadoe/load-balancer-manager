<?php
namespace Marktjagd\LoadBalancerManager\LoadBalancer\Adapter;

use Symfony\Component\DomCrawler\Crawler;

class Apache22 extends AbstractLoadBalancer
{
    public function getWebserverStatus($loadBalancer)
    {
        $balancerPart = $this->findBalancerPart();
        $workers = $this->getWorkerFromBalancerPart($balancerPart);

        return $workers;
    }

    public function activateWebserver($loadBalancer, $host)
    {
        $balancerPart = $this->findBalancerPart();
        $hostLink = $this->findLinkFromBalancerPart($balancerPart, $host);

        return $this->manageLoadBalancing($hostLink, self::ENABLE_WEBSERVER);
    }

    public function deactivateWebserver($loadBalancer, $host)
    {
        $balancerPart = $this->findBalancerPart();
        $hostLink = $this->findLinkFromBalancerPart($balancerPart, $host);

        return $this->manageLoadBalancing($hostLink, self::DISABLE_WEBSERVER);
    }

    protected function getH3Regex()
    {
        return '//h3[contains(text(), "balancer://%s")]';
    }

    /**
     * Removes server from lb.
     *
     * @param $hostLink
     * @param $enable
     *
     * @return boolean
     */
    private function manageLoadBalancing($hostLink, $enable)
    {
        $response = $this->getWorkerSettingsPart($hostLink);

        $crawler = new Crawler($response->getBody(true));

        $this->checkHeadline($crawler);

        $form = $crawler->filter('form');
        $action = $form->attr('action');
        $inputs = $form->filter('input');

        $uriParams = array();
        /* @var \DOMElement $input */
        foreach ($inputs as $input) {
            $name = $input->getAttribute('name');

            if ($name && $name !== 'dw') {
                $uriParams[$name] = $input->getAttribute('value');
            }
        }

        $uriParams['dw'] = $enable ? 'Enable' : 'Disable';
        $request = $this->getHttpClient()->get($action . '?' . http_build_query($uriParams));
        $response = $request->send();

        return ($response->getStatusCode() === 200);
    }
}
