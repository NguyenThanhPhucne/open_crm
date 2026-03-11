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
    \Drupal::service('page_cache_kill_switch')->trigger();

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
    $avatar_colors = [
      'A' => '#3b82f6', 'B' => '#3b82f6', 'C' => '#3b82f6', 'D' => '#3b82f6',
      'E' => '#8b5cf6', 'F' => '#8b5cf6', 'G' => '#8b5cf6',
      'H' => '#10b981', 'I' => '#10b981', 'J' => '#10b981', 'K' => '#10b981',
      'L' => '#f59e0b', 'M' => '#f59e0b', 'N' => '#f59e0b',
      'O' => '#ec4899', 'P' => '#ec4899', 'Q' => '#ec4899',
      'R' => '#14b8a6', 'S' => '#14b8a6', 'T' => '#14b8a6',
      'U' => '#ef4444', 'V' => '#ef4444', 'W' => '#ef4444',
      'X' => '#6366f1', 'Y' => '#6366f1', 'Z' => '#6366f1',
    ];

    $rows = [];
    foreach ($contacts as $contact) {
      $cid   = $contact->id();
      $name  = $contact->getTitle();
      $initial = strtoupper(mb_substr($name, 0, 1));
      $av_color = $avatar_colors[$initial] ?? '#64748b';
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
        'av_color'     => $av_color,
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
    $contacts_url      = '/crm/all-contacts';
    $organizations_url = $can_manage ? '/crm/all-organizations' : '/crm/my-organizations';
    $deals_url         = $can_manage ? '/crm/all-deals'         : '/crm/my-deals';
    $activities_url    = $can_manage ? '/crm/all-activities'    : '/crm/my-activities';
    $dashboard_url     = '/crm/dashboard';
    $pipeline_url      = '/crm/my-pipeline';
    $add_url           = '/crm/add/contact';

    // ── Pagination helper ─────────────────────────────────────────────────────
    $page_url = function ($p) use ($search_name, $search_email, $search_phone) {
      $params = ['page' => $p];
      if ($search_name)  { $params['search'] = $search_name; }
      if ($search_email) { $params['email']  = $search_email; }
      if ($search_phone) { $params['phone']  = $search_phone; }
      return '/crm/all-contacts?' . http_build_query($params);
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

  /* ── Toolbar ── */
  .crm-toolbar{background:linear-gradient(180deg,#fff 0%,#f8f9fa 100%);border-bottom:2px solid #3b82f6;box-shadow:0 2px 6px rgba(59,130,246,.15);height:42px;margin:-20px -20px 24px}
  .crm-toolbar-lining{display:flex;align-items:center;justify-content:space-between;height:100%;padding:0 1rem;max-width:100%}
  .crm-toolbar-menu{display:flex;align-items:center;gap:0;height:100%}
  .crm-toolbar-brand{display:flex;align-items:center;gap:8px;padding:0 16px;font-size:14px;font-weight:600;color:#333;text-decoration:none;height:100%;border-right:1px solid #e5e7eb}
  .crm-toolbar-brand:hover{background:rgba(0,0,0,.03);color:#0969da}
  .crm-toolbar-item{display:flex;align-items:center;gap:6px;padding:0 14px;height:100%;font-size:13px;font-weight:500;color:#4b5563;text-decoration:none;border-right:1px solid #f3f4f6;transition:all .15s ease;white-space:nowrap}
  .crm-toolbar-item:hover{background:rgba(59,130,246,.08);color:#2563eb}
  .crm-toolbar-item.active{color:#1e40af;font-weight:700;border-bottom:2px solid #3b82f6}
  .crm-toolbar-item svg,.crm-toolbar-item i{width:16px;height:16px}
  .crm-toolbar-btn{display:flex;align-items:center;gap:6px;padding:0 12px;height:100%;font-size:13px;font-weight:600;color:#fff;text-decoration:none;background:linear-gradient(135deg,#2563eb,#1d4ed8);border:none;cursor:pointer;transition:all .2s ease;border-left:1px solid rgba(255,255,255,.1)}
  .crm-toolbar-btn:hover{background:linear-gradient(135deg,#1d4ed8,#1e40af)}
  .crm-toolbar-btn svg,.crm-toolbar-btn i{width:16px;height:16px}

  /* ── Page shell ── */
  .contacts-page{max-width:1400px;margin:0 auto;padding-top:42px;animation:fadeIn .3s ease}
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
  .page-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
  .btn-primary{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s ease;box-shadow:0 2px 4px rgba(37,99,235,.25)}
  .btn-primary:hover{background:linear-gradient(135deg,#2563eb,#1d4ed8);box-shadow:0 4px 8px rgba(37,99,235,.35);transform:translateY(-1px)}
  .btn-primary i{width:15px;height:15px}
  .btn-generate{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s ease;box-shadow:0 2px 4px rgba(124,58,237,.25)}
  .btn-generate:hover{background:linear-gradient(135deg,#7c3aed,#6d28d9);transform:translateY(-1px)}
  .btn-generate i{width:15px;height:15px}

  /* ── Filter bar ── */
  .filter-bar{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;box-shadow:0 1px 3px rgba(0,0,0,.04)}
  .filter-input-wrap{position:relative;flex:1;min-width:160px;max-width:280px}
  .filter-input-wrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94a3b8;pointer-events:none}
  .filter-input{width:100%;padding:8px 12px 8px 34px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;transition:border .15s}
  .filter-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
  .filter-input::placeholder{color:#94a3b8}
  .btn-filter-apply{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;text-decoration:none}
  .btn-filter-apply:hover{background:#e2e8f0;color:#1e293b}
  .btn-filter-clear{display:inline-flex;align-items:center;gap:5px;padding:8px 12px;background:transparent;color:#94a3b8;border:1px solid transparent;border-radius:8px;font-size:13px;cursor:pointer;transition:all .15s;text-decoration:none}
  .btn-filter-clear:hover{color:#ef4444;border-color:#fee2e2;background:#fef2f2}
  .filter-count{font-size:12px;color:#64748b;font-weight:500;white-space:nowrap;margin-left:auto}

  /* ── Table card ── */
  .table-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)}
  .contacts-table{width:100%;border-collapse:collapse;font-size:13px}
  .contacts-table thead tr{background:linear-gradient(to right,#f8fafc,#f1f5f9);border-bottom:2px solid #e2e8f0}
  .contacts-table th{padding:12px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;white-space:nowrap}
  .contacts-table th.th-action{text-align:right}
  .contacts-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .12s}
  .contacts-table tbody tr:last-child{border-bottom:none}
  .contacts-table tbody tr:hover{background:#f8fafc}
  .contacts-table td{padding:13px 16px;vertical-align:middle}
  .td-name{display:flex;align-items:center;gap:11px}
  .contact-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0}
  .contact-name-block{display:flex;flex-direction:column;gap:2px;min-width:0}
  .contact-name-link{font-weight:600;color:#0f172a;text-decoration:none;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;display:block}
  .contact-name-link:hover{color:#3b82f6;text-decoration:underline}
  .contact-position{font-size:11px;color:#94a3b8;white-space:nowrap}
  .td-org a{color:#3b82f6;text-decoration:none;font-size:13px;font-weight:500;white-space:nowrap}
  .td-org a:hover{text-decoration:underline;color:#1d4ed8}
  .td-org .no-org{color:#cbd5e1;font-style:italic;font-size:12px}
  .cell-phone,.cell-email{display:flex;align-items:center;gap:7px;white-space:nowrap}
  .cell-phone>i,.cell-email>i{width:12px;height:12px;color:#d1d5db;flex-shrink:0;opacity:.85}
  .cell-phone a,.cell-email a{color:#374151;text-decoration:none;font-size:13px;font-weight:500}
  .cell-phone a:hover,.cell-email a:hover{color:#3b82f6}
  .td-empty-val{color:#cbd5e1;font-style:italic;font-size:12px}
  .badge{display:inline-block;padding:3px 9px;border-radius:12px;font-size:11px;font-weight:600;letter-spacing:.02em;white-space:nowrap}
  .cell-owner{display:flex;align-items:center;gap:5px;font-size:12px;color:#475569;white-space:nowrap}
  .cell-owner i{width:11px;height:11px;color:#d1d5db;opacity:.8}
  .td-time{font-size:12px;color:#94a3b8;white-space:nowrap}
  .cell-actions{display:flex;align-items:center;gap:6px;justify-content:flex-end}
  .btn-action{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:7px;border:1px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer;transition:all .15s;text-decoration:none}
  .btn-action:hover.btn-edit{border-color:#bfdbfe;background:#eff6ff;color:#3b82f6}
  .btn-action:hover.btn-delete{border-color:#fecaca;background:#fef2f2;color:#ef4444}
  .btn-action i{width:14px;height:14px}

  /* ── Empty state ── */
  .empty-state{text-align:center;padding:72px 30px}
  .empty-state-icon{width:64px;height:64px;background:#f1f5f9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
  .empty-state-icon i{width:30px;height:30px;color:#cbd5e1}
  .empty-state-title{font-size:18px;font-weight:700;color:#334155;margin-bottom:6px}
  .empty-state-sub{font-size:14px;color:#94a3b8;margin-bottom:24px}
  .empty-state-btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#3b82f6;color:#fff;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none}

  /* ── Pagination ── */
  .pagination{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid #f1f5f9;background:#fafafa;flex-wrap:wrap;gap:10px}
  .page-info{font-size:13px;color:#64748b}
  .page-links{display:flex;align-items:center;gap:4px}
  .page-link{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 8px;border-radius:7px;border:1px solid #e2e8f0;background:#fff;font-size:13px;font-weight:500;color:#374151;text-decoration:none;transition:all .15s;white-space:nowrap}
  .page-link:hover{border-color:#bfdbfe;background:#eff6ff;color:#2563eb}
  .page-link.active{background:#3b82f6;border-color:#3b82f6;color:#fff;font-weight:700}
  .page-link.disabled{opacity:.4;pointer-events:none}

  @media(max-width:1024px){.contacts-table th.th-owner,.contacts-table td.td-owner-cell,.contacts-table th.th-source,.contacts-table td.td-source-cell{display:none}}
  @media(max-width:768px){body{padding:12px}.contacts-table th.th-email,.contacts-table td.td-email-cell,.contacts-table th.th-phone,.contacts-table td.td-phone-cell{display:none}.crm-toolbar-item span{display:none}}
  .contacts-table th,.contacts-table td{box-sizing:border-box}
</style>
HTML;

    // ── Toolbar ───────────────────────────────────────────────────────────────
    $html .= <<<HTML
<div class="crm-toolbar">
  <div class="crm-toolbar-lining">
    <div class="crm-toolbar-menu">
      <a href="{$dashboard_url}" class="crm-toolbar-brand">
        <i data-lucide="zap" width="16" height="16"></i>
        <span>CRM</span>
      </a>
      <a href="{$dashboard_url}" class="crm-toolbar-item"><i data-lucide="layout-dashboard"></i><span>Dashboard</span></a>
      <a href="{$contacts_url}" class="crm-toolbar-item active"><i data-lucide="users"></i><span>Contacts</span></a>
      <a href="{$organizations_url}" class="crm-toolbar-item"><i data-lucide="building-2"></i><span>Organizations</span></a>
      <a href="{$deals_url}" class="crm-toolbar-item"><i data-lucide="briefcase"></i><span>Deals</span></a>
      <a href="{$activities_url}" class="crm-toolbar-item"><i data-lucide="activity"></i><span>Activities</span></a>
      <a href="{$pipeline_url}" class="crm-toolbar-item"><i data-lucide="git-branch"></i><span>Pipeline</span></a>
    </div>
  </div>
</div>

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
          . '<div class="contact-avatar" style="background:' . $r['av_color'] . '">' . $r['initial'] . '</div>'
          . '<div class="contact-name-block">'
          . '<a href="' . $r['url'] . '" class="contact-name-link" title="' . $r['name'] . '">' . $r['name'] . '</a>'
          . ($r['position'] ? '<span class="contact-position">' . $r['position'] . '</span>' : '')
          . '</div></div></td>'
          . '<td class="td-org">' . $org_cell . '</td>'
          . '<td class="td-phone-cell"><div class="cell-phone">' . $phone_cell . '</div></td>'
          . '<td class="td-email-cell"><div class="cell-email">' . $email_cell . '</div></td>'
          . '<td>' . $type_cell . '</td>'
          . '<td class="td-source-cell">' . $src_cell . '</td>'
          . '<td class="td-owner-cell"><div class="cell-owner"><i data-lucide="user"></i>' . ($r['owner'] ?: '—') . '</div></td>'
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
        'tags'    => ['node_list:contact'],
        'max-age' => 0,
      ],
    ];
  }

}
