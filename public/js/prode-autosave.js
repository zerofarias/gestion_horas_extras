(function () {
    'use strict';
    var cfg = window.PRODE_CONFIG || {};
    if (!cfg.saveUrl) return;

    var timers = {};
    var pendingSaves = {};

    function debounce(key, fn, ms) {
        if (timers[key]) clearTimeout(timers[key]);
        timers[key] = setTimeout(fn, ms || 500);
    }

    function setStatus(matchId, state, text) {
        var el = document.querySelector('[data-status-id="' + matchId + '"]');
        if (!el) return;
        el.classList.remove('is-saving', 'is-error');
        if (state === 'saving') el.classList.add('is-saving');
        if (state === 'error') el.classList.add('is-error');
        if (text !== undefined) el.innerHTML = text;
    }

    function lockCard(card) {
        card.setAttribute('data-saved', '1');
        card.classList.add('is-saved');
        card.querySelectorAll('.prode-score-input').forEach(function (input) {
            input.disabled = true;
        });
    }

    function updateProgress(filled, total) {
        var label = document.getElementById('prode-filled-label');
        var bar = document.getElementById('prode-progress-bar');
        var pctEl = document.getElementById('prode-progress-pct');
        var btn = document.getElementById('prode-submit-btn');
        if (label) label.textContent = filled + '/' + total;
        if (pctEl && total > 0) pctEl.textContent = Math.round((filled / total) * 100) + '%';
        if (bar && total > 0) bar.style.width = Math.round((filled / total) * 100) + '%';
        if (btn) {
            btn.disabled = filled < total;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Confirmar mis pronósticos (' + filled + '/' + total + ')';
        }
    }

    function saveMatch(card, onDone) {
        var matchId = card.getAttribute('data-match-id');
        if (!matchId || card.getAttribute('data-locked') === '1' || card.getAttribute('data-saved') === '1') {
            if (onDone) onDone(false);
            return;
        }

        var homeIn = card.querySelector('[data-side="home"]');
        var awayIn = card.querySelector('[data-side="away"]');
        if (!homeIn || !awayIn) {
            if (onDone) onDone(false);
            return;
        }

        var home = homeIn.value;
        var away = awayIn.value;
        if (home === '' || away === '') {
            if (onDone) onDone(false);
            return;
        }

        setStatus(matchId, 'saving', '<i class="fas fa-spinner fa-spin"></i> Guardando…');

        var fd = new FormData();
        fd.append('csrf_token', cfg.csrfToken);
        fd.append('match_id', matchId);
        fd.append('home_score', home);
        fd.append('away_score', away);

        pendingSaves[matchId] = true;

        fetch(cfg.saveUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                delete pendingSaves[matchId];
                if (!data.ok) {
                    setStatus(matchId, 'error', data.message || 'Error');
                    if (onDone) onDone(false);
                    return;
                }
                lockCard(card);
                setStatus(matchId, '', '<i class="fas fa-lock text-success"></i> Guardado');
                if (typeof data.filled_count === 'number') {
                    updateProgress(data.filled_count, data.total_matches || cfg.totalMatches);
                }
                if (onDone) onDone(true);
            })
            .catch(function () {
                delete pendingSaves[matchId];
                setStatus(matchId, 'error', 'Sin conexión');
                if (onDone) onDone(false);
            });
    }

    function flushPendingSaves(callback) {
        Object.keys(timers).forEach(function (k) {
            clearTimeout(timers[k]);
        });

        var cards = [];
        document.querySelectorAll('.prode-match').forEach(function (card) {
            if (card.getAttribute('data-locked') === '1' || card.getAttribute('data-saved') === '1') return;
            var homeIn = card.querySelector('[data-side="home"]');
            var awayIn = card.querySelector('[data-side="away"]');
            if (homeIn && awayIn && homeIn.value !== '' && awayIn.value !== '') {
                cards.push(card);
            }
        });

        if (!cards.length) {
            callback(true);
            return;
        }

        var remaining = cards.length;
        var ok = true;
        cards.forEach(function (card) {
            saveMatch(card, function (success) {
                if (!success) ok = false;
                remaining -= 1;
                if (remaining === 0) callback(ok);
            });
        });
    }

    function navigateWithFlush(e, url) {
        if (!url) return;
        e.preventDefault();
        flushPendingSaves(function (ok) {
            if (ok && Object.keys(pendingSaves).length === 0) {
                window.location.href = url;
            }
        });
    }

    document.querySelectorAll('.prode-match').forEach(function (card) {
        if (card.getAttribute('data-locked') === '1' || card.getAttribute('data-saved') === '1') return;
        card.querySelectorAll('.prode-score-input').forEach(function (input) {
            input.addEventListener('input', function () {
                var id = card.getAttribute('data-match-id');
                debounce(id, function () { saveMatch(card); }, 500);
            });
            input.addEventListener('change', function () {
                saveMatch(card);
            });
        });
    });

    document.querySelectorAll('.prode-group-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            navigateWithFlush(e, link.getAttribute('href'));
        });
    });

    var submitForm = document.getElementById('prode-submit-form');
    if (submitForm) {
        submitForm.addEventListener('submit', function (e) {
            e.preventDefault();
            flushPendingSaves(function (ok) {
                if (ok && Object.keys(pendingSaves).length === 0) {
                    submitForm.submit();
                }
            });
        });
    }
})();
