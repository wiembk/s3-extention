#Installation

Magento 2 S3amazon module installation is very easy, please follow the steps for installation-

Unzip the respective extension zip and then move "app" folder (inside "src" folder) into magento root directory.

Run Following Command via terminal
-----------------------------------
composer require aws/aws-sdk-php
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy

After enabling and adding credentials run the following command.
----------------------------------------------------------------
php bin/magento import:media

If Static View files enabled then add the static content bucket url into Base URL for Static View Files and run the following command.
----------------------------------------------------------------
php bin/magento import:static-content

2. Flush the cache and reindex all.

now module is properly installed

#User Guide

For Magento 2 S3amazon module's working process follow user guide : https://webkul.com/blog/magento2-amazon-s3-extension/

#Support

Find us our support policy - https://store.webkul.com/support.html/

#Refund

Find us our refund policy - https://store.webkul.com/refund-policy.html/