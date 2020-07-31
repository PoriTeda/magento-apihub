<?php
// @codingStandardsIgnoreFile
namespace Riki\MachineApi\Setup;

class UpgradeData extends \Riki\Framework\Setup\Version\Data implements \Magento\Framework\Setup\UpgradeDataInterface
{
    protected $frequencyFactory;

    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Riki\Subscription\Model\Frequency\FrequencyFactory $frequencyFactory
    ) {
        parent::__construct($functionCache, $logger, $resourceConnection, $deploymentConfig);
        $this->frequencyFactory = $frequencyFactory;
    }

    public function version102()
    {

        $rows = [
            [
                'NBA',
                json_encode([]),
                json_encode($this->getFrequency()),
                null,
                1,
                0
            ],
            [
                'NDG',
                json_encode([]),
                json_encode($this->getFrequency()),
                null,
                1,
                0
            ],
            [
                'SPT',
                json_encode([]),
                json_encode($this->getFrequency()),
                null,
                1,
                0
            ],
            [
                'BLC',
                json_encode([]),
                json_encode($this->getFrequency([2,'month'])),
                null,
                4,
                0
            ],
            [
                'BLC',
                json_encode([]),
                json_encode($this->getFrequency([1,'month'])),
                null,
                2,
                0
            ],
            [
                'BLC',
                json_encode([]),
                json_encode($this->getFrequency([3,'month'])),
                null,
                6,
                0
            ],
            [
                'BLC',
                json_encode([]),
                json_encode($this->getFrequency([1,'month'])),
                null,
                1,
                5000
            ],
            [
                'Nespresso',
                json_encode([]),
                json_encode($this->getFrequency()),
                null,
                1,
                0
            ],

        ];
        $this->insertArray('riki_machine_condition', ['machine_code','course_code', 'frequency', 'category_id','qty_min','threshold'], $rows);
    }
    protected function getFrequency($condition = null){
        $data = [];
        $frequencyModel = $this->frequencyFactory->create()->getCollection();
        if(sizeof($condition) > 0 ){
            $frequencyModel->addFieldToFilter('frequency_unit',$condition[1]);
            $frequencyModel->addFieldToFilter('frequency_interval',$condition[0]);
        }
        foreach ($frequencyModel as $frequency){
            $data[] = $frequency->getId();
        }
        return $data;

    }
}
