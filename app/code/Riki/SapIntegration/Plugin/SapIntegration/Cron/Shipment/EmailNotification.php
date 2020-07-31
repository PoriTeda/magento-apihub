<?php
namespace Riki\SapIntegration\Plugin\SapIntegration\Cron\Shipment;

use Riki\SapIntegration\Api\ConfigInterface;

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
     * @var \Riki\SapIntegration\Model\Email\SapShipmentExportNotification
     */
    protected $sapShipmentExportEmail;

    /**
     * EmailNotification constructor.
     *
     * @param \Riki\SapIntegration\Model\Email\SapShipmentExportNotification $sapShipmentExportEmail
     * @param \Magento\Framework\App\State $appState
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Riki\SapIntegration\Model\Email\SapShipmentExportNotification $sapShipmentExportEmail,
        \Magento\Framework\App\State $appState,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->sapShipmentExportEmail = $sapShipmentExportEmail;
        $this->appState = $appState;
        $this->logger = $logger;
    }


    /**
     * Send email notification
     *
     * @param \Riki\SapIntegration\Cron\ShipmentV2 $subject
     * @param $result
     *
     * @return mixed $result
     */
    public function afterExecute(\Riki\SapIntegration\Cron\ShipmentV2 $subject, $result)
    {
        $content = $subject->getLogger()->getLogContent();
        if (!$content) {
            return $result;
        }

        try {
            $this->appState->emulateAreaCode(
                \Magento\Framework\App\Area::AREA_FRONTEND,
                [$this->sapShipmentExportEmail, 'send'],
                [['log' => $content]]
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $result;
    }
}