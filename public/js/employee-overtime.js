(function () {

    function escapeHtml(text) {

        var d = document.createElement('div');

        d.textContent = text == null ? '' : String(text);

        return d.innerHTML;

    }



    function buildMonthTableHtml(entries, highlightId) {

        if (!entries || !entries.length) {

            return '<p class="text-muted mb-0 small">No hay otras cargas este mes.</p>';

        }

        var rows = entries.map(function (e) {

            var hi = highlightId && e.id === highlightId ? ' style="background:#ecfdf5;font-weight:600;"' : '';

            var hol = e.is_holiday ? ' <span class="badge bg-danger ms-1">Feriado</span>' : '';

            var hDetail = '';

            if (e.hours_100 > 0 && e.hours_50 > 0) {

                hDetail = e.hours + 'h (' + e.hours_50 + ' al 50% + ' + e.hours_100 + ' al 100%)';

            } else if (e.hours_100 > 0) {

                hDetail = e.hours + 'h al 100%';

            } else {

                hDetail = e.hours + 'h al 50%';

            }

            return '<tr' + hi + '>' +

                '<td>' + escapeHtml(e.date) + '</td>' +

                '<td>' + escapeHtml(e.time) + hol + '</td>' +

                '<td>' + escapeHtml(hDetail) + '</td>' +

                '<td><span class="badge ' + (e.status === 'archived' ? 'bg-success' : 'bg-warning text-dark') + '">' +

                escapeHtml(e.status_label) + '</span></td>' +

                '</tr>';

        }).join('');

        return '<div class="table-responsive mt-2"><table class="table table-sm mb-0 swal-month-table">' +

            '<thead><tr><th>Fecha</th><th>Horario</th><th>Hs.</th><th>Estado</th></tr></thead>' +

            '<tbody>' + rows + '</tbody></table></div>';

    }



    function showSuccessFeedback(feedback) {

        if (!feedback || typeof Swal === 'undefined') {

            return;

        }

        var saved = feedback.saved || {};

        var summary = feedback.month_summary || {};

        var highlightId = saved.id || null;



        var savedBlock = '<div class="ot-swal-saved">' +

            '<p class="mb-2"><strong>Última carga registrada</strong></p>' +

            '<ul class="list-unstyled mb-0 small text-start">' +

            '<li><i class="fas fa-calendar-day me-2 text-primary"></i>' + escapeHtml(saved.date) + '</li>' +

            '<li><i class="fas fa-clock me-2 text-primary"></i>' + escapeHtml(saved.time) + '</li>' +

            '<li><i class="fas fa-hourglass-half me-2 text-primary"></i>' +

            escapeHtml(saved.hours) + ' h' + (saved.is_holiday ? ' (feriado)' : '') + '</li>' +

            '<li><i class="fas fa-comment me-2 text-primary"></i>' + escapeHtml(saved.reason) + '</li>' +

            '</ul>' +

            '<p class="small text-muted mt-2 mb-0">Quedó <strong>pendiente de cierre</strong> por RRHH. Te avisamos cuando se procese el mes.</p>' +

            '</div>';



        var monthBlock = '<div class="ot-swal-month mt-3 pt-3 border-top">' +

            '<p class="mb-1"><strong>Tus horas de ' + escapeHtml(feedback.month_label || 'este mes') + '</strong></p>' +

            '<p class="small text-muted mb-2">' +

            escapeHtml(summary.count || 0) + ' carga(s) · ' +

            '<strong>' + escapeHtml(summary.hours_total || 0) + ' h</strong> en total' +

            (summary.pending_count ? ' · ' + escapeHtml(summary.pending_count) + ' pendiente(s)' : '') +

            '</p>' +

            buildMonthTableHtml(feedback.month_entries, highlightId) +

            '</div>';



        Swal.fire({

            icon: 'success',

            title: '¡Horas guardadas!',

            html: savedBlock + monthBlock,

            confirmButtonText: 'Ver en mi listado',

            confirmButtonColor: '#2563eb',

            width: 'min(520px, 94vw)',

            customClass: { popup: 'ot-swal-popup' },

        }).then(function () {

            var el = document.getElementById('monthSummary');

            if (el) {

                el.scrollIntoView({ behavior: 'smooth', block: 'start' });

            }

        });

    }



    function showSimpleError(codeOrMessage) {
        if (!codeOrMessage || typeof Swal === 'undefined') {
            return;
        }
        var isDuplicate = codeOrMessage === 'duplicate';
        Swal.fire({
            icon: isDuplicate ? 'warning' : 'error',
            title: isDuplicate ? 'Carga duplicada' : 'No se pudo guardar',
            text: isDuplicate
                ? 'Ya existe una carga de horas idéntica para esta fecha y horario.'
                : String(codeOrMessage),
            confirmButtonText: 'Entendido',
            confirmButtonColor: isDuplicate ? '#f59e0b' : '#dc3545',
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.OVERTIME_ERROR) {
            showSimpleError(window.OVERTIME_ERROR);
            window.OVERTIME_ERROR = null;
        }

        if (window.OVERTIME_FEEDBACK) {
            showSuccessFeedback(window.OVERTIME_FEEDBACK);
            window.OVERTIME_FEEDBACK = null;
        }

        var form = document.getElementById('formHorasExtras');

        if (form) {

            form.addEventListener('submit', function () {

                var btn = form.querySelector('button[type="submit"]');

                if (btn && !btn.disabled) {

                    btn.disabled = true;

                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

                }

            });

        }

    });

})();

