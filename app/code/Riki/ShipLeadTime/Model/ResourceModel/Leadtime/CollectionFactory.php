<?php
namespace Riki\ShipLeadTime\Model\ResourceModel\Leadtime;

class CollectionFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Riki\\ShipLeadTime\\Model\\ResourceModel\\Leadtime\\Collection') // @codingStandardsIgnoreLine
    {
        $this->_objectManager = $objectManager;  // @codingStandardsIgnoreLine
        $this->_instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \Riki\ShipLeadTime\Model\ResourceModel\Leadtime\Collection
     */
    public function create(array $data = array())
    {
        return $this->_objectManager->create($this->_instanceName, $data);  // @codingStandardsIgnoreLine
    }
}