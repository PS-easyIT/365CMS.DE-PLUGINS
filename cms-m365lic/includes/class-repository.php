<?php
/**
 * CMS M365 License – Repository & Persistence
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LIC_Repository
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    private function db(): \CMS\Database
    {
        return \CMS\Database::instance();
    }

    private function pdo(): \PDO
    {
        return $this->db()->getPdo();
    }

    private function prefix(): string
    {
        return $this->db()->getPrefix();
    }

    /**
     * @return array<string,string>
     */
    public function get_settings(): array
    {
        $defaults = CMS_M365LIC_Catalog::default_settings();

        try {
            $stmt = $this->db()->prepare("SELECT setting_key, setting_value FROM {$this->prefix()}m365lic_settings");
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
            foreach ($rows as $key => $value) {
                $defaults[(string) $key] = (string) $value;
            }
        } catch (\Throwable $e) {
            // ignore and use defaults
        }

        return $defaults;
    }

    /**
     * @param array<string,string> $settings
     */
    public function save_settings(array $settings): void
    {
        $sql = "INSERT INTO {$this->prefix()}m365lic_settings (setting_key, setting_value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        $stmt = $this->db()->prepare($sql);

        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function get_packages(bool $includeInactive = true): array
    {
        $sql = "SELECT * FROM {$this->prefix()}m365lic_packages";
        $params = [];

        if (!$includeInactive) {
            $sql .= ' WHERE is_active = ?';
            $params[] = 1;
        }

        $sql .= ' ORDER BY sort_order ASC, name ASC';
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return array_map([$this, 'hydrate_package'], $rows);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function get_package(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->prefix()}m365lic_packages WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->hydrate_package($row) : null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function get_package_by_slug(string $slug): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->prefix()}m365lic_packages WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->hydrate_package($row) : null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function save_package(array $data): void
    {
        $record = [
            'slug' => (string) ($data['slug'] ?? ''),
            'name' => (string) ($data['name'] ?? ''),
            'kind' => (string) ($data['kind'] ?? 'base'),
            'category' => (string) ($data['category'] ?? 'general'),
            'audience' => (string) ($data['audience'] ?? 'knowledge'),
            'description' => (string) ($data['description'] ?? ''),
            'features_json' => json_encode(array_values(array_unique($data['features'] ?? [])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'tags_json' => json_encode(array_values(array_unique($data['tags'] ?? [])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'prerequisite_tags_json' => json_encode(array_values(array_unique($data['prerequisite_tags'] ?? [])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'public_price' => $this->normalize_price($data['public_price'] ?? null),
            'member_price' => $this->normalize_price($data['member_price'] ?? null),
            'group_price' => $this->normalize_price($data['group_price'] ?? null),
            'currency' => (string) ($data['currency'] ?? 'EUR'),
            'pricing_note' => (string) ($data['pricing_note'] ?? ''),
            'source_note' => (string) ($data['source_note'] ?? ''),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
        ];

        $id = (int) ($data['id'] ?? 0);

        if ($id > 0) {
            $sql = "UPDATE {$this->prefix()}m365lic_packages
                    SET slug = ?, name = ?, kind = ?, category = ?, audience = ?, description = ?,
                        features_json = ?, tags_json = ?, prerequisite_tags_json = ?,
                        public_price = ?, member_price = ?, group_price = ?, currency = ?,
                        pricing_note = ?, source_note = ?, is_active = ?, sort_order = ?
                    WHERE id = ?";
            $params = array_values($record);
            $params[] = $id;
            $this->db()->prepare($sql)->execute($params);
            return;
        }

        $sql = "INSERT INTO {$this->prefix()}m365lic_packages
                (slug, name, kind, category, audience, description, features_json, tags_json, prerequisite_tags_json,
                 public_price, member_price, group_price, currency, pricing_note, source_note, is_active, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db()->prepare($sql)->execute(array_values($record));
    }

    public function reset_catalog_to_defaults(): void
    {
        $this->pdo()->exec("TRUNCATE TABLE {$this->prefix()}m365lic_packages");
        $this->seed_defaults(true);
    }

    public function seed_defaults(bool $force): void
    {
        $settings = CMS_M365LIC_Catalog::default_settings();
        $sqlSetting = "INSERT INTO {$this->prefix()}m365lic_settings (setting_key, setting_value)
                       VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        $stmtSetting = $this->db()->prepare($sqlSetting);
        foreach ($settings as $key => $value) {
            $stmtSetting->execute([$key, $value]);
        }

        if ($force) {
            $this->pdo()->exec("TRUNCATE TABLE {$this->prefix()}m365lic_packages");
        }

        foreach (CMS_M365LIC_Catalog::package_seeds() as $package) {
            if ($force) {
                $this->save_package($package);
                continue;
            }

            $this->sync_seed_package($package);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function get_statistics(): array
    {
        $packages = $this->get_packages(true);
        $activePackages = array_filter($packages, static fn(array $package): bool => !empty($package['is_active']));
        $basePackages = array_filter($activePackages, static fn(array $package): bool => ($package['kind'] ?? '') === 'base');
        $addonPackages = array_filter($activePackages, static fn(array $package): bool => ($package['kind'] ?? '') === 'addon');
        $withPrices = array_filter($activePackages, function (array $package): bool {
            return $package['public_price'] !== null || $package['member_price'] !== null || $package['group_price'] !== null;
        });

        return [
            'packages_total' => count($packages),
            'packages_active' => count($activePackages),
            'packages_base' => count($basePackages),
            'packages_addon' => count($addonPackages),
            'packages_with_prices' => count($withPrices),
            'feature_total' => count(CMS_M365LIC_Catalog::feature_definitions()),
            'preset_total' => count(CMS_M365LIC_Catalog::presets()),
        ];
    }

    /**
     * @return array<string,string>
     */
    public function resolve_pricing_context(?string $requestedTier, ?string $requestedGroupKey = null): array
    {
        $settings = $this->get_settings();
        $tier = in_array((string) $requestedTier, ['public', 'member', 'group'], true)
            ? (string) $requestedTier
            : (string) ($settings['default_pricing_tier'] ?? 'public');

        $groupKey = trim((string) ($requestedGroupKey ?? $settings['default_group_key'] ?? ''));
        $label = match ($tier) {
            'member' => 'Mitglied',
            'group' => $settings['default_group_label'] ?? 'Spezialgruppe',
            default => 'Öffentlich',
        };

        return [
            'tier' => $tier,
            'group_key' => $groupKey,
            'label' => (string) $label,
        ];
    }

    /**
     * @param array<string,mixed> $package
     */
    public function get_price_for_package(array $package, string $tier): ?float
    {
        $field = match ($tier) {
            'member' => 'member_price',
            'group' => 'group_price',
            default => 'public_price',
        };

        if (!isset($package[$field]) || $package[$field] === null || $package[$field] === '') {
            if ($tier === 'group' && $package['member_price'] !== null) {
                return (float) $package['member_price'];
            }
            if ($tier !== 'public' && $package['public_price'] !== null) {
                return (float) $package['public_price'];
            }
            return null;
        }

        return (float) $package[$field];
    }

    /**
     * @return array<string,mixed>
     */
    public function enforce_daily_limit(string $action, string $tier): array
    {
        $settings = $this->get_settings();
        $dateKey = date('Ymd');
        $actorHash = $this->resolve_actor_hash();
        $limit = $this->daily_limit_for_action($settings, $action, $tier);

        $stmt = $this->db()->prepare(
            "SELECT hits FROM {$this->prefix()}m365lic_usage_limits WHERE action_key = ? AND actor_hash = ? AND pricing_tier = ? AND date_key = ? LIMIT 1"
        );
        $stmt->execute([$action, $actorHash, $tier, $dateKey]);
        $hits = (int) ($stmt->fetchColumn() ?: 0);

        if ($hits >= $limit) {
            return [
                'allowed' => false,
                'used' => $hits,
                'limit' => $limit,
                'remaining' => 0,
                'message' => (string) ($settings['limit_notice'] ?? 'Tageslimit erreicht.'),
            ];
        }

        $sql = "INSERT INTO {$this->prefix()}m365lic_usage_limits (action_key, actor_hash, pricing_tier, date_key, hits)
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE hits = hits + 1, updated_at = CURRENT_TIMESTAMP";
        $this->db()->prepare($sql)->execute([$action, $actorHash, $tier, $dateKey]);

        return [
            'allowed' => true,
            'used' => $hits + 1,
            'limit' => $limit,
            'remaining' => max(0, $limit - ($hits + 1)),
            'message' => '',
        ];
    }

    public function export_user_data(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        // Keine direkte Ausgabe nötig – nur Hook-Kompatibilität.
    }

    public function delete_user_data(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $userHash = hash('sha256', 'user:' . $userId);
        $stmt = $this->db()->prepare("DELETE FROM {$this->prefix()}m365lic_usage_limits WHERE actor_hash = ?");
        $stmt->execute([$userHash]);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function hydrate_package(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['is_active'] = (int) ($row['is_active'] ?? 0);
        $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
        $row['public_price'] = $row['public_price'] !== null ? (float) $row['public_price'] : null;
        $row['member_price'] = $row['member_price'] !== null ? (float) $row['member_price'] : null;
        $row['group_price'] = $row['group_price'] !== null ? (float) $row['group_price'] : null;
        $row['features'] = $this->decode_json_list($row['features_json'] ?? '[]');
        $row['tags'] = $this->decode_json_list($row['tags_json'] ?? '[]');
        $row['prerequisite_tags'] = $this->decode_json_list($row['prerequisite_tags_json'] ?? '[]');
        return $row;
    }

    /**
     * @return array<int,string>
     */
    private function decode_json_list(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $decoded)));
    }

    private function normalize_price(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stringValue = str_replace(',', '.', trim((string) $value));
        return is_numeric($stringValue) ? round((float) $stringValue, 2) : null;
    }

    /**
     * @param array<string,mixed> $seed
     */
    private function sync_seed_package(array $seed): void
    {
        $existing = $this->get_package_by_slug((string) ($seed['slug'] ?? ''));
        if ($existing === null) {
            $this->save_package($seed);
            return;
        }

        $seed['id'] = (int) $existing['id'];
        $seed['public_price'] = $existing['public_price'] !== null
            ? $existing['public_price']
            : ($seed['public_price'] ?? null);
        $seed['member_price'] = $existing['member_price'] !== null
            ? $existing['member_price']
            : ($seed['member_price'] ?? null);
        $seed['group_price'] = $existing['group_price'] !== null
            ? $existing['group_price']
            : ($seed['group_price'] ?? null);
        $seed['currency'] = (string) ($existing['currency'] ?? $seed['currency'] ?? 'EUR');
        $seed['pricing_note'] = trim((string) ($existing['pricing_note'] ?? '')) !== ''
            ? (string) $existing['pricing_note']
            : (string) ($seed['pricing_note'] ?? '');
        $seed['source_note'] = trim((string) ($existing['source_note'] ?? '')) !== ''
            ? (string) $existing['source_note']
            : (string) ($seed['source_note'] ?? '');
        $seed['description'] = trim((string) ($existing['description'] ?? '')) !== ''
            ? (string) $existing['description']
            : (string) ($seed['description'] ?? '');
        $seed['is_active'] = (int) ($existing['is_active'] ?? $seed['is_active'] ?? 1);
        $seed['sort_order'] = (int) ($existing['sort_order'] ?? $seed['sort_order'] ?? 0);

        $this->save_package($seed);
    }

    private function resolve_actor_hash(): string
    {
        if (class_exists('CMS\\Auth')) {
            $auth = \CMS\Auth::instance();
            if (method_exists($auth, 'isLoggedIn') && $auth->isLoggedIn()) {
                if (method_exists($auth, 'getUserId')) {
                    return hash('sha256', 'user:' . (int) $auth->getUserId());
                }

                if (method_exists($auth, 'getUser')) {
                    $user = $auth->getUser();
                    $userId = is_array($user) ? (int) ($user['id'] ?? 0) : (int) ($user->id ?? 0);
                    if ($userId > 0) {
                        return hash('sha256', 'user:' . $userId);
                    }
                }
            }
        }

        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
        return hash('sha256', 'ip:' . $ip);
    }

    /**
     * @param array<string,string> $settings
     */
    private function daily_limit_for_action(array $settings, string $action, string $tier): int
    {
        $key = match ($tier) {
            'member' => $action === 'pdf_export' ? 'member_pdf_daily_limit' : 'member_daily_limit',
            'group' => $action === 'pdf_export' ? 'group_pdf_daily_limit' : 'group_daily_limit',
            default => $action === 'pdf_export' ? 'public_pdf_daily_limit' : 'public_daily_limit',
        };

        return max(1, (int) ($settings[$key] ?? 2));
    }
}
