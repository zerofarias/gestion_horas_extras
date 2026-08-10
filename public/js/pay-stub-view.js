document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('payStubFullscreenModal');
    var body = document.getElementById('payStubFullscreenBody');
    if (!modalEl || !body || typeof bootstrap === 'undefined') {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    function openFullscreen(url, isPdf) {
        if (!url) return;
        body.innerHTML = '';
        if (isPdf) {
            var iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.className = 'pay-stub-fullscreen-iframe';
            iframe.title = 'Recibo PDF';
            body.appendChild(iframe);
        } else {
            var img = document.createElement('img');
            img.src = url;
            img.className = 'pay-stub-fullscreen-img';
            img.alt = 'Recibo';
            body.appendChild(img);
        }
        modal.show();
    }

    function bindExpand(btn, isPdf) {
        if (!btn) return;
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-file-url');
            var pdf = isPdf !== undefined ? isPdf : btn.getAttribute('data-is-pdf') === '1';
            openFullscreen(url, pdf);
        });
    }

    bindExpand(document.getElementById('btnPayStubExpand'));
    bindExpand(document.getElementById('btnPayStubExpandImg'), false);

    modalEl.addEventListener('hidden.bs.modal', function () {
        body.innerHTML = '';
    });
});
