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
                    } elseif ($this->is_duplicate_package_slug($slug, (int) ($_POST['id'] ?? 0))) {
                        $error = 'Der Slug ist bereits vergeben. Bitte einen eindeutigen Slug verwenden.';
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
        $editPackageEffectivePrices = is_array($editPackage) ? self::repo()->get_effective_price_map($editPackage) : [];

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
                <div class="m365lic-admin-note">Wenn du nur <strong>einen</strong> Preis pflegst, wird dieser automatisch als Standard für Public, Member und Spezialbereich übernommen. Sobald mehrere Felder gepflegt sind, gelten die Bereiche wieder separat.</div>
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

                    <?php
                    $publicStatus = self::get_price_status_meta($editPackageEffectivePrices['public_price'] ?? ['value' => null, 'inherited' => false, 'source' => 'public_price'], 'public_price');
                    $memberStatus = self::get_price_status_meta($editPackageEffectivePrices['member_price'] ?? ['value' => null, 'inherited' => false, 'source' => 'member_price'], 'member_price');
                    $groupStatus = self::get_price_status_meta($editPackageEffectivePrices['group_price'] ?? ['value' => null, 'inherited' => false, 'source' => 'group_price'], 'group_price');
                    ?>
                    <div class="m365lic-inline-head m365lic-inline-head--pricing">
                        <div>
                            <p class="m365lic-section-kicker">Preislogik</p>
                            <p class="m365lic-help-text">Pflege bei Bedarf drei getrennte Bereichspreise – oder kopiere einen gepflegten Wert mit einem Klick in alle Bereiche.</p>
                        </div>
                        <div class="m365lic-price-actions">
                            <button type="button" class="btn btn-secondary btn-sm" data-m365lic-copy-price>↔ Preis für alle Bereiche übernehmen</button>
                            <span class="m365lic-muted" data-m365lic-copy-price-feedback aria-live="polite"></span>
                        </div>
                    </div>

                    <div class="m365lic-form-grid m365lic-form-grid--3">
                        <div class="form-group" data-m365lic-price-field="public_price">
                            <label class="form-label" for="pkg_price_public">Public Basispreis / Monat</label>
                            <input class="form-control" id="pkg_price_public" type="text" name="public_price" value="<?php echo self::esc((string) ($editPackage['public_price'] ?? '')); ?>" placeholder="z. B. 12.50" data-m365lic-price-input="public_price" data-m365lic-price-label="Public">
                            <small class="m365lic-help-text">Leer lassen, wenn der gleiche Preis wie in einem anderen Bereich gelten soll.</small>
                            <div class="m365lic-price-status" data-m365lic-price-status="public_price">
                                <span class="m365lic-status-pill m365lic-status-pill--<?php echo self::esc($publicStatus['type']); ?>" data-m365lic-price-badge><?php echo self::esc($publicStatus['label']); ?></span>
                                <span class="m365lic-price-status__text" data-m365lic-price-text><?php echo self::esc($publicStatus['detail']); ?></span>
                            </div>
                        </div>
                        <div class="form-group" data-m365lic-price-field="member_price">
                            <label class="form-label" for="pkg_price_member">Member Basispreis / Monat</label>
                            <input class="form-control" id="pkg_price_member" type="text" name="member_price" value="<?php echo self::esc((string) ($editPackage['member_price'] ?? '')); ?>" placeholder="z. B. 11.90" data-m365lic-price-input="member_price" data-m365lic-price-label="Member">
                            <small class="m365lic-help-text">Ohne eigenen Memberpreis greift automatisch der einzige gepflegte Paketpreis.</small>
                            <div class="m365lic-price-status" data-m365lic-price-status="member_price">
                                <span class="m365lic-status-pill m365lic-status-pill--<?php echo self::esc($memberStatus['type']); ?>" data-m365lic-price-badge><?php echo self::esc($memberStatus['label']); ?></span>
                                <span class="m365lic-price-status__text" data-m365lic-price-text><?php echo self::esc($memberStatus['detail']); ?></span>
                            </div>
                        </div>
                        <div class="form-group" data-m365lic-price-field="group_price">
                            <label class="form-label" for="pkg_price_group">Spezial Basispreis / Monat</label>
                            <input class="form-control" id="pkg_price_group" type="text" name="group_price" value="<?php echo self::esc((string) ($editPackage['group_price'] ?? '')); ?>" placeholder="z. B. 10.90" data-m365lic-price-input="group_price" data-m365lic-price-label="Spezial">
                            <small class="m365lic-help-text">Auch Spezialpreise erben automatisch, solange nur ein einzelner Preis hinterlegt ist.</small>
                            <div class="m365lic-price-status" data-m365lic-price-status="group_price">
                                <span class="m365lic-status-pill m365lic-status-pill--<?php echo self::esc($groupStatus['type']); ?>" data-m365lic-price-badge><?php echo self::esc($groupStatus['label']); ?></span>
                                <span class="m365lic-price-status__text" data-m365lic-price-text><?php echo self::esc($groupStatus['detail']); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($editPackageEffectivePrices !== []): ?>
                    <div class="m365lic-admin-note">
                        <strong>Effektive Preise aktuell:</strong>
                        <div class="m365lic-effective-price-list">
                            <?php foreach (['public_price' => 'Public', 'member_price' => 'Member', 'group_price' => 'Spezial'] as $priceField => $priceLabel): ?>
                            <?php $priceStatus = self::get_price_status_meta($editPackageEffectivePrices[$priceField] ?? ['value' => null, 'inherited' => false, 'source' => $priceField], $priceField); ?>
                            <div class="m365lic-effective-price-row">
                                <span><?php echo self::esc($priceLabel); ?> <strong><?php echo ($editPackageEffectivePrices[$priceField]['value'] ?? null) !== null ? self::esc(number_format((float) $editPackageEffectivePrices[$priceField]['value'], 2, ',', '.')) . ' €' : '—'; ?></strong></span>
                                <span class="m365lic-status-pill m365lic-status-pill--<?php echo self::esc($priceStatus['type']); ?>"><?php echo self::esc($priceStatus['label']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

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
                    <button type="button" class="btn btn-danger btn-sm" data-m365lic-open-modal="m365licResetCatalogModal">↺ Seed resetten</button>
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
                            <?php $effectivePrices = self::repo()->get_effective_price_map($package); ?>
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
                                        <?php foreach (['public_price' => 'Public', 'member_price' => 'Member', 'group_price' => 'Spezial'] as $priceField => $priceLabel): ?>
                                        <?php $priceStatus = self::get_price_status_meta($effectivePrices[$priceField] ?? ['value' => null, 'inherited' => false, 'source' => $priceField], $priceField); ?>
                                        <span class="m365lic-price-stack__row">
                                            <span><?php echo self::esc($priceLabel); ?>: <strong><?php echo ($effectivePrices[$priceField]['value'] ?? null) !== null ? self::esc(number_format((float) $effectivePrices[$priceField]['value'], 2, ',', '.')) . ' €' : '—'; ?></strong></span>
                                            <span class="m365lic-price-stack__meta">
                                                <span class="m365lic-status-pill m365lic-status-pill--<?php echo self::esc($priceStatus['type']); ?>"><?php echo self::esc($priceStatus['label']); ?></span>
                                                <?php if ($priceStatus['source_detail'] !== ''): ?>
                                                <small><?php echo self::esc($priceStatus['source_detail']); ?></small>
                                                <?php endif; ?>
                                            </span>
                                        </span>
                                        <?php endforeach; ?>
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

        <div id="m365licResetCatalogModal" class="modal" style="display:none;">
            <div class="modal-content m365lic-modal-content">
                <div class="modal-header">
                    <h3>🗑️ Seed-Katalog zurücksetzen</h3>
                    <button type="button" class="modal-close" aria-label="Modal schließen" data-m365lic-close-modal="m365licResetCatalogModal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Der komplette Paketkatalog wird auf die Standardwerte zurückgesetzt.</p>
                    <p class="m365lic-danger-note">⚠️ Manuell gepflegte Preise, Hinweise und Aktiv-Status können dabei überschrieben werden.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-m365lic-close-modal="m365licResetCatalogModal">Abbrechen</button>
                    <form method="POST" class="m365lic-inline-form">
                        <input type="hidden" name="action" value="reset_catalog">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <button type="submit" class="btn btn-danger">↺ Jetzt zurücksetzen</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * @param array{value:?float,inherited?:bool,source?:string} $effectivePrice
     * @return array{type:string,label:string,detail:string,source_detail:string}
     */
    private static function get_price_status_meta(array $effectivePrice, string $field): array
    {
        $value = $effectivePrice['value'] ?? null;
        $source = (string) ($effectivePrice['source'] ?? $field);
        $isInherited = !empty($effectivePrice['inherited']);

        if ($value === null) {
            return [
                'type' => 'empty',
                'label' => 'kein Preis',
                'detail' => 'Noch kein Preis hinterlegt.',
                'source_detail' => '',
            ];
        }

        if ($isInherited) {
            $sourceLabel = self::price_field_label($source);

            return [
                'type' => 'inherited',
                'label' => 'geerbt',
                'detail' => 'Verwendet aktuell den Preis aus ' . $sourceLabel . '.',
                'source_detail' => 'von ' . $sourceLabel,
            ];
        }

        return [
            'type' => 'direct',
            'label' => 'direkt gepflegt',
            'detail' => 'Eigener Preis für ' . self::price_field_label($field) . '.',
            'source_detail' => '',
        ];
    }

    private static function price_field_label(string $field): string
    {
        return match ($field) {
            'member_price' => 'Member',
            'group_price' => 'Spezial',
            default => 'Public',
        };
    }

    private function is_duplicate_package_slug(string $slug, int $currentId): bool
    {
        $existing = self::repo()->get_package_by_slug($slug);

        return is_array($existing) && (int) ($existing['id'] ?? 0) !== $currentId;
    }
}
