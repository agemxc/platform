define([
    'module',
    'routing',
    'oroui/js/mediator',
    'oroui/js/tools',
    'oroui/js/modal',
    'underscore'
], function(module, routing, mediator, tools, Modal, _) {
    'use strict';

    var defaults = _.defaults(module.config(), {
        headerServerError: _.__('Server error'),
        headerUserError: _.__('User input error'),
        message: _.__('oro.ui.error')
    });

    var ERROR_USER_INPUT = 'user_input_error';
    var console = window.console;

    var errorHandler = {
        /**
         * Global Ajax error handler
         *
         * @param {Object} event
         * @param {Object} xhr
         * @param {Object} settings
         */
        handle: function(event, xhr, settings) {
            if (this.isXHRStatus(xhr, 401)) {
                this._processRedirect();
            } else if (xhr.readyState === 4) {
                var errorMessage = this.getErrorMessage(event, xhr, settings);
                var isShowError = Boolean(errorMessage);

                if (!isShowError) {
                    this.showErrorInConsole(xhr);
                } else {
                    this.showError(xhr, errorMessage);
                }
            }
        },

        isXHRStatus: function(xhr, testStatus) {
            var status = 0;
            if (_.isObject(xhr)) {
                status = xhr.status;
                if ('responseJSON' in xhr && 'code' in xhr.responseJSON) {
                    status = xhr.responseJSON.code;
                }
            }
            return status === testStatus;
        },

        /**
         * @param {Object} event
         * @param {Object} xhr
         * @param {Object} settings
         * @return {Boolean}
         */
        getErrorMessage: function(event, xhr, settings) {
            var errorMessage = true;

            if (settings.errorHandlerMessage !== undefined) {
                errorMessage = settings.errorHandlerMessage;
                if (_.isFunction(errorMessage)) {
                    errorMessage = errorMessage(event, xhr, settings);
                }
            }

            return errorMessage;
        },

        /**
         * @param {Object|Error} context
         * @param {String|null} errorMessage
         */
        showError: function(context, errorMessage) {
            this.showErrorInUI(_.isString(errorMessage) ? errorMessage : context);
            this.showErrorInConsole(context);
        },

        /**
         * @param {Object|Error|String} context
         */
        showErrorInUI: function(context) {
            if (tools.debug && context instanceof Error) {
                this.showFlashError(context.toString());
            } else if (_.isString(context)) {
                this.showFlashError(context);
            } else if (_.isObject(context) && context.responseJSON && context.responseJSON.message) {
                this.showFlashError(context.responseJSON.message);
            } else if (this.isXHRStatus(context, 403)) {
                this.showFlashError(_.__('oro.ui.forbidden_error'));
            } else {
                this.showFlashError(defaults.message);
            }
        },

        /**
         * @param {Object|Error} context
         */
        showErrorInConsole: function(context) {
            var errorStyle = 'font-weight: bold;';
            console.error('%cDebug:', errorStyle, context);
        },

        /**
         * @param {String} message
         */
        showFlashError: function(message) {
            mediator.execute('showFlashMessage', 'error', message);
        },

        /**
         * @param {Error} xhr
         * @deprecated
         */
        modalHandler: function(xhr) {
            var message = defaults.message;
            if (tools.debug) {
                message += '<br><b>Debug:</b>' + xhr.responseText;
            }

            var responseObject = xhr.responseJSON || {};
            var errorType = responseObject.type;

            var modal = new Modal({
                title: errorType === ERROR_USER_INPUT ? defaults.headerUserError : defaults.headerServerError,
                content: responseObject.message || message,
                cancelText: false
            });
            modal.open();
        },

        /**
         * Redirects to login
         *
         * @private
         */
        _processRedirect: function() {
            var hashUrl = '';
            // @TODO add extra parameter for redirect after login
            /*if (Navigation.isEnabled()) {
                var navigation = Navigation.getInstance();
                hashUrl = '#url=' + navigation.getHashUrl();
            }*/

            window.location.href = routing.generate('oro_user_security_login') + hashUrl;
        }
    };

    return errorHandler;
});
