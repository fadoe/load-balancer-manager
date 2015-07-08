<?php
namespace Marktjagd\LoadBalancerManagerTest\LoadBalancer;

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Marktjagd\LoadBalancerManager\LoadBalancer\Adapter\Apache22;

class Apache22AdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Apache22
     */
    private $apacheAdapter;

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
            'host' => 'http://example.com',
            'part' => 'backend',
            'web1' => array(
                'host' => 'http://example.com',
            )
        );

        $this->apacheAdapter = new Apache22(
            $this->httpClient,
            $this->config
        );
    }

    public function invalidPartDataProvider()
    {
        $data = array(
            array(
                file_get_contents(__DIR__ . '/../assets/apache22_startpage.html'),
            ),
        );

        return $data;
    }
}
