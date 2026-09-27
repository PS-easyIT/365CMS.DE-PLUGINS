/**
 * CMS Promos – UTM-Builder im Promo-Formular.
 * Ausgelagert aus dem Admin-Template (CSP-konform, keine Inline-Skripte).
 */
(function () {
    var targetInput = document.getElementById('promo-target-url');
    var buildButton = document.getElementById('promo-build-utm');
    if (!targetInput || !buildButton) {
        return;
    }

    var utmFields = ['source', 'medium', 'campaign', 'term', 'content'];
    buildButton.addEventListener('click', function () {
        var rawUrl = (targetInput.value || '').trim();
        if (!rawUrl) {
            return;
        }

        var parsed;
        try {
            parsed = new URL(rawUrl);
        } catch (e) {
            return;
        }

        utmFields.forEach(function (field) {
            var input = document.getElementById('promo-utm-' + field);
            if (!input) {
                return;
            }
            var value = (input.value || '').trim();
            var key = 'utm_' + field;
            if (value && !parsed.searchParams.get(key)) {
                parsed.searchParams.set(key, value.toLowerCase().replace(/\s+/g, '-'));
            }
        });

        targetInput.value = parsed.toString();
    });
})();
