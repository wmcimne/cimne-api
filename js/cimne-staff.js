/**
 * Shared Staff behavior.
 *
 * Used by Research Staff, Directory and Staff Profile. It owns the single
 * implementation for decoding and copying the encoded staff email address.
 */
(function () {
    'use strict';

    var EMAIL_TRIGGER_SELECTOR = '.copy-email-to-clipboard';

    function decodeEmailElement(element) {
        var storedValue = String(element && element.id ? element.id : '').trim();

        if (!storedValue) {
            return '';
        }

        if (storedValue.indexOf('@') !== -1) {
            return storedValue;
        }

        var encodedValue = storedValue.endsWith('c')
            ? storedValue.slice(0, -1)
            : storedValue;

        if (!encodedValue) {
            return '';
        }

        try {
            return atob(encodedValue);
        } catch (error) {
            console.warn('[CIMNE Staff] Unable to decode email.', error);
            return '';
        }
    }

    function copyTextToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            var textarea = document.createElement('textarea');

            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                if (!document.execCommand('copy')) {
                    throw new Error('Copy command was rejected.');
                }
                resolve();
            } catch (error) {
                reject(error);
            } finally {
                textarea.remove();
            }
        });
    }

    function copyEmail(trigger) {
        var email = decodeEmailElement(trigger);

        if (!email) {
            return;
        }

        copyTextToClipboard(email).then(function () {
            var strings = window.cimneStaffI18n || {};
            var originalText = trigger.textContent;

            trigger.textContent = strings.emailCopied || 'Email copied';

            window.setTimeout(function () {
                trigger.textContent = originalText;
            }, 2000);
        }).catch(function (error) {
            console.warn('[CIMNE Staff] Unable to copy email.', error);
        });
    }

    function handleClick(event) {
        var target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        var trigger = target.closest(EMAIL_TRIGGER_SELECTOR);

        if (!trigger) {
            return;
        }

        event.preventDefault();
        copyEmail(trigger);
    }

    function handleKeydown(event) {
        if ('Enter' !== event.key && ' ' !== event.key) {
            return;
        }

        var target = event.target;

        if (!(target instanceof Element) || !target.matches(EMAIL_TRIGGER_SELECTOR)) {
            return;
        }

        event.preventDefault();
        copyEmail(target);
    }

    window.CIMNEStaff = {
        decodeEmailElement: decodeEmailElement,
        copyTextToClipboard: copyTextToClipboard
    };

    document.addEventListener('click', handleClick);
    document.addEventListener('keydown', handleKeydown);
}());
