<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;

class EditPost extends \Magento\GiftRegistry\Controller\Index
{
    /**
     * Strip tags from received data
     *
     * @param string|array $data
     * @return string|array
     */
    protected function _filterPost($data)
    {
        if (!is_array($data)) {
            return strip_tags($data);
        }
        foreach ($data as &$field) {
            if (!empty($field)) {
                if (!is_array($field)) {
                    $field = strip_tags($field);
                } else {
                    $field = $this->_filterPost($field);
                }
            }
        }
        return $data;
    }

    /**
     * Create gift registry action
     *
     * @return void|ResponseInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        if (!($typeId = $this->getRequest()->getParam('type_id'))) {
            $this->_redirect('*/*/addselect');
            return;
        }

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->_redirect('*/*/edit', ['type_id', $typeId]);
            return;
        }

        if ($this->getRequest()->isPost() && ($data = $this->getRequest()->getPostValue())) {
            $entityId = $this->getRequest()->getParam('entity_id');
            $isError = false;
            $isAddAction = true;
            try {
                if ($entityId) {
                    $isAddAction = false;
                    $model = $this->_initEntity('entity_id');
                }
                if ($isAddAction) {
                    $entityId = null;
                    $model = $this->_objectManager->create(\Magento\GiftRegistry\Model\Entity::class);
                    if ($model->setTypeById($typeId) === false) {
                        throw new LocalizedException(__('The type is incorrect. Verify and try again.'));
                    }
                }

                $data = $this->_objectManager->get(
                    \Magento\GiftRegistry\Helper\Data::class
                )->filterDatesByFormat(
                    $data,
                    $model->getDateFieldArray()
                );
                $data = $this->_filterPost($data);
                $this->getRequest()->setPostValue($data);
                $model->importData($data, $isAddAction);

                $registrantsPost = $this->getRequest()->getPost('registrant');
                $persons = [];
                if (is_array($registrantsPost)) {
                    foreach ($registrantsPost as $registrant) {
                        if (is_array($registrant)) {
                            /* @var $person \Magento\GiftRegistry\Model\Person */
                            $person = $this->_objectManager->create(\Magento\GiftRegistry\Model\Person::class);
                            $idField = $person->getIdFieldName();
                            if (!empty($registrant[$idField])) {
                                $person->load($registrant[$idField]);
                                if (!$person->getId()) {
                                    throw new LocalizedException(
                                        __('The registrant data is incorrect. Verify and try again.')
                                    );
                                }
                            } else {
                                unset($registrant['person_id']);
                            }
                            $person->setData($registrant);
                            $errors = $person->validate();
                            if ($errors !== true) {
                                foreach ($errors as $err) {
                                    $this->messageManager->addError($err);
                                }
                                $isError = true;
                            } else {
                                $persons[] = $person;
                            }
                        }
                    }
                }
                $addressTypeOrId = $this->getRequest()->getParam('address_type_or_id');
                if (!$addressTypeOrId || $addressTypeOrId == \Magento\GiftRegistry\Helper\Data::ADDRESS_NEW) {
                    // creating new address
                    if (!empty($data['address'])) {
                        /* @var $address \Magento\Customer\Model\Address */
                        $address = $this->_objectManager->create(\Magento\Customer\Model\Address::class);
                        $address->setData($data['address']);
                        $errors = $address->validate();
                        $model->importAddress($address);
                    } else {
                        throw new LocalizedException(__("The address can't be empty. Enter and try again."));
                    }
                    if ($errors !== true) {
                        foreach ($errors as $err) {
                            $this->messageManager->addError($err);
                        }
                        $isError = true;
                    }
                } elseif ($addressTypeOrId != \Magento\GiftRegistry\Helper\Data::ADDRESS_NONE) {
                    // using one of existing Customer addresses
                    $addressId = $addressTypeOrId;
                    if (!$addressId) {
                        throw new LocalizedException(__('An address needs to be selected. Select and try again.'));
                    }
                    /* @var $customer \Magento\Customer\Model\Customer */
                    $customer = $this->_objectManager->get(\Magento\Customer\Model\Session::class)->getCustomer();

                    $address = $customer->getAddressItemById($addressId);
                    if (!$address) {
                        throw new LocalizedException(__('The address is incorrect. Verify and try again.'));
                    }
                    $model->importAddress($address);
                }
                $errors = $model->validate();
                if ($errors !== true) {
                    foreach ($errors as $err) {
                        $this->messageManager->addError($err);
                    }
                    $isError = true;
                }

                if (!$isError) {
                    $model->save();
                    $entityId = $model->getId();
                    $personLeft = [];
                    foreach ($persons as $person) {
                        $person->setEntityId($entityId);
                        $person->save();
                        $personLeft[] = $person->getId();
                    }
                    if (!$isAddAction) {
                        $this->_objectManager->create(
                            \Magento\GiftRegistry\Model\Person::class
                        )->getResource()->deleteOrphan(
                            $entityId,
                            $personLeft
                        );
                    }
                    $this->messageManager->addSuccess(__('You saved this gift registry.'));
                    if ($isAddAction) {
                        $model->sendNewRegistryEmail();
                    }
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $isError = true;
            } catch (\Exception $e) {
                $this->messageManager->addError(__("We couldn't save this gift registry."));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                $isError = true;
            }

            if ($isError) {
                $this->_getSession()->setGiftRegistryEntityFormData($this->getRequest()->getPostValue());
                $params = $isAddAction ? ['type_id' => $typeId] : ['entity_id' => $entityId];
                return $this->_redirect('*/*/edit', $params);
            } else {
                $this->_redirect('*/*/');
            }
        }
        $this->_redirect('*/*/');
    }
}
