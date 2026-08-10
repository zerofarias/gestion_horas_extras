<?php



class Reward {

    private $db;



    public function __construct() {

        $this->db = new Database();

    }



    public function getByCompany($companyId, $activeOnly = true) {

        $sql = 'SELECT * FROM rewards WHERE company_id = :company_id';

        if ($activeOnly) {

            $sql .= ' AND is_active = 1';

        }

        $sql .= ' ORDER BY stars_required ASC';

        $this->db->query($sql);

        $this->db->bind(':company_id', (int)$companyId);

        return $this->db->resultSet();

    }



    public function getById($id) {

        $this->db->query('SELECT * FROM rewards WHERE id = :id');

        $this->db->bind(':id', (int)$id);

        return $this->db->single();

    }



    public function getByIdForCompany($id, $companyId) {

        $this->db->query('SELECT * FROM rewards WHERE id = :id AND company_id = :cid');

        $this->db->bind(':id', (int)$id);

        $this->db->bind(':cid', (int)$companyId);

        return $this->db->single();

    }



    public function create(array $data) {

        $this->db->query('INSERT INTO rewards (company_id, title, description, stars_required, is_active)

            VALUES (:company_id, :title, :description, :stars_required, :is_active)');

        $this->db->bind(':company_id', (int)$data['company_id']);

        $this->db->bind(':title', trim($data['title']));

        $this->db->bind(':description', $data['description'] ?? null);

        $this->db->bind(':stars_required', (int)$data['stars_required']);

        $this->db->bind(':is_active', !empty($data['is_active']) ? 1 : 0);

        if ($this->db->execute()) {

            return (int)$this->db->lastInsertId();

        }

        return false;

    }



    public function update($id, array $data) {

        $companyId = (int)($data['company_id'] ?? 0);

        $this->db->query('UPDATE rewards SET title = :title, description = :description,

            stars_required = :stars_required, is_active = :is_active

            WHERE id = :id AND company_id = :cid');

        $this->db->bind(':title', trim($data['title']));

        $this->db->bind(':description', $data['description'] ?? null);

        $this->db->bind(':stars_required', (int)$data['stars_required']);

        $this->db->bind(':is_active', !empty($data['is_active']) ? 1 : 0);

        $this->db->bind(':id', (int)$id);

        $this->db->bind(':cid', $companyId);

        return $this->db->execute() && $this->db->rowCount() > 0;

    }



    public function redeem($userId, $rewardId) {

        $userId = (int)$userId;

        $rewardId = (int)$rewardId;

        $user = (new User())->getUserById($userId);

        if (!$user) {

            return ['ok' => false, 'message' => 'Usuario no válido.'];

        }

        $userCompanyId = (int)($user->company_id ?? 0);

        $reward = $this->getById($rewardId);

        if (!$reward || !$reward->is_active || (int)$reward->company_id !== $userCompanyId) {

            return ['ok' => false, 'message' => 'Premio no disponible.'];

        }

        $wallet = new StarWallet();

        if ($wallet->getBalance($userId) < (int)$reward->stars_required) {

            return ['ok' => false, 'message' => 'No tenés estrellas suficientes.'];

        }

        $this->db->beginTransaction();
        try {
            if (!$wallet->spendStars($userId, (int)$reward->stars_required, 'reward', $rewardId, 'Canje: ' . $reward->title)) {
                throw new RuntimeException('spend failed');
            }

            $this->db->query('INSERT INTO reward_redemptions (user_id, reward_id, stars_spent, status)
                VALUES (:uid, :rid, :stars, \'pending\')');
            $this->db->bind(':uid', $userId);
            $this->db->bind(':rid', $rewardId);
            $this->db->bind(':stars', (int)$reward->stars_required);
            if (!$this->db->execute()) {
                throw new RuntimeException('insert failed');
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            $wallet->addStars($userId, (int)$reward->stars_required, 'reward_refund', $rewardId, 'Reversión canje fallido');
            return ['ok' => false, 'message' => 'No se pudo registrar el canje.'];
        }

        return ['ok' => true, 'message' => 'Solicitud de canje enviada. RRHH la revisará.'];

    }



    public function getPendingRedemptions($companyId) {

        $this->db->query('SELECT rr.*, r.title AS reward_title, u.full_name

            FROM reward_redemptions rr

            JOIN rewards r ON r.id = rr.reward_id

            JOIN users u ON u.id = rr.user_id

            WHERE r.company_id = :cid AND rr.status = \'pending\'

            ORDER BY rr.redeemed_at ASC');

        $this->db->bind(':cid', (int)$companyId);

        return $this->db->resultSet();

    }



    public function getRedemptionForCompany($id, $companyId) {

        $this->db->query('SELECT rr.* FROM reward_redemptions rr

            JOIN rewards r ON r.id = rr.reward_id

            WHERE rr.id = :id AND r.company_id = :cid');

        $this->db->bind(':id', (int)$id);

        $this->db->bind(':cid', (int)$companyId);

        return $this->db->single();

    }



    public function reviewRedemption($id, $status, $companyId) {

        $allowed = ['approved', 'rejected', 'pending'];

        if (!in_array($status, $allowed, true)) {

            return false;

        }

        if (!$this->getRedemptionForCompany($id, $companyId)) {

            return false;

        }

        $this->db->query('UPDATE reward_redemptions SET status = :status, reviewed_at = NOW() WHERE id = :id');

        $this->db->bind(':status', $status);

        $this->db->bind(':id', (int)$id);

        return $this->db->execute();

    }

}

