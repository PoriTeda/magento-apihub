<?php
namespace Riki\SapIntegration\Plugin\SapIntegration\Cron\Rma;

class EmailNotification
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Riki\SapIntegration\Model\Email\SapRmaExportNotification
     */
    protected $sapRmaExportEmail;

    /**
     * EmailNotification constructor.
     *
     * @param \Magento\Framework\App\State $appState
     * @param \Riki\SapIntegration\Model\Email\SapRmaExportNotification $sapRmaExportEmail
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Riki\SapIntegration\Model\Email\SapRmaExportNotification $sapRmaExportEmail,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->sapRmaExportEmail = $sapRmaExportEmail;
        $this->appState = $appState;
        $this->logger = $logger;
    }


    /**
     * Send email notification
     *
     * @param \Riki\SapIntegration\Cron\RmaV2 $subject
     * @param $result
     *
     * @return mixed $result
     */
    public function afterExecute(\Riki\SapIntegration\Cron\RmaV2 $subject, $result)
    {

        $content = $subject->getLogger()->getLogContent();
        if (!$content) {
            return $result;
        }

        try {
            $this->appState->emulateAreaCode(
                \Magento\Framework\App\Area::AREA_FRONTEND,
                [$this->sapRmaExportEmail, 'send'],
                [['log' => $content]]
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $result;
    }
}