(function () {
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!token) return;
    var csrf = token.getAttribute('content');
    var cfg = window.EMP_ANNOUNCEMENTS || {};
    var root = cfg.urlRoot || '';

    function markRead(id) {
        if (!id) return;
        var fd = new FormData();
        fd.append('csrf_token', csrf);
        fetch(root + '/employee/markNotificationRead/' + id, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) return;
                var badge = document.getElementById('empNotifyBadge');
                var count = document.getElementById('empNotifyMenuCount');
                if (data.unread === 0) {
                    if (badge) badge.remove();
                } else if (badge) {
                    badge.textContent = data.unread > 99 ? '99+' : data.unread;
                }
                if (count) count.textContent = data.unread;
            })
            .catch(function () {});
    }

    document.querySelectorAll('.emp-notif-item[data-notif-id]').forEach(function (el) {
        el.addEventListener('click', function () {
            markRead(el.getAttribute('data-notif-id'));
        });
    });
})();
