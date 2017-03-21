<?php

class Codaone_Logitrail_Block_Adminhtml_Catalog_Product_Grid extends Mage_Adminhtml_Block_Catalog_Product_Grid {

	protected function _prepareMassaction() {
		parent::_prepareMassaction();
		// Append new mass action option
		$this->getMassactionBlock()->addItem(
			'masscustomstatusses',
			array(
				'label' => $this->__('Create/Update products to Logitrail'),
				'url'   => $this->getUrl('adminhtml/logitrailbackend/masscreate')
			)
		);
	}

}
