(function () {
    function bindScopeToggle(form) {
        if (!form) return;
        var scopeAll = form.querySelector('.area-scope-all');
        var scopeCompany = form.querySelector('.area-scope-company');
        var companyWrap = form.querySelector('.area-company-wrap');
        var companySelect = form.querySelector('.area-company-select');

        function sync() {
            var isCompany = scopeCompany && scopeCompany.checked;
            if (companyWrap) {
                companyWrap.classList.toggle('d-none', !isCompany);
            }
            if (companySelect) {
                companySelect.required = !!isCompany;
                if (!isCompany) {
                    companySelect.value = '';
                }
            }
        }

        if (scopeAll) scopeAll.addEventListener('change', sync);
        if (scopeCompany) scopeCompany.addEventListener('change', sync);
        sync();
    }

    document.querySelectorAll('[data-area-form]').forEach(bindScopeToggle);

    document.querySelectorAll('[data-edit-area]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = document.getElementById('editAreaModal');
            if (!modal) return;
            var form = modal.querySelector('form');
            if (!form) return;

            form.querySelector('[name="id"]').value = btn.getAttribute('data-id') || '';
            form.querySelector('[name="name"]').value = btn.getAttribute('data-name') || '';
            var scope = btn.getAttribute('data-scope') || 'all';
            var scopeAll = form.querySelector('.area-scope-all');
            var scopeCompany = form.querySelector('.area-scope-company');
            if (scope === 'company' && scopeCompany) {
                scopeCompany.checked = true;
            } else if (scopeAll) {
                scopeAll.checked = true;
            }
            var companySelect = form.querySelector('.area-company-select');
            if (companySelect) {
                companySelect.value = btn.getAttribute('data-company-id') || '';
            }
            var activeCheck = form.querySelector('[name="is_active"]');
            if (activeCheck) {
                activeCheck.checked = btn.getAttribute('data-active') === '1';
            }
            var showOt = form.querySelector('[name="show_overtime"]');
            if (showOt) {
                showOt.value = btn.getAttribute('data-show-overtime') || 'inherit';
            }
            var showCp = form.querySelector('[name="show_cp_extras"]');
            if (showCp) {
                showCp.value = btn.getAttribute('data-show-cp-extras') || 'inherit';
            }
            bindScopeToggle(form);
        });
    });
})();
