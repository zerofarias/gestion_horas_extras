(function () {
    var sel = document.getElementById('lessonContentType');
    if (!sel) return;

    var panels = {
        text: document.getElementById('field-text'),
        video: document.getElementById('field-video'),
        file: document.getElementById('field-file')
    };
    var fileInputs = document.querySelectorAll('[data-lesson-file]');
    var ytInput = document.getElementById('lessonYoutubeUrl');
    var ytPreview = document.getElementById('youtubePreviewWrap');
    var ytFrame = document.getElementById('youtubePreviewFrame');

    function youtubeId(url) {
        if (!url) return '';
        var patterns = [
            /youtube\.com\/watch\?(?:[^&]*&)*v=([\w-]{6,})/i,
            /youtube\.com\/embed\/([\w-]{6,})/i,
            /youtube\.com\/shorts\/([\w-]{6,})/i,
            /youtube\.com\/live\/([\w-]{6,})/i,
            /youtu\.be\/([\w-]{6,})/i
        ];
        for (var i = 0; i < patterns.length; i++) {
            var m = url.match(patterns[i]);
            if (m) return m[1];
        }
        return '';
    }

    function vimeoId(url) {
        if (!url) return '';
        var m = url.match(/vimeo\.com\/(?:video\/)?(\d+)/i) ||
            url.match(/player\.vimeo\.com\/video\/(\d+)/i);
        return m ? m[1] : '';
    }

    function embedUrl(url) {
        var id = youtubeId(url);
        if (id) {
            return 'https://www.youtube-nocookie.com/embed/' + id +
                '?rel=0&modestbranding=1&playsinline=1&iv_load_policy=3';
        }
        id = vimeoId(url);
        if (id) {
            return 'https://player.vimeo.com/video/' + id + '?title=0&byline=0&portrait=0';
        }
        return '';
    }

    function syncYoutubePreview() {
        if (!ytInput || !ytPreview || !ytFrame) return;
        var src = embedUrl(ytInput.value.trim());
        if (src) {
            ytFrame.src = src;
            ytPreview.hidden = false;
        } else {
            ytFrame.removeAttribute('src');
            ytPreview.hidden = true;
        }
    }

    function sync() {
        var t = sel.value;
        Object.keys(panels).forEach(function (k) {
            if (panels[k]) {
                panels[k].classList.toggle('is-visible', k === t);
            }
        });
        fileInputs.forEach(function (inp) {
            var show = (t === 'video' && inp.accept.indexOf('video') >= 0) ||
                (t === 'file' && inp.accept.indexOf('pdf') >= 0);
            inp.disabled = !show;
            if (!show) inp.value = '';
        });
        if (t === 'video') {
            syncYoutubePreview();
        } else if (ytPreview) {
            ytPreview.hidden = true;
            if (ytFrame) ytFrame.removeAttribute('src');
        }
    }

    sel.addEventListener('change', sync);
    if (ytInput) {
        ytInput.addEventListener('input', syncYoutubePreview);
        ytInput.addEventListener('change', syncYoutubePreview);
    }
    sync();
})();
