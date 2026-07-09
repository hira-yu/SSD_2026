document.addEventListener('DOMContentLoaded', function () {
    const button = document.querySelector('[data-address-autofill-trigger]');
    const postalCodeInput = document.querySelector('#postal_code');
    const prefectureInput = document.querySelector('#prefecture');
    const cityInput = document.querySelector('#city');
    const addressLineInput = document.querySelector('#address_line');

    if (!button || !postalCodeInput || !prefectureInput || !cityInput || !addressLineInput) {
        return;
    }

    let inputTimer = 0;

    const lookupAddress = function () {
        const normalizedPostalCode = postalCodeInput.value.replace(/\D+/g, '');

        postalCodeInput.value = normalizedPostalCode;

        if (normalizedPostalCode.length !== 7) {
            postalCodeInput.focus();
            return;
        }

        if (typeof YubinBango === 'undefined' || typeof YubinBango.Core !== 'function') {
            return;
        }

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');

        new YubinBango.Core(normalizedPostalCode, function (address) {
            if (!address) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                return;
            }

            prefectureInput.value = String(address.region || '');
            cityInput.value = String(address.l || '');
            addressLineInput.value = String(address.m || '');
            button.disabled = false;
            button.removeAttribute('aria-busy');
            addressLineInput.focus();
        });
    };

    button.addEventListener('click', function () {
        lookupAddress();
    });

    postalCodeInput.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        lookupAddress();
    });

    postalCodeInput.addEventListener('input', function () {
        const normalizedPostalCode = postalCodeInput.value.replace(/\D+/g, '');

        if (postalCodeInput.value !== normalizedPostalCode) {
            postalCodeInput.value = normalizedPostalCode;
        }

        if (normalizedPostalCode.length !== 7) {
            return;
        }

        window.clearTimeout(inputTimer);
        inputTimer = window.setTimeout(lookupAddress, 120);
    });
});
