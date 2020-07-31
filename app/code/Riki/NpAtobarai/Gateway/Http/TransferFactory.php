<?php
namespace Riki\NpAtobarai\Gateway\Http;

use function Composer\Autoload\includeFile;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\ConfigInterface;
use \Magento\Framework\Exception\LocalizedException;

/**
 * Class TransferFactory
 * @package Riki\NpAtobarai\Gateway\Http
 */
class TransferFactory implements TransferFactoryInterface
{

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var string
     */
    private $requestMethod;

    /**
     * @var string
     */
    private $urlEndpoint;

    /**
     * TransferFactory constructor.
     * @param ConfigInterface $config
     * @param TransferBuilder $transferBuilder
     * @param string $requestMethod
     * @param string $urlEndPoint
     */
    public function __construct(
        ConfigInterface $config,
        TransferBuilder $transferBuilder,
        $requestMethod,
        $urlEndPoint
    ) {
        $this->config = $config;
        $this->transferBuilder = $transferBuilder;
        $this->requestMethod = $requestMethod;
        $this->urlEndpoint = $urlEndPoint;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(array $request)
    {
        if (!$this->requestMethod) {
            throw new LocalizedException(__('Request method could not be found'));
        }

        if (!$this->urlEndpoint) {
            throw new LocalizedException(__('Url Endpoint could not be found'));
        }
        $url = $this->getDomain().'/'.$this->urlEndpoint;
        return $this->transferBuilder
            ->setMethod($this->requestMethod)
            ->setHeaders($this->getHeaders())
            ->setBody(json_encode($request, JSON_UNESCAPED_UNICODE))
            ->setUri($url)
            ->build();
    }

    /**
     * @return mixed
     */
    private function getMerchantId()
    {
        return $this->config->getValue('merchant_id');
    }

    /**
     * @return mixed
     */
    private function getPassword()
    {
        return $this->config->getValue('password');
    }

    /**
     * @return string
     */
    private function getDomain()
    {
        return $this->config->getValue('api_url');
    }

    /**
     * @return string
     */
    private function getSpCode()
    {
        return $this->config->getValue('sp_code');
    }

    /**
     * @return string
     */
    private function getSystemId()
    {
        return $this->config->getValue('system_id');
    }

    /**
     * @return array
     */
    private function getHeaders()
    {
        $authorization = 'Basic ' . base64_encode($this->getMerchantId() . ':' . $this->getSpCode());

        return [
            'Content-Type' => 'application/json',
            'Authorization' => $authorization,
            'X-NP-Terminal-Id' => $this->getSystemId()
        ];
    }
}
