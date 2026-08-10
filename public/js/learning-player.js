(function () {
    var form = document.getElementById('reviewForm');
    if (!form) return;

    var voteInput = document.getElementById('reviewVote');
    var submitBtn = document.getElementById('reviewSubmitBtn');
    var buttons = form.querySelectorAll('[data-vote]');

    function setVote(v) {
        voteInput.value = v;
        buttons.forEach(function (btn) {
            var active = btn.getAttribute('data-vote') === v;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        if (submitBtn) submitBtn.disabled = !v;
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setVote(btn.getAttribute('data-vote'));
        });
    });
})();
