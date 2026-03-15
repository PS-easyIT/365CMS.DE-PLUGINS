<?php
/**
 * CMS M365 License – Packages Page
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_M365LIC_Page_Packages_Trait
{
    public function render_packages_page(): void
    {
        $notice = '';
        $error = '';
        $featureDefinitions = CMS_M365LIC_Catalog::feature_definitions();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('m365lic_packages')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = (string) ($_POST['action'] ?? '');
                if ($action === 'save_package') {
                    $slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
                    $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug) ?: '';
                    if ($slug === '' || trim((string) ($_POST['name'] ?? '')) === '') {
                        $error = 'Name und Slug sind Pflichtfelder.';
                    } else {
                        $features = [];
                        foreach (array_keys($featureDefinitions) as $featureKey) {
                            if (!empty($_POST['features'][$featureKey])) {
                                $features[] = $featureKey;
                            }
                        }

                        self::repo()->save_package([
                            'id' => (int) ($_POST['id'] ?? 0),
                            'slug' => $slug,
                            'name' => trim((string) ($_POST['name'] ?? '')),
                            'kind' => in_array((string) ($_POST['kind'] ?? 'base'), ['base', 'addon'], true) ? (string) $_POST['kind'] : 'base',
                            'category' => trim((string) ($_POST['category'] ?? 'general')),
                            'audience' => trim((string) ($_POST['audience'] ?? 'knowledge')),
                            'pricing_basis' => in_array((string) ($_POST['pricing_basis'] ?? 'per_user'), ['per_user', 'flat_monthly'], true) ? (string) $_POST['pricing_basis'] : 'per_user',
                            'description' => trim((string) ($_POST['description'] ?? '')),
                            'features' => $features,
                            'tags' => self::split_comma_list((string) ($_POST['tags'] ?? '')),
                            'prerequisite_tags' => self::split_comma_list((string) ($_POST['prerequisite_tags'] ?? '')),
                            'public_price' => $_POST['public_price'] ?? null,
                            'member_price' => $_POST['member_price'] ?? null,
                            'group_price' => $_POST['group_price'] ?? null,
                            'currency' => 'EUR',
                            'pricing_note' => trim((string) ($_POST['pricing_note'] ?? '')),
                            'source_note' => trim((string) ($_POST['source_note'] ?? '')),
                            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                        ]);
                        $notice = 'Paket gespeichert.';
                    }
                } elseif ($action === 'reset_catalog') {
                    self::repo()->reset_catalog_to_defaults();
                    $notice = 'Seed-Katalog wurde auf Standard zurückgesetzt.';
                }
            }
        }

        $packages = self::repo()->get_packages(true);
        $editId = max(0, (int) ($_GET['edit'] ?? ($_POST['id'] ?? 0)));
        $editPackage = $editId > 0 ? self::repo()->get_package($editId) : null;

        if ($editPackage === null) {
            $editPackage = [
                'id' => 0,
                'slug' => '',
                'name' => '',
                'kind' => 'base',
                'category' => 'general',
                'audience' => 'knowledge',
                'pricing_basis' => 'per_user',
                'description' => '',
                'features' => [],
                'tags' => [],
                'prerequisite_tags' => [],
                'public_price' => null,
                'member_price' => null,
                'group_price' => null,
                'currency' => 'EUR',
                'pricing_note' => '',
                'source_note' => '',
                'sort_order' => 0,
                'is_active' => 1,
            ];
        }

        $csrfToken = self::generate_nonce('m365lic_packages');
        ?>
        <div class="admin-page-header">
            <div>
                <h2>📦 Paketverwaltung</h2>
                <p>Pflege Basislizenzen, Copilot-Add-ons, Abrechnungsbasis und die Basispreise für Jahresbindung.</p>
            </div>
            <div class="header-actions">
                <a href="?page=m365lic-packages" class="btn btn-secondary">➕ Neues Paket</a>
            </div>
        </div>

        <?php if ($notice !== ''): ?>
        <div class="alert alert-success">✅ <?php echo self::esc($notice); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">❌ <?php echo self::esc($error); ?></div>
        <?php endif; ?>

        <div class="m365lic-admin-grid m365lic-admin-grid--wide">
            <div class="admin-card">
                <h3><?php echo (int) $editPackage['id'] > 0 ? '✏️ Paket bearbeiten' : '➕ Paket anlegen'; ?></h3>
                <p class="m365lic-help-text">Alle Preise hier sind der Basiswert für <strong>1 Jahr Laufzeit mit jährlicher Zahlung</strong>. Die Auswahl im Frontend rechnet daraus +5% bzw. +20% hoch.</p>
                <div class="m365lic-admin-note">Alle Preisfelder dieses Plugins werden in Euro gepflegt. Ein separates Währungsfeld ist daher bewusst entfernt.</div>
                <form method="POST" class="admin-form">
                    <input type="hidden" name="action" value="save_package">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="id" value="<?php echo (int) $editPackage['id']; ?>">
                    <input type="hidden" name="currency" value="EUR">

                    <div class="m365lic-form-grid m365lic-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="pkg_name">Name</label>
                            <input class="form-control" id="pkg_name" type="text" name="name" required value="<?php echo self::esc((string) $editPackage['name']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pkg_slug">Slug</label>
                            <input class="form-control" id="pkg_slug" type="text" name="slug" required value="<?php echo self::esc((string) $editPackage['slug']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pkg_kind">Typ</label>
                            <select class="form-control" id="pkg_kind" name="kind">
                                <option value="base" <?php echo $editPackage['kind'] === 'base' ? 'selected' : ''; ?>>Basislizenz</option>
                                <option value="addon" <?php echo $editPackage['kind'] === 'addon' ? 'selected' : ''; ?>>Add-on</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pkg_category">Kategorie</label>
                            <input class="form-control" id="pkg_category" type="text" name="category" value="<?php echo self::esc((string) $editPackage['category']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pkg_audience">Audience</label>
                            <select class="form-control" id="pkg_audience" name="audience">
                                <option value="knowledge" <?php echo $editPackage['audience'] === 'knowledge' ? 'selected' : ''; ?>>Knowledge</option>
                                <option value="frontline" <?php echo $editPackage['audience'] === 'frontline' ? 'selected' : ''; ?>>Frontline</option>
                                <option value="all" <?php echo $editPackage['audience'] === 'all' ? 'selected' : ''; ?>>All</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pricing_basis">Preisart</label>
                            <select class="form-control" id="pricing_basis" name="pricing_basis">
                                <option value="per_user" <?php echo ($editPackage['pricing_basis'] ?? 'per_user') === 'per_user' ? 'selected' : ''; ?>>Pro Benutzer / Monat</option>
                                <option value="flat_monthly" <?php echo ($editPackage['pricing_basis'] ?? '') === 'flat_monthly' ? 'selected' : ''; ?>>Fixpreis / Monat</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pkg_desc">Beschreibung</label>
                        <textarea class="form-control" id="pkg_desc" name="description" rows="3"><?php echo self::esc((string) $editPackage['description']); ?></textarea>
                    </div>

                    <div class="m365lic-form-grid m365lic-form-grid--3">
                        <div class="form-group">
                            <label class="form-label" for="pkg_price_public">Public Basispreis / Monat</label>
                            <input class="form-control" id="pkg_price_public" type="text" name="public_price" value="<?php echo self::esc((string) ($editPackage['public_price'] ?? '')); ?>" placeholder="z. B. 12.50">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pkg_price_member">Member Basispreis / Monat</label>
                            <input class="form-control" id="pkg_price_member" type="text" name="member_price" value="<?php echo self::esc((string) ($editPackage['member_price'] ?? '')); ?>" placeholder="z. B. 11.90">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pkg_price_group">Spezial Basispreis / Monat</label>
                            <input class="form-control" id="pkg_price_group" type="text" name="group_price" value="<?php echo self::esc((string) ($editPackage['group_price'] ?? '')); ?>" placeholder="z. B. 10.90">
                        </div>
                    </div>

                    <div class="m365lic-form-grid m365lic-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="pkg_currency">Währung</label>
                            <input class="form-control" id="pkg_currency" type="text" value="EUR / €" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pkg_sort">Sortierung</label>
                            <input class="form-control" id="pkg_sort" type="number" name="sort_order" min="0" value="<?php echo (int) $editPackage['sort_order']; ?>">
                        </div>
                    </div>

                    <div class="m365lic-form-grid m365lic-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="pkg_tags">Tags (kommagetrennt)</label>
                            <input class="form-control" id="pkg_tags" type="text" name="tags" value="<?php echo self::esc(implode(', ', $editPackage['tags'] ?? [])); ?>" placeholder="copilot_enterprise_eligible, teams, security">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pkg_prereq">Voraussetzungs-Tags (kommagetrennt)</label>
                            <input class="form-control" id="pkg_prereq" type="text" name="prerequisite_tags" value="<?php echo self::esc(implode(', ', $editPackage['prerequisite_tags'] ?? [])); ?>" placeholder="copilot_business_eligible">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pkg_pricing_note">Pricing-Hinweis</label>
                        <input class="form-control" id="pkg_pricing_note" type="text" name="pricing_note" value="<?php echo self::esc((string) ($editPackage['pricing_note'] ?? '')); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pkg_source_note">Source-Hinweis</label>
                        <input class="form-control" id="pkg_source_note" type="text" name="source_note" value="<?php echo self::esc((string) ($editPackage['source_note'] ?? '')); ?>">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input id="pkg_active" type="checkbox" name="is_active" value="1" <?php echo !empty($editPackage['is_active']) ? 'checked' : ''; ?>>
                            Paket aktiv in Empfehlungen berücksichtigen
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Feature-Matrix</label>
                        <div class="m365lic-feature-grid">
                            <?php foreach ($featureDefinitions as $featureKey => $feature): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="features[<?php echo self::esc($featureKey); ?>]" value="1" <?php echo in_array($featureKey, $editPackage['features'] ?? [], true) ? 'checked' : ''; ?>>
                                <span>
                                    <strong><?php echo self::esc((string) $feature['label']); ?></strong>
                                    <small><?php echo self::esc((string) $feature['description']); ?></small>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">💾 Paket speichern</button>
                </form>
            </div>

            <div class="admin-card">
                <div class="m365lic-inline-head">
                    <h3>🧪 Seed-Katalog</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_catalog">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">↺ Seed resetten</button>
                    </form>
                </div>
                <p class="m365lic-help-text">Setzt alle Pakete auf den mitgelieferten Katalog zurück. Manuell gepflegte Preise und Änderungen werden dadurch überschrieben.</p>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Typ</th>
                                <th>Preisart</th>
                                <th>Preise</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $package): ?>
                            <tr>
                                <td>
                                    <strong><?php echo self::esc((string) $package['name']); ?></strong><br>
                                    <span class="m365lic-muted"><?php echo self::esc((string) $package['slug']); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo ($package['kind'] ?? '') === 'addon' ? 'inactive' : 'active'; ?>">
                                        <?php echo self::esc((string) $package['kind']); ?>
                                    </span>
                                </td>
                                <td><?php echo self::esc((string) (($package['pricing_basis'] ?? 'per_user') === 'flat_monthly' ? 'Fixpreis' : 'pro Benutzer')); ?></td>
                                <td>
                                    <div class="m365lic-price-stack">
                                        <span>Public: <strong><?php echo $package['public_price'] !== null ? self::esc(number_format((float) $package['public_price'], 2, ',', '.')) . ' €' : '—'; ?></strong></span>
                                        <span>Member: <strong><?php echo $package['member_price'] !== null ? self::esc(number_format((float) $package['member_price'], 2, ',', '.')) . ' €' : '—'; ?></strong></span>
                                        <span>Spezial: <strong><?php echo $package['group_price'] !== null ? self::esc(number_format((float) $package['group_price'], 2, ',', '.')) . ' €' : '—'; ?></strong></span>
                                    </div>
                                </td>
                                <td>
                                    <a href="?page=m365lic-packages&edit=<?php echo (int) $package['id']; ?>" class="btn btn-secondary btn-sm">✏️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
