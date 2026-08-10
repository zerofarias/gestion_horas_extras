(function () {
    function initSalaryAdvanceAdmin() {
        if (typeof bootstrap === 'undefined') {
            return;
        }

        var entriesById = {};
        (window.SA_ENTRIES || []).forEach(function (e) {
            entriesById[e.id] = e;
        });

        var canvasEl = document.getElementById('saReviewCanvas');
        if (!canvasEl) {
            return;
        }

        var canvas = bootstrap.Offcanvas.getOrCreateInstance(canvasEl);
        var bodyEl = document.getElementById('saReviewBody');
        var titleEl = document.getElementById('saReviewTitle');

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s == null ? '' : String(s);
            return d.innerHTML;
        }

        function defaultMonths(count) {
            var out = [];
            var dt = new Date();
            dt.setDate(1);
            for (var i = 0; i < count; i++) {
                var y = dt.getFullYear();
                var m = String(dt.getMonth() + 1).padStart(2, '0');
                out.push(y + '-' + m);
                dt.setMonth(dt.getMonth() + 1);
            }
            return out;
        }

        function splitAmounts(total, count) {
            total = Math.round(total * 100) / 100;
            count = Math.max(1, count);
            var base = Math.floor((total / count) * 100) / 100;
            var amounts = [];
            var assigned = 0;
            for (var i = 0; i < count - 1; i++) {
                amounts.push(base);
                assigned += base;
            }
            amounts.push(Math.round((total - assigned) * 100) / 100);
            return amounts;
        }

        function renderApproveInstallmentFields(e, count) {
            var wrap = document.getElementById('saApproveInstallments');
            if (!wrap) return;
            count = Math.max(1, parseInt(count, 10) || 1);
            var months = defaultMonths(count);
            var amounts = splitAmounts(e.amount, count);
            var html = '<div class="mb-2 fw-semibold small">Plan de devolución</div>';
            html += '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>#</th><th>Mes descuento</th><th>Monto</th></tr></thead><tbody>';
            for (var i = 0; i < count; i++) {
                html += '<tr><td>' + (i + 1) + '</td>' +
                    '<td><input type="month" name="installment_month[]" class="form-control form-control-sm" value="' + esc(months[i]) + '" required></td>' +
                    '<td><input type="number" name="installment_amount[]" class="form-control form-control-sm" step="0.01" min="0.01" value="' + amounts[i].toFixed(2) + '" required></td></tr>';
            }
            html += '</tbody></table></div>';
            html += '<div class="form-text">La suma de las cuotas debe coincidir con ' + esc(e.amount_fmt) + '.</div>';
            wrap.innerHTML = html;
        }

        function bindApproveForm(e) {
            var countInput = document.getElementById('saInstallmentsApproved');
            if (!countInput) return;
            renderApproveInstallmentFields(e, countInput.value);
            countInput.addEventListener('input', function () {
                renderApproveInstallmentFields(e, countInput.value);
            });
        }

        function renderApprovedPanel(advance, installments) {
            var isFinalized = advance.status === 'Finalizado';
            var html = '<dl class="row small mb-3">' +
                '<dt class="col-5">Fecha</dt><dd class="col-7">' + esc(advance.created_at_fmt) + '</dd>' +
                '<dt class="col-5">Monto</dt><dd class="col-7">' + esc(advance.amount_fmt) + '</dd>' +
                '<dt class="col-5">Estado</dt><dd class="col-7"><span class="badge ' + (isFinalized ? 'bg-secondary' : 'bg-success') + '">' + esc(advance.status) + '</span></dd>' +
                '</dl>';

            if (isFinalized) {
                html += '<div class="alert alert-success small"><i class="fas fa-check-circle me-1"></i>Todas las cuotas fueron descontadas. Este adelanto está finalizado.</div>';
            }

            if (!window.SA_INSTALLMENTS_READY) {
                html += '<div class="alert alert-warning small">Ejecutá <code>migration_salary_advance_installments.sql</code> (#39) para gestionar cuotas y recibos.</div>';
                bodyEl.innerHTML = html;
                return;
            }

            if (!installments.length) {
                html += '<div class="alert alert-secondary small mb-3">Este adelanto no tiene cuotas registradas. Generá un plan inicial (podés editar meses e importes después).</div>' +
                    '<form method="post" action="' + esc(window.SA_URLROOT + '/salaryAdvanceAdmin/process') + '">' +
                    '<input type="hidden" name="csrf_token" value="' + esc(window.SA_CSRF || '') + '">' +
                    '<input type="hidden" name="id" value="' + advance.id + '">' +
                    '<input type="hidden" name="action" value="generate_schedule">' +
                    '<button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-calendar-plus me-1"></i>Generar plan de cuotas</button>' +
                    '</form>';
                bodyEl.innerHTML = html;
                return;
            }

            if (isFinalized) {
                html += '<div class="border-top pt-3 mb-3">' +
                    '<div class="fw-semibold small mb-2">Plan de devolución</div>' +
                    '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>#</th><th>Mes</th><th>Monto</th><th>Desc.</th><th>Recibo</th></tr></thead><tbody>';
                installments.forEach(function (inst) {
                    html += '<tr class="table-success">' +
                        '<td>' + inst.installment_number + '</td>' +
                        '<td>' + esc(inst.due_month_label || inst.due_month) + '</td>' +
                        '<td>' + esc(inst.amount_fmt) + '</td>' +
                        '<td class="text-center"><i class="fas fa-check text-success" title="' + esc(inst.deducted_at_fmt) + '"></i></td>' +
                        '<td class="text-nowrap"><a class="btn btn-outline-secondary btn-sm" target="_blank" href="' + esc(window.SA_URLROOT + '/salaryAdvanceAdmin/receipt/' + advance.id + '/' + inst.installment_number) + '"><i class="fas fa-print"></i></a></td>' +
                        '</tr>';
                });
                html += '</tbody></table></div></div>';
            } else {
                html += '<form method="post" action="' + esc(window.SA_URLROOT + '/salaryAdvanceAdmin/process') + '" class="border-top pt-3 mb-3">' +
                    '<input type="hidden" name="csrf_token" value="' + esc(window.SA_CSRF || '') + '">' +
                    '<input type="hidden" name="id" value="' + advance.id + '">' +
                    '<input type="hidden" name="action" value="save_schedule">' +
                    '<div class="fw-semibold small mb-2">Plan de devolución</div>' +
                    '<div class="table-responsive"><table class="table table-sm align-middle mb-2"><thead><tr><th>#</th><th>Mes</th><th>Monto</th><th>Desc.</th><th>Recibo</th></tr></thead><tbody>';

                installments.forEach(function (inst) {
                    html += '<tr class="' + (inst.is_deducted ? 'table-success' : '') + '">' +
                        '<td>' + inst.installment_number + '<input type="hidden" name="installment_id[]" value="' + inst.id + '"></td>' +
                        '<td><input type="month" name="installment_month[]" class="form-control form-control-sm" value="' + esc(inst.due_month) + '" required></td>' +
                        '<td><input type="number" name="installment_amount[]" class="form-control form-control-sm" step="0.01" min="0.01" value="' + inst.amount.toFixed(2) + '" required></td>' +
                        '<td class="text-center">' + (inst.is_deducted ? '<i class="fas fa-check text-success" title="' + esc(inst.deducted_at_fmt) + '"></i>' : '—') + '</td>' +
                        '<td class="text-nowrap"><a class="btn btn-outline-secondary btn-sm" target="_blank" href="' + esc(window.SA_URLROOT + '/salaryAdvanceAdmin/receipt/' + advance.id + '/' + inst.installment_number) + '"><i class="fas fa-print"></i></a></td>' +
                        '</tr>';
                });

                html += '</tbody></table>' +
                    '<button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Guardar meses e importes</button>' +
                    '</form>';
            }

            html += '<div class="fw-semibold small mb-2">Marcar descuentos realizados</div>';
            if (isFinalized) {
                html += '<p class="small text-muted mb-2">Si desmarcás una cuota, el adelanto vuelve a estado Aprobado.</p>';
            }
            installments.forEach(function (inst) {
                html += '<form method="post" action="' + esc(window.SA_URLROOT + '/salaryAdvanceAdmin/process') + '" class="d-flex align-items-center gap-2 mb-2 border rounded p-2">' +
                    '<input type="hidden" name="csrf_token" value="' + esc(window.SA_CSRF || '') + '">' +
                    '<input type="hidden" name="id" value="' + advance.id + '">' +
                    '<input type="hidden" name="action" value="toggle_deducted">' +
                    '<input type="hidden" name="installment_id" value="' + inst.id + '">' +
                    '<div class="form-check mb-0 flex-grow-1">' +
                    '<input class="form-check-input" type="checkbox" name="is_deducted" value="1" id="deduct-' + inst.id + '" ' + (inst.is_deducted ? 'checked' : '') + ' onchange="this.form.submit()">' +
                    '<label class="form-check-label small" for="deduct-' + inst.id + '">Cuota ' + inst.installment_number + ' — ' + esc(inst.due_month_label) + ' (' + esc(inst.amount_fmt) + ')</label>' +
                    '</div></form>';
            });

            if (advance.admin_notes) {
                html += '<div class="alert alert-light border small mt-3">Notas RRHH: ' + esc(advance.admin_notes) + '</div>';
            }

            bodyEl.innerHTML = html;
        }

        function renderHistory(items) {
            if (!items.length) {
                return '<p class="text-muted small mb-0">Sin historial previo.</p>';
            }
            var html = '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Fecha</th><th>Monto</th><th>Cuotas</th><th>Estado</th></tr></thead><tbody>';
            items.forEach(function (it) {
                html += '<tr><td>' + esc(it.created_at_fmt) + '</td><td>' + esc(it.amount) + '</td><td>' + esc(it.installments_requested) + '</td><td>' + esc(it.status) + '</td></tr>';
            });
            html += '</tbody></table></div>';
            return html;
        }

        function openEntry(id) {
            var e = entriesById[id];
            if (!e) {
                return;
            }
            titleEl.textContent = e.employee_name;
            bodyEl.innerHTML = '<p class="text-muted small">Cargando…</p>';
            canvas.show();

            if (e.status === 'Aprobado' || e.status === 'Finalizado') {
                fetch(window.SA_URLROOT + '/salaryAdvanceAdmin/installments/' + e.id, { credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.ok) {
                            bodyEl.innerHTML = '<p class="text-danger small">No se pudo cargar el plan de cuotas.</p>';
                            return;
                        }
                        renderApprovedPanel(data.advance, data.installments || []);
                    })
                    .catch(function () {
                        bodyEl.innerHTML = '<p class="text-danger small">Error al cargar cuotas.</p>';
                    });
                return;
            }

            var pending = e.status === 'Pendiente';
            var html = '<dl class="row small mb-3">' +
                '<dt class="col-5">Fecha</dt><dd class="col-7">' + esc(e.created_at_fmt) + '</dd>' +
                '<dt class="col-5">Monto</dt><dd class="col-7">' + esc(e.amount_fmt) + '</dd>' +
                '<dt class="col-5">Cuotas pedidas</dt><dd class="col-7">' + esc(e.installments_requested) + '</dd>' +
                '<dt class="col-5">Motivo</dt><dd class="col-7">' + esc(e.reason || '—') + '</dd>' +
                '<dt class="col-5">Estado</dt><dd class="col-7">' + esc(e.status) + '</dd>' +
                '</dl>';

            if (pending) {
                html += '<form method="post" action="' + esc(window.SA_URLROOT + '/salaryAdvanceAdmin/process') + '" class="border-top pt-3">' +
                    '<input type="hidden" name="csrf_token" value="' + esc(window.SA_CSRF || '') + '">' +
                    '<input type="hidden" name="id" value="' + e.id + '">' +
                    '<div class="mb-3"><label class="form-label">Notas RRHH</label><textarea name="admin_notes" class="form-control form-control-sm" rows="2">' + esc(e.admin_notes) + '</textarea></div>' +
                    '<div class="mb-3"><label class="form-label">Cuotas aprobadas</label><input type="number" id="saInstallmentsApproved" name="installments_approved" class="form-control form-control-sm" min="1" max="' + window.SA_MAX_HR + '" value="' + e.installments_requested + '"></div>' +
                    '<div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="hr_installments_override" id="saHrOverride" value="1"><label class="form-check-label" for="saHrOverride">Habilitar más cuotas que el máximo del empleado (hasta ' + window.SA_MAX_HR + ')</label></div>' +
                    '<div id="saApproveInstallments" class="mb-3"></div>' +
                    '<div class="d-flex gap-2 flex-wrap">' +
                    '<button type="submit" name="action" value="approve" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Aprobar</button>' +
                    '<button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm"><i class="fas fa-times me-1"></i>Rechazar</button>' +
                    '</div></form>';
            } else if (e.admin_notes) {
                html += '<div class="alert alert-light border small">Notas RRHH: ' + esc(e.admin_notes) + '</div>';
            }

            html += '<h3 class="h6 mt-4 mb-2">Historial del empleado</h3><div id="saHistoryWrap"><span class="text-muted small">Cargando…</span></div>';
            bodyEl.innerHTML = html;

            if (pending) {
                bindApproveForm(e);
            }

            fetch(window.SA_URLROOT + '/salaryAdvanceAdmin/history/' + e.user_id, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var wrap = document.getElementById('saHistoryWrap');
                    if (!wrap) return;
                    wrap.innerHTML = renderHistory(data.items || []);
                })
                .catch(function () {
                    var wrap = document.getElementById('saHistoryWrap');
                    if (wrap) wrap.innerHTML = '<p class="text-danger small">No se pudo cargar el historial.</p>';
                });
        }

        document.querySelectorAll('.sa-open-detail').forEach(function (btn) {
            btn.addEventListener('click', function (ev) {
                ev.stopPropagation();
                openEntry(parseInt(btn.getAttribute('data-id'), 10));
            });
        });

        document.querySelectorAll('.sa-row-detail').forEach(function (row) {
            row.addEventListener('click', function (ev) {
                if (ev.target.closest('a, button')) {
                    return;
                }
                openEntry(parseInt(row.getAttribute('data-id'), 10));
            });
            row.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    openEntry(parseInt(row.getAttribute('data-id'), 10));
                }
            });
        });

        if (window.SA_OPEN_ID > 0 && entriesById[window.SA_OPEN_ID]) {
            openEntry(window.SA_OPEN_ID);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSalaryAdvanceAdmin);
    } else {
        initSalaryAdvanceAdmin();
    }
})();
