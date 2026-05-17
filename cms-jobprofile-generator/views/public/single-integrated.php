<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * View: Theme-integrierte Job-Einzelansicht
 *
 * @var object        $profile
 * @var array<object> $tasks
 * @var array<object> $requirements
 * @var array         $benefits
 * @var array<object> $skills
 * @var string        $company
 * @var string        $jsonld
 * @var string        $applyCsrf   CSRF-Token für Bewerbungsformular
 *
 * @since   0.0.1
 * @package CMS_JobProfileGenerator
 */

$esc = function (string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
};
?>
<div class="jpg-public">
    <div class="jpg-job-card">

        <!-- Header -->
        <div class="jpg-job-header">
            <div>
                <h1><?php echo $esc($profile->title); ?></h1>
                <div class="jpg-job-meta">
                    <?php if (!empty($profile->location)): ?>
                    <span>📍 <?php echo $esc($profile->location); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($profile->employment_type)): ?>
                    <span>💼 <?php echo $esc(match ($profile->employment_type) {
                        'fulltime'  => 'Vollzeit',
                        'parttime'  => 'Teilzeit',
                        'contract'  => 'Freelance',
                        'temporary' => 'Befristet',
                        'intern'    => 'Praktikum',
                        'minijob'   => 'Minijob',
                        default     => $profile->employment_type,
                    }); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($profile->experience_level)): ?>
                    <span>⭐ <?php echo $esc(match ($profile->experience_level) {
                        'junior' => 'Junior',
                        'mid'    => 'Mid-Level',
                        'senior' => 'Senior',
                        'lead'   => 'Lead',
                        default  => $profile->experience_level,
                    }); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($profile->salary_min) || !empty($profile->salary_max)): ?>
            <div>
                <span class="jpg-salary-badge">
                    💰 <?php
                    if ($profile->salary_min && $profile->salary_max) {
                        echo number_format((float) $profile->salary_min, 0, ',', '.') . ' – '
                           . number_format((float) $profile->salary_max, 0, ',', '.') . ' €';
                    } elseif ($profile->salary_min) {
                        echo 'ab ' . number_format((float) $profile->salary_min, 0, ',', '.') . ' €';
                    } else {
                        echo 'bis ' . number_format((float) $profile->salary_max, 0, ',', '.') . ' €';
                    }
                    ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="jpg-job-body">

            <?php if (!empty($profile->summary)): ?>
            <div class="jpg-section">
                <h2>Über die Stelle</h2>
                <p><?php echo nl2br($esc($profile->summary)); ?></p>
            </div>
            <?php endif; ?>

            <div class="jpg-job-two-col">
                <div>
                    <!-- Aufgaben -->
                    <?php if (!empty($tasks)): ?>
                    <div class="jpg-section">
                        <h2>Ihre Aufgaben</h2>
                        <ul>
                        <?php foreach ($tasks as $task): ?>
                            <li><?php echo $esc($task->task_text); ?></li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Anforderungen -->
                    <?php if (!empty($requirements)): ?>
                    <div class="jpg-section">
                        <h2>Ihr Profil</h2>
                        <?php
                        $must = array_filter($requirements, fn($r) => ($r->type ?? 'must') === 'must');
                        $nice = array_filter($requirements, fn($r) => ($r->type ?? 'must') === 'nice');
                        ?>
                        <?php if ($must): ?>
                        <h3>Anforderungen</h3>
                        <ul>
                        <?php foreach ($must as $r): ?>
                            <li><?php echo $esc($r->description ?? $r->requirement_text ?? ''); ?></li>
                        <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        <?php if ($nice): ?>
                        <h3>Von Vorteil</h3>
                        <ul>
                        <?php foreach ($nice as $r): ?>
                            <li><?php echo $esc($r->description ?? $r->requirement_text ?? ''); ?></li>
                        <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="jpg-aside">
                    <!-- Benefits -->
                    <?php if (!empty($benefits)): ?>
                    <div class="jpg-aside-box">
                        <h2>Das bieten wir</h2>
                        <ul>
                        <?php foreach ($benefits as $b): ?>
                            <li class="jpg-benefit jpg-benefit-<?php echo $esc($b->source ?? 'job'); ?>"><?php echo (!empty($b->icon) ? $esc($b->icon) . ' ' : ''); echo $esc($b->title ?? ''); ?></li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Skills -->
                    <?php if (!empty($skills)): ?>
                    <div class="jpg-aside-box">
                        <h2>Gefragt</h2>
                        <div class="jpg-skill-tags">
                        <?php foreach ($skills as $s): ?>
                            <span class="jpg-skill-tag"><?php echo $esc($s->skill_name ?? ''); ?></span>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Phase 6.3: Lerne dein Team kennen (cms-experts) -->
        <?php if (!empty($experts)): ?>
        <div class="jpg-section">
            <h2>👥 Lerne dein Team kennen</h2>
            <div class="jpg-team-grid">
                <?php foreach ($experts as $expert): ?>
                <div class="jpg-team-card">
                    <?php if (!empty($expert->avatar_url)): ?>
                    <img src="<?php echo $esc($expert->avatar_url); ?>"
                         alt="<?php echo $esc($expert->name); ?>"
                         class="jpg-team-avatar__img">
                    <?php else: ?>
                    <div class="jpg-team-avatar--placeholder">👤</div>
                    <?php endif; ?>
                    <div class="jpg-team-name"><?php echo $esc($expert->name); ?></div>
                    <?php if (!empty($expert->job_title)): ?>
                    <div class="jpg-team-title"><?php echo $esc($expert->job_title); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($expert->bio_short)): ?>
                    <p class="jpg-team-bio"><?php echo $esc($expert->bio_short); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bewerben / Apply-Modal -->
        <div class="jpg-apply-section" id="apply">
            <h2>Jetzt bewerben</h2>
            <?php if (!empty($profile->description)): ?>
            <div class="jpg-apply-desc"><?php echo nl2br($esc(strip_tags((string) $profile->description))); ?></div>
            <?php endif; ?>
            <button type="button" class="jpg-btn-apply" onclick="jpgOpenApplyModal()">
                📩 Jetzt bewerben
            </button>
        </div>

    </div><!-- /.jpg-job-body -->

    <!-- Google for Jobs JSON-LD -->
    <?php if (!empty($jsonld)): ?>
    <script type="application/ld+json"><?php echo $jsonld; ?></script>
    <?php endif; ?>

</div><!-- /.jpg-public -->

<!-- ====================================================
     Bewerbungs-Modal
     ==================================================== -->
<div id="jpgApplyModal" class="jpg-modal">
    <div class="jpg-modal__box">
        <div class="jpg-modal__header">
            <h3 class="jpg-modal__title">📩 Bewerbung: <?php echo htmlspecialchars($profile->title, ENT_QUOTES); ?></h3>
            <button type="button" onclick="jpgCloseApplyModal()" class="jpg-modal__close" aria-label="Schließen">&times;</button>
        </div>

        <!-- Erfolgs-Banner -->
        <div id="jpgApplySuccess" class="jpg-modal__banner jpg-modal__banner--success">
            ✅ <strong>Bewerbung eingereicht!</strong> Wir melden uns so schnell wie möglich.
        </div>
        <!-- Fehler-Banner -->
        <div id="jpgApplyError" class="jpg-modal__banner jpg-modal__banner--error"></div>

        <form id="jpgApplyForm" class="jpg-modal__form" novalidate>
            <input type="hidden" name="_jpg_csrf" value="<?php echo htmlspecialchars($applyCsrf ?? '', ENT_QUOTES); ?>">
            <!-- Honeypot -->
            <input type="text" name="_hp_name" class="jpg-honeypot" aria-hidden="true" tabindex="-1" autocomplete="off">

            <div class="jpg-form-group">
                <label class="jpg-form-label">
                    Vollständiger Name <span class="jpg-required">*</span>
                </label>
                <input type="text" name="applicant_name" required autocomplete="name"
                       class="jpg-form-input"
                       placeholder="Max Mustermann">
            </div>

            <div class="jpg-form-group">
                <label class="jpg-form-label">
                    E-Mail-Adresse <span class="jpg-required">*</span>
                </label>
                <input type="email" name="applicant_email" required autocomplete="email"
                       class="jpg-form-input"
                       placeholder="max@beispiel.de">
            </div>

            <div class="jpg-form-group">
                <label class="jpg-form-label">
                    Telefon (optional)
                </label>
                <input type="tel" name="applicant_phone" autocomplete="tel"
                       class="jpg-form-input"
                       placeholder="+49 123 456789">
            </div>

            <div class="jpg-form-group">
                <label class="jpg-form-label">
                    Anschreiben <span class="jpg-required">*</span>
                </label>
                <textarea name="cover_letter" rows="5" required
                          class="jpg-form-input jpg-form-textarea"
                          placeholder="Warum möchtest du bei uns arbeiten? Was bringst du mit?"></textarea>
            </div>

            <div class="jpg-form-group jpg-form-group--file">
                <label class="jpg-form-label">
                    Lebenslauf / CV (PDF oder Word, max. 5 MB)
                </label>
                <input type="file" name="cv_file" accept=".pdf,.doc,.docx"
                       class="jpg-form-file">
                <small class="jpg-form-hint">Optionale Datei – max. 5 MB, PDF oder Word</small>
            </div>

            <div class="jpg-modal__footer">
                <button type="button" onclick="jpgCloseApplyModal()"
                        class="jpg-modal__btn jpg-modal__btn--cancel">
                    Abbrechen
                </button>
                <button type="submit" id="jpgApplySubmit"
                        class="jpg-modal__btn jpg-modal__btn--submit">
                    📩 Bewerbung absenden
                </button>
            </div>

            <p class="jpg-modal__privacy">
                Mit dem Absenden stimmst du der Verarbeitung deiner Daten gemäß unserer
                <a href="/datenschutz" class="jpg-modal__privacy-link">Datenschutzerklärung</a> zu.
                Deine Daten werden ausschließlich zur Bearbeitung deiner Bewerbung verwendet.
            </p>
        </form>
    </div>
</div>

<script>
(function () {
    var modal   = document.getElementById('jpgApplyModal');
    var form    = document.getElementById('jpgApplyForm');
    var success = document.getElementById('jpgApplySuccess');
    var errBox  = document.getElementById('jpgApplyError');
    var submitBtn = document.getElementById('jpgApplySubmit');

    window.jpgOpenApplyModal = function () {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };
    window.jpgCloseApplyModal = function () {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        errBox.style.display = 'none';
    };

    // Schließen per Klick auf Overlay
    modal.addEventListener('click', function (e) {
        if (e.target === modal) window.jpgCloseApplyModal();
    });

    // Schließen per Escape-Taste
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') window.jpgCloseApplyModal();
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errBox.style.display  = 'none';
        success.style.display = 'none';
        submitBtn.disabled    = true;
        submitBtn.textContent = '⏳ Sende…';

        try {
            var fd  = new FormData(form);
            var res = await fetch('<?php echo defined('SITE_URL') ? htmlspecialchars(SITE_URL, ENT_QUOTES) : ''; ?>/jobs/<?php echo htmlspecialchars($profile->slug, ENT_QUOTES); ?>/apply', {
                method: 'POST',
                body: fd,
            });
            var text = await res.text();
            var data;
            try { data = JSON.parse(text); }
            catch (_) { throw new Error('Ungültige Server-Antwort. Bitte versuche es erneut.'); }
            if (data.success) {
                form.style.display    = 'none';
                success.style.display = 'block';
            } else {
                errBox.textContent    = data.error || 'Unbekannter Fehler. Bitte versuche es erneut.';
                errBox.style.display  = 'block';
                submitBtn.disabled    = false;
                submitBtn.textContent = '📩 Bewerbung absenden';
            }
        } catch (err) {
            errBox.textContent   = 'Netzwerkfehler: ' + err.message;
            errBox.style.display = 'block';
            submitBtn.disabled   = false;
            submitBtn.textContent = '📩 Bewerbung absenden';
        }
    });
})();
</script>
