document.addEventListener('DOMContentLoaded', function () {
    const button = document.querySelector('[data-address-autofill-trigger]');
    const postalCodeInput = document.querySelector('#postal_code');
    const prefectureInput = document.querySelector('#prefecture');
    const cityInput = document.querySelector('#city');
    const addressLineInput = document.querySelector('#address_line');
    const statusNode = document.querySelector('[data-address-autofill-status]');

    if (!button || !postalCodeInput || !prefectureInput || !cityInput || !addressLineInput || !statusNode) {
        return;
    }

    const postalDirectory = {
        '1000001': { prefecture: '東京都', city: '千代田区', addressLine: '千代田' },
        '1500001': { prefecture: '東京都', city: '渋谷区', addressLine: '神宮前' },
        '2200012': { prefecture: '神奈川県', city: '横浜市西区', addressLine: 'みなとみらい' },
        '4600008': { prefecture: '愛知県', city: '名古屋市中区', addressLine: '栄' },
        '5300001': { prefecture: '大阪府', city: '大阪市北区', addressLine: '梅田' },
        '0600001': { prefecture: '北海道', city: '札幌市中央区', addressLine: '北一条西' },
    };

    const updateStatus = function (message, isError) {
        statusNode.textContent = message;
        statusNode.classList.toggle('is-error', Boolean(isError));
    };

    button.addEventListener('click', function () {
        const normalizedPostalCode = postalCodeInput.value.replace(/\D+/g, '');

        postalCodeInput.value = normalizedPostalCode;

        if (normalizedPostalCode.length !== 7) {
            updateStatus('郵便番号はハイフンなし7桁で入力してください。', true);
            postalCodeInput.focus();
            return;
        }

        const entry = postalDirectory[normalizedPostalCode];

        if (!entry) {
            updateStatus('住所候補が見つかりませんでした。市区町村以下を手入力してください。', true);
            return;
        }

        prefectureInput.value = entry.prefecture;
        cityInput.value = entry.city;

        if (addressLineInput.value.trim() === '') {
            addressLineInput.value = entry.addressLine;
        }

        updateStatus('住所候補を入力しました。番地と建物名を確認してください。', false);
    });
});
