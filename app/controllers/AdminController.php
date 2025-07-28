<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/controllers/AdminController.php (VERSIÓN COMPLETA Y CORREGIDA)
// ----------------------------------------------------------------------

class AdminController {
    private $userModel;
    private $overtimeModel;
    private $requestModel;
    private $scheduleModel;
    private $companyModel;

    private $suggestionModel;

    private $userNoteModel;

     private $shiftModel;

    private $workScheduleModel; 

    private $holidayModel;

    private $templateModel;

    

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
        $this->workScheduleModel = new WorkSchedule();
        $this->holidayModel = new Holiday();
        $this->templateModel = new Template();
    }

    public function index(){
        redirect('admin/dashboard');
    }

    public function sync(){
        $this->view('admin/sync');
    }

    // --- Métodos del Dashboard e Historial ---

    public function dashboard(){
        if (!isset($_SESSION['user_company_id'])) {
            $adminUser = $this->userModel->getUserById($_SESSION['user_id']);
            $_SESSION['user_company_id'] = $adminUser->company_id;
        }
        $companyId = $_SESSION['user_company_id'];
        
        // --- KPIs ---
        $activeUsers = $this->userModel->countActiveUsersByCompany($companyId);
        $workingNow = $this->workScheduleModel->countWorkingNowByCompany($companyId);
        $onLeaveToday = $this->requestModel->countOnLeaveTodayByCompany($companyId);
        $pendingRequests = $this->requestModel->getPendingRequestsWithDetails($companyId);
        $pendingOvertimeTotals = $this->overtimeModel->getPendingTotalsByType();
        $employeesWithPending = $this->overtimeModel->countEmployeesWithPendingHours();

        // --- Gráficos ---
        $weekSchedule = $this->workScheduleModel->getDashboardScheduleSummary($companyId, date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week')));
        $workloadByDay = array_fill(0, 7, 0);
        foreach($weekSchedule as $entry){ 
            $hours = 0;
            if($entry->start_time && $entry->end_time){
                $start = strtotime($entry->start_time);
                $end = strtotime($entry->end_time);
                if($end < $start) { $end += 24 * 3600; }
                $hours = ($end - $start) / 3600;
            }
            $dayIndex = date('N', strtotime($entry->schedule_date)) - 1;
            $workloadByDay[$dayIndex] += $hours;
        }
        
        $topEmployees = $this->overtimeModel->getTopEmployeesByPendingHours(5);
        $monthRequests = $this->requestModel->getMonthlyRequestSummary($companyId, date('Y-m'));
        $requestLabels = array(); 
        $requestData = array();
        foreach($monthRequests as $req){ $requestLabels[] = $req->type_name; $requestData[] = $req->count; }

        // --- Paneles de Acción y Comunidad ---
        $upcomingBirthdays = $this->userModel->getUpcomingBirthdays($companyId, 7);
        $latestSuggestion = $this->suggestionModel->getLatestSuggestionByCompany($companyId);

        // CORRECCIÓN PARA PHP 5.6: Reemplazar el operador ??
        $overtime_50 = isset($pendingOvertimeTotals->total_50) ? $pendingOvertimeTotals->total_50 : 0;
        $overtime_100 = isset($pendingOvertimeTotals->total_100) ? $pendingOvertimeTotals->total_100 : 0;

        $data = array(
            'stats' => array(
                'active_users' => $activeUsers,
                'working_now' => $workingNow,
                'on_leave_today' => $onLeaveToday,
                'pending_requests_count' => count($pendingRequests),
                'overtime_50' => $overtime_50,
                'overtime_100' => $overtime_100,
                'employees_with_pending' => $employeesWithPending
            ),
            'charts' => array(
                'overtime_distribution' => json_encode(array($overtime_50, $overtime_100)),
                'workload' => json_encode($workloadByDay),
                'requests' => array('labels' => json_encode($requestLabels), 'data' => json_encode($requestData))
            ),
            'top_employees' => $topEmployees,
            'pending_requests' => $pendingRequests,
            'upcoming_birthdays' => $upcomingBirthdays,
            'latest_suggestion' => $latestSuggestion
        );

        $this->view('admin/dashboard', $data);
    }

    
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
                'clock_id' => trim($_POST['clock_id']),
                'weekly_hour_limit' => trim($_POST['weekly_hour_limit'])
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
        $allRequests = $this->requestModel->getAllRequests(); // Asume que este método ya existe y funciona
        
        $calendarEvents = array();
        foreach($allRequests as $request){
            // Asigna un color según el estado de la solicitud
            $color = '#6c757d'; // Gris por defecto para 'Pendiente'
            if ($request->status == 'Aprobado') $color = '#198754'; // Verde
            if ($request->status == 'Rechazado') $color = '#dc3545'; // Rojo

            $calendarEvents[] = array(
                'title' => $request->full_name . ' - ' . $request->type_name,
                'start' => $request->start_date,
                // FullCalendar necesita que la fecha final sea exclusiva, por eso se añade 1 día
                'end' => $request->end_date ? date('Y-m-d', strtotime($request->end_date . ' +1 day')) : null,
                'color' => $color
            );
        }

        $data = array(
            'requests' => $allRequests,
            'calendarEvents' => json_encode($calendarEvents)
        );
        $this->view('admin/requests', $data);
    }

    /**
     * Procesa la aprobación de una solicitud.
     */
    public function approveRequest($id){
        if($this->requestModel->updateRequestStatus($id, 'Aprobado')){
            $_SESSION['flash_success'] = 'Solicitud aprobada con éxito.';
        } else {
            $_SESSION['flash_error'] = 'Error al aprobar la solicitud.';
        }
        redirect('admin/requests');
    }

    /**
     * Procesa el rechazo de una solicitud.
     */
    public function rejectRequest($id){
        if($this->requestModel->updateRequestStatus($id, 'Rechazado')){
            $_SESSION['flash_success'] = 'Solicitud rechazada.';
        } else {
            $_SESSION['flash_error'] = 'Error al rechazar la solicitud.';
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
        $adminUser = $this->userModel->getUserById($_SESSION['user_id']);
        if (!isset($_SESSION['user_company_id'])) {
             $_SESSION['user_company_id'] = $adminUser->company_id;
        }
        $shifts = $this->shiftModel->getShiftsWithRangesByCompany($_SESSION['user_company_id']);
        $data = array('shifts' => $shifts);
        $this->view('admin/shift_manager', $data);
    }
    
   public function createSplitShift(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            
            unset($_SESSION['db_error_details']);

            // Leemos todos los datos del formulario, incluyendo el color
            $shift_name = isset($_POST['shift_name']) ? trim($_POST['shift_name']) : '';
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
            $ranges = isset($_POST['ranges']) && is_array($_POST['ranges']) ? $_POST['ranges'] : array();
            // Esta es la línea clave que lee el color del formulario
            $color = isset($_POST['color']) ? $_POST['color'] : '#3788d8';

            // Creamos el array de datos para el modelo
            $data = array(
                'company_id' => $_SESSION['user_company_id'],
                'shift_name' => $shift_name,
                'notes' => $notes,
                'ranges' => $ranges,
                'color' => $color // Pasamos el color al modelo
            );

            // ... (El resto del método no cambia)
            
            if($this->shiftModel->createShiftWithRanges($data)){
                $_SESSION['flash_success'] = 'Turno partido creado con éxito.';
            } else {
                $error_details = isset($_SESSION['db_error_details']) ? $_SESSION['db_error_details'] : 'Error desconocido en la base de datos.';
                $_SESSION['flash_error'] = '<strong>No se pudo crear el turno.</strong><br>Detalles: ' . $error_details;
                unset($_SESSION['db_error_details']);
            }
            
            redirect('admin/shiftManager');

        } else {
            redirect('admin/shiftManager');
        }
    }
     public function weeklyPlanner(){
        if($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['is_ajax'])){
            $schedules = $_POST['schedules'];
            foreach($schedules as $userId => $days){
                foreach($days as $date => $entries){
                    $this->workScheduleModel->saveDaySchedule($userId, $date, !empty($entries) ? $entries : array());
                }
            }
            $_SESSION['flash_success'] = 'Planificador guardado con éxito.';
            redirect('admin/weeklyPlanner?week=' . $_POST['current_week']);
        }

        // --- Lógica GET para mostrar la página ---

        if (!isset($_SESSION['user_company_id'])) {
            $adminUser = $this->userModel->getUserById($_SESSION['user_id']);
            $_SESSION['user_company_id'] = $adminUser->company_id;
        }

        // 1. Obtener fechas
        $current_week_string = isset($_GET['week']) ? $_GET['week'] : date('Y-\WW');
        $year = date('Y', strtotime($current_week_string));
        $week = date('W', strtotime($current_week_string));
        
        $dias_semana = array('Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié', 'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb', 'Sun' => 'Dom');
        $nombres_mes = array(1=>'Ene', 2=>'Feb', 3=>'Mar', 4=>'Abr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Ago', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dic');
        
        $week_dates = array();
        for ($i = 0; $i < 7; $i++) {
            $date_string = date('Y-m-d', strtotime($year . "W" . $week . ($i + 1)));
            $week_dates[] = array('full_date' => $date_string, 'display' => $dias_semana[date('D', strtotime($date_string))] . ' ' . date('d/m', strtotime($date_string)));
        }
        
        $weekStartDate = $week_dates[0]['full_date'];
        $weekEndDate = end($week_dates)['full_date'];
        
        // 2. Obtener datos de la BD
        $users = $this->userModel->getUsersByCompany($_SESSION['user_company_id']);
        $shifts = $this->shiftModel->getShiftsWithRangesByCompany($_SESSION['user_company_id']);
        $holidaysData = $this->holidayModel->getHolidaysForPeriod($_SESSION['user_company_id'], $weekStartDate, $weekEndDate);
        $requestsData = $this->requestModel->getApprovedRequestsForPeriod($weekStartDate, $weekEndDate, $_SESSION['user_company_id']);
        
        $month1_start = date('Y-m-01', strtotime($weekStartDate));
        $month1_end = date('Y-m-t', strtotime($weekStartDate));
        $allEntries = $this->workScheduleModel->getScheduleEntriesForPeriod($_SESSION['user_company_id'], $month1_start, $month1_end);

        $month2_start = date('Y-m-01', strtotime($weekEndDate));
        if ($month1_start != $month2_start) {
            $month2_end = date('Y-m-t', strtotime($weekEndDate));
            $month2_entries = $this->workScheduleModel->getScheduleEntriesForPeriod($_SESSION['user_company_id'], $month2_start, $month2_end);
            $allEntries = array_merge($allEntries, $month2_entries);
        }

        // 3. Procesar y calcular totales
        $holidays = array();
        foreach($holidaysData as $h) { $holidays[$h->holiday_date] = true; }

        $requests = array();
        foreach($requestsData as $req) {
            $current_date = new DateTime($req->start_date);
            $end_date = new DateTime($req->end_date);
            while($current_date <= $end_date) {
                $requests[$req->user_id][$current_date->format('Y-m-d')] = $req;
                $current_date->modify('+1 day');
            }
        }

        $schedules = array(); // <-- INICIALIZACIÓN DE LA VARIABLE
        $weekly_totals = array();
        $monthly_totals = array();
        $shiftsById = array();
        foreach($shifts as $s){ $shiftsById[$s->id] = $s; }

        $month1_num = (int)date('m', strtotime($weekStartDate));
        $month2_num = (int)date('m', strtotime($weekEndDate));
        foreach($users as $user) {
            $weekly_totals[$user->id] = 0;
            $monthly_totals[$user->id][$month1_num] = 0;
            if ($month1_num != $month2_num) {
                $monthly_totals[$user->id][$month2_num] = 0;
            }
        }

        foreach ($allEntries as $entry) {
            if (!isset($schedules[$entry->user_id][$entry->schedule_date])) {
                $schedules[$entry->user_id][$entry->schedule_date] = array();
            }
            $schedules[$entry->user_id][$entry->schedule_date][] = $entry; // <-- POBLACIÓN DE LA VARIABLE

            $hours = 0;
            if ($entry->type == 'shift' && !empty($entry->shift_id) && isset($shiftsById[$entry->shift_id])) {
                $hours = $shiftsById[$entry->shift_id]->total_hours;
            } elseif (($entry->type == 'custom' || $entry->type == 'overtime') && !empty($entry->start_time) && !empty($entry->end_time)) {
                $start = strtotime($entry->start_time); $end = strtotime($entry->end_time);
                if ($end < $start) { $end += 24 * 3600; }
                $hours = ($end - $start) / 3600;
            }

            $entry_month = (int)date('m', strtotime($entry->schedule_date));
            if (isset($monthly_totals[$entry->user_id][$entry_month])) {
                $monthly_totals[$entry->user_id][$entry_month] += $hours;
            }
            if ($entry->schedule_date >= $weekStartDate && $entry->schedule_date <= $weekEndDate) {
                if (isset($weekly_totals[$entry->user_id])) {
                    $weekly_totals[$entry->user_id] += $hours;
                }
            }
        }

        $data = array(
            'users' => $users, 'shifts' => $shifts, 'week_dates' => $week_dates,
            'schedules' => $schedules, 'weekly_totals' => $weekly_totals, 'monthly_totals' => $monthly_totals, 
            'holidays' => $holidays, 'requests' => $requests,
            'nombres_mes' => $nombres_mes, 'month1_num' => $month1_num, 'month2_num' => $month2_num,
            'current_week_string' => $current_week_string,
            'prev_week_string' => date('Y-\WW', strtotime($current_week_string . ' -1 week')),
            'next_week_string' => date('Y-\WW', strtotime($current_week_string . ' +1 week'))
            
        );
        $templates = $this->templateModel->getTemplatesByCompany($_SESSION['user_company_id']);
        $data['templates'] = $templates; 
        $this->view('admin/weekly_planner', $data);
    }

    public function saveDayAjax(){
        header('Content-Type: application/json');
        
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $userId = $_POST['user_id'];
            $date = $_POST['date'];
            $entries = isset($_POST['schedules'][$userId][$date]) ? $_POST['schedules'][$userId][$date] : array();

            if($this->workScheduleModel->saveDaySchedule($userId, $date, $entries)){
                // Recalcular totales actualizados para este usuario
                $weekStartDate = date('Y-m-d', strtotime(date('Y', strtotime($date)) . "W" . date('W', strtotime($date)) . 1));
                $weekEndDate = date('Y-m-d', strtotime($weekStartDate . ' +6 days'));
                
                // Obtener datos para todos los meses que toca la semana actual
                $month1_start = date('Y-m-01', strtotime($weekStartDate));
                $month1_end = date('Y-m-t', strtotime($weekStartDate));
                $allEntries = $this->workScheduleModel->getScheduleEntriesForPeriod($_SESSION['user_company_id'], $month1_start, $month1_end);

                $month2_start = date('Y-m-01', strtotime($weekEndDate));
                if ($month1_start != $month2_start) {
                    $month2_end = date('Y-m-t', strtotime($weekEndDate));
                    $month2_entries = $this->workScheduleModel->getScheduleEntriesForPeriod($_SESSION['user_company_id'], $month2_start, $month2_end);
                    $allEntries = array_merge($allEntries, $month2_entries);
                }

                $shifts = $this->shiftModel->getShiftsWithRangesByCompany($_SESSION['user_company_id']);
                $shiftsById = array();
                foreach($shifts as $s){ $shiftsById[$s->id] = $s; }

                $weekly_total = 0;
                $monthly_totals = array(); // <-- Será un objeto/array

                foreach($allEntries as $entry){
                    if($entry->user_id != $userId) continue;
                    
                    $hours = 0;
                    if ($entry->type == 'shift' && isset($shiftsById[$entry->shift_id])) {
                        $hours = $shiftsById[$entry->shift_id]->total_hours;
                    } elseif (($entry->type == 'custom' || $entry->type == 'overtime') && !empty($entry->start_time)) {
                        $start = strtotime($entry->start_time); $end = strtotime($entry->end_time);
                        if ($end < $start) { $end += 24 * 3600; }
                        $hours = ($end - $start) / 3600;
                    }
                    
                    $entry_month = (int)date('m', strtotime($entry->schedule_date));
                    if (!isset($monthly_totals[$entry_month])) {
                        $monthly_totals[$entry_month] = 0;
                    }
                    $monthly_totals[$entry_month] += $hours;

                    if ($entry->schedule_date >= $weekStartDate && $entry->schedule_date <= $weekEndDate) {
                        $weekly_total += $hours;
                    }
                }
                
                echo json_encode(array(
                    'success' => true, 
                    'weekly_total' => $weekly_total, 
                    'monthly_totals' => $monthly_totals // <-- Devuelve el objeto completo
                ));
            } else {
                echo json_encode(array('success' => false, 'message' => 'Error al guardar en la base de datos.'));
            }
        } else {
            echo json_encode(array('success' => false, 'message' => 'Método no permitido.'));
        }
    }


     public function deleteShift($id){
        $this->shiftModel->deleteShift($id);
        $_SESSION['flash_success'] = 'Turno eliminado con éxito.';
        redirect('admin/shiftManager');
    }

    public function holidays(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $data = [
                'name' => trim($_POST['name']),
                'holiday_date' => trim($_POST['holiday_date']),
                'company_id' => $_SESSION['user_company_id']
            ];
            if(!empty($data['name']) && !empty($data['holiday_date'])){
                $this->holidayModel->createHoliday($data);
                $_SESSION['flash_success'] = 'Feriado añadido con éxito.';
            } else {
                $_SESSION['flash_error'] = 'Por favor, completa todos los campos.';
            }
            redirect('admin/holidays');
        } else {
            $holidays = $this->holidayModel->getHolidaysByCompany($_SESSION['user_company_id']);
            $this->view('admin/holidays', ['holidays' => $holidays]);
        }
    }

     public function deleteHoliday($id){
        if($this->holidayModel->deleteHoliday($id)){
            $_SESSION['flash_success'] = 'Feriado eliminado con éxito.';
        }
        redirect('admin/holidays');
    }

    public function templates(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $weekString = $_POST['source_week']; // Formato '2025-W30'
            list($year, $week) = sscanf($weekString, '%d-W%d');
            $startDate = date('Y-m-d', strtotime($year . "W" . $week . 1));
            $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));
            
            // Usamos el modelo WorkSchedule para obtener los horarios de la semana de origen
            $entries = $this->workScheduleModel->getScheduleEntriesForPeriod($_SESSION['user_company_id'], $startDate, $endDate);

            $data = [
                'template_name' => trim($_POST['template_name']),
                'company_id' => $_SESSION['user_company_id'],
                'entries' => $entries
            ];

            if(!empty($data['template_name']) && !empty($data['entries'])){
                $this->templateModel->createTemplateFromWeek($data);
                $_SESSION['flash_success'] = 'Plantilla guardada con éxito.';
            } else {
                $_SESSION['flash_error'] = 'El nombre es requerido y la semana de origen no puede estar vacía.';
            }
            redirect('admin/templates');

        } else {
            $templates = $this->templateModel->getTemplatesByCompany($_SESSION['user_company_id']);
            $this->view('admin/templates', ['templates' => $templates]);
        }
    }

    public function deleteTemplate($id){
        if($this->templateModel->deleteTemplate($id)){
            $_SESSION['flash_success'] = 'Plantilla eliminada con éxito.';
        }
        redirect('admin/templates');
    }


    public function applyTemplate(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $templateId = $_POST['template_id'];
            $weekString = $_POST['target_week'];
            list($year, $week) = sscanf($weekString, '%d-W%d');
            $startDate = date('Y-m-d', strtotime($year . "W" . $week . 1));

            if($this->templateModel->applyTemplateToWeek($templateId, $startDate, $_SESSION['user_company_id'])){
                $_SESSION['flash_success'] = 'Plantilla aplicada con éxito.';
            } else {
                $_SESSION['flash_error'] = 'Error al aplicar la plantilla.';
            }
            redirect('admin/weeklyPlanner?week=' . $weekString);
        }
    }

    public function reports(){
        // Definir filtros (fechas por defecto: mes actual)
        $filters = [
            'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'),
            'end_date'   => isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'),
            'user_id'    => isset($_GET['user_id']) ? $_GET['user_id'] : 'all'
        ];

        $users = $this->userModel->getUsersByCompany($_SESSION['user_company_id']);
        $report_data = array();

        // Solo generar reporte si se ha enviado el formulario (hay un start_date)
        if(isset($_GET['start_date'])){
            $allEntries = $this->workScheduleModel->getScheduleEntriesForPeriod($_SESSION['user_company_id'], $filters['start_date'], $filters['end_date']);
            
            // Filtrar por usuario si no son "todos"
            $filteredEntries = ($filters['user_id'] != 'all') 
                ? array_filter($allEntries, function($e) use ($filters) { return $e->user_id == $filters['user_id']; })
                : $allEntries;

            // Procesar los datos para el reporte
            foreach($filteredEntries as $entry){
                $userId = $entry->user_id;
                if(!isset($report_data[$userId])){
                    $user = $this->userModel->getUserById($userId);
                    $report_data[$userId] = [
                        'full_name' => $user->full_name,
                        'limit' => $user->weekly_hour_limit,
                        'regular_hours' => 0,
                        'overtime_hours' => 0,
                        'total_hours' => 0
                    ];
                }

                $hours = 0;
                if (!empty($entry->start_time) && !empty($entry->end_time)) {
                    $start = strtotime($entry->start_time); $end = strtotime($entry->end_time);
                    if ($end < $start) { $end += 24 * 3600; }
                    $hours = ($end - $start) / 3600;
                }

                if($entry->type == 'overtime'){
                    $report_data[$userId]['overtime_hours'] += $hours;
                } else {
                    $report_data[$userId]['regular_hours'] += $hours;
                }
                $report_data[$userId]['total_hours'] += $hours;
            }
        }
        
        $data = [
            'users' => $users,
            'filters' => $filters,
            'report_data' => $report_data
        ];

        $this->view('admin/reports', $data);
    }

    /**
     * NUEVO: Exporta el reporte generado a un archivo CSV.
     */
    public function exportReportCsv(){
        // Re-generamos los datos exactamente como en el reporte
        $filters = [
            'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'),
            'end_date'   => isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'),
            'user_id'    => isset($_GET['user_id']) ? $_GET['user_id'] : 'all'
        ];
        // ... (copiar la misma lógica de generación de $report_data del método reports() aquí) ...

        // Lógica para generar el CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="reporte_horas_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        // Encabezados del CSV
        fputcsv($output, ['Empleado', 'Horas Regulares', 'Horas Extras', 'Total Horas']);

        foreach($report_data as $report){
            fputcsv($output, [
                $report['full_name'],
                number_format($report['regular_hours'], 2, ',', ''),
                number_format($report['overtime_hours'], 2, ',', ''),
                number_format($report['total_hours'], 2, ',', '')
            ]);
        }
        fclose($output);
    }
    
}
?>
