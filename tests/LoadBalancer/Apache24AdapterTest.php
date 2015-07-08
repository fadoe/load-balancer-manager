<?php
namespace Marktjagd\LoadBalancerManagerTest\LoadBalancer;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Marktjagd\LoadBalancerManager\LoadBalancer\Adapter\Apache24;

class Apache24AdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Apache24
     */
    protected $apacheAdapter;

    /**
     * @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpClient;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var Response|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

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
            'host' => 'http://example.com',
            'part' => 'backend',
            'web1' => array(
                'host' => 'http://example.com',
            )
        );

        $this->apacheAdapter = new Apache24(
            $this->httpClient,
            $this->config
        );
    }

    public function testInit()
    {
        $this->assertInstanceOf(
            'Marktjagd\LoadBalancerManager\LoadBalancer\Adapter\AbstractLoadBalancer',
            $this->apacheAdapter
        );
    }

    /**
     * @param $balancerStartPage
     *
     * @dataProvider invalidPartDataProvider
     */
    public function testThrowsExceptionIfLoadBalancerPartNotFound($balancerStartPage)
    {
        $config = $this->config;
        $config['part'] = 'unknown';

        $expectedMessage = sprintf('Can\'t find part "%s" on load balancer', $config['part']);
        $this->setExpectedException('\Exception', $expectedMessage);

        $this->apacheAdapter = new Apache24(
            $this->httpClient,
            $config
        );

        $this->response->expects($this->any())
            ->method('getBody')
            ->with(true)
            ->willReturn($balancerStartPage);

        $this->apacheAdapter->activateWebserver('web1');
    }

    public function testThrowsExceptionIfWorkerNotFoundInConfiguration()
    {
        $worker = 'web1';
        $config = $this->config;
        $balancerStartPage = file_get_contents(__DIR__ . '/../assets/apache24_startpage.html');

        unset($config[$worker]);

        $expectedMessage = sprintf('Worker "%s" not found in configuration', $worker);
        $this->setExpectedException('\Exception', $expectedMessage);

        $this->apacheAdapter = new Apache24(
            $this->httpClient,
            $config
        );

        $this->response->expects($this->any())
            ->method('getBody')
            ->with(true)
            ->willReturn($balancerStartPage);

        $this->apacheAdapter->activateWebserver($worker);
    }

    public function testThrowExceptionIfWorkerNotFoundOnApacheLoadBalancerPage()
    {
        $worker = 'web1';
        $config = $this->config;
        $balancerStartPage = file_get_contents(__DIR__ . '/../assets/apache24_startpage.html');

        $expectedMessage = sprintf('Worker "%s" not found in configuration', $worker);
        $this->setExpectedException('\Exception', $expectedMessage);

        $this->apacheAdapter = new Apache24(
            $this->httpClient,
            $config
        );

        $this->response->expects($this->any())
            ->method('getBody')
            ->with(true)
            ->willReturn($balancerStartPage);

        $this->apacheAdapter->activateWebserver($worker);
    }

    public function invalidPartDataProvider()
    {
        $data = array(
            array(
                file_get_contents(__DIR__ . '/../assets/apache24_startpage.html'),
            ),
        );

        return $data;
    }
}
