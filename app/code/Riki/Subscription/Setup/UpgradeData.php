<?php
// @codingStandardsIgnoreFile
namespace Riki\Subscription\Setup;

class UpgradeData extends \Riki\Framework\Setup\Version\Data implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Riki\Subscription\Model\Version\VersionFactory
     */
    protected $versionFactory;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;


    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Model\Version\VersionFactory $versionFactory,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct($functionCache, $logger, $resourceConnection, $deploymentConfig);
        $this->profileFactory = $profileFactory;
        $this->versionFactory = $versionFactory;
        $this->appState = $state;
    }
    public function version173(){
        $this->appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB, [$this, 'upgradeData173']);
    }
    public function upgradeData173()
    {
        $versionModel = $this->versionFactory->create()->getCollection();
        foreach ($versionModel->getItems() as $profileVersion){
            $profileVersionId = $profileVersion->getData('moved_to');
            $profileModel = $this->profileFactory->create()->load($profileVersionId);
            if($profileModel->getId()){
                $profileModel->setData('type','version');
                try{
                    $profileModel->save();
                }catch (\Exception $exception){
                    $this->logger->critical($exception);
                }
            }
        }

    }
}
