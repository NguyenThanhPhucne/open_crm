/**
 * CRM Import — Premium JS
 * Drag-drop upload, CSV preview, field mapping, progress simulation
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.crmImport = {
    attach: function (context, settings) {
      // Init drag-drop on each dropzone
      once('crm-import-init', '.crm-dropzone', context).forEach(function (zone) {
        initDropzone(zone);
      });
    }
  };

  /* ---- Dropzone ---- */
  function initDropzone(zone) {
    var input = zone.querySelector('.crm-dropzone__input');
    var fileInfo = zone.closest('.crm-import-form-wrap') ?
      zone.closest('.crm-import-form-wrap').querySelector('.crm-file-info') :
      document.querySelector('.crm-file-info');
    var preview = document.querySelector('.crm-csv-preview');
    var hiddenInput = document.getElementById('crm-csv-hidden-fid');

    // Drag events
    ['dragenter', 'dragover'].forEach(function (evt) {
      zone.addEventListener(evt, function (e) {
        e.preventDefault();
        e.stopPropagation();
        zone.classList.add('drag-over');
      });
    });

    ['dragleave', 'dragend'].forEach(function (evt) {
      zone.addEventListener(evt, function () {
        zone.classList.remove('drag-over');
      });
    });

    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      e.stopPropagation();
      zone.classList.remove('drag-over');
      var files = e.dataTransfer.files;
      if (files.length > 0) handleFile(files[0], zone, fileInfo, preview, input);
    });

    // Click browse
    if (input) {
      input.addEventListener('change', function () {
        if (this.files.length > 0) handleFile(this.files[0], zone, fileInfo, preview, input);
      });
    }

    // Also hook the Drupal managed_file upload trigger
    var drupalFileInput = document.querySelector('input[data-drupal-selector="edit-csv-file-upload"]');
    if (drupalFileInput) {
      drupalFileInput.addEventListener('change', function () {
        if (this.files.length > 0) {
          handleFile(this.files[0], zone, fileInfo, preview, null);
        }
      });
    }
  }

  /* ---- Handle file selection ---- */
  function handleFile(file, zone, fileInfo, preview, input) {
    // Validate extension
    if (!file.name.match(/\.(csv|txt)$/i)) {
      showAlert('error', 'Please upload a CSV or TXT file.');
      return;
    }

    // Update zone appearance
    zone.classList.add('has-file');
    zone.classList.remove('drag-over');

    // Show file info bar
    if (fileInfo) {
      fileInfo.classList.add('visible');
      var nameEl = fileInfo.querySelector('.crm-file-info__name');
      var metaEl = fileInfo.querySelector('.crm-file-info__meta');
      if (nameEl) nameEl.textContent = file.name;
      if (metaEl) metaEl.textContent = formatFileSize(file.size) + ' · UTF-8 CSV';

      // Remove button
      var removeBtn = fileInfo.querySelector('.crm-file-info__remove');
      if (removeBtn) {
        removeBtn.addEventListener('click', function () {
          zone.classList.remove('has-file');
          fileInfo.classList.remove('visible');
          if (preview) preview.classList.remove('visible');
          if (input) input.value = '';
        });
      }
    }

    // Parse CSV and show preview
    readCSVPreview(file, preview);

    // Also trigger the hidden Drupal file upload input so form submits correctly
    if (input && input.closest('form')) {
      // Create DataTransfer to programmatically set files
      try {
        var dt = new DataTransfer();
        dt.items.add(file);
        // Find the actual Drupal file input
        var drupalInput = document.querySelector('input[data-drupal-selector="edit-csv-file-upload"]');
        if (drupalInput) {
          drupalInput.files = dt.files;
          // Trigger change to activate Drupal's managed_file upload
          drupalInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
      } catch (e) {
        // Fallback: just allow the form to submit with the hidden input
      }
    }
  }

  /* ---- CSV Preview ---- */
  function readCSVPreview(file, previewContainer) {
    if (!previewContainer) return;

    var reader = new FileReader();
    reader.onload = function (e) {
      var text = e.target.result;
      var rows = parseCSV(text, 6); // header + 5 data rows

      if (rows.length < 2) return;

      var headers = rows[0];
      var dataRows = rows.slice(1);

      // Build table HTML
      var html = '<table><thead><tr>';
      headers.forEach(function (h) {
        html += '<th>' + escapeHTML(h) + '</th>';
      });
      html += '</tr></thead><tbody>';

      dataRows.forEach(function (row) {
        html += '<tr>';
        headers.forEach(function (_, i) {
          html += '<td title="' + escapeHTML(row[i] || '') + '">' + escapeHTML(truncate(row[i] || '', 30)) + '</td>';
        });
        html += '</tr>';
      });

      html += '</tbody></table>';

      var scrollDiv = previewContainer.querySelector('.crm-csv-preview__scroll');
      if (scrollDiv) scrollDiv.innerHTML = html;

      // Count total rows (rough approximation)
      var totalLines = text.split('\n').filter(function (l) { return l.trim(); }).length - 1;
      var badge = previewContainer.querySelector('.crm-csv-preview__badge');
      if (badge) badge.textContent = totalLines + ' rows detected';

      previewContainer.classList.add('visible');
    };

    reader.readAsText(file, 'UTF-8');
  }

  /* ---- Simple CSV parser ---- */
  function parseCSV(text, maxRows) {
    var rows = [];
    var lines = text.split('\n');
    var count = 0;

    for (var i = 0; i < lines.length && count < maxRows; i++) {
      var line = lines[i].trim();
      if (!line) continue;
      rows.push(parseCSVLine(line));
      count++;
    }

    return rows;
  }

  function parseCSVLine(line) {
    var result = [];
    var current = '';
    var inQuotes = false;

    for (var i = 0; i < line.length; i++) {
      var ch = line[i];
      if (ch === '"') {
        inQuotes = !inQuotes;
      } else if (ch === ',' && !inQuotes) {
        result.push(current.trim());
        current = '';
      } else {
        current += ch;
      }
    }
    result.push(current.trim());
    return result;
  }

  /* ---- Progress bar (used during Drupal Batch API) ---- */
  Drupal.behaviors.crmImportProgress = {
    attach: function (context, settings) {
      var bar = document.querySelector('.crm-import-progress');
      if (!bar) return;

      // Listen to Drupal batch progress events
      $(document).on('drupalBatchUpdate', function (e, data) {
        if (!bar.classList.contains('visible')) bar.classList.add('visible');
        var pct = Math.round(data.percentage || 0);
        var fill = bar.querySelector('.crm-import-progress__fill');
        var pctEl = bar.querySelector('.crm-import-progress__pct');
        var statusEl = bar.querySelector('.crm-import-progress__status');
        if (fill) fill.style.width = pct + '%';
        if (pctEl) pctEl.textContent = pct + '%';
        if (statusEl && data.message) statusEl.textContent = data.message;
      });
    }
  };

  /* ---- Form submit animation ---- */
  Drupal.behaviors.crmImportSubmit = {
    attach: function (context, settings) {
      once('crm-submit', '.crm-import-submit-btn', context).forEach(function (btn) {
        btn.closest('form') && btn.closest('form').addEventListener('submit', function () {
          btn.disabled = true;
          btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Processing…';
          var progress = document.querySelector('.crm-import-progress');
          if (progress) {
            progress.classList.add('visible');
            animateProgress(progress);
          }
        });
      });
    }
  };

  function animateProgress(bar) {
    var fill = bar.querySelector('.crm-import-progress__fill');
    var pctEl = bar.querySelector('.crm-import-progress__pct');
    var statusEl = bar.querySelector('.crm-import-progress__status');
    var pct = 0;

    var messages = ['Reading CSV file…', 'Validating rows…', 'Creating records…', 'Finalizing…'];
    var msgIdx = 0;

    var iv = setInterval(function () {
      if (pct >= 90) { clearInterval(iv); return; }
      pct += Math.random() * 3 + 1;
      pct = Math.min(pct, 90);
      if (fill) fill.style.width = pct + '%';
      if (pctEl) pctEl.textContent = Math.round(pct) + '%';
      if (statusEl && pct > msgIdx * 22 && messages[msgIdx]) {
        statusEl.textContent = messages[msgIdx++];
      }
    }, 300);
  }

  /* ---- Helpers ---- */
  function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  }

  function escapeHTML(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function truncate(str, max) {
    return str.length > max ? str.substring(0, max) + '…' : str;
  }

  function showAlert(type, message) {
    var el = document.createElement('div');
    el.style.cssText = 'position:fixed;top:24px;right:24px;z-index:9999;padding:14px 20px;border-radius:10px;font-size:14px;font-weight:600;color:white;box-shadow:0 8px 20px rgba(0,0,0,.15);animation:slideIn .3s ease;';
    el.style.background = type === 'error' ? '#ef4444' : '#10b981';
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(function () { el.remove(); }, 4000);
  }

  // CSS animation for spinner + slide-in alert
  var style = document.createElement('style');
  style.textContent = '@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}} @keyframes slideIn{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}';
  document.head.appendChild(style);

})(jQuery, Drupal);
