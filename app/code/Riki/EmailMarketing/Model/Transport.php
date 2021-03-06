<?php
namespace Riki\EmailMarketing\Model;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;
use Magento\Store\Model\ScopeInterface;

    /**
     * Class that responsible for filling some message data before transporting it.
     * This class checks whether this email should be sent in the first place, based on System Configurations.
     * @see Zend_Mail_Transport_Sendmail is used for transport
     */
class Transport implements TransportInterface
{
    /**
     * Config path to mail sending setting that shows if email communications are disabled
     */
    const XML_PATH_SYSTEM_SMTP_DISABLE = 'system/smtp/disable';

    /**
     * Configuration path to source of Return-Path and whether it should be set at all
     * @see \Magento\Config\Model\Config\Source\Yesnocustom to possible values
     */
    const XML_PATH_SENDING_SET_RETURN_PATH = 'system/smtp/set_return_path';

    /**
     * Configuration path for custom Return-Path email
     */
    const XML_PATH_SENDING_RETURN_PATH_EMAIL = 'system/smtp/return_path_email';

    /**
     * Object for sending eMails
     *
     * @var \Zend_Mail_Transport_Sendmail
     */
    private $transport;

    /**
     * Email message object that should be instance of \Zend_Mail
     *
     * @var MessageInterface
     */
    private $message;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var bool
     */
    protected $_stopSend = false;

    protected $relationEntityType;

    protected $relationEntityId;

    protected $templateIdentifier;

    /**
     * @param \Riki\EmailMarketing\Framework\Zend\Mail\Transport\Sendmail $transport
     * @param MessageInterface $message Email message object
     * @param ScopeConfigInterface $scopeConfig Core store config
     * @param string|array|\Zend_Config|null $parameters Config options for sendmail parameters
     *
     * @throws \InvalidArgumentException when $message is not an instance of \Zend_Mail
     */
    public function __construct(
        \Riki\EmailMarketing\Framework\Zend\Mail\Transport\Sendmail $transport,
        MessageInterface $message,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->transport = $transport;
        $this->message = $message;
        $this->scopeConfig = $scopeConfig;
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

    /**
     * Sets Return-Path to email if necessary, and sends email if it is allowed by System Configurations
     *
     * @return void
     * @throws MailException
     */
    public function sendMessage()
    {
        try {
            if (!$this->scopeConfig->isSetFlag(self::XML_PATH_SYSTEM_SMTP_DISABLE, ScopeInterface::SCOPE_STORE)) {
                /* configuration of whether return path should be set or no. Possible values are:
                 * 0 - no
                 * 1 - yes (set value as FROM address)
                 * 2 - use custom value
                 * @see Magento\Config\Model\Config\Source\Yesnocustom
                 */
                $isSetReturnPath = $this->scopeConfig->getValue(
                    self::XML_PATH_SENDING_SET_RETURN_PATH,
                    ScopeInterface::SCOPE_STORE
                );
                $returnPathValue = $this->scopeConfig->getValue(
                    self::XML_PATH_SENDING_RETURN_PATH_EMAIL,
                    ScopeInterface::SCOPE_STORE
                );

                if ($isSetReturnPath == '1') {
                    $this->message->setReturnPath($this->message->getFrom());
                    $this->transport->parameters = sprintf('-f%s', $this->message->getFrom());
                } elseif ($isSetReturnPath == '2' && $returnPathValue !== null) {
                    $this->message->setReturnPath($returnPathValue);
                    $this->transport->parameters = sprintf('-f%s', $returnPathValue);
                }
                //prevent no any receivers

                if(strlen(implode($this->message->getRecipients()))){
                    if ($this->_message instanceof \Zend_Mail) {
                        $this->transport->send($this->message);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new MailException(__($e->getMessage()), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }
    /**
     * @param $stopSend
     */
    public function setStopSend($stopSend)
    {
        $this->_stopSend = $stopSend;
    }

    /**
     * @return bool
     */
    public function getStopSend()
    {
        return $this->_stopSend;
    }
}
