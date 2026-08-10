// public/js/main.js — listeners globales compartidos entre vistas admin

$(document).ready(function() {
    const dtLang = window.DATATABLES_LANG_ES || { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' };
    const exportConfig = {
        language: dtLang,
        dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success btn-sm' },
            { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger btn-sm' },
            { extend: 'print', text: '<i class="fas fa-print"></i> Imprimir', className: 'btn btn-info btn-sm' }
        ]
    };

    if ($('#employee-table').length && !$.fn.dataTable.isDataTable('#employee-table')) {
        $('#employee-table').DataTable(exportConfig);
    }
    if ($('#history-table').length && !$.fn.dataTable.isDataTable('#history-table')) {
        $('#history-table').DataTable($.extend({}, exportConfig, { order: [[0, 'desc']] }));
    }
    if ($('#users-table').length && !$.fn.dataTable.isDataTable('#users-table')) {
        $('#users-table').DataTable(exportConfig);
    }
    if ($('#details-table').length && !$.fn.dataTable.isDataTable('#details-table')) {
        $('#details-table').DataTable($.extend({}, exportConfig, { order: [[0, 'desc']] }));
    }
    if ($('#summary-employee-table').length && !$.fn.dataTable.isDataTable('#summary-employee-table')) {
        $('#summary-employee-table').DataTable($.extend({}, exportConfig, { order: [[3, 'desc']] }));
    }

    $('body').on('click', '[data-confirm-title]', function(e) {
        e.preventDefault();
        const button = $(this);
        Swal.fire({
            title: button.data('confirm-title') || 'Confirmar acción',
            text: button.data('confirm-text') || 'Esta acción requiere confirmación.',
            icon: button.data('confirm-icon') || 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c4156f',
            cancelButtonColor: '#6b7280',
            confirmButtonText: button.data('confirm-button') || 'Continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const href = button.attr('href');
                if (href && href !== '#') {
                    window.location.href = href;
                }
            }
        });
    });

    $('body').on('click', '.toggle-status-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const form = button.closest('form');
        const config = {
            activar: { title: '¿Activar Usuario?', text: 'Este usuario podrá volver a iniciar sesión.', icon: 'success', confirmButtonText: 'Sí, activar' },
            desactivar: { title: '¿Desactivar Usuario?', text: 'El usuario no podrá iniciar sesión.', icon: 'warning', confirmButtonText: 'Sí, desactivar' }
        };
        const currentConfig = config[button.data('action')];
        if (!currentConfig || !form.length) {
            return;
        }

        Swal.fire({
            title: currentConfig.title,
            text: currentConfig.text,
            icon: currentConfig.icon,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: currentConfig.confirmButtonText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.trigger('submit');
            }
        });
    });

    $('body').on('click', '.delete-btn', function(e) {
        const href = $(this).attr('href');
        if (!href || href === '#') {
            return;
        }
        e.preventDefault();
        const button = $(this);
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¡No podrás revertir esta acción!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, borrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = button.attr('href');
            }
        });
    });
});
