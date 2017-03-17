<?php

class Codaone_Logitrail_AjaxController extends Mage_Core_Controller_Front_Action {

	/*
	 *  Return logitrail form for checkout
	 *
	 *
	 */
	public function formAction() {
		echo Mage::getModel('logitrail/carrier_logitrail')->getForm();
	}

	/*
	 * Handle success callback from Logitrail
	 *
	 */
	public function successAction() {
		Mage::getModel('logitrail/carrier_logitrail')->shippingDetails(
			preg_replace('/[^A-Za-z0-9\-]/', '', $this->getRequest()
				->getParam('order_id')),
			(float) $this->getRequest()->getParam('delivery_fee'));
	}

	/*
	 * Handle fail callback from Logitrail
	 *
	 */
	public function failAction() {
		// placeholder, never called, failure handled at js
	}

}

