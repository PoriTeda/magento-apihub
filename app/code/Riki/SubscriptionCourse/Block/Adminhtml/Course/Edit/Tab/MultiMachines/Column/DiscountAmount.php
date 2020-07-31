<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\MultiMachines\Column;

class DiscountAmount extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
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
     * @param \Riki\MachineApi\Model\B2cMachineSkusFactory $courseFactory,
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
        $discountAmount = $machine && array_key_exists('discount_percent', $machine) ? $machine['discount_percent'] : 0;

        $html = '<input type="text" name="discount_percent" value="'.$discountAmount.'" class="input-text ">';

        return $html;
    }
}
