<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\MultiMachines\Column;

class Wbs extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Riki\MachineApi\Model\B2cMachineSkusFactory
     */
    protected $machineTypeFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Riki\MachineApi\Model\B2cMachineSkusFactory $machineTypeFactory,
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Riki\MachineApi\Model\B2cMachineSkusFactory $machineTypeFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->machineTypeFactory = $machineTypeFactory;
        $this->request = $context->getRequest();
    }

    /**
     * Render product field
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $productId = $row->getData('entity_id');
        $typeId = (int)$this->request->getParam('type_id');
        $machine = $this->machineTypeFactory->create()->getMachine($typeId, $productId);
        $wbs = $machine && array_key_exists('wbs', $machine) ? $machine['wbs'] : '';

        $html = '<input type="text" rel="wbs" name="wbs['.$productId.']" value="'.$wbs;
        $html .='" class="input-text input-wbs validate-wbs-code">';

        return $html;
    }
}
