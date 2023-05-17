/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_S3amazon
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
define([
    "jquery",
    "jquery/ui",
    "mage/translate"
], function ($) {
    'use strict';
    $.widget('mage.checkBucket', {
        options: {},
        _create: function () {
            var self = this;

            $(self.options.checkButton).on('click', function () {
                var access_key = $(self.options.configForm + ' #s3_amazon_general_settings_access_key').val();
                var secret_key = $(self.options.configForm + ' #s3_amazon_general_settings_secret_key').val();
                var bucket = $(self.options.configForm + ' #s3_amazon_general_settings_bucket').val();
                var region = $(self.options.configForm + ' #s3_amazon_general_settings_region').val();
                var message = '';

                if (access_key == '') {
                    message = $.mage.__('Access Key is required');
                }
                if (secret_key == '') {
                    message = $.mage.__('Secret Key is required');
                }
                if (bucket == '') {
                    message = $.mage.__('Bucket Name is required');
                }
                if (region == '') {
                    message = $.mage.__('Region is required');
                }
                
                if (message == '') {
                    $(self.options.status).html($.mage.__("Checking..."));
                    $.ajax({
                        url: self.options.ajaxUrl,
                        data: {bucket: bucket, access_key: access_key, secret_key: secret_key, region: region},
                        type: 'POST',
                        success: function (response) {
                            if (response.status == true) {
                                $(self.options.status).html(response.message);
                            } else {
                                $(self.options.status).html(response.message);
                                self._callModel(response.message);
                            }
                        }
                    })
                } else {
                    self._callModel(message);
                }
            });
        },
        _callModel: function (message) {
            var self = this;
            $('<div />').html(message)
            .modal({
                title: $.mage.__('Status'),
                autoOpen: true,
                buttons: [{
                    text: 'OK',
                    attr: {
                        'data-action': 'cancel'
                    },
                    'class': 'action-primary',
                    click: function () {
                        this.closeModal();
                    }
                }]
            });
        }
    });
    return $.mage.checkBucket;
});
