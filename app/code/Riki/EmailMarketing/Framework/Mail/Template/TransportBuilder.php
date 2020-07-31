<?php
/**
 * Email Marketing Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Framework\Mail\Template;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface; // @codingStandardsIgnoreLine
use Riki\EmailMarketing\Helper\Data;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Email\Model\TemplateFactory;
use Riki\EmailMarketing\Model\QueueFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\Context;
/**
 * Class TransportBuilder
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{

    /**
     * Template Identifier
     *
     * @var string
     */
    protected $templateIdentifier;

    /**
     * Template Model
     *
     * @var string
     */
    protected $templateModel;

    /**
     * Template Variables
     *
     * @var array
     */
    protected $templateVars;

    /**
     * Template Options
     *
     * @var array
     */
    protected $templateOptions;

    /**
     * Mail Transport
     *
     * @var \Magento\Framework\Mail\TransportInterface
     */
    protected $transport;

    /**
     * Template Factory
     *
     * @var FactoryInterface
     */
    protected $templateFactory;

    /**
     * Object Manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Message
     *
     * @var \Magento\Framework\Mail\Message
     */
    protected $message;

    /**
     * Sender resolver
     *
     * @var \Magento\Framework\Mail\Template\SenderResolverInterface
     */
    protected $_senderResolver;

    /**
     * @var \Magento\Framework\Mail\TransportInterfaceFactory
     */
    protected $mailTransportFactory;
    /**
     * @var Data
     */
    protected $emailHelper;
    /**
     * @var TemplateFactory
     */
    protected $emailTemplateInterface;
    /**
     * @var QueueFactory
     */
    protected $emailQueueFactory;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var
     */
    protected $innerSenderName;
    /**
     * @var
     */
    protected $innerSenderEmail;
    /**
     * @var
     */
    protected $innerSendto;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $storeConfig;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * TransportBuilder constructor.
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param Data $emailHelper
     * @param TemplateFactory $emailTemplateInteface
     * @param QueueFactory $queueFactory
     */
    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager, // @codingStandardsIgnoreLine
        TransportInterfaceFactory $mailTransportFactory,
        Data $emailHelper,
        TemplateFactory $emailTemplateInteface,
        QueueFactory $queueFactory,
        \Magento\Framework\Registry $coreRegistry,
        Context $context
    ) {
        parent::__construct
        (
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory
        );
        $this->emailHelper = $emailHelper;
        $this->emailTemplateInterface = $emailTemplateInteface;
        $this->emailQueueFactory  = $queueFactory;
        $this->logger = $context->getLogger();
        $this->storeConfig = $context->getScopeConfig();
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * Set template vars
     *
     * @param array $templateVars
     * @return $this
     */
    public function setTemplateVars($templateVars)
    {
        $canSend = false;
        $templateEmail = $this->emailTemplateInterface->create()->load($this->templateIdentifier);
        $templateVars['email_footer'] = $this->emailHelper->getEmailFooter();
        $canSendMidle =  ($templateEmail->getData('send_midnight') && !$this->emailHelper->isMidnightHour());
        $canSendEnable = $templateEmail->getData('enable_sent');
        if($canSendMidle && $canSendEnable){
            $canSend = true;
        }
        $templateVars['can_send'] = $canSend;
        $this->templateVars = $templateVars;
        return $this;
    }

    /**
     * Get mail transport
     *
     * @return \Magento\Framework\Mail\TransportInterface
     */
    public function getTransport()
    {
        $this->prepareMessage();
        $mailTransport = $this->mailTransportFactory->create(['message' => clone $this->message]);
        $mailTransport->setTemplateIdentifier($this->templateIdentifier);

        /*default value for case that email template is default template*/
        $canSendMidnight = true;
        $canSendEnable = true;

        /*load email template data*/
        $templateEmail = $this->emailTemplateInterface->create()->load($this->templateIdentifier);

        if (!empty($templateEmail)) {
            if ($templateEmail->hasData('send_midnight')) {
                $canSendMidnight = $templateEmail->getData('send_midnight');
            }
            if ($templateEmail->hasData('enable_sent')) {
                $canSendEnable = $templateEmail->getData('enable_sent');
            }
        }

        if(is_array($this->templateVars))
        {
            if(array_key_exists('is_sent', $this->templateVars))
            {
                $isSent = $this->templateVars['is_sent'];
            }
            else
            {
                $isSent = false;
            }
        }
        else
        {
            $isSent = false;

        }

        /**
         * Send mail confirm for cancel order
         */
        $sendMailCancel =  $this->_coreRegistry->registry('send_mail_confirm_before_email_cancel');
        if($canSendEnable && $sendMailCancel !=null)
        {
            $this->reset();
            return $mailTransport;
        }

        /**
         * Check mail enable for templates
         *
         */
        if(!$canSendEnable){
            $mailTransport->setStopSend(true);
            $this->reset();
            return $mailTransport;
        }
        /*
         * Check mail send on middle night
         *
         */
        if($this->templateIdentifier && $this->emailHelper->isMidnightHour() && !$canSendMidnight && !$isSent)
        {
            $mailTransport->setStopSend(true);
            $sendTo = is_array($this->innerSendto) ? $this->innerSendto[0] : $this->innerSendto;
            //store to database
            $variables = \Zend_Json::encode($this->templateVars);
            $emailQueue = $this->emailQueueFactory->create();
            $senderName = $this->innerSenderName ?
                $this->innerSenderName :$this->storeConfig->getValue('trans_email/ident_general/name');
            $senderEmail = $this->innerSenderEmail ?
                $this->innerSenderEmail :$this->storeConfig->getValue('trans_email/ident_general/email');
            try
            {
                $emailQueue->setData('from_name', $senderName);
                $emailQueue->setData('from_email', $senderEmail);
                $emailQueue->setData('template_id', $this->templateIdentifier);
                $emailQueue->setData('send_to', $sendTo);
                $emailQueue->setData('is_sent', 0);
                $emailQueue->setData('variables',$variables);
                $emailQueue->save();

            }
            catch(\Exception $e)
            {
                $this->logger->info($e->getMessage());
            }
            $mailTransport->setStopSend(true);
        }
        $this->reset();
        return $mailTransport;

    }
    /**
     * Set mail from address
     *
     * @param string|array $from
     * @return $this
     */
    public function setFrom($from)
    {

        if(is_array($from) && array_key_exists('email',$from))
        {
            $this->innerSenderEmail = $from['email'];
        }
        if(is_array($from) && array_key_exists('name',$from))
        {
            $this->innerSenderName = $from['name'];
        }

        parent::setFrom($from);
        return $this;
    }
    /**
     * Add to address
     *
     * @param array|string $address
     * @param string $name
     * @return $this
     */
    public function addTo($address, $name = '')
    {
        $this->innerSendto = $address;
        parent::addTo($address, $name);
        return $this;
    }

    /**
     * Reset transport builder
     */
    public function resetTransportBuilder()
    {
        $this->reset();
    }
}