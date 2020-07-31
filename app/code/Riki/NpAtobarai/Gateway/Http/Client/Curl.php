<?php
namespace Riki\NpAtobarai\Gateway\Http\Client;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Adapter;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ConverterInterface;

/**
 * Class Curl
 */
class Curl implements ClientInterface
{
    /**
     * HTTP protocol versions
     */
    const HTTP_1 = '1.1';
    const HTTP_0 = '1.0';

    /**
     * HTTP request methods
     */
    const GET     = 'GET';
    const POST    = 'POST';
    const PUT     = 'PUT';
    const HEAD    = 'HEAD';
    const DELETE  = 'DELETE';
    const TRACE   = 'TRACE';
    const OPTIONS = 'OPTIONS';
    const CONNECT = 'CONNECT';
    const MERGE   = 'MERGE';
    const PATCH   = 'PATCH';

    /**
     * Encrypt fields
     */
    const ENCRYPT_FIELDS = [
        'zip_code',
        'address',
        'tel',
        'email'
    ];

    /**
     * Request timeout
     */
    const REQUEST_TIMEOUT = 30;

    /**
     * @var ConverterInterface
     */
    private $converter;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Adapter\Curl
     */
    private $curl;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param ConverterInterface $converter
     * @param ResponseFactory $responseFactory
     * @param Adapter\Curl $curl
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Logger $logger,
        ConverterInterface $converter,
        ResponseFactory $responseFactory,
        Adapter\Curl $curl,
        EncryptorInterface $encryptor
    ) {
        $this->logger = $logger;
        $this->converter = $converter;
        $this->responseFactory = $responseFactory;
        $this->curl = $curl;
        $this->encryptor = $encryptor;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $log = [
            'request' => $transferObject->getBody(),
            'request_uri' => $transferObject->getUri()
        ];
        $response = [];

        try {
            $headers = [];
            $options = [CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT];
            $method = $transferObject->getMethod();
            $body = $transferObject->getBody();

            foreach ($transferObject->getHeaders() as $name => $value) {
                $headers[] = sprintf('%s: %s', $name, $value);
            }

            if ($method == self::PATCH) {
                $headers[] = 'X-HTTP-Method-Override: ' . self::PATCH;
                $options[CURLOPT_POSTFIELDS] = $body;
            }

            $this->curl->setOptions($options);
            $this->curl->write($method, $transferObject->getUri(), self::HTTP_1, $headers, $body);

            $response = $this->converter->convert($this->read());
        } catch (\Exception $e) {
            $log['error'] = $e->getMessage();
            throw new ClientException(__($e->getMessage()));
        } finally {
            $log['response'] = json_encode($response,JSON_UNESCAPED_UNICODE);
            $request = json_decode($log['request'], true);
            $log['request'] = json_encode($this->encryptDataLog($request), JSON_UNESCAPED_UNICODE);
            $this->logger->debug($log);
        }

        return (array) $response;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        return $this->responseFactory->create($this->curl->read())->getBody();
    }

    /**
     * @param array $data
     * @return array
     */
    protected function encryptDataLog(array $data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::ENCRYPT_FIELDS) && is_string($value)) {
                $data[$key] = $this->encryptor->encrypt($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->encryptDataLog($value);
            }
        }
        return $data;
    }
}
