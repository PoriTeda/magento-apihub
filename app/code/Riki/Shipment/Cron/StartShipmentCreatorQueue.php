<?php
// @codingStandardsIgnoreFile
/**
 * Shipment Cron
 *
 * PHP version 7
 *
 * @category  RIKI Shipment
 * @package   Riki\Shipment\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Cron;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;

/**
 * Class PublishMessageOrder
 *
 * @category  RIKI Shipment
 * @package   Riki\Shipment\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class StartShipmentCreatorQueue
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var string
     */
    protected $pathPhp;
    /**
     * @var LoggerShipment
     */
    protected $log;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;
    /**
     * StartShipmentCreatorQueue constructor.
     * @param LoggerInterface $loggerShipment
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Shell $shell
     */
    public function __construct(
        LoggerInterface $loggerShipment,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Shell $shell,
        \Magento\Framework\App\State $state
    ){
        $this->log = $loggerShipment;
        $this->shell = $shell;
        $this->scopeConfig = $scopeConfig;
        $this->appState = $state;

    }
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $maxMessageConsumer  = $this->getMaxMessageConsumer();
        $sPhpPath = $this->getPathPhp();
        $sPathBinMagento = BP.'/bin/magento';
        $keyCommand = 'generateOrderShipments';
        $nameCommand = $sPathBinMagento.' queue:consumers:start --max-messages='.$maxMessageConsumer.' generateOrderShipments';
        try{
            $resultCommand  = $this->shell->execute("ps aux | grep -i " . $keyCommand);
            if (strpos($resultCommand, $nameCommand) === false)
            {
                $this->shell->execute(
                    $sPhpPath
                    .' '
                    .$sPathBinMagento
                    .' queue:consumers:start --max-messages='
                    .$maxMessageConsumer
                    .' '.$keyCommand
                    .' >> /dev/null &'
                );
            }
        }catch(\Exception $e)
        {
            $this->log->critical($e->getMessage());
        }
    }
    /**
     * @return mixed
     */
    public function getMaxMessageConsumer(){

        return $this->scopeConfig->getValue('shipmentexporter/shipmentqueue/number_message_per_consumer',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * GetPathPhp
     *
     *
     * @return string
     */
    public function getPathPhp(){

        $sPhpPath = '';

        $aCommands = [
            'whereis php',
            'which php'
        ];
        try{
            foreach($aCommands as $sCommand){
                $sPhpPath = $this->shell->execute($sCommand);
                $aPhpPath = explode(" ",$sPhpPath);
                foreach($aPhpPath as $sPhpPathLine){
                    if(strpos($sPhpPathLine,"/bin/php") !== false){
                        $sPhpPath = $sPhpPathLine;
                        break;
                    }
                }

                return $sPhpPath;
            }
        }catch (\Exception $e){
            $this->log->critical($e->getMessage());
        }

    }
}