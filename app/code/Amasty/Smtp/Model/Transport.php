<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */

namespace Amasty\Smtp\Model;

use Amasty\Smtp\Model\Logger\DebugLogger;
use Amasty\Smtp\Model\Logger\MessageLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;
use Magento\Store\Model\ScopeInterface;

class Transport extends \Zend_Mail_Transport_Smtp implements TransportInterface
{
    /**
     * @var MessageInterface
     */
    protected $_message;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Amasty\Smtp\Model\Logger\MessageLogger
     */
    protected $messageLogger;

    /**
     * @var \Amasty\Smtp\Model\Logger\DebugLogger
     */
    protected $debugLogger;
    /**
     * @var \Amasty\Smtp\Helper\Data
     */
    protected $helper;

    /**
     * @var \Amasty\Smtp\Model\Config
     */
    protected $config;

    protected $_stopSend = false;

    protected $relationEntityType;

    protected $relationEntityId;

    protected $templateIdentifier;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;


    public function __construct(
        MessageInterface $message,
        ScopeConfigInterface $scopeConfig,
        MessageLogger $messageLogger,
        DebugLogger $debugLogger,
        \Amasty\Smtp\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Amasty\Smtp\Model\Config $config,
        $host = '127.0.0.1',
        array $parameters = []
    ) {
        parent::__construct($host, $parameters);

        $this->_message = $message;
        $this->scopeConfig = $scopeConfig;
        $this->messageLogger = $messageLogger;
        $this->debugLogger = $debugLogger;
        $this->helper = $helper;
        $this->objectManager = $objectManager;
        $this->config = $config;
    }

    /**
     * @param $stopSend
     */
    public function setStopSend($stopSend)
    {
        $this->_stopSend = $stopSend;
    }

    /**
     * @param $entityType
     */
    public function setRelationEntityType($entityType){
        $this->relationEntityType = $entityType;
    }

    /**
     * @param $templateIdentifier
     */
    public function setTemplateIdentifier($templateIdentifier)
    {
        $this->templateIdentifier = $templateIdentifier;
    }

    /**
     * @param $entityId
     */
    public  function setRelationEntityId($entityId){
        $this->relationEntityId = $entityId;
    }

    public function writeHeaderLog()
    {
        $header = [];

        if (isset($_SERVER['SERVER_NAME'])) {
            $header['SERVER_NAME'] = $_SERVER['SERVER_NAME'];
        }
        if (isset($_SERVER['SERVER_PORT'])) {
            $header['SERVER_PORT'] = $_SERVER['SERVER_PORT'];
        }
        if (isset($_SERVER['SERVER_ADDR'])) {
            $header['SERVER_ADDR'] = $_SERVER['SERVER_ADDR'];
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $header['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
        }
        return $header;
    }

    /**
     * Send a mail using this transport
     *
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        if (!$this->_stopSend) {
            $this->debugLogger->log(__('Ready to send e-mail at amsmtp/transport::sendMessage()'));
            $header['server'] = $this->writeHeaderLog();

            $config = $this->_config;
            if (isset($config) && isset($config['custom_sender'])) {
                $header['email']['from'] =  $config['custom_sender'];
            }
            $header['email']['raw_message'] = $this->_message->getRawMessage();
            $logData = [
                'relation_entity_type' => $this->relationEntityType,
                'relation_entity_id'     => $this->relationEntityId,
                'template_identifier'     => $this->templateIdentifier,
                'header' => serialize($header)
            ];
            try {
                $logId = $this->messageLogger->log($this->_message, $logData);
                $storeId = $this->helper->getCurrentStore();

                if (!$this->scopeConfig->isSetFlag(
                    'amsmtp/general/disable_delivery',
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                )
                ) {
                    $this->setFromData($storeId, ScopeInterface::SCOPE_STORE);

                    if ($this->_message instanceof \Zend_Mail) {
                        $this->modifyMessage();

                        parent::send($this->_message);
                    } else {
                        /** @var \Zend\Mail\Transport\Smtp $zendTransport */
                        $zendTransport = $this->objectManager->get(\Zend\Mail\Transport\Smtp::class);

                        $zendSmtpOptions = new \Zend\Mail\Transport\SmtpOptions([
                            'name' => $this->_name,
                            'host' => $this->_host,
                            'port' => $this->_port,
                            'connection_config' => $this->_config
                        ]);

                        if ($this->_auth) {
                            $zendSmtpOptions->setConnectionClass($this->_auth);
                        }

                        $zendTransport->setOptions($zendSmtpOptions);

                        $zendTransport->send(
                            $this->modifyMessage(\Zend\Mail\Message::fromString($this->_message->getRawMessage())->setEncoding('utf-8'))
                        );
                    }
                    $this->debugLogger->log(__('E-mail sent successfully at amsmtp/transport::sendMessage().'));
                } else {
                    $this->debugLogger->log(__('Actual delivery disabled under settings.'));
                }
                $this->messageLogger->updateStatus($logId, Log::STATUS_SENT);
            } catch (\Exception $e) {
                $this->debugLogger->log(__('Error sending e-mail: %1 . Email title: %2', $e->getMessage(), $this->_message->getSubject()));
                $this->messageLogger->updateStatus($logId, Log::STATUS_FAILED);
                throw new \Magento\Framework\Exception\MailException(new \Magento\Framework\Phrase($e->getMessage()),
                    $e);
            }
        }
    }

    public function runTest($testEmail, $storeId, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        if (fsockopen($this->_host, $this->_port, $errno, $errstr, 20)) {
            $this->debugLogger->log(__('Connection test successful: connected to %1', $this->_host . ':' . $this->_port));

            if ($testEmail) {
                $this->setFromData($storeId, $scope);
                $this->debugLogger->log(__('Preparing to send test e-mail to %1 from %2', $testEmail, $this->_config['custom_sender']['email']));

                $this->_message
                    ->addTo($testEmail)
                    ->setSubject(__('Amasty SMTP Email Test Message'))
                    ->setBodyText(__('If you see this e-mail, your configuration is OK.'));

                try {
                    $this->sendMessage();
                    $this->debugLogger->log(__('Test e-mail was sent successfully!'));
                } catch (\Exception $e) {
                    $this->debugLogger->log(__('Test e-mail failed: %1', $e->getMessage()));
                    throw $e;
                }
            }
        } else {
            $this->debugLogger->log(__(
                'Connection test failed: connection to %1 failed. Error: %2',
                $this->_host . ':' . $this->_port,
                $errstr . ' (' . $errno . ')'
            ));

            throw new \Exception(__('Connection failed'));
        }
    }

    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * @param null|\Zend\Mail\Message $message
     *
     * @return bool|\Zend\Mail\Message
     */
    private function modifyMessage($message = null)
    {
        if (isset($this->_config['custom_sender'])) {
            return $this->setFrom($this->_config['custom_sender']['email'], $this->_config['custom_sender']['name'], $message);
        }

        return false;
    }

    /**
     * Set email sender
     * Function for compatibility of Zend Framework 1 and 2
     *
     * @param string $email
     * @param string $name
     * @param \Zend\Mail\Message|null $message
     *
     * @return MessageInterface|\Zend\Mail\Message|null
     */
    private function setFrom($email, $name, $message = null)
    {
        $name = '=?utf-8?Q?' . str_replace(\Zend\Mime\Mime::$qpKeys, \Zend\Mime\Mime::$qpReplaceValues, $name) . '?=';
        if (class_exists(\Zend\Mail\Message::class, false) && $message instanceof \Zend\Mail\Message) {
            $message->setFrom($email, $name);

            return $message;
        } else {
            $this->_message->clearFrom();
            $this->_message->setFrom($email, $name);
        }

        return $this->_message;
    }

    /**
     * @param string $storeId
     * @param string $scope
     *
     * @throws LocalizedException
     */
    private function setFromData($storeId, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        if (!isset($this->_config['custom_sender'])) {
            $from = $this->config->getGeneralEmail($storeId, $scope);

            if (empty($from['email']) || empty($from['name'])) {
                throw new LocalizedException(__('\'Sender Email\' or \'Sender Name\' is empty. Please ensure that all values in the \'General Contact\' section are correctly set by visiting Stores > Configuration > General > Store Email Addresses.'));
            }

            $this->_config['custom_sender']['email'] = $from['email'];
            $this->_config['custom_sender']['name'] = $from['name'];
        }
    }
}
