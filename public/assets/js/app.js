document.documentElement.classList.add('js-ready');

document.addEventListener('DOMContentLoaded', function () {
    const images = document.querySelectorAll('img[data-fallback-src]');

    images.forEach(function (image) {
        const fallbackSrc = image.getAttribute('data-fallback-src');

        if (!fallbackSrc) {
            return;
        }

        image.addEventListener('error', function handleImageError() {
            if (image.getAttribute('src') === fallbackSrc) {
                return;
            }

            image.setAttribute('src', fallbackSrc);
        });
    });
});
