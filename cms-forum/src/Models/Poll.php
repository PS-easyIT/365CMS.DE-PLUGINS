<?php
/**
 * CMS Forum – Poll Model
 *
 * @package CMS_Forum\Models
 */

declare(strict_types=1);

namespace CMS_Forum\Models;

if (!defined('ABSPATH')) {
    exit;
}

final class Poll
{
    public const string TABLE         = 'cmsforum_polls';
    public const string OPTIONS_TABLE = 'cmsforum_poll_options';
    public const string VOTES_TABLE   = 'cmsforum_poll_votes';

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

    /**
     * Umfrage per Thread laden.
     */
    public function findByThread(int $threadId): ?object
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare("SELECT * FROM {$p}" . self::TABLE . " WHERE thread_id = ?");
        $stmt->execute([$threadId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Umfrage-Optionen laden.
     *
     * @return array<int, object>
     */
    public function getOptions(int $pollId): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare("SELECT * FROM {$p}" . self::OPTIONS_TABLE . " WHERE poll_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$pollId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Neue Umfrage erstellen.
     */
    public function create(int $threadId, string $question, int $maxChoices = 1, bool $isPublic = true, ?string $endsAt = null): int
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "INSERT INTO {$p}" . self::TABLE . " (thread_id, question, max_choices, is_public, ends_at) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$threadId, $question, $maxChoices, (int) $isPublic, $endsAt]);
        return (int) $this->db()->getPdo()->lastInsertId();
    }

    /**
     * Option hinzufügen.
     */
    public function addOption(int $pollId, string $text, int $sortOrder = 0): int
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "INSERT INTO {$p}" . self::OPTIONS_TABLE . " (poll_id, text, sort_order) VALUES (?, ?, ?)"
        );
        $stmt->execute([$pollId, $text, $sortOrder]);
        return (int) $this->db()->getPdo()->lastInsertId();
    }

    /**
     * Abstimmen.
     */
    public function vote(int $pollId, int $optionId, int $userId): bool
    {
        $p = $this->db()->prefix();

        // Prüfe ob bereits abgestimmt
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM {$p}" . self::VOTES_TABLE . " WHERE poll_id = ? AND user_id = ?"
        );
        $stmt->execute([$pollId, $userId]);
        $existingVotes = (int) $stmt->fetchColumn();

        // Max. Stimmen prüfen
        $poll = $this->findById($pollId);
        if ($poll && $existingVotes >= $poll->max_choices) {
            return false;
        }

        $stmt = $this->db()->prepare(
            "INSERT IGNORE INTO {$p}" . self::VOTES_TABLE . " (poll_id, option_id, user_id) VALUES (?, ?, ?)"
        );
        $result = $stmt->execute([$pollId, $optionId, $userId]);

        if ($result) {
            // Zähler aktualisieren
            $this->db()->prepare("UPDATE {$p}" . self::OPTIONS_TABLE . " SET vote_count = vote_count + 1 WHERE id = ?")->execute([$optionId]);
            $this->db()->prepare("UPDATE {$p}" . self::TABLE . " SET vote_count = vote_count + 1 WHERE id = ?")->execute([$pollId]);
        }

        return $result;
    }

    /**
     * Umfrage per ID laden.
     */
    public function findById(int $id): ?object
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare("SELECT * FROM {$p}" . self::TABLE . " WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * Hat Benutzer bereits abgestimmt?
     */
    public function hasVoted(int $pollId, int $userId): bool
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM {$p}" . self::VOTES_TABLE . " WHERE poll_id = ? AND user_id = ?"
        );
        $stmt->execute([$pollId, $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Stimmen des Benutzers in dieser Umfrage.
     *
     * @return array<int> Option-IDs
     */
    public function getUserVotes(int $pollId, int $userId): array
    {
        $p = $this->db()->prefix();
        $stmt = $this->db()->prepare(
            "SELECT option_id FROM {$p}" . self::VOTES_TABLE . " WHERE poll_id = ? AND user_id = ?"
        );
        $stmt->execute([$pollId, $userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
