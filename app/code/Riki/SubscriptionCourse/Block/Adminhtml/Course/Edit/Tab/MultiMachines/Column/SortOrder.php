<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\MultiMachines\Column;

class SortOrder extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
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
        $wbs = $machine && array_key_exists('sort_order', $machine) ? $machine['sort_order'] : '';

        $html = '<input type="text" name="sort_order" value="'.$wbs.'" class="input-text ">';

        return $html;
    }
}
