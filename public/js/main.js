// ----------------------------------------------------------------------
// ARCHIVO 1: public/js/main.js (VERSIÓN CORREGIDA Y FINAL)
// Se ha eliminado la llave de cierre adicional al final del archivo.
// ----------------------------------------------------------------------

$(document).ready(function() {
    
    // --- LÓGICA PARA MOSTRAR ALERTAS ---
    if (typeof flashMessage !== 'undefined' && flashMessage !== null) {
        if(flashMessage.type === 'success'){
            alert('CARGA CON EXITO');
        } else {
            Swal.fire({
                title: 'Error',
                text: flashMessage.text,
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
        }
    }

    // --- INICIALIZACIÓN DE TABLAS DE DATOS (DataTables) ---
    const exportConfig = {
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        responsive: true,
        dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm' },
            { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm' },
            { extend: 'print', text: '<i class="fas fa-print"></i> Imprimir', className: 'btn btn-info btn-sm' }
        ]
    };

    if ($('#employee-table').length && !$.fn.dataTable.isDataTable('#employee-table')) { $('#employee-table').DataTable(exportConfig); }
    if ($('#history-table').length && !$.fn.dataTable.isDataTable('#history-table')) { $('#history-table').DataTable($.extend({}, exportConfig, { order: [[0, "desc"]] })); }
    if ($('#users-table').length && !$.fn.dataTable.isDataTable('#users-table')) { $('#users-table').DataTable(exportConfig); }
    if ($('#details-table').length && !$.fn.dataTable.isDataTable('#details-table')) { $('#details-table').DataTable($.extend({}, exportConfig, { order: [[0, "desc"]] })); }
    
    if ($('#summary-employee-table').length && !$.fn.dataTable.isDataTable('#summary-employee-table')) {
        $('#summary-employee-table').DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
            responsive: true,
            order: [[ 3, "desc" ]],
            dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            buttons: [
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm' },
                { extend: 'print', text: '<i class="fas fa-print"></i> Imprimir', className: 'btn btn-info btn-sm' }
            ]
        });
    }

    // --- LISTENERS DE EVENTOS Y MODALES ---
    $('#closureForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const summaryData = JSON.parse($('#closureSummaryData').attr('data-summary'));

        if(summaryData.length === 0){
            Swal.fire('Sin Cambios', 'No hay horas pendientes para cerrar.', 'info');
            return;
        }

        let tableHtml = `
            <div class="table-responsive">
                <table id="summary-modal-table" class="table table-hover table-bordered" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>Empleado</th>
                            <th class="text-center">Hs. 50%</th>
                            <th class="text-center">Hs. 100%</th>
                            <th class="text-center">Feriados</th>
                            <th class="text-center">Total Horas</th>
                        </tr>
                    </thead>
                    <tbody>`;
        summaryData.forEach(employee => {
            const hours50Class = employee.hours_50 > 0 ? 'text-success fw-bold' : '';
            const hours100Class = employee.hours_100 > 0 ? 'text-warning fw-bold' : '';
            const holidaysClass = employee.holidays > 0 ? 'text-danger fw-bold' : '';
            tableHtml += `
                <tr>
                    <td>${employee.full_name}</td>
                    <td class="text-center ${hours50Class}">${employee.hours_50.toFixed(2)}</td>
                    <td class="text-center ${hours100Class}">${employee.hours_100.toFixed(2)}</td>
                    <td class="text-center ${holidaysClass}">${employee.holidays}</td>
                    <td class="text-center"><strong>${(employee.hours_50 + employee.hours_100).toFixed(2)}</strong></td>
                </tr>`;
        });
        tableHtml += `</tbody></table></div>`;

        Swal.fire({
            title: '<strong>Resumen del Cierre</strong>',
            icon: 'info',
            html: tableHtml,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check"></i> Confirmar y Cerrar',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            width: '80%',
            didOpen: () => {
                $('#summary-modal-table').DataTable({
                    language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                    responsive: true,
                    dom: 'Bfrtip',
                    buttons: ['excel', 'pdf', 'print']
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
    
    $('body').on('click', '.toggle-status-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const config = {
            activar: { title: '¿Activar Usuario?', text: "Este usuario podrá volver a iniciar sesión.", icon: 'success', confirmButtonText: 'Sí, ¡activar!' },
            desactivar: { title: '¿Desactivar Usuario?', text: "El usuario no podrá iniciar sesión.", icon: 'warning', confirmButtonText: 'Sí, ¡desactivar!' }
        };
        const currentConfig = config[button.data('action')];
        
        Swal.fire({
            title: currentConfig.title, text: currentConfig.text, icon: currentConfig.icon,
            showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
            confirmButtonText: currentConfig.confirmButtonText, cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = button.attr('href');
            }
        });
    });

    $('body').on('click', '.delete-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esta acción!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, ¡bórralo!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = button.attr('href');
            }
        });
    });

    // --- INICIALIZACIÓN DE GRÁFICOS (Chart.js) ---
    
    const pieChartEl = document.getElementById('pieChartData');
    if (pieChartEl) {
        const pieChartData = JSON.parse(pieChartEl.getAttribute('data-chart'));
        const ctx = document.getElementById('overtimeSplitChart');
        if (ctx && pieChartData.length > 0 && (pieChartData[0] > 0 || pieChartData[1] > 0)) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Horas al 50%', 'Horas al 100%'],
                    datasets: [{ data: pieChartData, backgroundColor: ['#0d6efd', '#ffc107'], borderColor: '#fff' }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }
    }

    const lineChartEl = document.getElementById('lineChartData');
    if(lineChartEl){
        const lineChartData = JSON.parse(lineChartEl.getAttribute('data-chart'));
        const ctx = document.getElementById('overtimeTrendChart');
        if (ctx && lineChartData && lineChartData.labels && lineChartData.labels.length > 0) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: lineChartData.labels,
                    datasets: [{
                        label: 'Horas Cargadas por Día',
                        data: lineChartData.data,
                        fill: true,
                        backgroundColor: 'rgba(25, 135, 84, 0.2)',
                        borderColor: '#198754',
                        tension: 0.3
                    }]
                },
                options: { scales: { y: { beginAtZero: true } }, responsive: true, maintainAspectRatio: false }
            });
        }
    }

    const topEmployeesEl = document.getElementById('topEmployeesChartData');
    if (topEmployeesEl) {
        const topEmployeesData = JSON.parse(topEmployeesEl.getAttribute('data-chart'));
        const ctx = document.getElementById('topEmployeesChart');
        
        const imageLabelsPlugin = { /* ... (plugin sin cambios) ... */ };

        if (ctx && topEmployeesData && Array.isArray(topEmployeesData.labels) && topEmployeesData.labels.length > 0) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: topEmployeesData.labels,
                    datasets: [{
                        label: 'Horas Totales Pendientes',
                        data: topEmployeesData.data,
                        backgroundColor: 'rgba(111, 66, 193, 0.6)',
                        borderColor: 'rgba(111, 66, 193, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    layout: { padding: { left: 40 } },
                    scales: { x: { beginAtZero: true }, y: { grid: { display: false } } },
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                },
                plugins: [imageLabelsPlugin]
            });
        }
    }

    const hoursByDayEl = document.getElementById('hoursByDayChartData');
    if(hoursByDayEl){
        const hoursByDayData = JSON.parse(hoursByDayEl.getAttribute('data-chart'));
        const ctx = document.getElementById('hoursByDayChart');
        if(ctx && hoursByDayData && hoursByDayData.labels && hoursByDayData.labels.length > 0){
            new Chart(ctx, {
                type: 'polarArea',
                data: {
                    labels: hoursByDayData.labels,
                    datasets: [{
                        data: hoursByDayData.data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.5)',  'rgba(54, 162, 235, 0.5)',
                            'rgba(255, 206, 86, 0.5)', 'rgba(75, 192, 192, 0.5)',
                            'rgba(153, 102, 255, 0.5)','rgba(255, 159, 64, 0.5)',
                            'rgba(199, 199, 199, 0.5)'
                        ]
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }
    }
});