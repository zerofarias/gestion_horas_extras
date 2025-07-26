<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/controllers/AdminController.php (VERSIÓN COMPLETA Y CORREGIDA)
// ----------------------------------------------------------------------

class AdminController {
    private $userModel;
    private $overtimeModel;
    private $requestModel;
    private $workScheduleModel;
    private $scheduleModel;
    private $companyModel;

    private $suggestionModel;

    private $userNoteModel;

    private $shiftModel;

    public function __construct(){
        if(!hasRole('admin')){ 
            redirect('login'); 
        }
        $this->userModel = new User();
        $this->overtimeModel = new Overtime();
        $this->requestModel = new Request();
        $this->workScheduleModel = new WorkSchedule();
        $this->scheduleModel = new Schedule();
        $this->companyModel = new Company();
        $this->suggestionModel = new Suggestion();
        $this->userNoteModel = new UserNote();
        $this->shiftModel = new Shift();
    }

    public function index(){
        redirect('admin/dashboard');
    }

    public function sync(){
        $this->view('admin/sync');
    }

    // --- Métodos del Dashboard e Historial ---

    public function dashboard(){
        // Obtiene todas las horas extras pendientes
        $allEntries = $this->overtimeModel->getAllPendingEntries();
        $totalHours = 0;
        $hoursByEmployeeChart = [];
        $employeeSummary = [];

        // Procesa las entradas para agrupar datos
        foreach($allEntries as $entry){
            $totalHours += $entry->total_hours;
            $employeeName = !empty($entry->full_name) ? $entry->full_name : $entry->username;
            if(!isset($hoursByEmployeeChart[$employeeName])){ $hoursByEmployeeChart[$employeeName] = 0; }
            $hoursByEmployeeChart[$employeeName] += $entry->total_hours;
            $userId = $entry->user_id;
            if(!isset($employeeSummary[$userId])){
                $employeeSummary[$userId] = [
                    'full_name' => $employeeName,
                    'hours_50' => 0,
                    'hours_100' => 0,
                    'holidays' => 0
                ];
            }
            $employeeSummary[$userId]['hours_50'] += $entry->hours_50;
            $employeeSummary[$userId]['hours_100'] += $entry->hours_100;
            if($entry->is_holiday){
                $employeeSummary[$userId]['holidays']++;
            }
        }
        
        // Obtiene datos para el gráfico de torta (50% vs 100%)
        $pendingTotals = $this->overtimeModel->getPendingTotalsByType();
        $pieChartData = [
            'total_50' => isset($pendingTotals->total_50) ? $pendingTotals->total_50 : 0,
            'total_100' => isset($pendingTotals->total_100) ? $pendingTotals->total_100 : 0
        ];

        // Obtiene datos para el gráfico de tendencia (últimos 7 días)
        $trendData = $this->overtimeModel->getOvertimeTrend(7);
        $lineLabels = []; $lineDataPoints = [];
        foreach($trendData as $day) {
            $lineLabels[] = date('d/m', strtotime($day->entry_day));
            $lineDataPoints[] = $day->total_hours;
        }

        // Obtiene datos para el gráfico de Top 5 Empleados (con fotos)
        $topEmployees = $this->overtimeModel->getTopEmployeesByHours(5);
        $topEmployeesLabels = []; 
        $topEmployeesData = [];
        $topEmployeesPictures = [];
        foreach($topEmployees as $employee) {
            $topEmployeesLabels[] = $employee->full_name;
            $topEmployeesData[] = $employee->total_hours;
            $topEmployeesPictures[] = URLROOT . '/uploads/avatars/' . (isset($employee->profile_picture) ? $employee->profile_picture : 'default.png');
        }
        $topEmployeesChartData = ['labels' => $topEmployeesLabels, 'data' => $topEmployeesData, 'pictures' => $topEmployeesPictures];
        
        // Obtiene datos para el gráfico de Horas por Día de la Semana
        $hoursByDayRaw = $this->overtimeModel->getHoursByDayOfWeek();
        $daysOfWeek = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $hoursByDayData = array_fill(0, 7, 0);
        foreach($hoursByDayRaw as $day) {
            $hoursByDayData[$day->day_of_week - 1] = $day->total_hours;
        }
        
        // Formatea la fecha actual
        $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        $mesActual = $meses[date('n') - 1]; $anoActual = date('Y');
        $fechaFormateada = $mesActual . ' de ' . $anoActual;
        
        // Empaqueta todos los datos para pasarlos a la vista
        $data = [
            'entries' => $allEntries,
            'stats' => [ 
                'total_hours' => $totalHours, 
                'employee_count' => count($hoursByEmployeeChart),
                'totals_by_type' => $pieChartData
            ],
            'employee_summary' => $employeeSummary,
            'bar_chart_data' => json_encode(['labels' => array_keys($hoursByEmployeeChart), 'data' => array_values($hoursByEmployeeChart)]),
            'pie_chart_data' => json_encode(array_values($pieChartData)),
            'line_chart_data' => json_encode(['labels' => $lineLabels, 'data' => $lineDataPoints]),
            'top_employees_chart_data' => json_encode($topEmployeesChartData),
            'hours_by_day_chart_data' => json_encode(['labels' => $daysOfWeek, 'data' => $hoursByDayData]),
            'success_message' => ''
        ];

        // Maneja los mensajes flash de éxito
        if(isset($_SESSION['flash_success'])){
            $data['success_message'] = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }

        // Carga la vista del dashboard
        $this->view('admin/dashboard', $data);
    }
    
    // ... (el resto de las funciones del controlador)


    
    public function employeeDetails($user_id = 0){
        if ($user_id == 0) { redirect('admin/dashboard'); }
        $user = $this->userModel->getUserById($user_id);
        if(!$user){ redirect('admin/dashboard'); }
        $entries = $this->overtimeModel->getPendingEntriesByUserId($user_id);
        $data = [ 'entries' => $entries, 'user' => $user ];
        $this->view('admin/employee_details', $data);
    }

    public function history(){
        $historyData = $this->overtimeModel->getArchivedHistory();
        $this->view('admin/history', ['history' => $historyData]);
    }
    
    public function closureDetails($closure_id = 0){
        if ($closure_id == 0) { redirect('admin/history'); }
        $entries = $this->overtimeModel->getEntriesByClosureId($closure_id);
        $this->view('admin/closure_details', ['entries' => $entries, 'closure_id' => $closure_id]);
    }

    public function createClosure(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            if($this->overtimeModel->createClosure($_SESSION['user_id'])){
                $_SESSION['flash_success'] = 'El cierre se ha realizado con éxito.';
            } else {
                $_SESSION['flash_success'] = 'No había horas pendientes para cerrar.';
            }
            redirect('admin/dashboard');
        } else {
            redirect('admin/dashboard');
        }
    }

    // --- Métodos de Gestión de Usuarios ---

    public function users(){
        $users = $this->userModel->getAllUsers();
        $this->view('admin/users', ['users' => $users]);
    }

    public function createUser(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = [
                'username' => trim($_POST['username']), 'full_name' => trim($_POST['full_name']),
                'password' => trim($_POST['password']), 'confirm_password' => trim($_POST['confirm_password']),
                'role' => $_POST['role'], 'profile_picture' => 'default.png', 'errors' => []
            ];
            if(empty($data['username']) || empty($data['full_name']) || empty($data['password'])){ $data['errors']['general'] = 'Por favor, completa todos los campos obligatorios.'; }
            if($this->userModel->findUserByUsername($data['username'])){ $data['errors']['username'] = 'Este nombre de usuario ya está en uso.'; }
            if(strlen($data['password']) < 4){ $data['errors']['password'] = 'La contraseña debe tener al menos 4 caracteres.'; }
            if($data['password'] != $data['confirm_password']){ $data['errors']['confirm_password'] = 'Las contraseñas no coinciden.'; }
            if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0){
                $target_dir = "uploads/avatars/";
                if (!file_exists($target_dir)) { mkdir($target_dir, 0755, true); }
                $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
                $new_filename = uniqid('avatar_', true) . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if(in_array($file_extension, $allowed_types)){
                    if(move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)){ $data['profile_picture'] = $new_filename; }
                }
            }
            if(empty($data['errors'])){
                $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                if($this->userModel->createUser($data)){
                    $_SESSION['flash_success'] = 'Usuario creado con éxito.';
                    redirect('admin/users');
                }
            } else {
                $this->view('admin/create_user', $data);
            }
        } else {
            $this->view('admin/create_user', ['errors' => []]);
        }
    }
    
    public function editUser($id){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $user = $this->userModel->getUserById($id);

            $data = [
                'id' => $id, 'user' => $user, 'errors' => [],
                'full_name' => trim($_POST['full_name']),
                'birth_date' => trim($_POST['birth_date']),
                'address' => trim($_POST['address']),
                'phone' => trim($_POST['phone']),
                'children_count' => (int)$_POST['children_count'],
                'email' => trim($_POST['email']),
                'emergency_contact_name' => trim($_POST['emergency_contact_name']),
                'emergency_contact_phone' => trim($_POST['emergency_contact_phone']),
                'start_date' => trim($_POST['start_date']),
                'company_id' => (int)$_POST['company_id'],
                'health_insurance' => trim($_POST['health_insurance']),
                'username' => trim($_POST['username']),
                'password' => trim($_POST['password']),
                'confirm_password' => trim($_POST['confirm_password']),
                'role' => $_POST['role'],
                'clock_id' => trim($_POST['clock_id'])
            ];
            function handleUpload($file_input_name, $user_id, $prefix, $target_dir, $allowed_types){
                if(isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0){
                    if (!file_exists($target_dir)) { mkdir($target_dir, 0755, true); }
                    $file_extension = strtolower(pathinfo($_FILES[$file_input_name]["name"], PATHINFO_EXTENSION));
                    $new_filename = $prefix . '_' . $user_id . '_' . time() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if(in_array($file_extension, $allowed_types)){
                        if(move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)){
                            return $new_filename;
                        }
                    }
                }
                return null;
            }
            
            $data['profile_picture_new_name'] = handleUpload('profile_picture', $id, 'avatar', 'uploads/avatars/', ['jpg', 'jpeg', 'png', 'gif']);
            $data['dni_photo_front_new_name'] = handleUpload('dni_photo_front', $id, 'dni_front', 'uploads/documents/', ['jpg', 'jpeg', 'png', 'pdf']);
            $data['dni_photo_back_new_name'] = handleUpload('dni_photo_back', $id, 'dni_back', 'uploads/documents/', ['jpg', 'jpeg', 'png', 'pdf']);
            
            if(empty($data['errors'])){
                if(!empty($data['password'])){
                    $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                if($this->userModel->updateUser($data)){
                    $_SESSION['flash_success'] = 'Ficha de empleado actualizada con éxito.';
                    redirect('admin/users');
                }
            } else {
                $data['companies'] = $this->companyModel->getAllCompanies();
                $this->view('admin/edit_user', $data);
            }

        } else {
            // Lógica para mostrar el formulario (GET request)
            $user = $this->userModel->getUserById($id);
            $companies = $this->companyModel->getAllCompanies();
            if(!$user){ redirect('admin/users'); }
            $data = ['id' => $id, 'user' => $user, 'companies' => $companies, 'errors' => []];
            $this->view('admin/edit_user', $data);
        }
    }

    public function toggleUserStatus($id){
        if ($id == $_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'No puedes desactivar tu propia cuenta.';
            redirect('admin/users');
        }
        if($this->userModel->toggleUserStatus($id)){
            $_SESSION['flash_success'] = 'El estado del usuario ha sido cambiado con éxito.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo cambiar el estado del usuario.';
        }
        redirect('admin/users');
    }

    // --- Métodos de Gestión de Entradas de Horas ---

    public function editEntry($id){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = [
                'id' => $id, 'date' => trim($_POST['date']), 'start_time' => trim($_POST['start_time']),
                'end_time' => trim($_POST['end_time']), 'is_holiday' => (int)$_POST['is_holiday'],
                'reason' => trim($_POST['reason']), 'user_id' => trim($_POST['user_id'])
            ];
            if($this->overtimeModel->updateEntry($data)){
                $_SESSION['flash_success'] = 'Entrada actualizada con éxito.';
                redirect('admin/employeeDetails/' . $data['user_id']);
            } else { die('Algo salió mal.'); }
        } else {
            $entry = $this->overtimeModel->getEntryById($id);
            if(!$entry || $entry->status != 'pending'){ redirect('admin/dashboard'); }
            $data = ['entry' => $entry];
            $this->view('admin/edit_entry', $data);
        }
    }

    public function deleteEntry($id, $user_id){
        if($this->overtimeModel->deleteEntry($id)){
            $_SESSION['flash_success'] = 'La entrada de horas ha sido eliminada.';
        } else {
            // Manejar error
        }
        redirect('admin/employeeDetails/' . $user_id);
    }

    // --- Métodos de Gestión de Solicitudes ---

    public function requests(){
        $allRequests = $this->requestModel->getAllRequests();
        
        $calendarEvents = [];
        foreach($allRequests as $request){
            $className = '';
            if ($request->status == 'Aprobado') $className = 'fc-event-success';
            if ($request->status == 'Rechazado') $className = 'fc-event-danger';

            $calendarEvents[] = [
                'title' => $request->full_name . ' - ' . $request->type_name,
                'start' => $request->start_date,
                'end' => $request->end_date ? date('Y-m-d', strtotime($request->end_date . ' +1 day')) : null,
                'color' => $request->color,
                'className' => $className,
                'extendedProps' => [
                    'reason' => $request->reason,
                    'status' => $request->status,
                    'id' => $request->id
                ]
            ];
        }

        $data = [
            'requests' => $allRequests,
            'calendarEvents' => json_encode($calendarEvents)
        ];
        $this->view('admin/requests', $data);
    }
    
    public function approveRequest($id){
        if($this->requestModel->updateRequestStatus($id, 'Aprobado')){
            $_SESSION['flash_success'] = 'Solicitud aprobada con éxito.';
        }
        redirect('admin/requests');
    }

    public function rejectRequest($id){
        if($this->requestModel->updateRequestStatus($id, 'Rechazado')){
            $_SESSION['flash_success'] = 'Solicitud rechazada.';
        }
        redirect('admin/requests');
    }

    public function editRequest($id){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = [
                'id' => $id,
                'request_type_id' => trim($_POST['request_type_id']),
                'start_date' => trim($_POST['start_date']),
                'end_date' => !empty($_POST['end_date']) ? trim($_POST['end_date']) : NULL,
                'reason' => trim($_POST['reason']),
                'status' => trim($_POST['status'])
            ];
            
            if($this->requestModel->updateRequest($data)){
                $_SESSION['flash_success'] = 'Solicitud actualizada con éxito.';
                redirect('admin/requests');
            } else { die('Algo salió mal.'); }

        } else {
            $request = $this->requestModel->getRequestById($id);
            $requestTypes = $this->requestModel->getRequestTypes();
            if(!$request){ redirect('admin/requests'); }
            $data = ['request' => $request, 'requestTypes' => $requestTypes];
            $this->view('admin/edit_request', $data);
        }
    }
    
    // --- Métodos de Planificación de Horarios ---

    public function schedules(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $schedules = $_POST['schedules'];
            $year = $_POST['year'];
            $week_number = $_POST['week_number'];

            foreach ($schedules as $user_id => $days) {
                $data = [
                    'user_id' => $user_id,
                    'year' => $year,
                    'week_number' => $week_number,
                    'monday' => trim($days['monday']),
                    'tuesday' => trim($days['tuesday']),
                    'wednesday' => trim($days['wednesday']),
                    'thursday' => trim($days['thursday']),
                    'friday' => trim($days['friday']),
                    'saturday' => trim($days['saturday']),
                    'sunday' => trim($days['sunday'])
                ];
                $this->workScheduleModel->upsertSchedule($data);
            }
            $_SESSION['flash_success'] = 'Horarios guardados con éxito para la semana ' . $week_number . '.';
            redirect('admin/schedules?week=' . $year . '-W' . $week_number);

        } else {
            $current_week = isset($_GET['week']) ? $_GET['week'] : date('Y-\WW');
            list($year, $week_number) = sscanf($current_week, '%d-W%d');

            $users = $this->userModel->getAllUsers();
            $schedules = $this->workScheduleModel->getSchedulesForWeek($year, $week_number);

            $data = [
                'users' => $users,
                'schedules' => $schedules,
                'year' => $year,
                'week_number' => $week_number,
                'current_week_input' => $current_week
            ];
            $this->view('admin/schedules', $data);
        }
    }

    public function clockingsReport(){
        $filters = [
            'start_date' => isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'),
            'end_date' => isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'),
            'user_id' => isset($_GET['user_id']) ? $_GET['user_id'] : ''
        ];
        $rawClockings = $this->scheduleModel->getRawClockingsReport($filters);
        $groupedClockings = [];
        foreach ($rawClockings as $clocking) {
            $date = date('Y-m-d', strtotime($clocking->event_time));
            $key = $clocking->user_id . '_' . $date;
            if (!isset($groupedClockings[$key])) {
                $groupedClockings[$key] = [
                    'full_name' => $clocking->full_name, 'work_date' => $date, 'events' => []
                ];
            }
            $groupedClockings[$key]['events'][] = new DateTime($clocking->event_time);
        }
        $processedClockings = [];
        foreach ($groupedClockings as $group) {
            sort($group['events']);
            $events = $group['events'];
            $numEvents = count($events);
            for ($i = 0; $i < $numEvents; $i += 2) {
                $entryTime = $events[$i];
                $exitTime = isset($events[$i + 1]) ? $events[$i + 1] : null;
                $totalHours = null;
                if ($exitTime) {
                    $interval = $entryTime->diff($exitTime);
                    $totalHours = $interval->h + ($interval->i / 60);
                }
                $processedClockings[] = [
                    'full_name' => $group['full_name'], 'work_date' => $group['work_date'],
                    'entry_time' => $entryTime->format('H:i:s'),
                    'exit_time' => $exitTime ? $exitTime->format('H:i:s') : null,
                    'total_hours' => $totalHours,
                    'status' => $exitTime ? 'Completo' : 'Incompleto (Salida Faltante)'
                ];
            }
        }
        $users = $this->userModel->getAllUsers();
        $data = ['clockings' => $processedClockings, 'users' => $users, 'filters' => $filters];
        $this->view('admin/clockings_report', $data);
    }
    
    
    public function runSync(){
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect('admin/sync');
        }

        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $apiUrl = 'http://' . HIKVISION_IP . '/ISAPI/AccessControl/AcsEvent?format=json';
        $username = HIKVISION_USER;
        $password = HIKVISION_PASS;

        $startTime = $startDate . 'T00:00:00-03:00';
        $endTime = $endDate . 'T23:59:59-03:00';

        $allEvents = [];
        $searchResultPosition = 0;
        $maxResults = 500;
        $totalMatches = 0;
        $searchID = uniqid();

        do {
            $requestBody = json_encode([
                'AcsEventCond' => [
                    'searchID' => $searchID, 'searchResultPosition' => $searchResultPosition,
                    'maxResults' => $maxResults, 'major' => 5, 'minor' => 75,
                    'startTime' => $startTime, 'endTime' => $endTime
                ]
            ]);

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true, CURLOPT_USERPWD => "$username:$password",
                CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $requestBody,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json')
            ));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode != 200 || !$response) {
                $data = [ 'httpCode' => $httpCode, 'error' => $error, 'rawResponse' => $response, 'startDate' => $startDate, 'endDate' => $endDate ];
                $this->view('admin/sync_results', $data);
                return;
            }

            $responseData = json_decode($response, true);
            
            if (isset($responseData['AcsEvent']['InfoList'])) {
                $eventsInBatch = $responseData['AcsEvent']['InfoList'];
                $allEvents = array_merge($allEvents, $eventsInBatch);
                $numOfMatches = isset($responseData['AcsEvent']['numOfMatches']) ? $responseData['AcsEvent']['numOfMatches'] : 0;
                $totalMatches = isset($responseData['AcsEvent']['totalMatches']) ? $responseData['AcsEvent']['totalMatches'] : 0;
                $searchResultPosition += $numOfMatches;
            } else {
                break;
            }

        } while ($searchResultPosition < $totalMatches && $totalMatches > 0);

        $finalResponseData = ['AcsEvent' => ['InfoList' => $allEvents]];

        $data = [
            'httpCode' => 200,
            'error' => '',
            'rawResponse' => json_encode($finalResponseData),
            'startDate' => $startDate,
            'endDate' => $endDate
        ];

        $this->view('admin/sync_results', $data);
    }

    /**
     * Paso 2: Procesa los datos verificados y los guarda en la base de datos.
     */
    public function processSync(){
        if ($_SERVER['REQUEST_METHOD'] != 'POST') { redirect('admin/sync'); }

        $rawResponse = $_POST['raw_response'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $response_data = json_decode($rawResponse, true);
        $events = isset($response_data['AcsEvent']['InfoList']) ? $response_data['AcsEvent']['InfoList'] : [];
        
        $attendancesByDate = [];
        $allClockIds = [];

        if (is_array($events)) {
            foreach ($events as $event) {
                if (isset($event['employeeNoString'])) {
                    $clockId = $event['employeeNoString'];
                    $allClockIds[] = $clockId;
                    $dateTime = new DateTime($event['time']);
                    $date = $dateTime->format('Y-m-d');
                    if (!isset($attendancesByDate[$date])) { $attendancesByDate[$date] = []; }
                    if (!isset($attendancesByDate[$date][$clockId])) { $attendancesByDate[$date][$clockId] = []; }
                    $attendancesByDate[$date][$clockId][] = $dateTime;
                }
            }
        }

        $uniqueClockIds = array_unique($allClockIds);
        $userIdsToClear = $this->userModel->getUserIdsByClockIds($uniqueClockIds);
        $this->scheduleModel->clearSchedulesForRange($userIdsToClear, $startDate, $endDate);

        $syncBatchId = uniqid('sync_');
        $processedEmployees = [];
        
        foreach ($events as $event) {
            if (isset($event['employeeNoString'])) {
                $clockId = $event['employeeNoString'];
                $user = $this->userModel->findUserByClockId($clockId);
                if ($user) {
                    $dateTime = new DateTime($event['time']);
                    $this->scheduleModel->insertClockEvent($user->id, $clockId, $dateTime->format('Y-m-d H:i:s'), $syncBatchId);
                }
            }
        }

        foreach ($attendancesByDate as $date => $employeesOnDate) {
            foreach($employeesOnDate as $clockId => $times){
                $user = $this->userModel->findUserByClockId($clockId);
                if ($user) {
                    sort($times);
                    $firstEntry = min($times);
                    $lastExit = null;
                    $totalHours = null;
                    if (count($times) >= 2 && count($times) % 2 == 0) {
                        $totalMinutes = 0;
                        for ($i = 0; $i < count($times); $i += 2) {
                            $interval = $times[$i]->diff($times[$i + 1]);
                            $totalMinutes += ($interval->h * 60) + $interval->i;
                        }
                        $totalHours = $totalMinutes / 60;
                        $lastExit = max($times);
                    }
                    $this->scheduleModel->upsertScheduleFromClock($user->id, $date, $firstEntry->format('H:i:s'), $lastExit ? $lastExit->format('H:i:s') : null, $totalHours);
                    $processedEmployees[$user->id] = true;
                }
            }
        }
        
        $processedCount = count($processedEmployees);
        if ($processedCount > 0) {
            $_SESSION['flash_success'] = "Importación completada. Se procesaron los horarios de {$processedCount} empleados.";
        } else {
            $_SESSION['flash_error'] = "Importación finalizada, pero no se encontraron datos válidos para procesar.";
        }
        redirect('admin/sync');
    }
    // --- Métodos de Gestión de Empresas ---
    public function companies(){
        $companies = $this->companyModel->getAllCompanies();
        $this->view('admin/companies', ['companies' => $companies]);
    }

    public function createCompany(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $companyName = trim($_POST['company_name']);
            if(!empty($companyName)){
                $this->companyModel->createCompany($companyName);
                $_SESSION['flash_success'] = 'Empresa creada con éxito.';
            }
            redirect('admin/companies');
        }
    }

    public function suggestions(){
        // Obtenemos la empresa del admin actual para mostrar solo sus sugerencias
        $adminUser = $this->userModel->getUserById($_SESSION['user_id']);
        $suggestions = $this->suggestionModel->getAllSuggestionsByCompany($adminUser->company_id);
        
        $data = [
            'suggestions' => $suggestions
        ];
        
        $this->view('admin/suggestions', $data);
    }


    
    public function employeeSummary($userId){
        $user = $this->userModel->getUserById($userId);
        if(!$user){ redirect('admin/users'); }

        // 1. Obtener datos para el calendario
        $overtimeEntries = $this->overtimeModel->getOvertimeForUserCalendar($userId);
        $approvedRequests = $this->requestModel->getApprovedRequestsForUserCalendar($userId);
        $calendarEvents = [];
        foreach($overtimeEntries as $entry){
            $calendarEvents[] = [
                'title' => 'Horas Extras', 'start' => $entry->entry_date,
                'color' => '#ff9f40', 'allDay' => true
            ];
        }
        foreach($approvedRequests as $request){
            $calendarEvents[] = [
                'title' => $request->type_name, 'start' => $request->start_date,
                'end' => $request->end_date ? date('Y-m-d', strtotime($request->end_date . ' +1 day')) : null,
                'color' => $request->color, 'allDay' => true
            ];
        }

        // 2. Obtener datos para el gráfico de horas extras
        $monthlySummary = $this->overtimeModel->getMonthlyOvertimeSummaryForUser($userId);
        $chartLabels = []; $chartData50 = []; $chartData100 = [];
        foreach($monthlySummary as $month){
            $chartLabels[] = date("M Y", strtotime($month->month . "-01"));
            $chartData50[] = $month->total_50;
            $chartData100[] = $month->total_100;
        }
        $overtimeChartData = ['labels' => $chartLabels, 'data50' => $chartData50, 'data100' => $chartData100];

        // 3. Obtener últimas marcaciones (emparejadas)
        $latestRawClockings = $this->scheduleModel->getLatestClockingsByUserId($userId, 50);
        $groupedClockings = [];
        foreach ($latestRawClockings as $clocking) {
            $date = date('Y-m-d', strtotime($clocking->event_time));
            if (!isset($groupedClockings[$date])) { $groupedClockings[$date] = []; }
            $groupedClockings[$date][] = new DateTime($clocking->event_time);
        }
        $processedClockings = [];
        foreach ($groupedClockings as $date => $events) {
            sort($events);
            for ($i = 0; $i < count($events); $i += 2) {
                $entryTime = $events[$i];
                $exitTime = isset($events[$i + 1]) ? $events[$i + 1] : null;
                $totalHours = $exitTime ? ($entryTime->diff($exitTime)->h + $entryTime->diff($exitTime)->i / 60) : null;
                $processedClockings[] = [
                    'work_date' => $date,
                    'entry_time' => $entryTime->format('H:i:s'),
                    'exit_time' => $exitTime ? $exitTime->format('H:i:s') : null,
                    'total_hours' => $totalHours,
                    'status' => $exitTime ? 'Completo' : 'Incompleto'
                ];
            }
        }

        // 4. Obtener notas del usuario
        $userNotes = $this->userNoteModel->getNotesByUserId($userId);
        
        $data = [
            'user' => $user,
            'calendarEvents' => json_encode($calendarEvents),
            'overtimeChartData' => json_encode($overtimeChartData),
            'latestClockings' => $processedClockings,
            'userNotes' => $userNotes
        ];

        $this->view('admin/employee_summary', $data);
    }
    /**
     * NUEVO: Añade una nota/incidencia a un empleado.
     */
    public function addNote($userId){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $note = trim($_POST['note']);
            if(!empty($note)){
                $data = [
                    'user_id' => $userId,
                    'admin_id' => $_SESSION['user_id'],
                    'note' => $note
                ];
                $this->userNoteModel->addNote($data);
            }
        }
        redirect('admin/employeeSummary/' . $userId);
    }

    // --- Método de Vista Helper ---
    
    public function view($view, $data = []){
        if(file_exists('../app/views/' . $view . '.php')){
            require_once '../app/views/'. $view . '.php';
        } else {
            die('Error: La vista no existe: ' . $view);
        }
    }

    public function shiftManager(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Lógica para crear un nuevo turno
            $data = [
                'company_id' => $_SESSION['user_company_id'], // Asumimos que esto se guarda en la sesión
                'shift_name' => trim($_POST['shift_name']),
                'start_time' => trim($_POST['start_time']),
                'end_time' => trim($_POST['end_time'])
            ];
            $this->shiftModel->createShift($data);
            $_SESSION['flash_success'] = 'Turno creado con éxito.';
            redirect('admin/shiftManager');
        } else {
            $adminUser = $this->userModel->getUserById($_SESSION['user_id']);
            $_SESSION['user_company_id'] = $adminUser->company_id;

            $shifts = $this->shiftModel->getShiftsByCompany($_SESSION['user_company_id']);
            $this->view('admin/shift_manager', ['shifts' => $shifts]);
        }
    }
    
    /**
     * NUEVO: Elimina un turno predefinido.
     */
    public function deleteShift($id){
        $this->shiftModel->deleteShift($id);
        $_SESSION['flash_success'] = 'Turno eliminado con éxito.';
        redirect('admin/shiftManager');
    }

    /**
     * ACTUALIZADO: El planificador mensual ahora también carga los turnos predefinidos.
     */
    public function monthlySchedules(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // ... (lógica de guardado sin cambios)
        } else {
            // ... (lógica de carga de datos existente)
            $shifts = $this->shiftModel->getShiftsByCompany($_SESSION['user_company_id']);
            $data['shifts'] = $shifts; // Se añaden los turnos a los datos de la vista
            $this->view('admin/monthly_schedules', $data);
        }
    }
}
?>
