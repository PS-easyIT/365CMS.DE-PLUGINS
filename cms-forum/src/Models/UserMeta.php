<?php
/**
 * CMS Forum – UserMeta Model
 *
 * Forum-spezifische Benutzerdaten (Beitrags-Zähler, Rang, Signatur, Ban-Status).
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class UserMeta
{
    public const string TABLE = 'cmsforum_user_meta';

    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    private function db(): \CMS\Database
    {
        return \CMS\Database::instance();
    }

    private function table(): string
    {
        return $this->db()->prefix() . self::TABLE;
    }

    /**
     * User-Meta per User-ID laden – erstellt automatisch einen Eintrag, falls keiner existiert.
     */
    public function findOrCreate(int $userId): object
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);

        if ($row) {
            return $row;
        }

        // Automatisch erstellen
        $this->db()->prepare(
            "INSERT INTO {$this->table()} (user_id) VALUES (?)"
        )->execute([$userId]);

        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * User-Meta per User-ID laden.
     */
    public function findByUserId(int $userId): ?object
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table()} WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Profil-Daten aktualisieren.
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $meta = $this->findOrCreate($userId);

        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET custom_title = ?, signature = ?, location = ?, website = ?, updated_at = NOW() WHERE user_id = ?"
        );
        return $stmt->execute([
            $data['custom_title'] ?? $meta->custom_title,
            $data['signature'] ?? $meta->signature,
            $data['location'] ?? $meta->location,
            $data['website'] ?? $meta->website,
            $userId,
        ]);
    }

    /**
     * Beitrags-Zähler erhöhen.
     */
    public function incrementPostCount(int $userId): void
    {
        $this->findOrCreate($userId);
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET post_count = post_count + 1, last_active = NOW() WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
    }

    /**
     * Thread-Zähler erhöhen.
     */
    public function incrementThreadCount(int $userId): void
    {
        $this->findOrCreate($userId);
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET thread_count = thread_count + 1 WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
    }

    /**
     * Rang automatisch basierend auf Beitragszahl aktualisieren.
     */
    public function updateRank(int $userId): void
    {
        $p = $this->db()->prefix();
        $meta = $this->findOrCreate($userId);

        // Passenden Rang anhand der Beitrags-Anzahl ermitteln
        $stmt = $this->db()->prepare(
            "SELECT id FROM {$p}cmsforum_ranks WHERE is_special = 0 AND min_posts <= ? ORDER BY min_posts DESC LIMIT 1"
        );
        $stmt->execute([$meta->post_count]);
        $rankId = $stmt->fetchColumn();

        if ($rankId) {
            $this->db()->prepare("UPDATE {$this->table()} SET rank_id = ? WHERE user_id = ?")->execute([(int) $rankId, $userId]);
        }
    }

    /**
     * Benutzer sperren.
     */
    public function ban(int $userId, string $reason, ?\DateTimeInterface $expiresAt = null): bool
    {
        $this->findOrCreate($userId);
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET is_banned = 1, ban_reason = ?, ban_expires = ?, updated_at = NOW() WHERE user_id = ?"
        );
        return $stmt->execute([
            $reason,
            $expiresAt?->format('Y-m-d H:i:s'),
            $userId,
        ]);
    }

    /**
     * Sperre aufheben.
     */
    public function unban(int $userId): bool
    {
        $stmt = $this->db()->prepare(
            "UPDATE {$this->table()} SET is_banned = 0, ban_reason = NULL, ban_expires = NULL, updated_at = NOW() WHERE user_id = ?"
        );
        return $stmt->execute([$userId]);
    }

    /**
     * Prüfe ob Benutzer gesperrt ist.
     */
    public function isBanned(int $userId): bool
    {
        $meta = $this->findByUserId($userId);
        if (!$meta || !$meta->is_banned) {
            return false;
        }

        // Temporäre Sperre: Abgelaufen?
        if ($meta->ban_expires !== null) {
            $expires = new \DateTimeImmutable($meta->ban_expires);
            if ($expires < new \DateTimeImmutable()) {
                $this->unban($userId);
                return false;
            }
        }

        return true;
    }

    /**
     * Letzte Aktivität aktualisieren.
     */
    public function touch(int $userId): void
    {
        $this->findOrCreate($userId);
        $stmt = $this->db()->prepare("UPDATE {$this->table()} SET last_active = NOW() WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    /**
     * Top-Benutzer nach Beitragszahl.
     *
     * @return array<int, object>
     */
    public function findTopPosters(int $limit = 10): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT um.*, u.username, u.email AS user_email, r.name AS rank_name, r.color AS rank_color
             FROM {$this->table()} um
             LEFT JOIN {$p}users u ON u.id = um.user_id
             LEFT JOIN {$p}cmsforum_ranks r ON r.id = um.rank_id
             WHERE um.post_count > 0
             ORDER BY um.post_count DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
