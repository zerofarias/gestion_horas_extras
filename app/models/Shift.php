<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/Shift.php (VERSIÓN CON CÁLCULO DE HORAS)
// ----------------------------------------------------------------------

class Shift {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    /**
     * Obtiene los turnos, sus rangos Y CALCULA EL TOTAL DE HORAS.
     */
    public function getShiftsWithRangesByCompany($companyId){
        // 1. Obtener los turnos principales (sin cambios)
        $this->db->query("SELECT * FROM shifts WHERE company_id = :company_id ORDER BY shift_name ASC");
        $this->db->bind(':company_id', $companyId);
        $shifts = $this->db->resultSet();

        $sql_ranges = "SELECT start_time, end_time FROM shift_time_ranges WHERE shift_id = :shift_id ORDER BY start_time ASC";
        
        // 2. Para cada turno, obtener rangos y calcular horas
        for ($i = 0; $i < count($shifts); $i++) {
            $this->db->query($sql_ranges);
            $this->db->bind(':shift_id', $shifts[$i]->id);
            $shifts[$i]->ranges = $this->db->resultSet();

            // ▼▼▼ LÓGICA NUEVA PARA CALCULAR HORAS ▼▼▼
            $totalSeconds = 0;
            if (is_array($shifts[$i]->ranges)) {
                foreach ($shifts[$i]->ranges as $range) {
                    $start = strtotime($range->start_time);
                    $end = strtotime($range->end_time);

                    // Maneja el caso de turnos que terminan al día siguiente (ej: 22:00 a 06:00)
                    if ($end < $start) {
                        $end += 24 * 3600; // Añade 24 horas en segundos
                    }
                    
                    $totalSeconds += $end - $start;
                }
            }
            // Guardamos el total de horas como una nueva propiedad del objeto
            $shifts[$i]->total_hours = $totalSeconds / 3600;
            // ▲▲▲ FIN DE LA LÓGICA NUEVA ▲▲▲
        }

        return $shifts;
    }

    /**
     * Crea un turno partido con una transacción (sin cambios).
     */
    public function createShiftWithRanges($data){
        try {
            $this->db->beginTransaction();

            // 1. La consulta INSERT ahora incluye la columna 'color'
            $this->db->query('INSERT INTO shifts (company_id, shift_name, notes, color) VALUES (:company_id, :shift_name, :notes, :color)');
            
            // 2. Hacemos el "binding" de todos los valores, incluyendo el nuevo color
            $this->db->bind(':company_id', $data['company_id']);
            $this->db->bind(':shift_name', $data['shift_name']);
            $this->db->bind(':notes', $data['notes']);
            $this->db->bind(':color', $data['color']); // Esta línea es crucial
            $this->db->execute();

            $shiftId = $this->db->lastInsertId();

            // La lógica para los rangos horarios no cambia
            $this->db->query('INSERT INTO shift_time_ranges (shift_id, start_time, end_time) VALUES (:shift_id, :start_time, :end_time)');
            foreach($data['ranges'] as $range){
                $this->db->bind(':shift_id', $shiftId);
                $this->db->bind(':start_time', $range['inicio']);
                $this->db->bind(':end_time', $range['fin']);
                $this->db->execute();
            }

            return $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['db_error_details'] = 'Excepción de BD: ' . $e->getMessage();
            return false;
        }
    }
    /**
     * Elimina un turno (sin cambios).
     */
    public function deleteShift($id){
        $this->db->query('DELETE FROM shifts WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
?>
