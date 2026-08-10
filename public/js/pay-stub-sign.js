(function () {
    var canvas = document.getElementById('signatureCanvas');
    var form = document.getElementById('signPayStubForm');
    if (!canvas || !form || typeof SignaturePad === 'undefined') return;

    function resize() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
    }
    resize();
    window.addEventListener('resize', resize);

    var pad = new SignaturePad(canvas, { backgroundColor: 'rgb(250, 250, 250)' });
    var clearBtn = document.getElementById('clearSignature');
    if (clearBtn) clearBtn.addEventListener('click', function () { pad.clear(); });

    form.addEventListener('submit', function (e) {
        if (pad.isEmpty()) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Firma requerida', text: 'Dibujá tu firma antes de confirmar.' });
            } else {
                alert('Dibujá tu firma antes de confirmar.');
            }
            return;
        }
        document.getElementById('signatureData').value = pad.toDataURL('image/png');
    });
})();
