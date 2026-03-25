/**
 * CRM Import — Premium JS
 * Drag-drop + CSV preview + progress animation
 *
 * The file input physically overlays the dropzone (position:absolute;inset:0;
 * opacity:0), so clicking anywhere in the zone opens the OS file picker.
 * For drag-and-drop, we intercept the drop event, push the file into the
 * input's file list via DataTransfer, then dispatch a synthetic 'change'.
 */
(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.crmImportDrop = {
    attach: function (context, settings) {
      once('crm-drop-init', '.crm-dropzone', context).forEach(initDropzone);
    }
  };

  // ── DROPZONE INIT ─────────────────────────────────────────────────────────
  function initDropzone(zone) {
    // The real file input that overlays the zone.
    var input = zone.querySelector('input[type="file"]');
    if (!input) return;

    var zoneId   = zone.id || 'crm-dropzone';
    var suffix   = zoneId.replace('crm-contacts-dropzone', 'contacts')
                         .replace('crm-orgs-dropzone', 'orgs')
                         .replace('crm-deals-dropzone', 'deals');

    var fileInfo    = document.getElementById('crm-file-info-' + suffix);
    var preview     = document.getElementById('crm-preview-' + suffix);
    var removeBtn   = document.getElementById('crm-remove-' + suffix);
    var badge       = document.getElementById('crm-preview-badge-' + suffix);
    var tableWrap   = document.getElementById('crm-preview-table-' + suffix);

    // ── Drag visual feedback (input captures drops natively, but we add
    //    visual cues by listening on the parent zone).
    zone.addEventListener('dragover', function (e) {
      e.preventDefault();
      zone.classList.add('drag-over');
    });
    zone.addEventListener('dragleave', function () {
      zone.classList.remove('drag-over');
    });

    // When a file is dropped, set it on the input so the form submission
    // sees it in $_FILES['files']['csv_file'].
    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      e.stopPropagation();
      zone.classList.remove('drag-over');

      var files = e.dataTransfer && e.dataTransfer.files;
      if (!files || !files.length) return;

      // Assign the dropped file to the native file input.
      try {
        var dt = new DataTransfer();
        dt.items.add(files[0]);
        input.files = dt.files;
      }
      catch (ex) {
        // DataTransfer not supported — fall back: show error.
        showAlert('error', 'Please use the Browse button to select a file on this browser.');
        return;
      }

      handleFile(files[0], zone, fileInfo, preview, badge, tableWrap);
    });

    // When the user selects via file picker (click).
    input.addEventListener('change', function () {
      if (this.files && this.files.length) {
        handleFile(this.files[0], zone, fileInfo, preview, badge, tableWrap);
      }
    });

    // Remove / reset.
    if (removeBtn) {
      removeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        try { input.value = ''; } catch(ex) {}
        zone.classList.remove('has-file', 'drag-over');
        if (fileInfo)  fileInfo.classList.remove('visible');
        if (preview)   preview.classList.remove('visible');
      });
    }
  }

  // ── HANDLE SELECTED FILE ─────────────────────────────────────────────────
  function handleFile(file, zone, fileInfo, preview, badge, tableWrap) {
    if (!file) return;

    var ext = file.name.split('.').pop().toLowerCase();
    if (ext !== 'csv' && ext !== 'txt') {
      showAlert('error', 'Only CSV or TXT files are supported.');
      return;
    }
    if (file.size > 10 * 1024 * 1024) {
      showAlert('error', 'File size exceeds 10 MB limit.');
      return;
    }

    zone.classList.add('has-file');

    // Show file info bar.
    if (fileInfo) {
      fileInfo.classList.add('visible');
      var nameEl = fileInfo.querySelector('.crm-file-info__name');
      var metaEl = fileInfo.querySelector('.crm-file-info__meta');
      if (nameEl) nameEl.textContent = file.name;
      if (metaEl) metaEl.textContent = formatSize(file.size) + ' · UTF-8 CSV';
    }

    // Render CSV preview.
    if (preview) {
      readCSVPreview(file, preview, badge, tableWrap);
    }
  }

  // ── CSV PREVIEW ───────────────────────────────────────────────────────────
  function readCSVPreview(file, previewEl, badge, tableWrap) {
    var reader = new FileReader();
    reader.onload = function (e) {
      var text = e.target.result;
      var rows = parseCSV(text, 6); // header + 5 data rows
      if (rows.length < 1) return;

      var headers  = rows[0];
      var dataRows = rows.slice(1);

      var html = '<table><thead><tr>';
      headers.forEach(function (h) { html += '<th>' + esc(h) + '</th>'; });
      html += '</tr></thead><tbody>';
      dataRows.forEach(function (row) {
        html += '<tr>';
        headers.forEach(function (_, i) {
          var v = row[i] || '';
          html += '<td title="' + esc(v) + '">' + esc(trunc(v, 28)) + '</td>';
        });
        html += '</tr>';
      });
      html += '</tbody></table>';

      if (tableWrap) tableWrap.innerHTML = html;

      // Count total lines.
      var total = Math.max(0, text.split('\n').filter(function (l) { return l.trim(); }).length - 1);
      if (badge) badge.textContent = total.toLocaleString() + ' row' + (total !== 1 ? 's' : '') + ' detected';

      previewEl.classList.add('visible');
    };
    reader.readAsText(file, 'UTF-8');
  }

  // ── PROGRESS ANIMATION on submit ─────────────────────────────────────────
  Drupal.behaviors.crmImportSubmit = {
    attach: function (context, settings) {
      once('crm-submit', '.crm-import-submit-btn', context).forEach(function (btn) {
        var form = btn.closest('form');
        if (!form) return;
        form.addEventListener('submit', function () {
          // Check file is set before allowing submit.
          var fileInput = form.querySelector('input[type="file"]');
          if (fileInput && !fileInput.files.length) {
            // Drupal validation will catch this, but show a hint anyway.
            return; // let form submit and server validates.
          }
          btn.disabled = true;
          btn.textContent = 'Importing…';
          var prog = form.querySelector('.crm-import-progress');
          if (prog) {
            prog.classList.add('visible');
            animateProgress(prog);
          }
        });
      });
    }
  };

  function animateProgress(bar) {
    var fill   = bar.querySelector('.crm-import-progress__fill');
    var pctEl  = bar.querySelector('.crm-import-progress__pct');
    var statEl = bar.querySelector('.crm-import-progress__status');
    var msgs   = ['Reading CSV file…', 'Validating data…', 'Creating records…', 'Finalizing…'];
    var pct = 0, idx = 0;
    var iv = setInterval(function () {
      if (pct >= 90) { clearInterval(iv); return; }
      pct = Math.min(90, pct + Math.random() * 3.5 + 0.5);
      if (fill)  fill.style.width = pct + '%';
      if (pctEl) pctEl.textContent = Math.round(pct) + '%';
      if (statEl && pct > idx * 22 && msgs[idx]) statEl.textContent = msgs[idx++];
    }, 300);
  }

  // ── CSV PARSER ────────────────────────────────────────────────────────────
  function parseCSV(text, max) {
    var rows = [], lines = text.split('\n'), count = 0;
    for (var i = 0; i < lines.length && count < max; i++) {
      var l = lines[i].trim();
      if (l) { rows.push(parseLine(l)); count++; }
    }
    return rows;
  }

  function parseLine(line) {
    var res = [], cur = '', inQ = false;
    for (var i = 0; i < line.length; i++) {
      var c = line[i];
      if (c === '"') { inQ = !inQ; }
      else if (c === ',' && !inQ) { res.push(cur.trim()); cur = ''; }
      else cur += c;
    }
    res.push(cur.trim());
    return res;
  }

  // ── HELPERS ───────────────────────────────────────────────────────────────
  function formatSize(b) {
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
    return (b / 1048576).toFixed(1) + ' MB';
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function trunc(s, n) { return s.length > n ? s.slice(0, n) + '…' : s; }

  function showAlert(type, msg) {
    var el = document.createElement('div');
    el.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;padding:14px 20px;border-radius:10px;font-family:Inter,sans-serif;font-size:14px;font-weight:600;color:#fff;box-shadow:0 8px 24px rgba(0,0,0,.18);animation:crmSlideIn .3s ease;';
    el.style.background = type === 'error' ? '#ef4444' : '#10b981';
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(function () { el.remove(); }, 5000);
  }

  var _styleEl = document.createElement('style');
  _styleEl.textContent = '@keyframes crmSlideIn{from{transform:translateX(120px);opacity:0}to{transform:translateX(0);opacity:1}}';
  document.head.appendChild(_styleEl);

})(jQuery, Drupal, once);
