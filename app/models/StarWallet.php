<?php



class StarWallet {

    private $db;



    public function __construct() {

        $this->db = new Database();

    }



    public function getBalance($userId) {

        $this->db->query('SELECT total_stars FROM user_star_wallets WHERE user_id = :uid');

        $this->db->bind(':uid', (int)$userId);

        $row = $this->db->single();

        return $row ? (int)$row->total_stars : 0;

    }



    public function ensureWallet($userId) {

        $this->db->query('INSERT IGNORE INTO user_star_wallets (user_id, total_stars) VALUES (:uid, 0)');

        $this->db->bind(':uid', (int)$userId);

        $this->db->execute();

    }



    public function addStars($userId, $delta, $sourceType, $sourceId, $note = null) {

        $delta = (int)$delta;

        if ($delta <= 0) {

            return false;

        }

        $userId = (int)$userId;

        $this->db->beginTransaction();

        try {

            $this->ensureWallet($userId);

            $this->db->query('UPDATE user_star_wallets SET total_stars = total_stars + :delta WHERE user_id = :uid');

            $this->db->bind(':delta', $delta);

            $this->db->bind(':uid', $userId);

            $this->db->execute();

            $this->db->query('INSERT INTO star_transactions (user_id, delta, source_type, source_id, note)

                VALUES (:uid, :delta, :stype, :sid, :note)');

            $this->db->bind(':uid', $userId);

            $this->db->bind(':delta', $delta);

            $this->db->bind(':stype', $sourceType);

            $this->db->bind(':sid', $sourceId ? (int)$sourceId : null);

            $this->db->bind(':note', $note);

            if (!$this->db->execute()) {

                throw new RuntimeException('ledger insert failed');

            }

            $this->db->commit();

            return true;

        } catch (Throwable $e) {

            $this->db->rollBack();

            return false;

        }

    }



    public function spendStars($userId, $delta, $sourceType, $sourceId, $note = null) {

        $delta = (int)$delta;

        if ($delta <= 0) {

            return false;

        }

        $userId = (int)$userId;

        $ownTx = !$this->db->inTransaction();
        if ($ownTx) {
            $this->db->beginTransaction();
        }

        try {

            $this->ensureWallet($userId);

            $this->db->query('UPDATE user_star_wallets SET total_stars = total_stars - :delta1
                WHERE user_id = :uid AND total_stars >= :delta2');

            $this->db->bind(':delta1', $delta);
            $this->db->bind(':delta2', $delta);

            $this->db->bind(':uid', $userId);

            $this->db->execute();

            if ($this->db->rowCount() === 0) {

                throw new RuntimeException('insufficient balance');

            }

            $this->db->query('INSERT INTO star_transactions (user_id, delta, source_type, source_id, note)

                VALUES (:uid, :delta, :stype, :sid, :note)');

            $this->db->bind(':uid', $userId);

            $this->db->bind(':delta', -$delta);

            $this->db->bind(':stype', $sourceType);

            $this->db->bind(':sid', $sourceId ? (int)$sourceId : null);

            $this->db->bind(':note', $note);

            if (!$this->db->execute()) {

                throw new RuntimeException('ledger insert failed');

            }

            if ($ownTx) {
                $this->db->commit();
            }

            return true;

        } catch (Throwable $e) {

            if ($ownTx) {
                $this->db->rollBack();
            }

            return false;

        }

    }



    public function getTransactions($userId, $limit = 20) {

        $this->db->query('SELECT * FROM star_transactions WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim');

        $this->db->bind(':uid', (int)$userId);

        $this->db->bind(':lim', (int)$limit, PDO::PARAM_INT);

        return $this->db->resultSet();

    }

}

