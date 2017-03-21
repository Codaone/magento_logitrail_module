<?php

class Codaone_Logitrail_Model_Observer extends Varien_Event_Observer {

	/*
	 * Confirm order to Logitrail
	 *
	 *
	 */
	public function confirmOrder($observer) {
		Mage::getModel('logitrail/logitrail')
			->confirmOrder($observer->getPayment()->getOrder());
	}

	public function saveProduct($observer) {
		if (Mage::getStoreConfig('carriers/logitrail/autosaveproduct') == 0) {
			return; // do nothing
		}
		$helper = Mage::helper('logitrail');
		$result = Mage::getModel('logitrail/logitrail')
			->addProducts(array($observer->getEvent()->getProduct()->getId()));
		if ($result === TRUE) {

			Mage::getSingleton('core/session')
				->addSuccess($helper->__('Product successfully added/updated to Logitrail'));
		}
		else {
			Mage::getSingleton('core/session')
				->addError($helper->__('Error: Adding product failed: ') . $helper->escapeHtml($result));
		}
	}
}       
