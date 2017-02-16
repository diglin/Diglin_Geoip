## Diglin_Geoip

Magento 1.x Module to restrict access to robots or any unwanted visitors to the website based on  a list of IP addresses (via configuration and/or file) or based on country thanks to MaxMind database (free and paid are supported).

Blocked visitors can be redirected to a specific url or have a strict 403 HTTP error

### Features
- Allow / disallow specifi countries (allowed countries overwrites disallowed countries restrictions)
- Allow / disallow a list of IPs (Allowed IPs overwrites countries restrictions)
- Redirect restricted access to a specific url or send a 403 HTTT Error
- A file to allow or disallow IPs can be added into your Magento installation under the `media` folder and configuration into the module configuraion page
- Specific User Agents like GoogleBot can be allowed to get through the restriction

## Installation

### Via modman
- Install [modman](https://github.com/colinmollenhour/modman)
- Use the command from your Magento installation folder: `modman clone https://github.com/diglin/Diglin_Geoip.git`

### Via Composer

- Install [composer](http://getcomposer.org/download/)
- Create a composer.json into your project like the following sample:

```json
{
    ...
    "require": {
        "diglin/diglin_geoip":"1.*"
    },
    "repositories": [
	    {
            "type": "composer",
            "url": "http://packages.firegento.com"
        }
    ]
}

```

- Then from your composer.json folder: `php composer.phar install` or `composer install`


### Manually
- You can copy the files from the folders of this repository to the same folders of your installation starting from the `src` folder

## Documentation

Go to the menu `System > Configuration > Diglin > Geoip` and setup the module following your own restriction rules

## Uninstall

### Via modman

`modman remove Diglin_Geoip`

### Via manually

- Delete the files
	- app/code/community/Diglin/Geoip
	- app/etc/modules/Diglin_Geoip.xml
	- lib/Diglin/MaxMind
	- lib/Diglin/GeoIp2

## Author

* Sylvain Rayé
* http://www.diglin.com/
* [@diglin](https://twitter.com/diglin_)
* [Follow me on github!](https://github.com/diglin)