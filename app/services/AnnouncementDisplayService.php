<?php

class AnnouncementDisplayService {

    public function getPendingForUser($userId) {
        if (!notifications_is_ready()) {
            return [];
        }
        $userId = (int)$userId;
        $db = new Database();
        $now = date('Y-m-d H:i:s');

        $db->query("SELECT a.* FROM announcements a
            WHERE a.is_active = 1
            AND a.starts_at <= :now AND a.ends_at >= :now2
            ORDER BY a.created_at ASC");
        $db->bind(':now', $now);
        $db->bind(':now2', $now);
        $rows = $db->resultSet();

        $pending = [];
        foreach ($rows as $a) {
            if (!$this->userIsTargeted((int)$a->id, $userId, (int)$a->target_all)) {
                continue;
            }
            if ($this->shouldShow((int)$a->id, $userId, $a->display_mode)) {
                $pending[] = $a;
            }
        }
        return $pending;
    }

    public function recordShown($announcementId, $userId) {
        $announcementId = (int)$announcementId;
        $userId = (int)$userId;
        $db = new Database();
        $db->query("INSERT INTO announcement_user_state (announcement_id, user_id, times_shown, last_shown_at)
            VALUES (:aid, :uid, 1, NOW())
            ON DUPLICATE KEY UPDATE times_shown = times_shown + 1, last_shown_at = NOW()");
        $db->bind(':aid', $announcementId);
        $db->bind(':uid', $userId);
        $db->execute();
    }

    /** El usuario puede ver el aviso (activo, vigente y dentro del target). */
    public function userCanAccess($announcementId, $userId) {
        if (!notifications_is_ready()) {
            return false;
        }
        $announcementId = (int)$announcementId;
        $userId = (int)$userId;
        if ($announcementId <= 0 || $userId <= 0) {
            return false;
        }
        $db = new Database();
        $db->query('SELECT * FROM announcements WHERE id = :id LIMIT 1');
        $db->bind(':id', $announcementId);
        $a = $db->single();
        if (!$a || !(int)$a->is_active) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        if ($a->starts_at > $now || $a->ends_at < $now) {
            return false;
        }
        return $this->userIsTargeted($announcementId, $userId, (int)$a->target_all);
    }

    public function dismiss($announcementId, $userId) {
        $db = new Database();
        $db->query("INSERT INTO announcement_user_state (announcement_id, user_id, times_shown, dismissed_at, last_shown_at)
            VALUES (:aid, :uid, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE dismissed_at = NOW(), last_shown_at = NOW()");
        $db->bind(':aid', (int)$announcementId);
        $db->bind(':uid', (int)$userId);
        return $db->execute();
    }

    private function shouldShow($announcementId, $userId, $displayMode) {
        $db = new Database();
        $db->query("SELECT * FROM announcement_user_state WHERE announcement_id = :aid AND user_id = :uid");
        $db->bind(':aid', $announcementId);
        $db->bind(':uid', $userId);
        $state = $db->single();

        if ($state && $state->dismissed_at) {
            return false;
        }

        if ($displayMode === 'once') {
            return !$state || (int)$state->times_shown === 0;
        }
        if ($displayMode === 'sessions_3') {
            if ($state && (int)$state->times_shown >= 3) {
                return false;
            }
            return true;
        }
        return true;
    }

    private function userIsTargeted($announcementId, $userId, $targetAll) {
        if ($targetAll) {
            return true;
        }
        $user = (new User())->getUserById($userId);
        if (!$user) {
            return false;
        }
        $db = new Database();
        $db->query("SELECT target_type, target_id FROM announcement_targets WHERE announcement_id = :aid");
        $db->bind(':aid', $announcementId);
        $targets = $db->resultSet();
        if (empty($targets)) {
            return false;
        }
        foreach ($targets as $t) {
            if ($t->target_type === 'company' && (int)$t->target_id === (int)$user->company_id) {
                return true;
            }
            if ($t->target_type === 'area' && !empty($user->area_id) && (int)$t->target_id === (int)$user->area_id) {
                $area = class_exists('Area') ? (new Area())->getById((int)$t->target_id) : null;
                if ($area && $area->company_id !== null && (int)$area->company_id > 0) {
                    if ((int)$user->company_id !== (int)$area->company_id) {
                        continue;
                    }
                }
                return true;
            }
            if ($t->target_type === 'user' && (int)$t->target_id === (int)$userId) {
                return true;
            }
        }
        return false;
    }
}
