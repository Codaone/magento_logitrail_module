<?php

class Codaone_Logitrail_Block_Adminhtml_Catalog_Product_Edit extends Mage_Adminhtml_Block_Catalog_Product_Edit {

	protected function _prepareLayout() {
		parent::_prepareLayout();
		$prid = $this->getProduct()->getId();
		$this->setChild('product_sendbutton',
			$this->getLayout()->createBlock('adminhtml/widget_button')
				->setData(array(
					'label'   => Mage::helper('logitrail')
						->__('Create/update product to Logitrail'),
					'onclick' => "setLocation('" .
						$this->getUrl('adminhtml/logitrailbackend/addproduct', array('prid' => (int) $prid)) . "')"
				)));
		return $this;
	}

	public function getSaveButtonHtml() {
		if (Mage::getStoreConfig('carriers/logitrail/autosaveproduct') == 1) {
			return parent::getSaveButtonHtml();
		}
		else {
			return $this->getChildHtml('product_sendbutton') . parent::getSaveButtonHtml();
		}
	}

}
