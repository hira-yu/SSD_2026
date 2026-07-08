document.documentElement.classList.add('js-ready');

document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons({
            attrs: {
                'stroke-width': 1.8,
                'aria-hidden': 'true',
            },
        });
    }

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

    const customerSearchForm = document.querySelector('.customer-search-form');

    if (customerSearchForm) {
        const categorySelect = customerSearchForm.querySelector('.customer-search-category');
        const searchInput = customerSearchForm.querySelector('#global-search');
        let lastSearchIntent = '';

        if (categorySelect && searchInput) {
            categorySelect.addEventListener('change', function () {
                lastSearchIntent = 'category';
            });

            searchInput.addEventListener('input', function () {
                lastSearchIntent = 'keyword';
            });

            customerSearchForm.addEventListener('submit', function () {
                if (lastSearchIntent === 'category') {
                    searchInput.value = '';
                    return;
                }

                if (lastSearchIntent === 'keyword') {
                    categorySelect.value = '';
                }
            });
        }
    }

    const renderIcons = function () {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons({
                attrs: {
                    'stroke-width': 1.8,
                    'aria-hidden': 'true',
                },
            });
        }
    };

    const updateFavoriteCount = function (count) {
        const favoriteLabel = document.querySelector('[data-favorite-label]');
        const favoriteStat = document.querySelector('.market-utility-stat strong');
        const numericCount = Number.parseInt(String(count), 10);

        if (favoriteLabel && Number.isFinite(numericCount)) {
            favoriteLabel.textContent = numericCount > 0 ? `お気に入り商品 (${numericCount})` : 'お気に入り商品';
        }

        if (favoriteStat && Number.isFinite(numericCount)) {
            favoriteStat.textContent = String(numericCount);
        }
    };

    document.querySelectorAll('.market-favorite-form').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (form.dataset.submitting === 'true') {
                return;
            }

            const button = event.submitter instanceof HTMLButtonElement
                ? event.submitter
                : form.querySelector('button[type="submit"]');

            form.dataset.submitting = 'true';
            form.classList.add('is-updating');

            if (button) {
                button.disabled = true;
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const payload = await response.json();

                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'お気に入りの更新に失敗しました。');
                }

                const isFavorite = Boolean(payload.is_favorite);
                form.action = isFavorite ? '/favorites/remove' : '/favorites/add';
                updateFavoriteCount(payload.favorite_count);

                if (button) {
                    const removeLabel = button.textContent.includes('解除') ? 'お気に入り解除' : 'お気に入りから外す';
                    const label = isFavorite ? removeLabel : 'お気に入りに追加';
                    const icon = isFavorite ? 'heart-off' : 'heart';
                    button.innerHTML = `<i data-lucide="${icon}" aria-hidden="true"></i>${label}`;
                }

                if (window.location.pathname === '/favorites' && !isFavorite) {
                    const card = form.closest('.market-product-card');

                    if (card) {
                        card.remove();
                    }
                }

                renderIcons();
            } catch (error) {
                window.alert(error instanceof Error ? error.message : 'お気に入りの更新に失敗しました。');
            } finally {
                delete form.dataset.submitting;
                form.classList.remove('is-updating');

                if (button) {
                    button.disabled = false;
                }
            }
        });
    });
});
