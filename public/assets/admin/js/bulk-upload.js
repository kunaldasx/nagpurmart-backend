(function () {
    document.addEventListener('DOMContentLoaded', function () {
        let cfg = window.BULK_UPLOAD_CFG || {};
        let form = document.getElementById('bulk-upload-form');
        let fileInput = document.getElementById('csv-file');
        let submitBtn = document.getElementById('bulk-upload-submit');
        if (!form || !fileInput || !submitBtn) return;

        let spinner = submitBtn.querySelector('.spinner-border');
        let uploadText = submitBtn.querySelector('.upload-text');
        let resultWrap = document.getElementById('bulk-upload-result');
        let successEl = document.getElementById('bulk-success');
        let failedEl = document.getElementById('bulk-failed');
        let failedTableWrap = document.getElementById('bulk-failed-table-wrap');
        let failedTbody = document.getElementById('bulk-failed-tbody');

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            let fd = new FormData(form);

            // UI state
            submitBtn.disabled = true;
            if (spinner) spinner.classList.remove('d-none');
            if (uploadText) uploadText.classList.add('d-none');
            if (resultWrap) resultWrap.classList.add('d-none');
            if (failedEl) failedEl.classList.add('d-none');
            if (failedTableWrap) failedTableWrap.classList.add('d-none');
            if (failedTbody) failedTbody.innerHTML = '';

            axios.post(
                cfg.uploadUrl || form.getAttribute('action') || window.location.href,
                fd,
            )
                .then(function (resp) {
                    let json = resp.data;

                    if (resultWrap) resultWrap.classList.remove('d-none');

                    if (json && json.success) {
                        let d = json.data || {};
                        let successMsg =
                            (cfg.i18n?.upload_success_count || 'Successfully uploaded') +
                            ': ' + (d.success_count || 0) + '. ' +
                            (cfg.i18n?.upload_failed_count || 'Failed') +
                            ': ' + (d.failed_count || 0) + '.';

                        if (successEl) successEl.textContent = successMsg;

                        if ((d.failed_rows || []).length) {
                            if (failedEl) {
                                failedEl.classList.remove('d-none');
                                failedEl.textContent =
                                    cfg.i18n?.some_rows_failed || 'Some rows failed';
                            }
                            if (failedTableWrap) failedTableWrap.classList.remove('d-none');

                            d.failed_rows.forEach(function (r) {
                                if (!failedTbody) return;
                                let tr = document.createElement('tr');
                                tr.innerHTML =
                                    '<td>' + r.row + '</td>' +
                                    '<td>' + (r.title || '') + '</td>' +
                                    '<td>' + (r.error || '') + '</td>';
                                failedTbody.appendChild(tr);
                            });
                        }
                    } else {
                        if (failedEl) {
                            failedEl.classList.remove('d-none');
                            failedEl.textContent =
                                json?.message ||
                                cfg.i18n?.upload_failed_generic ||
                                'Upload failed';
                        }
                    }
                })
                .catch(function () {
                    if (resultWrap) resultWrap.classList.remove('d-none');
                    if (failedEl) {
                        failedEl.classList.remove('d-none');
                        failedEl.textContent =
                            cfg.i18n?.unexpected_error || 'Unexpected error occurred';
                    }
                })
                .finally(function () {
                    submitBtn.disabled = false;
                    if (spinner) spinner.classList.add('d-none');
                    if (uploadText) uploadText.classList.remove('d-none');
                });
        });
    });
})();
