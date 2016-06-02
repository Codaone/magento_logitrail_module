# Magento logitrail module

This is Logitrail's Magento 1.x shipping module. The module is maintained by Codaone.

## System requirements

The Logitrail shipping module for Magento was tested on and requires the following set of applications in order to fully work:

* Magento 1.5.x - 1.9.x
* PHP version 5 or higher (version 7 not tested)
* PHP cURL support

There is no guarantee that the module is fully functional in any other environment which does not fulfill the requirements.

## Installation

Prior to any change in your environment, it is strongly recommended to perform a full backup of the entire Magento installation.
It is also strongly recommended to do installation first in development environment, and only after that in production environment.

1. Extract the module release files under Magento installation
2. Clean Magento cache
3. Configure the module
4. Verify the shipping work

## Configuration

Configuration for the module can be found from standard location under *System -> Configuration -> Shipping Methods -> Logitrail*.

##### Test mode
If enabled, communication is pointed at logitrail test url, otherwise production url is used.

#### Automatic creation or update to Logitrail on product save
If enabled, when products are saved they will be automatically created to logitrail

#### Automatic shipment creation
If enabled, shipment is created when order is confirmed
