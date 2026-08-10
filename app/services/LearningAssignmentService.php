<?php

/**
 * Resuelve si un usuario tiene acceso a un curso o tarea según asignaciones.
 */
class LearningAssignmentService {

    public function userCanAccessCourse($userId, $courseId) {
        if (!learning_is_ready()) {
            return false;
        }
        $user = (new User())->getUserById($userId);
        $course = (new Course())->getById($courseId);
        if (!$user || !$course || !$course->is_published) {
            return false;
        }
        if ((int)$user->company_id !== (int)$course->company_id) {
            return false;
        }
        return $this->matchesAssignments('course_assignments', 'course_id', $courseId, $user);
    }

    public function userCanAccessTask($userId, $taskId) {
        if (!learning_is_ready()) {
            return false;
        }
        $user = (new User())->getUserById($userId);
        $task = (new Task())->getById($taskId);
        if (!$user || !$task || !$task->is_active) {
            return false;
        }
        if ((int)$user->company_id !== (int)$task->company_id) {
            return false;
        }
        return $this->matchesAssignments('task_assignments', 'task_id', $taskId, $user);
    }

    public function getCourseIdsForUser($userId) {
        if (!learning_is_ready()) {
            return [];
        }
        $user = (new User())->getUserById($userId);
        if (!$user || empty($user->company_id)) {
            return [];
        }
        $db = new Database();
        $db->query('SELECT c.id FROM courses c
            WHERE c.company_id = :company_id AND c.is_published = 1
            ORDER BY c.sort_order ASC, c.title ASC');
        $db->bind(':company_id', (int)$user->company_id);
        $courses = $db->resultSet();
        $ids = [];
        foreach ($courses as $c) {
            if ($this->matchesAssignments('course_assignments', 'course_id', (int)$c->id, $user)) {
                $ids[] = (int)$c->id;
            }
        }
        return $ids;
    }

    public function getUserIdsForCourse($courseId) {
        if (!learning_is_ready()) {
            return [];
        }
        $course = (new Course())->getById((int)$courseId);
        if (!$course || !(int)$course->is_published) {
            return [];
        }
        $db = new Database();
        $db->query("SELECT id FROM users WHERE company_id = :cid AND role = 'empleado' AND is_active = 1");
        $db->bind(':cid', (int)$course->company_id);
        $ids = [];
        foreach ($db->resultSet() as $row) {
            $user = (new User())->getUserById((int)$row->id);
            if ($user && $this->matchesAssignments('course_assignments', 'course_id', (int)$courseId, $user)) {
                $ids[] = (int)$user->id;
            }
        }
        return array_values(array_unique($ids));
    }

    public function getTaskIdsForUser($userId) {
        if (!learning_is_ready()) {
            return [];
        }
        $user = (new User())->getUserById($userId);
        if (!$user || empty($user->company_id)) {
            return [];
        }
        $db = new Database();
        $db->query('SELECT t.id FROM tasks t
            WHERE t.company_id = :company_id AND t.is_active = 1
            ORDER BY t.due_date ASC, t.title ASC');
        $db->bind(':company_id', (int)$user->company_id);
        $tasks = $db->resultSet();
        $ids = [];
        foreach ($tasks as $t) {
            if ($this->matchesAssignments('task_assignments', 'task_id', (int)$t->id, $user)) {
                $ids[] = (int)$t->id;
            }
        }
        return $ids;
    }

    private function matchesAssignments($table, $fkColumn, $entityId, $user) {
        $db = new Database();
        $db->query("SELECT target_type, target_id FROM {$table} WHERE {$fkColumn} = :eid");
        $db->bind(':eid', (int)$entityId);
        $rows = $db->resultSet();
        if (empty($rows)) {
            return false;
        }
        foreach ($rows as $row) {
            if ($row->target_type === 'company' && (int)$row->target_id === (int)$user->company_id) {
                return true;
            }
            if ($row->target_type === 'area' && !empty($user->area_id) && (int)$row->target_id === (int)$user->area_id) {
                return true;
            }
            if ($row->target_type === 'user' && (int)$row->target_id === (int)$user->id) {
                return true;
            }
        }
        return false;
    }
}
