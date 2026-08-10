(function () {
    var companySelect = document.getElementById('company_id');
    var areaSelect = document.getElementById('area_id');
    if (!companySelect || !areaSelect || !window.AREAS_BY_COMPANY) {
        return;
    }

    var wrap = document.getElementById('user-area-field-wrap');
    var preferredArea = wrap ? parseInt(wrap.getAttribute('data-selected-area') || '0', 10) : 0;

    function refreshAreas() {
        var companyId = companySelect.value || '';
        var areas = window.AREAS_BY_COMPANY[companyId] || window.AREAS_BY_COMPANY[String(companyId)] || [];
        var current = areaSelect.value;

        areaSelect.innerHTML = '';
        var emptyOpt = document.createElement('option');
        emptyOpt.value = '';
        emptyOpt.textContent = '— Sin área —';
        areaSelect.appendChild(emptyOpt);

        areas.forEach(function (a) {
            var opt = document.createElement('option');
            opt.value = String(a.id);
            var label = a.name;
            if (a.scope === 'global') {
                label += ' (todas las empresas)';
            }
            opt.textContent = label;
            areaSelect.appendChild(opt);
        });

        var pick = preferredArea > 0 ? String(preferredArea) : current;
        if (pick && areaSelect.querySelector('option[value="' + pick + '"]')) {
            areaSelect.value = pick;
        } else {
            areaSelect.value = '';
        }
        preferredArea = 0;

        var hint = document.getElementById('user-area-empty-hint');
        if (hint) {
            hint.classList.toggle('d-none', areas.length > 0 || !companyId);
        }
    }

    companySelect.addEventListener('change', function () {
        preferredArea = 0;
        refreshAreas();
    });

    refreshAreas();
})();
