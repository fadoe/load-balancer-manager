<?php
namespace Marktjagd\LoadBalancerManagerTest\LoadBalancer;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Marktjagd\LoadBalancerManager\LoadBalancer\LoadBalancerFactory;

/**
 * Class LoadBalancerFactoryTest
 * @package Marktjagd\LoadBalancerManagerTest\LoadBalancer
 * @covers Marktjagd\LoadBalancerManager\LoadBalancer\LoadBalancerFactory
 */
class LoadBalancerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoadBalancerFactory
     */
    private $loadBalancerFactory;

    /**
     * @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpClient;

    /**
     * @var array
     */
    private $config;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var Response|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    protected function setUp()
    {
        $this->httpClient = $this->getMock('Guzzle\Http\ClientInterface');

        $this->request = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $this->response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $this->httpClient->expects($this->any())
            ->method('get')
            ->willReturn($this->request);

        $this->request->expects($this->any())
            ->method('send')
            ->willReturn($this->response);

        $this->config = array(
            'loadbalancer1' => array(
                'host' => 'example.com',
                'protocols' => array('http'),
                'web1' => array(
                    'host' => 'example.com'
                )
            )
        );

        $this->loadBalancerFactory = new LoadBalancerFactory(
            $this->httpClient,
            $this->config
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Config for load balancer "unknown" not found
     */
    public function testLoadBalancerFactoryThrowsExceptionOnInvalidLoadBalancerConfig()
    {
        $loadBalancer = 'unknown';

        $this->loadBalancerFactory->getLoadBalancerAdapter($loadBalancer);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unknown Server version
     */
    public function testLoadBalancerFactoryThrowsExceptionOnUnknownLoadBalancerVersion()
    {
        $loadBalancer = 'loadbalancer1';

        $this->response->expects($this->any())
            ->method('getBody')
            ->with(true)
            ->willReturn(file_get_contents(__DIR__ . '/../assets/empty_page.html'));

        $this->loadBalancerFactory->getLoadBalancerAdapter($loadBalancer);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No load balancer adapter found
     */
    public function testLoadBalancerFactoryThrowsExceptionIfAdapterNotFound()
    {
        $loadBalancer = 'loadbalancer1';

        $this->response->expects($this->any())
            ->method('getBody')
            ->with(true)
            ->willReturn(file_get_contents(__DIR__ . '/../assets/apache10_startpage.html'));

        $this->loadBalancerFactory->getLoadBalancerAdapter($loadBalancer);
    }

    /**
     * @dataProvider loadBalancerStartPages
     */
    public function testLoadCorrectAdapter($loadBalancer, $balancerStartPage, $expectedAdapter)
    {
        $this->response->expects($this->any())
            ->method('getBody')
            ->with(true)
            ->willReturn($balancerStartPage);

        $adapter = $this->loadBalancerFactory->getLoadBalancerAdapter($loadBalancer);

        $this->assertInternalType('array', $adapter);
        $this->assertInstanceOf(
            $expectedAdapter,
            $adapter[0]
        );
    }

    public function loadBalancerStartPages()
    {
        $data = array(
            array(
                'loadbalancer1',
                file_get_contents(__DIR__ . '/../assets/apache22_startpage.html'),
                'Marktjagd\LoadBalancerManager\LoadBalancer\Adapter\Apache22',
            ),
            array(
                'loadbalancer1',
                file_get_contents(__DIR__ . '/../assets/apache24_startpage.html'),
                'Marktjagd\LoadBalancerManager\LoadBalancer\Adapter\Apache24',
            )
        );

        return $data;
    }
}
