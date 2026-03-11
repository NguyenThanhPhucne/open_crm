<?php

namespace Drupal\crm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\Request;

/**
 * Professional All Organizations list controller.
 */
class AllOrganizationsController extends ControllerBase {

  /**
   * Render the All / My Organizations page.
   */
  public function view(Request $request) {
    $current_user = \Drupal::currentUser();
    $user_id      = $current_user->id();
    $is_admin     = in_array('administrator', $current_user->getRoles()) || $user_id == 1;
    $is_manager   = in_array('sales_manager', $current_user->getRoles());
    $can_manage   = $is_admin || $is_manager;

    // ── Filter parameters ─────────────────────────────────────────────────────
    $search_name     = trim($request->query->get('search', ''));
    $search_industry = trim($request->query->get('industry', ''));
    $search_status   = trim($request->query->get('status', ''));
    $page            = max(0, (int) $request->query->get('page', 0));
    $per_page        = 25;

    // ── Query builder ─────────────────────────────────────────────────────────
    $build_query = function () use ($search_name, $search_industry, $search_status, $can_manage, $user_id) {
      $q = \Drupal::entityQuery('node')
        ->condition('type', 'organization')
        ->accessCheck(FALSE);
      if ($search_name) {
        $q->condition('title', '%' . $search_name . '%', 'LIKE');
      }
      if ($search_industry) {
        $q->condition('field_industry', '%' . $search_industry . '%', 'LIKE');
      }
      if ($search_status) {
        $q->condition('field_status', $search_status);
      }
      if (!$can_manage && $user_id > 0) {
        $q->condition('field_assigned_staff', $user_id);
      }
      return $q;
    };

    // ── Stats ─────────────────────────────────────────────────────────────────
    $now         = \Drupal::time()->getCurrentTime();
    $month_start = mktime(0, 0, 0, (int) date('n', $now), 1);

    $total_q = \Drupal::entityQuery('node')->condition('type', 'organization')->accessCheck(FALSE);
    if (!$can_manage && $user_id > 0) { $total_q->condition('field_assigned_staff', $user_id); }
    $total_all = (int) $total_q->count()->execute();

    $active_q = \Drupal::entityQuery('node')->condition('type', 'organization')
      ->condition('field_status', 'active')->accessCheck(FALSE);
    if (!$can_manage && $user_id > 0) { $active_q->condition('field_assigned_staff', $user_id); }
    $total_active = (int) $active_q->count()->execute();

    $month_q = \Drupal::entityQuery('node')->condition('type', 'organization')
      ->condition('created', $month_start, '>=')->accessCheck(FALSE);
    if (!$can_manage && $user_id > 0) { $month_q->condition('field_assigned_staff', $user_id); }
    $new_this_month = (int) $month_q->count()->execute();

    // ── Paged results ─────────────────────────────────────────────────────────
    $filtered_total = (int) $build_query()->count()->execute();
    $total_pages    = max(1, (int) ceil($filtered_total / $per_page));
    $page           = min($page, $total_pages - 1);

    $ids   = $build_query()->sort('changed', 'DESC')->range($page * $per_page, $per_page)->execute();
    $orgs  = !empty($ids) ? \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids) : [];

    // ── Avatar palette ────────────────────────────────────────────────────────
    $avatar_pairs = [
      'A' => ['#dbeafe','#2563eb'], 'B' => ['#dbeafe','#2563eb'], 'C' => ['#dbeafe','#2563eb'], 'D' => ['#dbeafe','#2563eb'],
      'E' => ['#ede9fe','#6d28d9'], 'F' => ['#ede9fe','#6d28d9'], 'G' => ['#ede9fe','#6d28d9'],
      'H' => ['#dcfce7','#059669'], 'I' => ['#dcfce7','#059669'], 'J' => ['#dcfce7','#059669'], 'K' => ['#dcfce7','#059669'],
      'L' => ['#fef9c3','#b45309'], 'M' => ['#fef9c3','#b45309'], 'N' => ['#fef9c3','#b45309'],
      'O' => ['#fce7f3','#be185d'], 'P' => ['#fce7f3','#be185d'], 'Q' => ['#fce7f3','#be185d'],
      'R' => ['#ccfbf1','#0d9488'], 'S' => ['#ccfbf1','#0d9488'], 'T' => ['#ccfbf1','#0d9488'],
      'U' => ['#fee2e2','#dc2626'], 'V' => ['#fee2e2','#dc2626'], 'W' => ['#fee2e2','#dc2626'],
      'X' => ['#e0e7ff','#4338ca'], 'Y' => ['#e0e7ff','#4338ca'], 'Z' => ['#e0e7ff','#4338ca'],
    ];

    // ── Format rows ───────────────────────────────────────────────────────────
    $rows = [];
    foreach ($orgs as $org) {
      $oid     = $org->id();
      $name    = $org->getTitle();
      $initial = strtoupper(mb_substr($name, 0, 1));
      $av_pair  = $avatar_pairs[$initial] ?? ['#f1f5f9', '#64748b'];
      $av_style = "background:linear-gradient(135deg,{$av_pair[0]} 0%,#fff 80%);color:{$av_pair[1]};border-color:{$av_pair[1]}4d";
      $org_url  = Url::fromRoute('entity.node.canonical', ['node' => $oid])->toString();

      $industry = $org->hasField('field_industry') && !$org->get('field_industry')->isEmpty()
        ? Html::escape($org->get('field_industry')->value ?? '') : '';

      $phone = $org->hasField('field_phone') && !$org->get('field_phone')->isEmpty()
        ? Html::escape($org->get('field_phone')->value ?? '') : '';

      $email = $org->hasField('field_email') && !$org->get('field_email')->isEmpty()
        ? Html::escape($org->get('field_email')->value ?? '') : '';

      $status = $org->hasField('field_status') && !$org->get('field_status')->isEmpty()
        ? ($org->get('field_status')->value ?? '') : '';

      $employees = $org->hasField('field_employees_count') && !$org->get('field_employees_count')->isEmpty()
        ? (int) $org->get('field_employees_count')->value : 0;

      $revenue_raw = $org->hasField('field_annual_revenue') && !$org->get('field_annual_revenue')->isEmpty()
        ? (float) $org->get('field_annual_revenue')->value : 0.0;

      $website = '';
      if ($org->hasField('field_website') && !$org->get('field_website')->isEmpty()) {
        $website = $org->get('field_website')->uri ?? '';
      }

      $assigned_name = '';
      if ($org->hasField('field_assigned_staff') && !$org->get('field_assigned_staff')->isEmpty()) {
        $assigned_user = $org->get('field_assigned_staff')->entity;
        if ($assigned_user) { $assigned_name = $assigned_user->getDisplayName(); }
      }

      $can_edit = $can_manage ||
        ($org->hasField('field_assigned_staff') && $org->get('field_assigned_staff')->target_id == $user_id);

      $diff = $now - $org->getChangedTime();
      if ($diff < 60)         { $time_ago = 'just now'; }
      elseif ($diff < 3600)   { $time_ago = floor($diff / 60) . 'm ago'; }
      elseif ($diff < 86400)  { $time_ago = floor($diff / 3600) . 'h ago'; }
      elseif ($diff < 604800) { $time_ago = floor($diff / 86400) . 'd ago'; }
      else                    { $time_ago = floor($diff / 604800) . 'w ago'; }

      // Format revenue
      $revenue_fmt = '';
      if ($revenue_raw > 0) {
        if ($revenue_raw >= 1_000_000) {
          $revenue_fmt = '$' . number_format($revenue_raw / 1_000_000, 1) . 'M';
        } elseif ($revenue_raw >= 1_000) {
          $revenue_fmt = '$' . number_format($revenue_raw / 1_000, 0) . 'K';
        } else {
          $revenue_fmt = '$' . number_format($revenue_raw, 0);
        }
      }

      $rows[] = [
        'id'            => $oid,
        'name'          => Html::escape($name),
        'initial'       => $initial,
        'av_style'      => $av_style,
        'url'           => $org_url,
        'website'       => $website,
        'industry'      => $industry,
        'phone'         => $phone,
        'email'         => $email,
        'status'        => $status,
        'employees'     => $employees,
        'revenue'       => $revenue_fmt,
        'assigned'      => Html::escape($assigned_name),
        'time_ago'      => $time_ago,
        'can_edit'      => $can_edit,
        'title_js'      => addslashes($name),
      ];
    }

    // ── Navigation URLs ───────────────────────────────────────────────────────
    $current_path      = $request->getPathInfo();
    $contacts_url      = $can_manage ? '/crm/all-contacts'      : '/crm/my-contacts';
    $organizations_url = $current_path;
    $deals_url         = $can_manage ? '/crm/all-deals'         : '/crm/my-deals';
    $activities_url    = $can_manage ? '/crm/all-activities'    : '/crm/my-activities';
    $add_url           = '/node/add/organization';

    // ── Pagination helper ─────────────────────────────────────────────────────
    $page_url = function ($p) use ($search_name, $search_industry, $search_status, $current_path) {
      $params = ['page' => $p];
      if ($search_name)     { $params['search']   = $search_name; }
      if ($search_industry) { $params['industry'] = $search_industry; }
      if ($search_status)   { $params['status']   = $search_status; }
      return $current_path . '?' . http_build_query($params);
    };

    $e_search   = Html::escape($search_name);
    $e_industry = Html::escape($search_industry);
    $e_status   = Html::escape($search_status);

    // ── HTML ──────────────────────────────────────────────────────────────────
    $html = <<<HTML
<script src="https://unpkg.com/lucide@latest"></script>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f8fafc;color:#1e293b}

  /* ── Page shell ── */
  .orgs-page{max-width:1400px;margin:0 auto;animation:fadeIn .3s ease}
  @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

  /* ── Stats bar ── */
  .stats-bar{display:flex;align-items:center;gap:16px;margin-bottom:24px;flex-wrap:wrap}
  .stat-chip{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;border:1px solid}
  .stat-chip.blue{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
  .stat-chip.green{background:#ecfdf5;color:#15803d;border-color:#bbf7d0}
  .stat-chip.purple{background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe}
  .stat-chip i{width:14px;height:14px}

  /* ── Page header card ── */
  .page-header{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;box-shadow:0 1px 3px rgba(0,0,0,.05);flex-wrap:wrap}
  .page-header-left{display:flex;flex-direction:column;gap:6px}
  .page-title{font-size:22px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:10px;letter-spacing:-.02em}
  .page-title i{color:#3b82f6;width:24px;height:24px}
  .page-subtitle{font-size:13px;color:#64748b}
  .page-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto}
  .btn-primary,.btn-generate{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:#fff;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:background .15s,border-color .15s,color .15s;white-space:nowrap}
  .btn-primary{color:#2563eb;border:1.5px solid #2563eb}
  .btn-primary:hover{background:#eff6ff;border-color:#1d4ed8;color:#1d4ed8}
  .btn-primary:active{background:#dbeafe}
  .btn-primary:focus-visible{outline:2px solid #3b82f6;outline-offset:2px}
  .btn-primary i{width:15px;height:15px;color:inherit}
  .btn-generate{color:#7c3aed;border:1.5px solid #7c3aed}
  .btn-generate:hover{background:#f5f3ff;border-color:#6d28d9;color:#6d28d9}
  .btn-generate:active{background:#ede9fe}
  .btn-generate i{width:15px;height:15px;color:inherit}

  /* ── Filter bar ── */
  .filter-bar{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;box-shadow:0 1px 3px rgba(0,0,0,.04)}
  .filter-input-wrap{position:relative;flex:1;min-width:160px;max-width:260px}
  .filter-input-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#9ca3af;pointer-events:none;z-index:2;flex-shrink:0}
  .filter-input{width:100%;height:40px !important;padding:0 12px 0 36px !important;margin:0;border:1px solid #e5e7eb !important;border-radius:8px !important;font-size:14px !important;color:#1e293b;outline:none;transition:border-color .15s,box-shadow .15s;box-sizing:border-box !important;background:#fff !important;display:block}
  .filter-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
  .filter-input::placeholder{color:#9ca3af}
  .filter-select{width:100%;height:40px !important;padding:0 12px 0 36px !important;margin:0;border:1px solid #e5e7eb !important;border-radius:8px !important;font-size:14px !important;color:#1e293b;outline:none;transition:border-color .15s,box-shadow .15s;box-sizing:border-box !important;background:#fff !important;display:block;cursor:pointer;appearance:none}
  .filter-select:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
  .btn-filter-apply{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:40px;padding:0 16px;background:#fff;color:#2563eb;border:1.5px solid #2563eb;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s,color .15s,border-color .15s;text-decoration:none;white-space:nowrap;flex-shrink:0;box-sizing:border-box}
  .btn-filter-apply:hover{background:#eff6ff;color:#1d4ed8;border-color:#1d4ed8}
  .btn-filter-apply i{width:15px;height:15px;color:inherit;flex-shrink:0}
  .btn-filter-clear{display:inline-flex;align-items:center;justify-content:center;gap:5px;height:40px;padding:0 12px;background:transparent;color:#94a3b8;border:1px solid transparent;border-radius:8px;font-size:14px;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap;flex-shrink:0;box-sizing:border-box}
  .btn-filter-clear:hover{color:#ef4444;border-color:#fee2e2;background:#fef2f2}
  .filter-count{font-size:12px;color:#64748b;font-weight:500;white-space:nowrap;margin-left:auto}

  /* ── Table card ── */
  .table-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)}
  .orgs-table{width:100%;border-collapse:collapse;font-size:12px;table-layout:fixed}
  .orgs-table thead tr{background:linear-gradient(to right,#f8fafc,#f1f5f9);border-bottom:2px solid #e2e8f0}
  .orgs-table th{padding:10px 12px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;white-space:nowrap;overflow:hidden}
  .orgs-table th.th-action{text-align:right}
  .orgs-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .12s}
  .orgs-table tbody tr:last-child{border-bottom:none}
  .orgs-table tbody tr:hover{background:#f8fafc}
  .orgs-table td{padding:10px 12px;vertical-align:middle;overflow:hidden}

  /* Column widths */
  .orgs-table .col-org{width:220px}
  .orgs-table .col-industry{width:100px}
  .orgs-table .col-phone{width:120px}
  .orgs-table .col-email{width:155px}
  .orgs-table .col-status{width:80px}
  .orgs-table .col-employees{width:75px}
  .orgs-table .col-revenue{width:90px}
  .orgs-table .col-assigned{width:95px}
  .orgs-table .col-updated{width:68px}
  .orgs-table .col-actions{width:76px}

  /* Name cell */
  .td-name{display:flex;align-items:center;gap:9px}
  .org-avatar{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;border:1.5px solid transparent;box-shadow:0 2px 8px rgba(0,0,0,.06),inset 0 1px 0 rgba(255,255,255,.85)}
  .org-name-block{display:flex;flex-direction:column;gap:1px;min-width:0;overflow:hidden}
  .org-name-link{font-weight:600;color:#0f172a;text-decoration:none;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}
  .org-name-link:hover{color:#3b82f6;text-decoration:underline}
  .org-website{font-size:11px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}
  .org-website a{color:#3b82f6;text-decoration:none}
  .org-website a:hover{text-decoration:underline}

  /* Phone / Email */
  .cell-phone,.cell-email{display:flex;align-items:center;gap:6px;overflow:hidden}
  .cell-phone>i,.cell-email>i{width:11px;height:11px;color:#d1d5db;flex-shrink:0;opacity:.85}
  .cell-phone a,.cell-email a{color:#374151;text-decoration:none;font-size:12px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
  .cell-phone a:hover,.cell-email a:hover{color:#3b82f6}
  .td-empty-val{color:#cbd5e1;font-style:italic;font-size:12px}

  /* Status badge */
  .status-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;letter-spacing:.02em;white-space:nowrap}
  .status-badge.active{background:#dcfce7;color:#15803d}
  .status-badge.inactive{background:#fee2e2;color:#991b1b}
  .status-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0}
  .status-badge.active .status-dot{background:#22c55e}
  .status-badge.inactive .status-dot{background:#ef4444}

  /* Industry badge */
  .badge{display:inline-block;padding:2px 7px;border-radius:10px;font-size:11px;font-weight:600;letter-spacing:.02em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}

  /* Employees / Revenue */
  .td-number{font-size:12px;font-weight:600;color:#334155}
  .td-revenue{font-size:12px;font-weight:700;color:#059669}

  /* Owner / Assigned */
  .cell-owner{display:flex;align-items:center;gap:4px;font-size:11px;color:#475569;overflow:hidden}
  .cell-owner span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .cell-owner i{width:10px;height:10px;color:#d1d5db;opacity:.8;flex-shrink:0}

  .td-time{font-size:11px;color:#94a3b8;white-space:nowrap}
  .cell-actions{display:flex;align-items:center;gap:4px;justify-content:flex-end}
  .btn-action{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;border:1.5px solid #e2e8f0;background:#fff;color:#94a3b8;cursor:pointer;transition:all .15s;text-decoration:none;flex-shrink:0}
  .btn-action:hover.btn-edit{border-color:#2563eb;background:#eff6ff;color:#2563eb}
  .btn-action:hover.btn-delete{border-color:#dc2626;background:#fef2f2;color:#dc2626}
  .btn-action i{width:13px;height:13px;color:inherit}

  /* ── Empty state ── */
  .empty-state{text-align:center;padding:72px 30px}
  .empty-state-icon{width:64px;height:64px;background:#f1f5f9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
  .empty-state-icon i{width:30px;height:30px;color:#cbd5e1}
  .empty-state-title{font-size:18px;font-weight:700;color:#334155;margin-bottom:6px}
  .empty-state-sub{font-size:14px;color:#94a3b8;margin-bottom:24px}
  .empty-state-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#fff;color:#2563eb;border:1.5px solid #2563eb;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;transition:background .15s}
  .empty-state-btn:hover{background:#eff6ff}

  /* ── Pagination ── */
  .pagination{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid #f1f5f9;background:#fafafa;flex-wrap:wrap;gap:10px}
  .page-info{font-size:13px;color:#64748b}
  .page-links{display:flex;align-items:center;gap:4px}
  .page-link{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 8px;border-radius:7px;border:1px solid #e2e8f0;background:#fff;font-size:13px;font-weight:500;color:#374151;text-decoration:none;transition:all .15s;white-space:nowrap}
  .page-link:hover{border-color:#bfdbfe;background:#eff6ff;color:#2563eb}
  .page-link.active{background:#3b82f6;border-color:#3b82f6;color:#fff;font-weight:700}
  .page-link.disabled{opacity:.4;pointer-events:none}

  @media(max-width:1280px){.orgs-table .col-revenue,.orgs-table th.th-revenue,.orgs-table td.td-revenue-cell{display:none}}
  @media(max-width:1100px){.orgs-table .col-assigned,.orgs-table th.th-assigned,.orgs-table td.td-assigned-cell{display:none}}
  @media(max-width:1000px){.orgs-table .col-employees,.orgs-table th.th-employees,.orgs-table td.td-employees-cell{display:none}}
  @media(max-width:900px){.orgs-table .col-email,.orgs-table th.th-email,.orgs-table td.td-email-cell{display:none}}
  @media(max-width:700px){.orgs-table .col-phone,.orgs-table th.th-phone,.orgs-table td.td-phone-cell{display:none}}
  .orgs-table th,.orgs-table td{box-sizing:border-box}
</style>
HTML;

    // ── Select option helpers (must be before HTML blocks) ───────────────────
    $active_sel   = $search_status === 'active'   ? ' selected' : '';
    $inactive_sel = $search_status === 'inactive' ? ' selected' : '';

    // ── Industry badge colors ──────────────────────────────────────────────────
    $industry_colors = [
      'technology'  => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
      'software'    => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
      'finance'     => ['bg' => '#dcfce7', 'color' => '#15803d'],
      'retail'      => ['bg' => '#fef9c3', 'color' => '#854d0e'],
      'real estate' => ['bg' => '#fce7f3', 'color' => '#9d174d'],
      'healthcare'  => ['bg' => '#ccfbf1', 'color' => '#0d9488'],
      'education'   => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
      'testing'     => ['bg' => '#fee2e2', 'color' => '#991b1b'],
      'qa'          => ['bg' => '#fee2e2', 'color' => '#991b1b'],
    ];
    $get_industry_badge = function ($value) use ($industry_colors) {
      $key = strtolower($value);
      foreach ($industry_colors as $k => $v) {
        if (str_contains($key, $k)) { return $v; }
      }
      return ['bg' => '#f1f5f9', 'color' => '#475569'];
    };

    // ── Stats bar ─────────────────────────────────────────────────────────────
    $html .= <<<HTML
<div class="orgs-page">

  <!-- Stats bar -->
  <div class="stats-bar">
    <span class="stat-chip blue"><i data-lucide="building-2"></i>{$total_all} organizations</span>
    <span class="stat-chip green"><i data-lucide="check-circle-2"></i>{$total_active} active</span>
    <span class="stat-chip purple"><i data-lucide="calendar"></i>+{$new_this_month} this month</span>
  </div>

  <!-- Page header -->
  <div class="page-header">
    <div class="page-header-left">
      <div class="page-title">
        <i data-lucide="building-2" width="24" height="24"></i>
        All Organizations
      </div>
      <div class="page-subtitle">Manage your companies, clients, and partner organizations</div>
    </div>
    <div class="page-actions">
      <a href="{$add_url}" class="btn-primary">
        <i data-lucide="plus-circle"></i>
        Add Organization
      </a>
      <button id="crm-ai-generate-btn" class="btn-generate" data-entity-type="organization">
        <i data-lucide="sparkles"></i>
        Generate data
      </button>
    </div>
  </div>

  <!-- Filter bar -->
  <form class="filter-bar" method="get" action="{$organizations_url}">
    <div class="filter-input-wrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:#3b82f6"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input class="filter-input" type="text" name="search" placeholder="Search by name…" value="{$e_search}">
    </div>
    <div class="filter-input-wrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      <input class="filter-input" type="text" name="industry" placeholder="Filter by industry…" value="{$e_industry}">
    </div>
    <div class="filter-input-wrap" style="max-width:180px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      <select class="filter-select" name="status">
        <option value="">All statuses</option>
        <option value="active"{$active_sel}>Active</option>
        <option value="inactive"{$inactive_sel}>Inactive</option>
      </select>
    </div>
    <button type="submit" class="btn-filter-apply">
      <i data-lucide="filter"></i>
      Apply
    </button>
HTML;

    if ($search_name || $search_industry || $search_status) {
      $html .= '<a href="' . $organizations_url . '" class="btn-filter-clear"><i data-lucide="x"></i> Clear</a>';
    }

    $from = $filtered_total === 0 ? 0 : $page * $per_page + 1;
    $to   = min(($page + 1) * $per_page, $filtered_total);
    $html .= '<span class="filter-count">Showing ' . $from . '–' . $to . ' of ' . $filtered_total . '</span>';
    $html .= '</form>';

    // ── Table ─────────────────────────────────────────────────────────────────
    $html .= <<<HTML
  <div class="table-card">
    <table class="orgs-table">
      <colgroup>
        <col class="col-org">
        <col class="col-industry">
        <col class="col-phone">
        <col class="col-email">
        <col class="col-status">
        <col class="col-employees">
        <col class="col-revenue">
        <col class="col-assigned">
        <col class="col-updated">
        <col class="col-actions">
      </colgroup>
      <thead>
        <tr>
          <th>Organization</th>
          <th>Industry</th>
          <th class="th-phone">Phone</th>
          <th class="th-email">Email</th>
          <th>Status</th>
          <th class="th-employees">Employees</th>
          <th class="th-revenue">Revenue</th>
          <th class="th-assigned">Assigned To</th>
          <th>Updated</th>
          <th class="th-action">Actions</th>
        </tr>
      </thead>
      <tbody>
HTML;

    if (empty($rows)) {
      $html .= <<<EMPTY
      <tr><td colspan="10">
        <div class="empty-state">
          <div class="empty-state-icon"><i data-lucide="search-x"></i></div>
          <div class="empty-state-title">No organizations found</div>
          <div class="empty-state-sub">Try adjusting your search filters or add a new organization.</div>
          <a href="{$add_url}" class="empty-state-btn"><i data-lucide="plus-circle"></i> Add Organization</a>
        </div>
      </td></tr>
EMPTY;
    } else {
      foreach ($rows as $r) {
        $ind_style = $get_industry_badge($r['industry']);

        // Website sub-line
        $website_sub = '';
        if ($r['website']) {
          $domain = preg_replace('#^https?://(www\.)?#i', '', rtrim($r['website'], '/'));
          $domain = strlen($domain) > 28 ? substr($domain, 0, 28) . '…' : $domain;
          $website_sub = '<span class="org-website"><a href="' . Html::escape($r['website']) . '" target="_blank" rel="noopener">' . Html::escape($domain) . '</a></span>';
        }

        // Phone cell
        $phone_cell = $r['phone']
          ? '<i data-lucide="phone"></i><a href="tel:' . $r['phone'] . '">' . $r['phone'] . '</a>'
          : '<span class="td-empty-val">—</span>';

        // Email cell
        $email_cell = $r['email']
          ? '<i data-lucide="mail"></i><a href="mailto:' . $r['email'] . '">' . $r['email'] . '</a>'
          : '<span class="td-empty-val">—</span>';

        // Industry badge
        $industry_cell = $r['industry']
          ? '<span class="badge" style="background:' . $ind_style['bg'] . ';color:' . $ind_style['color'] . '">' . $r['industry'] . '</span>'
          : '<span class="td-empty-val">—</span>';

        // Status badge
        $status_label = $r['status'] === 'active' ? 'Active' : ($r['status'] === 'inactive' ? 'Inactive' : '—');
        $status_class = $r['status'] ?: '';
        $status_cell  = $r['status']
          ? '<span class="status-badge ' . $status_class . '"><span class="status-dot"></span>' . $status_label . '</span>'
          : '<span class="td-empty-val">—</span>';

        // Employees
        $emp_cell = $r['employees'] > 0
          ? '<span class="td-number">' . number_format($r['employees']) . '</span>'
          : '<span class="td-empty-val">—</span>';

        // Revenue
        $rev_cell = $r['revenue']
          ? '<span class="td-revenue">' . $r['revenue'] . '</span>'
          : '<span class="td-empty-val">—</span>';

        // Actions
        $action_btns = '';
        if ($r['can_edit']) {
          $action_btns .= '<button class="btn-action btn-edit" title="Edit" onclick="CRMInlineEdit.openModal(' . $r['id'] . ',\'organization\')">'
            . '<i data-lucide="pencil"></i></button>';
          $action_btns .= '<button class="btn-action btn-delete" title="Delete" onclick="CRMInlineEdit.confirmDelete(' . $r['id'] . ',\'organization\',\'' . $r['title_js'] . '\')">'
            . '<i data-lucide="trash-2"></i></button>';
        }

        $html .= '<tr id="org-row-' . $r['id'] . '">'
          . '<td><div class="td-name">'
          . '<div class="org-avatar" style="' . $r['av_style'] . '">' . $r['initial'] . '</div>'
          . '<div class="org-name-block">'
          . '<a href="' . $r['url'] . '" class="org-name-link" title="' . $r['name'] . '">' . $r['name'] . '</a>'
          . $website_sub
          . '</div></div></td>'
          . '<td>' . $industry_cell . '</td>'
          . '<td class="td-phone-cell"><div class="cell-phone">' . $phone_cell . '</div></td>'
          . '<td class="td-email-cell"><div class="cell-email">' . $email_cell . '</div></td>'
          . '<td>' . $status_cell . '</td>'
          . '<td class="td-employees-cell">' . $emp_cell . '</td>'
          . '<td class="td-revenue-cell">' . $rev_cell . '</td>'
          . '<td class="td-assigned-cell"><div class="cell-owner"><i data-lucide="user"></i><span>' . ($r['assigned'] ?: '—') . '</span></div></td>'
          . '<td class="td-time">' . $r['time_ago'] . '</td>'
          . '<td><div class="cell-actions">' . $action_btns . '</div></td>'
          . '</tr>';
      }
    }

    $html .= '</tbody></table>';

    // ── Pagination ────────────────────────────────────────────────────────────
    if ($total_pages > 1) {
      $from_count = $filtered_total === 0 ? 0 : $page * $per_page + 1;
      $to_count   = min(($page + 1) * $per_page, $filtered_total);
      $html .= '<div class="pagination">'
        . '<span class="page-info">Page ' . ($page + 1) . ' of ' . $total_pages . ' &nbsp;·&nbsp; ' . $from_count . '–' . $to_count . ' of ' . $filtered_total . '</span>'
        . '<div class="page-links">';

      $html .= $page > 0
        ? '<a class="page-link" href="' . $page_url($page - 1) . '">‹ Prev</a>'
        : '<span class="page-link disabled">‹ Prev</span>';

      $start_page = max(0, $page - 2);
      $end_page   = min($total_pages - 1, $page + 2);
      if ($start_page > 0) {
        $html .= '<a class="page-link" href="' . $page_url(0) . '">1</a>';
        if ($start_page > 1) { $html .= '<span class="page-link disabled">…</span>'; }
      }
      for ($p = $start_page; $p <= $end_page; $p++) {
        $active = $p === $page ? ' active' : '';
        $html .= '<a class="page-link' . $active . '" href="' . $page_url($p) . '">' . ($p + 1) . '</a>';
      }
      if ($end_page < $total_pages - 1) {
        if ($end_page < $total_pages - 2) { $html .= '<span class="page-link disabled">…</span>'; }
        $html .= '<a class="page-link" href="' . $page_url($total_pages - 1) . '">' . $total_pages . '</a>';
      }

      $html .= $page < $total_pages - 1
        ? '<a class="page-link" href="' . $page_url($page + 1) . '">Next ›</a>'
        : '<span class="page-link disabled">Next ›</span>';

      $html .= '</div></div>';
    }

    $html .= '</div>'; // .table-card
    $html .= '</div>'; // .orgs-page

    $html .= <<<JS
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide) lucide.createIcons();
    const t = localStorage.getItem('crmToast');
    if (t) {
      try {
        const d = JSON.parse(t);
        localStorage.removeItem('crmToast');
        setTimeout(() => { if (window.CRMInlineEdit) CRMInlineEdit.showMessage(d.message, d.type); }, 300);
      } catch(e) {}
    }
  });
  document.addEventListener('crm:icons-refresh', function () {
    if (window.lucide) lucide.createIcons();
  });
</script>
JS;

    return [
      '#markup' => Markup::create($html),
      '#attached' => [
        'library' => [
          'core/drupal',
          'crm_edit/inline_edit',
          'crm_ai_autocomplete/ai-generate-button',
        ],
      ],
      '#cache' => [
        'contexts' => ['user', 'url.query_args'],
        'tags'     => ['node_list:organization'],
        'max-age'  => 300,
      ],
    ];
  }

}
