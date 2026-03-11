<?php

namespace Drupal\crm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\Request;

/**
 * Professional All Contacts list controller with inline CRUD.
 */
class AllContactsController extends ControllerBase {

  /**
   * Render the All Contacts page.
   */
  public function view(Request $request) {
    $current_user = \Drupal::currentUser();
    $user_id      = $current_user->id();
    $is_admin     = in_array('administrator', $current_user->getRoles()) || $user_id == 1;
    $is_manager   = in_array('sales_manager', $current_user->getRoles());
    $can_manage   = $is_admin || $is_manager;

    // ── Search / filter parameters ────────────────────────────────────────────
    $search_name  = trim($request->query->get('search', ''));
    $search_email = trim($request->query->get('email', ''));
    $search_phone = trim($request->query->get('phone', ''));
    $page         = max(0, (int) $request->query->get('page', 0));
    $per_page     = 25;

    // ── Query builder (reusable closure) ──────────────────────────────────────
    $build_query = function () use ($search_name, $search_email, $search_phone, $can_manage, $user_id) {
      $q = \Drupal::entityQuery('node')
        ->condition('type', 'contact')
        ->accessCheck(FALSE);
      if ($search_name) {
        $q->condition('title', '%' . $search_name . '%', 'LIKE');
      }
      if ($search_email) {
        $q->condition('field_email.value', '%' . $search_email . '%', 'LIKE');
      }
      if ($search_phone) {
        $q->condition('field_phone', '%' . $search_phone . '%', 'LIKE');
      }
      if (!$can_manage && $user_id > 0) {
        $q->condition('field_owner', $user_id);
      }
      return $q;
    };

    // ── Totals ────────────────────────────────────────────────────────────────
    $now          = \Drupal::time()->getCurrentTime();
    $dow          = (int) date('N', $now);
    $week_start   = mktime(0, 0, 0, (int) date('n', $now), (int) date('j', $now) - ($dow - 1));
    $month_start  = mktime(0, 0, 0, (int) date('n', $now), 1);

    // Total contacts (unfiltered, for stats chip)
    $all_q = \Drupal::entityQuery('node')->condition('type', 'contact')->accessCheck(FALSE);
    if (!$can_manage && $user_id > 0) { $all_q->condition('field_owner', $user_id); }
    $total_all = (int) $all_q->count()->execute();

    // New this week
    $week_q = \Drupal::entityQuery('node')->condition('type', 'contact')
      ->condition('created', $week_start, '>=')->accessCheck(FALSE);
    if (!$can_manage && $user_id > 0) { $week_q->condition('field_owner', $user_id); }
    $new_this_week = (int) $week_q->count()->execute();

    // New this month
    $month_q = \Drupal::entityQuery('node')->condition('type', 'contact')
      ->condition('created', $month_start, '>=')->accessCheck(FALSE);
    if (!$can_manage && $user_id > 0) { $month_q->condition('field_owner', $user_id); }
    $new_this_month = (int) $month_q->count()->execute();

    // ── Paged results ─────────────────────────────────────────────────────────
    $filtered_total = (int) $build_query()->count()->execute();
    $total_pages    = max(1, (int) ceil($filtered_total / $per_page));
    $page           = min($page, $total_pages - 1);

    $ids      = $build_query()->sort('changed', 'DESC')->range($page * $per_page, $per_page)->execute();
    $contacts = !empty($ids) ? \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids) : [];

    // ── Format rows ───────────────────────────────────────────────────────────
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

    $rows = [];
    foreach ($contacts as $contact) {
      $cid   = $contact->id();
      $name  = $contact->getTitle();
      $initial = strtoupper(mb_substr($name, 0, 1));
      $av_pair  = $avatar_pairs[$initial] ?? ['#f1f5f9', '#64748b'];
      $av_style = "background:linear-gradient(135deg,{$av_pair[0]} 0%,#fff 80%);color:{$av_pair[1]};border-color:{$av_pair[1]}4d";
      $contact_url = Url::fromRoute('entity.node.canonical', ['node' => $cid])->toString();

      $org_name = ''; $org_url = '';
      if ($contact->hasField('field_organization') && !$contact->get('field_organization')->isEmpty()) {
        $org = $contact->get('field_organization')->entity;
        if ($org) {
          $org_name = $org->getTitle();
          $org_url  = Url::fromRoute('entity.node.canonical', ['node' => $org->id()])->toString();
        }
      }

      $phone = $contact->hasField('field_phone') && !$contact->get('field_phone')->isEmpty()
        ? ($contact->get('field_phone')->value ?? '') : '';

      $email = $contact->hasField('field_email') && !$contact->get('field_email')->isEmpty()
        ? ($contact->get('field_email')->value ?? '') : '';

      $position = $contact->hasField('field_position') && !$contact->get('field_position')->isEmpty()
        ? ($contact->get('field_position')->value ?? '') : '';

      $source = '';
      if ($contact->hasField('field_source') && !$contact->get('field_source')->isEmpty()) {
        $src_term = $contact->get('field_source')->entity;
        if ($src_term) { $source = $src_term->getName(); }
      }

      $ctype = '';
      if ($contact->hasField('field_customer_type') && !$contact->get('field_customer_type')->isEmpty()) {
        $ct_term = $contact->get('field_customer_type')->entity;
        if ($ct_term) { $ctype = $ct_term->getName(); }
      }

      $owner_name = '';
      if ($contact->hasField('field_owner') && !$contact->get('field_owner')->isEmpty()) {
        $owner_user = $contact->get('field_owner')->entity;
        if ($owner_user) { $owner_name = $owner_user->getDisplayName(); }
      }

      $can_edit = $can_manage ||
        ($contact->hasField('field_owner') && $contact->get('field_owner')->target_id == $user_id);

      $diff = $now - $contact->getChangedTime();
      if ($diff < 60)        { $time_ago = 'just now'; }
      elseif ($diff < 3600)  { $time_ago = floor($diff / 60) . 'm ago'; }
      elseif ($diff < 86400) { $time_ago = floor($diff / 3600) . 'h ago'; }
      elseif ($diff < 604800){ $time_ago = floor($diff / 86400) . 'd ago'; }
      else                   { $time_ago = floor($diff / 604800) . 'w ago'; }

      $rows[] = [
        'id'           => $cid,
        'name'         => Html::escape($name),
        'initial'      => $initial,
        'av_style'     => $av_style,
        'url'          => $contact_url,
        'org_name'     => Html::escape($org_name),
        'org_url'      => $org_url,
        'phone'        => Html::escape($phone),
        'email'        => Html::escape($email),
        'position'     => Html::escape($position),
        'source'       => Html::escape($source),
        'ctype'        => Html::escape($ctype),
        'owner'        => Html::escape($owner_name),
        'time_ago'     => $time_ago,
        'can_edit'     => $can_edit,
        'title_js'     => addslashes($name),
      ];
    }

    // ── Navigation URLs ───────────────────────────────────────────────────────
    $current_path      = $request->getPathInfo();
    $contacts_url      = $current_path;
    $organizations_url = $can_manage ? '/crm/all-organizations' : '/crm/my-organizations';
    $deals_url         = $can_manage ? '/crm/all-deals'         : '/crm/my-deals';
    $activities_url    = $can_manage ? '/crm/all-activities'    : '/crm/my-activities';
    $dashboard_url     = '/crm/dashboard';
    $pipeline_url      = '/crm/my-pipeline';
    $add_url           = '/crm/add/contact';

    // ── Pagination helper ─────────────────────────────────────────────────────
    $page_url = function ($p) use ($search_name, $search_email, $search_phone, $current_path) {
      $params = ['page' => $p];
      if ($search_name)  { $params['search'] = $search_name; }
      if ($search_email) { $params['email']  = $search_email; }
      if ($search_phone) { $params['phone']  = $search_phone; }
      return $current_path . '?' . http_build_query($params);
    };

    // Escape for HTML attributes
    $e_search = Html::escape($search_name);
    $e_email  = Html::escape($search_email);
    $e_phone  = Html::escape($search_phone);

    // ── Source / Customer-type badge colors ───────────────────────────────────
    $source_colors = [
      'linkedin'  => ['bg' => '#e0f2fe', 'color' => '#0369a1'],
      'website'   => ['bg' => '#dcfce7', 'color' => '#166534'],
      'referral'  => ['bg' => '#fef9c3', 'color' => '#854d0e'],
      'cold call' => ['bg' => '#fee2e2', 'color' => '#991b1b'],
      'email'     => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
      'event'     => ['bg' => '#fce7f3', 'color' => '#9d174d'],
    ];
    $ctype_colors = [
      'enterprise' => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
      'smb'        => ['bg' => '#dcfce7', 'color' => '#15803d'],
      'startup'    => ['bg' => '#fef3c7', 'color' => '#b45309'],
      'individual' => ['bg' => '#f3e8ff', 'color' => '#7e22ce'],
    ];
    $get_badge = function ($value, $map) {
      $key = strtolower($value);
      foreach ($map as $k => $v) {
        if (str_contains($key, $k)) { return $v; }
      }
      return ['bg' => '#f1f5f9', 'color' => '#475569'];
    };

    // ── HTML output ───────────────────────────────────────────────────────────
    $html = <<<HTML
<script src="https://unpkg.com/lucide@latest"></script>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f8fafc;color:#1e293b}

  /* ── Page shell ── */
  .contacts-page{max-width:1400px;margin:0 auto;animation:fadeIn .3s ease}
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
  /* ── Outlined button base ── */
  .btn-primary,.btn-generate{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:#fff;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:background .15s,border-color .15s,color .15s;white-space:nowrap}
  .btn-primary{color:#2563eb;border:1.5px solid #2563eb}
  .btn-primary:hover{background:#eff6ff;border-color:#1d4ed8;color:#1d4ed8}
  .btn-primary:active{background:#dbeafe}
  .btn-primary:focus-visible{outline:2px solid #3b82f6;outline-offset:2px}
  .btn-primary i{width:15px;height:15px;color:inherit}
  .btn-generate{color:#7c3aed;border:1.5px solid #7c3aed}
  .btn-generate:hover{background:#f5f3ff;border-color:#6d28d9;color:#6d28d9}
  .btn-generate:active{background:#ede9fe}
  .btn-generate:focus-visible{outline:2px solid #8b5cf6;outline-offset:2px}
  .btn-generate i{width:15px;height:15px;color:inherit}

  /* ── Filter bar ── */
  .filter-bar{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;box-shadow:0 1px 3px rgba(0,0,0,.04)}
  .filter-input-wrap{position:relative;flex:1;min-width:160px;max-width:280px}
  .filter-input-wrap i,.filter-input-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#9ca3af;pointer-events:none;z-index:2;flex-shrink:0}
  .filter-input{width:100%;height:40px !important;padding:0 12px 0 36px !important;margin:0;border:1px solid #e5e7eb !important;border-radius:8px !important;font-size:14px !important;color:#1e293b;outline:none;transition:border-color .15s,box-shadow .15s;box-sizing:border-box !important;background:#fff !important;display:block}
  .filter-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
  .filter-input::placeholder{color:#9ca3af}
  .btn-filter-apply{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:40px;padding:0 16px;background:#fff;color:#2563eb;border:1.5px solid #2563eb;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s,color .15s,border-color .15s;text-decoration:none;white-space:nowrap;flex-shrink:0;box-sizing:border-box}
  .btn-filter-apply:hover{background:#eff6ff;color:#1d4ed8;border-color:#1d4ed8}
  .btn-filter-apply i{width:15px;height:15px;color:inherit;flex-shrink:0}
  .btn-filter-clear{display:inline-flex;align-items:center;justify-content:center;gap:5px;height:40px;padding:0 12px;background:transparent;color:#94a3b8;border:1px solid transparent;border-radius:8px;font-size:14px;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap;flex-shrink:0;box-sizing:border-box}
  .btn-filter-clear:hover{color:#ef4444;border-color:#fee2e2;background:#fef2f2}
  .filter-count{font-size:12px;color:#64748b;font-weight:500;white-space:nowrap;margin-left:auto}

  /* ── Table card ── */
  .table-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)}
  .contacts-table{width:100%;border-collapse:collapse;font-size:12px;table-layout:fixed}
  .contacts-table thead tr{background:linear-gradient(to right,#f8fafc,#f1f5f9);border-bottom:2px solid #e2e8f0}
  .contacts-table th{padding:10px 12px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;white-space:nowrap;overflow:hidden}
  .contacts-table th.th-action{text-align:right}
  .contacts-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .12s}
  .contacts-table tbody tr:last-child{border-bottom:none}
  .contacts-table tbody tr:hover{background:#f8fafc}
  .contacts-table td{padding:10px 12px;vertical-align:middle;overflow:hidden}
  /* Column widths */
  .contacts-table .col-contact{width:200px}
  .contacts-table .col-org{width:130px}
  .contacts-table .col-phone{width:120px}
  .contacts-table .col-email{width:155px}
  .contacts-table .col-type{width:85px}
  .contacts-table .col-source{width:80px}
  .contacts-table .col-owner{width:90px}
  .contacts-table .col-updated{width:68px}
  .contacts-table .col-actions{width:76px}
  /* Name cell */
  .td-name{display:flex;align-items:center;gap:9px}
  .contact-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;border:1.5px solid transparent;box-shadow:0 2px 8px rgba(0,0,0,.06),inset 0 1px 0 rgba(255,255,255,.85)}
  .contact-name-block{display:flex;flex-direction:column;gap:1px;min-width:0;overflow:hidden}
  .contact-name-link{font-weight:600;color:#0f172a;text-decoration:none;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}
  .contact-name-link:hover{color:#3b82f6;text-decoration:underline}
  .contact-position{font-size:11px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}
  /* Org */
  .td-org{overflow:hidden}
  .td-org a{color:#3b82f6;text-decoration:none;font-size:12px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;max-width:100%}
  .td-org a:hover{text-decoration:underline;color:#1d4ed8}
  .td-org .no-org{color:#cbd5e1;font-style:italic;font-size:12px}
  /* Phone / Email */
  .cell-phone,.cell-email{display:flex;align-items:center;gap:6px;overflow:hidden}
  .cell-phone>i,.cell-email>i{width:11px;height:11px;color:#d1d5db;flex-shrink:0;opacity:.85}
  .cell-phone a,.cell-email a{color:#374151;text-decoration:none;font-size:12px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
  .cell-phone a:hover,.cell-email a:hover{color:#3b82f6}
  .td-empty-val{color:#cbd5e1;font-style:italic;font-size:12px}
  .badge{display:inline-block;padding:2px 7px;border-radius:10px;font-size:11px;font-weight:600;letter-spacing:.02em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
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

  @media(max-width:1280px){.contacts-table .col-source,.contacts-table th.th-source,.contacts-table td.td-source-cell{display:none}}
  @media(max-width:1100px){.contacts-table .col-owner,.contacts-table th.th-owner,.contacts-table td.td-owner-cell{display:none}}
  @media(max-width:900px){.contacts-table .col-email,.contacts-table th.th-email,.contacts-table td.td-email-cell{display:none}}
  @media(max-width:700px){.contacts-table .col-phone,.contacts-table th.th-phone,.contacts-table td.td-phone-cell{display:none}}
  .contacts-table th,.contacts-table td{box-sizing:border-box}
</style>
HTML;

    // ── Page content ──────────────────────────────────────────────────────────
    $html .= <<<HTML
<div class="contacts-page">

  <!-- Stats bar -->
  <div class="stats-bar">
    <span class="stat-chip blue"><i data-lucide="users"></i>{$total_all} total contacts</span>
    <span class="stat-chip green"><i data-lucide="user-plus"></i>+{$new_this_week} this week</span>
    <span class="stat-chip purple"><i data-lucide="calendar"></i>+{$new_this_month} this month</span>
  </div>

  <!-- Page header -->
  <div class="page-header">
    <div class="page-header-left">
      <div class="page-title">
        <i data-lucide="users" width="24" height="24"></i>
        All Contacts
      </div>
      <div class="page-subtitle">Manage your customer contacts and relationships</div>
    </div>
    <div class="page-actions">
      <a href="{$add_url}" class="btn-primary">
        <i data-lucide="user-plus"></i>
        Add Contact
      </a>
      <button id="crm-ai-generate-btn" class="btn-generate" data-entity-type="contact">
        <i data-lucide="sparkles"></i>
        Generate data
      </button>
    </div>
  </div>

  <!-- Filter bar -->
  <form class="filter-bar" method="get" action="{$contacts_url}">
    <div class="filter-input-wrap">
      <i data-lucide="search"></i>
      <input class="filter-input" type="text" name="search" placeholder="Search by name…" value="{$e_search}">
    </div>
    <div class="filter-input-wrap">
      <i data-lucide="mail"></i>
      <input class="filter-input" type="text" name="email" placeholder="Filter by email…" value="{$e_email}">
    </div>
    <div class="filter-input-wrap">
      <i data-lucide="phone"></i>
      <input class="filter-input" type="text" name="phone" placeholder="Filter by phone…" value="{$e_phone}">
    </div>
    <button type="submit" class="btn-filter-apply">
      <i data-lucide="filter"></i>
      Apply
    </button>
HTML;

    if ($search_name || $search_email || $search_phone) {
      $html .= '<a href="' . $contacts_url . '" class="btn-filter-clear"><i data-lucide="x"></i> Clear</a>';
    }

    $from = $filtered_total === 0 ? 0 : $page * $per_page + 1;
    $to   = min(($page + 1) * $per_page, $filtered_total);
    $html .= '<span class="filter-count">Showing ' . $from . '–' . $to . ' of ' . $filtered_total . '</span>';
    $html .= '</form>';

    // ── Table ─────────────────────────────────────────────────────────────────
    $html .= <<<HTML
  <div class="table-card">
    <table class="contacts-table">
      <colgroup>
        <col class="col-contact">
        <col class="col-org">
        <col class="col-phone th-phone">
        <col class="col-email th-email">
        <col class="col-type">
        <col class="col-source th-source">
        <col class="col-owner th-owner">
        <col class="col-updated">
        <col class="col-actions">
      </colgroup>
      <thead>
        <tr>
          <th>Contact</th>
          <th>Organization</th>
          <th class="th-phone">Phone</th>
          <th class="th-email">Email</th>
          <th>Type</th>
          <th class="th-source">Source</th>
          <th class="th-owner">Owner</th>
          <th>Updated</th>
          <th class="th-action">Actions</th>
        </tr>
      </thead>
      <tbody>
HTML;

    if (empty($rows)) {
      $clear_url = $contacts_url;
      $html .= <<<EMPTY
      <tr><td colspan="9">
        <div class="empty-state">
          <div class="empty-state-icon"><i data-lucide="search-x"></i></div>
          <div class="empty-state-title">No contacts found</div>
          <div class="empty-state-sub">Try adjusting your search filters or add a new contact.</div>
          <a href="{$add_url}" class="empty-state-btn"><i data-lucide="user-plus"></i> Add Contact</a>
        </div>
      </td></tr>
EMPTY;
    } else {
      foreach ($rows as $r) {
        $src_style = $get_badge($r['source'], $source_colors);
        $ct_style  = $get_badge($r['ctype'],  $ctype_colors);

        // Org cell — clean text link, no icon inside
        $org_cell = $r['org_name']
          ? '<a href="' . $r['org_url'] . '">' . $r['org_name'] . '</a>'
          : '<span class="no-org">—</span>';

        // Phone cell
        $phone_cell = $r['phone']
          ? '<i data-lucide="phone"></i><a href="tel:' . $r['phone'] . '">' . $r['phone'] . '</a>'
          : '<span class="td-empty-val">—</span>';

        // Email cell
        $email_cell = $r['email']
          ? '<i data-lucide="mail"></i><a href="mailto:' . $r['email'] . '">' . $r['email'] . '</a>'
          : '<span class="td-empty-val">—</span>';

        // Type badge
        $type_cell = $r['ctype']
          ? '<span class="badge" style="background:' . $ct_style['bg'] . ';color:' . $ct_style['color'] . '">' . $r['ctype'] . '</span>'
          : '<span class="td-empty-val">—</span>';

        // Source badge
        $src_cell = $r['source']
          ? '<span class="badge" style="background:' . $src_style['bg'] . ';color:' . $src_style['color'] . '">' . $r['source'] . '</span>'
          : '<span class="td-empty-val">—</span>';

        // Action buttons
        $action_btns = '';
        if ($r['can_edit']) {
          $action_btns .= '<button class="btn-action btn-edit" title="Edit" onclick="CRMInlineEdit.openModal(' . $r['id'] . ',\'contact\')">'
            . '<i data-lucide="pencil"></i></button>';
          $action_btns .= '<button class="btn-action btn-delete" title="Delete" onclick="CRMInlineEdit.confirmDelete(' . $r['id'] . ',\'contact\',\'' . $r['title_js'] . '\')">'
            . '<i data-lucide="trash-2"></i></button>';
        }

        $html .= '<tr id="contact-row-' . $r['id'] . '">'
          . '<td><div class="td-name">'
          . '<div class="contact-avatar" style="' . $r['av_style'] . '">' . $r['initial'] . '</div>'
          . '<div class="contact-name-block">'
          . '<a href="' . $r['url'] . '" class="contact-name-link" title="' . $r['name'] . '">' . $r['name'] . '</a>'
          . ($r['position'] ? '<span class="contact-position">' . $r['position'] . '</span>' : '')
          . '</div></div></td>'
          . '<td class="td-org">' . $org_cell . '</td>'
          . '<td class="td-phone-cell"><div class="cell-phone">' . $phone_cell . '</div></td>'
          . '<td class="td-email-cell"><div class="cell-email">' . $email_cell . '</div></td>'
          . '<td>' . $type_cell . '</td>'
          . '<td class="td-source-cell">' . $src_cell . '</td>'
          . '<td class="td-owner-cell"><div class="cell-owner"><i data-lucide="user"></i><span>' . ($r['owner'] ?: '—') . '</span></div></td>'
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

      // Prev
      $html .= $page > 0
        ? '<a class="page-link" href="' . $page_url($page - 1) . '">‹ Prev</a>'
        : '<span class="page-link disabled">‹ Prev</span>';

      // Page numbers (show window around current)
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

      // Next
      $html .= $page < $total_pages - 1
        ? '<a class="page-link" href="' . $page_url($page + 1) . '">Next ›</a>'
        : '<span class="page-link disabled">Next ›</span>';

      $html .= '</div></div>';
    }

    $html .= '</div>'; // .table-card
    $html .= '</div>'; // .contacts-page

    $html .= <<<JS
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide) lucide.createIcons();

    // Show pending toast (after edit/delete reload)
    const t = localStorage.getItem('crmToast');
    if (t) {
      try {
        const d = JSON.parse(t);
        localStorage.removeItem('crmToast');
        setTimeout(() => { if (window.CRMInlineEdit) CRMInlineEdit.showMessage(d.message, d.type); }, 300);
      } catch(e) {}
    }
  });
  // Re-init Lucide after any DOM change (modal open/close, etc.)
  const _origCreate = window.lucide ? lucide.createIcons.bind(lucide) : null;
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
        'tags'     => ['node_list:contact'],
        'max-age'  => 300,
      ],
    ];
  }

}
