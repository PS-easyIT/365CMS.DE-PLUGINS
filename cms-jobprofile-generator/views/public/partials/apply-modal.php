<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Partial: Bewerbungs-Modal mit optionaler Inline-Registrierung
 *
 * Wird von allen 4 Single-Page Layouts eingebunden.
 * Unterstützt Login-/Registrierungspflicht je nach Design-Einstellung.
 *
 * @var object $profile
 * @var string $applyCsrf
 * @var array  $designSettings
 *
 * @since   0.9.7
 * @package CMS_JobProfileGenerator
 */

$ds = fn(string $k, string $d = '') => $designSettings['pd_' . $k] ?? $d;
$publicLang = $publicLang ?? (function_exists('jpg_public_lang') ? jpg_public_lang() : 'de');
$t = static fn(string $key, array $replace = []): string
    => function_exists('jpg_public_t') ? jpg_public_t($key, $replace, $publicLang) : $key;
$requireLogin  = ($ds('modal_require_login', '1') === '1');
$showRegister  = ($ds('modal_show_register', '1') === '1');
$showPhone     = ($ds('modal_show_phone', '1') === '1');
$showCv        = ($ds('modal_show_cv', '1') === '1');
$requireCv     = ($ds('modal_require_cv', '0') === '1');
$privacyUrlDefault = function_exists('jpg_public_path') ? jpg_public_path('datenschutz', $publicLang) : '/datenschutz';
$privacyUrl    = $ds('modal_privacy_url', $privacyUrlDefault);
$privacyUrl    = preg_match('#^/[A-Za-z0-9/_\-.]*$#', $privacyUrl) || (filter_var($privacyUrl, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($privacyUrl, PHP_URL_SCHEME)), ['http', 'https'], true))
    ? $privacyUrl
    : '/datenschutz';
$privacyText   = $ds('modal_privacy_text', ($publicLang === 'en'
    ? 'By submitting, you agree to the processing of your data according to our privacy policy.'
    : 'Mit dem Absenden stimmst du der Verarbeitung deiner Daten gemäß unserer Datenschutzerklärung zu.'));
$successText   = $ds('modal_success_text', $t('apply_success_default'));
$coverMinChars = max(0, (int) $ds('modal_cover_min_chars', '20'));

$isLoggedIn  = false;
$currentUser = null;
$jobSlugEsc = htmlspecialchars((string) ($profile->slug ?? ''), ENT_QUOTES, 'UTF-8');
if (class_exists('CMS\\Auth') && \CMS\Auth::instance()->isLoggedIn()) {
    $isLoggedIn = true;
    $currentUser = method_exists(\CMS\Auth::instance(), 'currentUser')
        ? \CMS\Auth::instance()->currentUser()
        : null;
}
?>

<!-- Bewerbungs-Modal -->
<div id="jpgApplyModal" class="jpg-modal">
    <div class="jpg-modal__box">
        <div class="jpg-modal__header">
            <h3 class="jpg-modal__title"><?php echo htmlspecialchars($t('apply_title', ['title' => (string) $profile->title]), ENT_QUOTES, 'UTF-8'); ?></h3>
            <button type="button" data-jpg-modal-close class="jpg-modal__close" aria-label="<?php echo htmlspecialchars($t('apply_close'), ENT_QUOTES, 'UTF-8'); ?>">&times;</button>
        </div>

        <!-- Erfolgs-Banner -->
        <div id="jpgApplySuccess" class="jpg-modal__banner jpg-modal__banner--success">
            <strong><?php echo htmlspecialchars((string) $successText, ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
        <!-- Fehler-Banner -->
        <div id="jpgApplyError" class="jpg-modal__banner jpg-modal__banner--error"></div>

        <?php if ($requireLogin && !$isLoggedIn && $showRegister): ?>
        <!-- Registrierungs-/Login-Tabs -->
        <div class="jpg-modal__auth-tabs" id="jpgAuthTabs">
            <div class="jpg-modal__tab-nav">
                <button type="button" class="jpg-modal__tab-btn active" data-tab="register"><?php echo htmlspecialchars($t('apply_register_and_apply'), ENT_QUOTES, 'UTF-8'); ?></button>
                <button type="button" class="jpg-modal__tab-btn" data-tab="login"><?php echo htmlspecialchars($t('apply_already_member'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>

            <!-- Tab: Registrierung -->
            <div class="jpg-modal__tab-content active" id="jpgTabRegister">
                <form id="jpgRegisterForm" class="jpg-modal__form" novalidate>
                    <input type="hidden" name="_jpg_csrf" value="<?php echo htmlspecialchars((string) ($applyCsrf ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="job_slug" value="<?php echo $jobSlugEsc; ?>">
                    <input type="text" name="_hp_name" class="jpg-honeypot" aria-hidden="true" tabindex="-1" autocomplete="off">

                    <p class="jpg-modal__info"><?php echo htmlspecialchars($t('apply_register_info'), ENT_QUOTES, 'UTF-8'); ?></p>

                    <div class="jpg-form-group">
                        <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_name'), ENT_QUOTES, 'UTF-8'); ?> <span class="jpg-required">*</span></label>
                        <input type="text" name="display_name" required autocomplete="name" class="jpg-form-input" placeholder="Max Mustermann">
                    </div>
                    <div class="jpg-form-group">
                        <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_email'), ENT_QUOTES, 'UTF-8'); ?> <span class="jpg-required">*</span></label>
                        <input type="email" name="email" required autocomplete="email" class="jpg-form-input" placeholder="max@beispiel.de">
                    </div>
                    <?php if ($showPhone): ?>
                    <div class="jpg-form-group">
                        <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_phone_optional'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="tel" name="phone" autocomplete="tel" class="jpg-form-input" placeholder="+49 123 456789">
                    </div>
                    <?php endif; ?>
                    <div class="jpg-form-group">
                        <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_password'), ENT_QUOTES, 'UTF-8'); ?> <span class="jpg-required">*</span></label>
                        <input type="password" name="password" required autocomplete="new-password" class="jpg-form-input" placeholder="Mind. 12 Zeichen" minlength="12">
                        <small class="jpg-form-hint">Mindestens 12 Zeichen, Groß-/Kleinbuchstaben, Zahl, Sonderzeichen</small>
                    </div>

                    <hr style="border:0;border-top:1px solid var(--jpg-border,#e2e8f0);margin:1rem 0;">
                    <p class="jpg-modal__info" style="font-weight:600;"><?php echo htmlspecialchars($t('apply_apply_data'), ENT_QUOTES, 'UTF-8'); ?></p>

                    <div class="jpg-form-group">
                        <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_cover_letter'), ENT_QUOTES, 'UTF-8'); ?> <span class="jpg-required">*</span></label>
                        <textarea name="cover_letter" rows="4" required class="jpg-form-input jpg-form-textarea"
                                  placeholder="Warum möchtest du bei uns arbeiten?"
                                  minlength="<?php echo $coverMinChars; ?>"></textarea>
                    </div>
                    <?php if ($showCv): ?>
                    <div class="jpg-form-group jpg-form-group--file">
                        <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_cv'), ENT_QUOTES, 'UTF-8'); ?><?php echo $requireCv ? ' <span class="jpg-required">*</span>' : ''; ?></label>
                        <input type="file" name="cv_file" accept=".pdf,.doc,.docx" class="jpg-form-file" <?php echo $requireCv ? 'required' : ''; ?>>
                    </div>
                    <?php endif; ?>

                    <div class="jpg-modal__footer">
                        <button type="button" data-jpg-modal-close class="jpg-modal__btn jpg-modal__btn--cancel"><?php echo htmlspecialchars($t('apply_cancel'), ENT_QUOTES, 'UTF-8'); ?></button>
                        <button type="submit" id="jpgRegSubmit" class="jpg-modal__btn jpg-modal__btn--submit">📩 <?php echo htmlspecialchars($t('apply_register_and_apply'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </div>
                    <p class="jpg-modal__privacy"><?php echo htmlspecialchars((string) $privacyText, ENT_QUOTES, 'UTF-8'); ?>
                        <a href="<?php echo htmlspecialchars((string) $privacyUrl, ENT_QUOTES, 'UTF-8'); ?>" class="jpg-modal__privacy-link">Datenschutzerklärung</a>
                    </p>
                </form>
            </div>

            <!-- Tab: Login -->
            <div class="jpg-modal__tab-content" id="jpgTabLogin">
                <form id="jpgLoginForm" class="jpg-modal__form" novalidate>
                    <input type="hidden" name="_jpg_csrf" value="<?php echo htmlspecialchars((string) ($applyCsrf ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                    <p class="jpg-modal__info"><?php echo htmlspecialchars($t('apply_login_info'), ENT_QUOTES, 'UTF-8'); ?></p>

                    <div class="jpg-form-group">
                        <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_email'), ENT_QUOTES, 'UTF-8'); ?> <span class="jpg-required">*</span></label>
                        <input type="email" name="login_email" required autocomplete="email" class="jpg-form-input" placeholder="max@beispiel.de">
                    </div>
                    <div class="jpg-form-group">
                        <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_password'), ENT_QUOTES, 'UTF-8'); ?> <span class="jpg-required">*</span></label>
                        <input type="password" name="login_password" required autocomplete="current-password" class="jpg-form-input">
                    </div>

                    <div class="jpg-modal__footer">
                        <button type="button" data-jpg-modal-close class="jpg-modal__btn jpg-modal__btn--cancel"><?php echo htmlspecialchars($t('apply_cancel'), ENT_QUOTES, 'UTF-8'); ?></button>
                        <button type="submit" id="jpgLoginSubmit" class="jpg-modal__btn jpg-modal__btn--submit">🔐 <?php echo htmlspecialchars($t('apply_login'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- Normales Bewerbungsformular (eingeloggt oder Login nicht erzwungen) -->
        <form id="jpgApplyForm" class="jpg-modal__form" novalidate>
            <input type="hidden" name="_jpg_csrf" value="<?php echo htmlspecialchars((string) ($applyCsrf ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="text" name="_hp_name" class="jpg-honeypot" aria-hidden="true" tabindex="-1" autocomplete="off">

            <div class="jpg-form-group">
                <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_name'), ENT_QUOTES, 'UTF-8'); ?> <span class="jpg-required">*</span></label>
                <input type="text" name="applicant_name" required autocomplete="name" class="jpg-form-input"
                       placeholder="Max Mustermann"
                       value="<?php echo $currentUser ? htmlspecialchars((string) ($currentUser->display_name ?? ''), ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>
            <div class="jpg-form-group">
                <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_email'), ENT_QUOTES, 'UTF-8'); ?> <span class="jpg-required">*</span></label>
                <input type="email" name="applicant_email" required autocomplete="email" class="jpg-form-input"
                       placeholder="max@beispiel.de"
                       value="<?php echo $currentUser ? htmlspecialchars((string) ($currentUser->email ?? ''), ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>
            <?php if ($showPhone): ?>
            <div class="jpg-form-group">
                <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_phone_optional'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="tel" name="applicant_phone" autocomplete="tel" class="jpg-form-input" placeholder="+49 123 456789">
            </div>
            <?php endif; ?>
            <div class="jpg-form-group">
                <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_cover_letter'), ENT_QUOTES, 'UTF-8'); ?> <span class="jpg-required">*</span></label>
                <textarea name="cover_letter" rows="5" required class="jpg-form-input jpg-form-textarea"
                          placeholder="Warum möchtest du bei uns arbeiten? Was bringst du mit?"
                          minlength="<?php echo $coverMinChars; ?>"></textarea>
            </div>
            <?php if ($showCv): ?>
            <div class="jpg-form-group jpg-form-group--file">
                <label class="jpg-form-label"><?php echo htmlspecialchars($t('apply_cv'), ENT_QUOTES, 'UTF-8'); ?><?php echo $requireCv ? ' <span class="jpg-required">*</span>' : ''; ?></label>
                <input type="file" name="cv_file" accept=".pdf,.doc,.docx" class="jpg-form-file" <?php echo $requireCv ? 'required' : ''; ?>>
                <small class="jpg-form-hint"><?php echo htmlspecialchars($t('apply_cv_hint'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
            <?php endif; ?>

            <div class="jpg-modal__footer">
                <button type="button" data-jpg-modal-close class="jpg-modal__btn jpg-modal__btn--cancel"><?php echo htmlspecialchars($t('apply_cancel'), ENT_QUOTES, 'UTF-8'); ?></button>
                <button type="submit" id="jpgApplySubmit" class="jpg-modal__btn jpg-modal__btn--submit">📩 <?php echo htmlspecialchars($t('apply_submit'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
            <p class="jpg-modal__privacy"><?php echo htmlspecialchars((string) $privacyText, ENT_QUOTES, 'UTF-8'); ?>
                <a href="<?php echo htmlspecialchars((string) $privacyUrl, ENT_QUOTES, 'UTF-8'); ?>" class="jpg-modal__privacy-link">Datenschutzerklärung</a>
            </p>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var modal      = document.getElementById('jpgApplyModal');
    var success    = document.getElementById('jpgApplySuccess');
    var errBox     = document.getElementById('jpgApplyError');
    var siteUrl    = '<?php echo defined('SITE_URL') ? htmlspecialchars((string) SITE_URL, ENT_QUOTES, 'UTF-8') : ''; ?>';
    var jobSlug    = '<?php echo $jobSlugEsc; ?>';
    var jobsBasePath = '<?php echo htmlspecialchars((function_exists('jpg_public_path') ? jpg_public_path('jobs', $publicLang) : '/jobs'), ENT_QUOTES, 'UTF-8'); ?>';
    var registerPath = '<?php echo htmlspecialchars((function_exists('jpg_public_path') ? jpg_public_path('jobs/register', $publicLang) : '/jobs/register'), ENT_QUOTES, 'UTF-8'); ?>';
    var submitText = '📩 <?php echo htmlspecialchars($t('apply_submit'), ENT_QUOTES, 'UTF-8'); ?>';
    var loginText = '🔐 <?php echo htmlspecialchars($t('apply_login'), ENT_QUOTES, 'UTF-8'); ?>';
    var registerApplyText = '📩 <?php echo htmlspecialchars($t('apply_register_and_apply'), ENT_QUOTES, 'UTF-8'); ?>';

    window.jpgOpenApplyModal = function () {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };
    window.jpgCloseApplyModal = function () {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        errBox.style.display = 'none';
    };
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-jpg-apply-open]')) {
            e.preventDefault();
            window.jpgOpenApplyModal();
        }
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) window.jpgCloseApplyModal();
        if (e.target.closest('[data-jpg-modal-close]')) window.jpgCloseApplyModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') window.jpgCloseApplyModal();
    });

    // ── Tab-Navigation ──────────────────────────────────────────────
    var tabBtns = modal.querySelectorAll('.jpg-modal__tab-btn');
    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            tabBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            modal.querySelectorAll('.jpg-modal__tab-content').forEach(function (c) { c.classList.remove('active'); });
            var target = btn.getAttribute('data-tab');
            var el = document.getElementById(target === 'register' ? 'jpgTabRegister' : 'jpgTabLogin');
            if (el) el.classList.add('active');
        });
    });

    // ── Helper: Show Error ──────────────────────────────────────────
    function showError(msg) {
        errBox.textContent   = msg;
        errBox.style.display = 'block';
    }

    // ── Apply Form (logged-in user) ─────────────────────────────────
    var applyForm = document.getElementById('jpgApplyForm');
    if (applyForm) {
        applyForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            errBox.style.display = 'none';
            var btn = document.getElementById('jpgApplySubmit');
            btn.disabled = true;
            btn.textContent = '⏳ Sende…';
            try {
                var res  = await fetch(siteUrl + jobsBasePath + '/' + jobSlug + '/apply', { method: 'POST', body: new FormData(applyForm) });
                var data = await res.json();
                if (data.success) {
                    applyForm.style.display = 'none';
                    success.style.display   = 'block';
                } else {
                    showError(data.error || 'Unbekannter Fehler.');
                    btn.disabled = false; btn.textContent = submitText;
                }
            } catch (err) {
                showError('Netzwerkfehler: ' + err.message);
                btn.disabled = false; btn.textContent = submitText;
            }
        });
    }

    // ── Register + Apply Form ───────────────────────────────────────
    var regForm = document.getElementById('jpgRegisterForm');
    if (regForm) {
        regForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            errBox.style.display = 'none';
            var btn = document.getElementById('jpgRegSubmit');
            btn.disabled = true;
            btn.textContent = '⏳ Registriere…';
            try {
                // Schritt 1: Registrieren
                var regFd = new FormData();
                regFd.append('_jpg_csrf',    regForm.querySelector('[name="_jpg_csrf"]').value);
                regFd.append('job_slug',     jobSlug);
                regFd.append('display_name', regForm.querySelector('[name="display_name"]').value);
                regFd.append('email',        regForm.querySelector('[name="email"]').value);
                regFd.append('password',     regForm.querySelector('[name="password"]').value);
                var phoneEl = regForm.querySelector('[name="phone"]');
                if (phoneEl) regFd.append('phone', phoneEl.value);

                var regRes  = await fetch(siteUrl + registerPath, { method: 'POST', body: regFd });
                var regData = await regRes.json();
                if (!regData.success) {
                    showError(regData.error || 'Registrierung fehlgeschlagen.');
                    btn.disabled = false; btn.textContent = registerApplyText;
                    return;
                }

                // Schritt 2: Bewerbung absenden (jetzt eingeloggt)
                btn.textContent = '⏳ Bewerbung wird gesendet…';
                var applyFd = new FormData();
                applyFd.append('_jpg_csrf',       regData.apply_csrf || regForm.querySelector('[name="_jpg_csrf"]').value);
                applyFd.append('applicant_name',  regForm.querySelector('[name="display_name"]').value);
                applyFd.append('applicant_email', regForm.querySelector('[name="email"]').value);
                if (phoneEl) applyFd.append('applicant_phone', phoneEl.value);
                applyFd.append('cover_letter',    regForm.querySelector('[name="cover_letter"]').value);
                var cvEl = regForm.querySelector('[name="cv_file"]');
                if (cvEl && cvEl.files.length) applyFd.append('cv_file', cvEl.files[0]);

                var applyRes  = await fetch(siteUrl + jobsBasePath + '/' + jobSlug + '/apply', { method: 'POST', body: applyFd });
                var applyData = await applyRes.json();

                if (applyData.success) {
                    regForm.style.display   = 'none';
                    success.style.display   = 'block';
                    var authTabs = document.getElementById('jpgAuthTabs');
                    if (authTabs) authTabs.querySelector('.jpg-modal__tab-nav').style.display = 'none';
                } else if (applyData.require_auth) {
                    showError('Konto erstellt. Bitte melde dich erneut an und sende die Bewerbung danach ab.');
                    btn.disabled = false; btn.textContent = submitText;
                } else {
                    showError(applyData.error || 'Bewerbung konnte nicht abgeschickt werden.');
                    btn.disabled = false; btn.textContent = registerApplyText;
                }
            } catch (err) {
                showError('Netzwerkfehler: ' + err.message);
                btn.disabled = false; btn.textContent = registerApplyText;
            }
        });
    }

    // ── Login Form ──────────────────────────────────────────────────
    var loginForm = document.getElementById('jpgLoginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            errBox.style.display = 'none';
            var btn = document.getElementById('jpgLoginSubmit');
            btn.disabled = true;
            btn.textContent = '⏳ Anmelden…';
            try {
                var fd = new FormData();
                fd.append('email',    loginForm.querySelector('[name="login_email"]').value);
                fd.append('password', loginForm.querySelector('[name="login_password"]').value);
                fd.append('_jpg_csrf', loginForm.querySelector('[name="_jpg_csrf"]').value);

                var res  = await fetch(siteUrl + '/api/auth/login', { method: 'POST', body: fd });
                var data = await res.json();
                if (data.success) {
                    // Nach Login: Seite neuladen, damit Bewerbungsformular mit User-Daten erscheint
                    window.location.reload();
                } else {
                    showError(data.error || 'Anmeldung fehlgeschlagen.');
                    btn.disabled = false; btn.textContent = loginText;
                }
            } catch (err) {
                showError('Netzwerkfehler: ' + err.message);
                btn.disabled = false; btn.textContent = loginText;
            }
        });
    }
})();
</script>
