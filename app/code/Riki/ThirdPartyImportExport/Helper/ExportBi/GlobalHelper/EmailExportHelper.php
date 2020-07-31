<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper;

class EmailExportHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslation;
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper
     */
    protected $_configHelper;

    /*sftp logger, provided by setLogger function*/
    protected $_logger;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper
    ) {
        parent::__construct($context);
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        $this->_configHelper = $configHelper;
    }

    /**
     * After export data, send notification email
     *
     * @param $logContent
     * @return bool
     */
    public function sentNotificationEmail($logContent)
    {
        /*check enable email notification*/
        $emailNotification = $this->_configHelper->getEmailEnable();

        if (!$emailNotification) {
            return false;
        }

        $this->addLogInfo('Sending notification emails ....');

        $emailVariable = ['logContent'=> $logContent];

        try {
            $this->sendMailExporting($emailVariable);
            $this->addLogInfo('Send notification email success.');
        } catch (\Exception $e) {
            $this->addLogInfo('Sent notification email failed: '.$e->getMessage());
        }
    }

    /**
     * Send notification email - generate tamplate and send email process
     *
     * @param $emailTemplateVariables
     */
    public function sendMailExporting($emailTemplateVariables)
    {
        $this->_inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables);
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();
        $this->_inlineTranslation->resume();
    }

    /**
     * @param $emailTemplateVariables
     * @return $this
     * @throws \Magento\Framework\Exception\MailException
     */
    public function generateTemplate($emailTemplateVariables)
    {
        /*list email address will be received notification email*/
        $emailTo = $this->_configHelper->getEmailAlert();

        if (!$emailTo) {
            throw new \Magento\Framework\Exception\MailException(
                __('Recipient is empty.')
            );
        }

        $receivedEmail = explode(';',$emailTo);

        $senderInfo = [
            'name' => $this->_configHelper->getSenderName(),
            'email' => $this->_configHelper->getSenderEmail()
        ];

        $this->_transportBuilder->setTemplateIdentifier(
            $this->_configHelper->getEmailTemplate()
        )->setTemplateOptions([
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* Here you can defile area and store of template for which you prepare it */
            'store' => $this->_storeManager->getStore()->getId(),
        ])->setTemplateVars(
            $emailTemplateVariables
        )->setFrom(
            $senderInfo
        )->addTo(
            $receivedEmail
        );

        return $this;
    }

    /**
     * Provide logger object for this helper
     *
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Add message to logger
     *
     * @param $msg
     */
    public function addLogInfo($msg)
    {
        if (!empty($this->_logger)) {
            $this->_logger->info($msg);
        }
    }
}