<?php
namespace Marktjagd\LoadBalancerManager\LoadBalancer\Adapter;

use Symfony\Component\DomCrawler\Crawler;

class Apache24 extends AbstractLoadBalancer
{
    /**
     * @param string $loadBalancer
     *
     * @return array
     * @throws \Exception
     */
    public function getWebserverStatus($loadBalancer)
    {
        $balancerPart = $this->findBalancerPart();
        $workers = $this->getWorkerFromBalancerPart($balancerPart);

        return $workers;
    }

    /**
     * @param string $loadBalancer
     * @param string $host
     *
     * @return bool
     * @throws \Exception
     */
    public function activateWebserver($loadBalancer, $host)
    {
        $balancerPart = $this->findBalancerPart();
        $hostLink = $this->findLinkFromBalancerPart($balancerPart, $host);

        return $this->manageLoadBalancing($hostLink, self::ENABLE_WEBSERVER);
    }

    /**
     * @param string $loadBalancer
     * @param        $host
     *
     * @return bool
     * @throws \Exception
     */
    public function deactivateWebserver($loadBalancer, $host)
    {
        $balancerPart = $this->findBalancerPart();
        $hostLink = $this->findLinkFromBalancerPart($balancerPart, $host);

        return $this->manageLoadBalancing($hostLink, self::DISABLE_WEBSERVER);
    }

    protected function getH3Regex()
    {
        return '//h3[./a[contains(text(), "balancer://%s")]]';
    }

    private function manageLoadBalancing($hostLink, $enable)
    {
        $response = $this->getWorkerSettingsPart($hostLink);

        $crawler = new Crawler($response->getBody(true));

        $this->checkHeadline($crawler);

        $form = $crawler->filter('form');
        $action = $form->attr('action');
        $inputs = $form->filter('input');

        $checkedInputs = $form->filter('input[type="radio"]:checked');

        $uriParameter = array();
        /**
         * @var \DOMElement $check
         */
        foreach ($checkedInputs as $check) {
            $name = $check->getAttribute('name');
            $value = $check->getAttribute('value');
            $uriParameter[$name] = $value;
        }

        $excludeParameter = array_keys($uriParameter);

        /**
         * @var \DOMElement $value
         */
        foreach ($inputs as $check) {
            $name = $check->getAttribute('name');
            $value = $check->getAttribute('value');
            if (false === in_array($name, $excludeParameter)) {
                $uriParameter[$name] = $value;
            }
        }

        $uriParameter['w_status_D'] = $enable ? 0 : 1;

        $request = $this->getHttpClient()->post($action, null, $uriParameter);
        $response = $request->send();

        return ($response->getStatusCode() === 200);
    }
}
