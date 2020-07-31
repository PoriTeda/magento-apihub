<?php
namespace Riki\ThirdPartyImportExport\Plugin\ThirdPartyImportExport\Cron\Order\Import;

use Riki\ThirdPartyImportExport\Api\ConfigInterface;

class EmailNotification
{
    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $appState;

    /**
     * EmailNotification constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
    ){
        $this->appState = $appState;
        $this->logger = $logger;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
    }

    public function afterExecute(\Riki\ThirdPartyImportExport\Cron\Order\Import1 $subject, $result)
    {
        $content = $subject->getLogger()->getLogContent();
        if (!$content) {
            return $result;
        }
        $params = ['log' => $content];

        $this->appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND, [$this, 'sendEmail'], [$params]);
        return $result;
    }


    /**
     * Send email notification
     *
     * @param $params
     *
     * @return mixed $result
     */
    public function sendEmail($params = [])
    {
        $recipients = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->orderImport()
            ->email()
            ->recipientsReport();
        if (!trim($recipients)) {
            return true;
        }

        $recipients = array_map('trim', explode(',', $recipients));
        try {
            $this->inlineTranslation->suspend();
            $this->transportBuilder->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ]
            )
                ->setTemplateIdentifier('thirdpartyimportexport_order_import_error_email_template') //TODO: should use constant/config, I will do later, no time now
                ->setTemplateVars($params)
                ->addTo($recipients)
                ->getTransport()
                ->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return true;
    }
}