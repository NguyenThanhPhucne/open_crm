<?php

namespace Drupal\crm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\Request;

/**
 * Professional All Deals list controller — UI consistent with AllContactsController.
 */
class AllDealsController extends ControllerBase {

  /**
   * Stage metadata (tid → label, colors).
   */
  private static function stageMap(): array {
    return [
      1 => ['label' => 'New',         'bg' => '#dbeafe', 'color' => '#1d4ed8'],
      2 => ['label' => 'Qualified',   'bg' => '#dcfce7', 'color' => '#15803d'],
      3 => ['label' => 'Proposal',    'bg' => '#fef9c3', 'color' => '#854d0e'],
      4 => ['label' => 'Negotiation', 'bg' => '#fed7aa', 'color' => '#c2410c'],
      5 => ['label' => 'Won',         'bg' => '#bbf7d0', 'color' => '#065f46'],
      6 => ['label' => 'Lost',        'bg' => '#fee2e2', 'color' => '#991b1b'],
    ];
  }

  /** Format amount as $1,234,567 */
  private static function formatAmount(string $raw): string {
    $n = (float) $raw;
    if ($n >= 1_000_000) { return '$' . number_format($n / 1_000_000, 1) . 'M'; }
    if ($n >= 1_000)     { return '$' . number_format($n / 1_000, 0) . 'K'; }
    return '$' . number_format($n, 0);
  }

  public function view(Request $request) {
    $current_user = \Drupal::currentUser();
    $user_id      = $current_user->id();
    $is_admin     = in_array('administrator', $current_user->getRoles()) || $user_id == 1;
    $is_manager   = in_array('sales_manager', $current_user->getRoles());
    $can_manage   = $is_admin || $is_manager;

    // ── Filter parameters ─────────────────────────────────────────────────────
    $search_name  = trim($request->query->get('search', ''));
    $filter_stage = (int) $request->query->get('stage', 0);
    $page         = max(0, (int) $request->query->get('page', 0));
    $per_page     = 25;

    // ── Query builder ─────────────────────────────────────────────────────────
    $build_query = function () use ($search_name, $filter_stage, $can_manage, $user_id) {
      $q = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->accessCheck(FALSE);
      if ($search_name) {
        $q->condition('title', '%' . $search_name . '%', 'LIKE');
      }
      if ($filter_stage > 0) {
        $q->condition('field_stage', $filter_stage);
      }
      if (!$can_manage && $user_id > 0) {
        $q->condition('field_owner', $user_id);
      }
      return $q;
    };

    // ── Stats ─────────────────────────────────────────────────────────────────
    $now         = \Drupal::time()->getCurrentTime();
    $month_start = mktime(0, 0, 0, (int) date('n', $now), 1);

    $all_q = \Drupal::entityQuery('node')->condition('type', 'deal')->accessCheck(FALSE);
    if (!$can_manage && $user_id > 0) { $all_q->condition('field_owner', $user_id); }
    $total_all = (int) $all_q->count()->execute();

    // Pipeline value: sum of all open deal amounts
    $db = \Drupal::database();
    $pipeline_alias = $db->select('node_field_data', 'n');
    $pipeline_alias->join('node__field_amount', 'fa', 'fa.entity_id = n.nid AND fa.deleted = 0');
    $pipeline_alias->join('node__field_stage', 'fs', 'fs.entity_id = n.nid AND fs.deleted = 0');
    $pipeline_alias->condition('n.type', 'deal');
    $pipeline_alias->condition('fs.field_stage_value', [5, 6], 'NOT IN'); // exclude Won/Lost
    if (!$can_manage && $user_id > 0) {
      $pipeline_alias->join('node__field_owner', 'fo', 'fo.entity_id = n.nid AND fo.deleted = 0');
      $pipeline_alias->condition('fo.field_owner_target_id', $user_id);
    }
    $pipeline_value = (float) ($pipeline_alias->addExpression('SUM(fa.field_amount_value)', 'total')->execute()->fetchField() ?? 0);

    // Won this month
    $won_q = $db->select('node_field_data', 'n');
    $won_q->join('node__field_stage', 'fs', 'fs.entity_id = n.nid AND fs.deleted = 0');
    $won_q->condition('n.type', 'deal');
    $won_q->condition('fs.field_stage_value', 5);
    $won_q->condition('n.changed', $month_start, '>=');
    if (!$can_manage && $user_id > 0) {
      $won_q->join('node__field_owner', 'fo', 'fo.entity_id = n.nid AND fo.deleted = 0');
      $won_q->condition('fo.field_owner_target_id', $user_id);
    }
    $won_this_month = (int) $won_q->countQuery()->execute()->fetchField();

    // ── Paged results ─────────────────────────────────────────────────────────
    $filtered_total = (int) $build_query()->count()->execute();
    $total_pages    = max(1, (int) ceil($filtered_total / $per_page));
    $page           = min($page, $total_pages - 1);

    $ids   = $build_query()->sort('changed', 'DESC')->range($page * $per_page, $per_page)->execute();
    $deals = !empty($ids) ? \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids) : [];

    // ── Stage map ─────────────────────────────────────────────────────────────
    $stage_map = self::stageMap();

    // ── Avatar colors (by initial) ────────────────────────────────────────────
    $avatar_colors = [
      'A' => '#3b82f6','B' => '#3b82f6','C' => '#3b82f6','D' => '#3b82f6',
      'E' => '#8b5cf6','F' => '#8b5cf6','G' => '#8b5cf6',
      'H' => '#10b981','I' => '#10b981','J' => '#10b981','K' => '#10b981',
      'L' => '#f59e0b','M' => '#f59e0b','N' => '#f59e0b',
      'O' => '#ec4899','P' => '#ec4899','Q' => '#ec4899',
      'R' => '#14b8a6','S' => '#14b8a6','T' => '#14b8a6',
      'U' => '#ef4444','V' => '#ef4444','W' => '#ef4444',
      'X' => '#6366f1','Y' => '#6366f1','Z' => '#6366f1',
    ];

    // ── Format rows ───────────────────────────────────────────────────────────
    $rows = [];
    foreach ($deals as $deal) {
      $did     = $deal->id();
      $name    = $deal->getTitle();
      $initial = strtoupper(mb_substr($name, 0, 1));
      $av_color = $avatar_colors[$initial] ?? '#64748b';
      $deal_url = Url::fromRoute('entity.node.canonical', ['node' => $did])->toString();

      // Stage
      $stage_tid = (int) $deal->get('field_stage')->getString();
      $stage_info = $stage_map[$stage_tid] ?? ['label' => 'Unknown', 'bg' => '#f1f5f9', 'color' => '#475569'];

      // Amount
      $amount_raw = $deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty()
        ? $deal->get('field_amount')->value : 0;
      $amount_fmt = $amount_raw ? self::formatAmount((string) $amount_raw) : '—';

      // Probability
      $prob = $deal->hasField('field_probability') && !$deal->get('field_probability')->isEmpty()
        ? (int) $deal->get('field_probability')->value : null;

      // Close date
      $close_date = '';
      if ($deal->hasField('field_expected_close_date') && !$deal->get('field_expected_close_date')->isEmpty()) {
        $ts = strtotime($deal->get('field_expected_close_date')->value);
        if ($ts) {
          $close_date = date('M j, Y', $ts);
          // Mark overdue
          if ($ts < $now && $stage_tid !== 5 && $stage_tid !== 6) {
            $close_date = '⚠ ' . $close_date;
          }
        }
      }

      // Contact
      $contact_name = ''; $contact_url = '';
      if ($deal->hasField('field_contact') && !$deal->get('field_contact')->isEmpty()) {
        $contact = $deal->get('field_contact')->entity;
        if ($contact) {
          $contact_name = $contact->getTitle();
          $contact_url  = Url::fromRoute('entity.node.canonical', ['node' => $contact->id()])->toString();
        }
      }

      // Organization
      $org_name = ''; $org_url = '';
      if ($deal->hasField('field_organization') && !$deal->get('field_organization')->isEmpty()) {
        $org = $deal->get('field_organization')->entity;
        if ($org) {
          $org_name = $org->getTitle();
          $org_url  = Url::fromRoute('entity.node.canonical', ['node' => $org->id()])->toString();
        }
      }

      // Owner
      $owner_name = '';
      if ($deal->hasField('field_owner') && !$deal->get('field_owner')->isEmpty()) {
        $owner_user = $deal->get('field_owner')->entity;
        if ($owner_user) { $owner_name = $owner_user->getDisplayName(); }
      }

      // Can edit?
      $can_edit = $can_manage ||
        ($deal->hasField('field_owner') && $deal->get('field_owner')->target_id == $user_id);

      // Time ago
      $diff = $now - $deal->getChangedTime();
      if ($diff < 60)        { $time_ago = 'just now'; }
      elseif ($diff < 3600)  { $time_ago = floor($diff / 60) . 'm ago'; }
      elseif ($diff < 86400) { $time_ago = floor($diff / 3600) . 'h ago'; }
      elseif ($diff < 604800){ $time_ago = floor($diff / 86400) . 'd ago'; }
      else                   { $time_ago = floor($diff / 604800) . 'w ago'; }

      $rows[] = [
        'id'           => $did,
        'name'         => Html::escape($name),
        'initial'      => $initial,
        'av_color'     => $av_color,
        'url'          => $deal_url,
        'stage_label'  => Html::escape($stage_info['label']),
        'stage_bg'     => $stage_info['bg'],
        'stage_color'  => $stage_info['color'],
        'amount_fmt'   => Html::escape($amount_fmt),
        'amount_raw'   => (float) $amount_raw,
        'prob'         => $prob,
        'close_date'   => Html::escape($close_date),
        'contact_name' => Html::escape($contact_name),
        'contact_url'  => $contact_url,
        'org_name'     => Html::escape($org_name),
        'org_url'      => $org_url,
        'owner'        => Html::escape($owner_name),
        'time_ago'     => $time_ago,
        'can_edit'     => $can_edit,
        'title_js'     => addslashes($name),
      ];
    }

    // ── URL helpers ───────────────────────────────────────────────────────────
    $current_path = $request->getPathInfo();
    $deals_url    = $current_path;
    $add_url      = '/crm/add/deal';
    $pipeline_url = $can_manage ? '/crm/all-pipeline' : '/crm/my-pipeline';

    $page_url = function ($p) use ($search_name, $filter_stage, $current_path) {
      $params = ['page' => $p];
      if ($search_name)  { $params['search'] = $search_name; }
      if ($filter_stage) { $params['stage']  = $filter_stage; }
      return $current_path . '?' . http_build_query($params);
    };

    $filter_url = function ($extra = []) use ($search_name, $filter_stage, $current_path) {
      $params = [];
      if ($search_name)  { $params['search'] = $search_name; }
      if ($filter_stage) { $params['stage']  = $filter_stage; }
      return $current_path . '?' . http_build_query(array_merge($params, $extra));
    };

    $e_search = Html::escape($search_name);
    $pipeline_value_fmt = self::formatAmount((string) $pipeline_value);

    // ── HTML ──────────────────────────────────────────────────────────────────
    $html = <<<HTML
<script src="https://unpkg.com/lucide@latest"></script>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f8fafc;color:#1e293b}

  .deals-page{max-width:1400px;margin:0 auto;animation:fadeIn .3s ease}
  @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

  /* Stats */
  .stats-bar{display:flex;align-items:center;gap:16px;margin-bottom:24px;flex-wrap:wrap}
  .stat-chip{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;border:1px solid}
  .stat-chip.blue{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
  .stat-chip.green{background:#ecfdf5;color:#15803d;border-color:#bbf7d0}
  .stat-chip.amber{background:#fffbeb;color:#b45309;border-color:#fde68a}
  .stat-chip i{width:14px;height:14px}

  /* Header */
  .page-header{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;box-shadow:0 1px 3px rgba(0,0,0,.05);flex-wrap:wrap}
  .page-header-left{display:flex;flex-direction:column;gap:6px}
  .page-title{font-size:22px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:10px;letter-spacing:-.02em}
  .page-title i{color:#3b82f6;width:24px;height:24px}
  .page-subtitle{font-size:13px;color:#64748b}
  .page-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto}

  /* Buttons */
  .btn-primary,.btn-secondary,.btn-generate{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s;white-space:nowrap}
  .btn-primary{color:#2563eb;border:1.5px solid #2563eb;background:#fff}
  .btn-primary:hover{background:#eff6ff;border-color:#1d4ed8;color:#1d4ed8}
  .btn-secondary{color:#475569;border:1.5px solid #e2e8f0;background:#fff}
  .btn-secondary:hover{background:#f8fafc;border-color:#cbd5e1;color:#1e293b}
  .btn-generate{color:#7c3aed;border:1.5px solid #7c3aed;background:#fff}
  .btn-generate:hover{background:#f5f3ff;border-color:#6d28d9;color:#6d28d9}
  .btn-primary i,.btn-secondary i,.btn-generate i{width:15px;height:15px;color:inherit}

  /* Filter bar */
  .filter-bar{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;box-shadow:0 1px 3px rgba(0,0,0,.04)}
  .filter-input-wrap{position:relative;flex:1;min-width:160px;max-width:280px}
  .filter-input-wrap i,.filter-input-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#9ca3af;pointer-events:none;z-index:2;flex-shrink:0}
  .filter-input{width:100%;height:40px !important;padding:0 12px 0 36px !important;margin:0;border:1px solid #e5e7eb !important;border-radius:8px !important;font-size:14px !important;color:#1e293b;outline:none;transition:border-color .15s,box-shadow .15s;box-sizing:border-box !important;background:#fff !important;display:block}
  .filter-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
  .filter-input::placeholder{color:#9ca3af}
  .filter-select-wrap{position:relative;min-width:160px}
  .filter-select-wrap i{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:#9ca3af;pointer-events:none;z-index:2}
  .filter-select{height:40px !important;padding:0 12px 0 34px !important;border:1px solid #e5e7eb !important;border-radius:8px !important;font-size:14px !important;color:#1e293b;background:#fff;outline:none;cursor:pointer;transition:border-color .15s;appearance:none;min-width:160px}
  .filter-select:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
  .btn-filter-apply{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:40px;padding:0 16px;background:#fff;color:#2563eb;border:1.5px solid #2563eb;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap;flex-shrink:0}
  .btn-filter-apply:hover{background:#eff6ff;color:#1d4ed8;border-color:#1d4ed8}
  .btn-filter-apply i{width:15px;height:15px;color:inherit;flex-shrink:0}
  .btn-filter-clear{display:inline-flex;align-items:center;justify-content:center;gap:5px;height:40px;padding:0 12px;background:transparent;color:#94a3b8;border:1px solid transparent;border-radius:8px;font-size:14px;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap;flex-shrink:0}
  .btn-filter-clear:hover{color:#ef4444;border-color:#fee2e2;background:#fef2f2}
  .filter-count{font-size:12px;color:#64748b;font-weight:500;white-space:nowrap;margin-left:auto}

  /* Table */
  .table-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)}
  .deals-table{width:100%;border-collapse:collapse;font-size:12px;table-layout:fixed}
  .deals-table thead tr{background:linear-gradient(to right,#f8fafc,#f1f5f9);border-bottom:2px solid #e2e8f0}
  .deals-table th{padding:10px 12px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;white-space:nowrap;overflow:hidden}
  .deals-table th.th-action{text-align:right}
  .deals-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .12s}
  .deals-table tbody tr:last-child{border-bottom:none}
  .deals-table tbody tr:hover{background:#f8fafc}
  .deals-table td{padding:10px 12px;vertical-align:middle;overflow:hidden}

  /* Column widths */
  .deals-table .col-deal{width:220px}
  .deals-table .col-stage{width:100px}
  .deals-table .col-amount{width:90px}
  .deals-table .col-contact{width:130px}
  .deals-table .col-org{width:130px}
  .deals-table .col-prob{width:72px}
  .deals-table .col-close{width:100px}
  .deals-table .col-owner{width:90px}
  .deals-table .col-updated{width:68px}
  .deals-table .col-actions{width:68px}

  /* Name cell */
  .td-name{display:flex;align-items:center;gap:9px}
  .deal-avatar{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0}
  .deal-name-block{display:flex;flex-direction:column;gap:1px;min-width:0;overflow:hidden}
  .deal-name-link{font-weight:600;color:#0f172a;text-decoration:none;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}
  .deal-name-link:hover{color:#3b82f6;text-decoration:underline}

  /* Badge */
  .badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;letter-spacing:.02em;white-space:nowrap}
  .td-empty-val{color:#cbd5e1;font-style:italic;font-size:12px}

  /* Amount */
  .td-amount{font-weight:700;color:#0f172a;font-size:13px}

  /* Probability bar */
  .prob-wrap{display:flex;align-items:center;gap:6px}
  .prob-bar{flex:1;height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;min-width:28px}
  .prob-fill{height:100%;border-radius:3px;background:#3b82f6;transition:width .3s}
  .prob-label{font-size:11px;color:#64748b;white-space:nowrap;min-width:28px}

  /* Close date */
  .td-close{font-size:12px;color:#475569;white-space:nowrap}
  .td-close.overdue{color:#dc2626;font-weight:600}

  /* Contact / Org links */
  .td-ref a{color:#3b82f6;text-decoration:none;font-size:12px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;max-width:100%}
  .td-ref a:hover{text-decoration:underline;color:#1d4ed8}

  /* Owner */
  .cell-owner{display:flex;align-items:center;gap:4px;font-size:11px;color:#475569;overflow:hidden}
  .cell-owner span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .cell-owner i{width:10px;height:10px;color:#d1d5db;flex-shrink:0}
  .td-time{font-size:11px;color:#94a3b8;white-space:nowrap}

  /* Actions */
  .cell-actions{display:flex;align-items:center;gap:4px;justify-content:flex-end}
  .btn-action{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;border:1.5px solid #e2e8f0;background:#fff;color:#94a3b8;cursor:pointer;transition:all .15s;text-decoration:none;flex-shrink:0}
  .btn-action:hover.btn-edit{border-color:#2563eb;background:#eff6ff;color:#2563eb}
  .btn-action:hover.btn-delete{border-color:#dc2626;background:#fef2f2;color:#dc2626}
  .btn-action i{width:13px;height:13px;color:inherit}

  /* Empty state */
  .empty-state{text-align:center;padding:72px 30px}
  .empty-state-icon{width:64px;height:64px;background:#f1f5f9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
  .empty-state-icon i{width:30px;height:30px;color:#cbd5e1}
  .empty-state-title{font-size:18px;font-weight:700;color:#334155;margin-bottom:6px}
  .empty-state-sub{font-size:14px;color:#94a3b8;margin-bottom:24px}
  .empty-state-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#fff;color:#2563eb;border:1.5px solid #2563eb;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;transition:background .15s}
  .empty-state-btn:hover{background:#eff6ff}

  /* Pagination */
  .pagination{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid #f1f5f9;background:#fafafa;flex-wrap:wrap;gap:10px}
  .page-info{font-size:13px;color:#64748b}
  .page-links{display:flex;align-items:center;gap:4px}
  .page-link{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 8px;border-radius:7px;border:1px solid #e2e8f0;background:#fff;font-size:13px;font-weight:500;color:#374151;text-decoration:none;transition:all .15s;white-space:nowrap}
  .page-link:hover{border-color:#bfdbfe;background:#eff6ff;color:#2563eb}
  .page-link.active{background:#3b82f6;border-color:#3b82f6;color:#fff;font-weight:700}
  .page-link.disabled{opacity:.4;pointer-events:none}

  @media(max-width:1280px){.deals-table .col-org,.deals-table th.th-org,.deals-table td.td-org-cell{display:none}}
  @media(max-width:1100px){.deals-table .col-owner,.deals-table th.th-owner,.deals-table td.td-owner-cell{display:none}}
  @media(max-width:900px){.deals-table .col-close,.deals-table th.th-close,.deals-table td.td-close-cell{display:none}}
  @media(max-width:700px){.deals-table .col-prob,.deals-table th.th-prob,.deals-table td.td-prob-cell{display:none}}
  .deals-table th,.deals-table td{box-sizing:border-box}
</style>
HTML;

    // ── Stats bar ─────────────────────────────────────────────────────────────
    $html .= <<<HTML
<div class="deals-page">

  <div class="stats-bar">
    <span class="stat-chip blue"><i data-lucide="briefcase"></i>{$total_all} total deals</span>
    <span class="stat-chip amber"><i data-lucide="trending-up"></i>{$pipeline_value_fmt} in pipeline</span>
    <span class="stat-chip green"><i data-lucide="trophy"></i>{$won_this_month} won this month</span>
  </div>

  <!-- Page header -->
  <div class="page-header">
    <div class="page-header-left">
      <div class="page-title">
        <i data-lucide="briefcase" width="24" height="24"></i>
        All Deals
      </div>
      <div class="page-subtitle">Track your sales deals and pipeline progress</div>
    </div>
    <div class="page-actions">
      <a href="{$pipeline_url}" class="btn-secondary">
        <i data-lucide="kanban-square"></i>
        Pipeline view
      </a>
      <a href="{$add_url}" class="btn-primary">
        <i data-lucide="plus-circle"></i>
        Add Deal
      </a>
      <button id="crm-ai-generate-btn" class="btn-generate" data-entity-type="deal">
        <i data-lucide="sparkles"></i>
        Generate data
      </button>
    </div>
  </div>
HTML;

    // ── Filter bar ────────────────────────────────────────────────────────────
    $html .= '<form class="filter-bar" method="get" action="' . $deals_url . '">';
    $html .= '<div class="filter-input-wrap"><i data-lucide="search"></i>'
      . '<input class="filter-input" type="text" name="search" placeholder="Search by deal name…" value="' . $e_search . '"></div>';

    // Stage dropdown
    $html .= '<div class="filter-select-wrap"><i data-lucide="layers"></i>'
      . '<select class="filter-select" name="stage">'
      . '<option value="0"' . ($filter_stage === 0 ? ' selected' : '') . '>All stages</option>';
    foreach ($stage_map as $tid => $s) {
      $sel = $filter_stage === $tid ? ' selected' : '';
      $html .= '<option value="' . $tid . '"' . $sel . '>' . $s['label'] . '</option>';
    }
    $html .= '</select></div>';

    $html .= '<button type="submit" class="btn-filter-apply"><i data-lucide="filter"></i>Apply</button>';

    if ($search_name || $filter_stage > 0) {
      $html .= '<a href="' . $deals_url . '" class="btn-filter-clear"><i data-lucide="x"></i> Clear</a>';
    }

    $from_label = $filtered_total === 0 ? 0 : $page * $per_page + 1;
    $to_label   = min(($page + 1) * $per_page, $filtered_total);
    $html .= '<span class="filter-count">Showing ' . $from_label . '–' . $to_label . ' of ' . $filtered_total . '</span>';
    $html .= '</form>';

    // ── Table ─────────────────────────────────────────────────────────────────
    $html .= <<<HTML
  <div class="table-card">
    <table class="deals-table">
      <colgroup>
        <col class="col-deal">
        <col class="col-stage">
        <col class="col-amount">
        <col class="col-contact">
        <col class="col-org">
        <col class="col-prob th-prob">
        <col class="col-close th-close">
        <col class="col-owner th-owner">
        <col class="col-updated">
        <col class="col-actions">
      </colgroup>
      <thead>
        <tr>
          <th>Deal</th>
          <th>Stage</th>
          <th>Amount</th>
          <th>Contact</th>
          <th class="th-org">Organization</th>
          <th class="th-prob">Prob.</th>
          <th class="th-close">Close Date</th>
          <th class="th-owner">Owner</th>
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
          <div class="empty-state-title">No deals found</div>
          <div class="empty-state-sub">Try adjusting your filters or add a new deal.</div>
          <a href="{$add_url}" class="empty-state-btn"><i data-lucide="plus-circle"></i> Add Deal</a>
        </div>
      </td></tr>
EMPTY;
    } else {
      foreach ($rows as $r) {
        // Stage badge
        $stage_badge = '<span class="badge" style="background:' . $r['stage_bg'] . ';color:' . $r['stage_color'] . '">'
          . $r['stage_label'] . '</span>';

        // Probability bar
        if ($r['prob'] !== null) {
          $prob_pct = max(0, min(100, $r['prob']));
          $prob_cell = '<div class="prob-wrap">'
            . '<div class="prob-bar"><div class="prob-fill" style="width:' . $prob_pct . '%"></div></div>'
            . '<span class="prob-label">' . $prob_pct . '%</span></div>';
        } else {
          $prob_cell = '<span class="td-empty-val">—</span>';
        }

        // Close date classes
        $close_class = str_starts_with($r['close_date'], '⚠') ? ' overdue' : '';
        $close_cell  = $r['close_date']
          ? '<span class="td-close' . $close_class . '">' . $r['close_date'] . '</span>'
          : '<span class="td-empty-val">—</span>';

        // Contact link
        $contact_cell = $r['contact_name']
          ? '<a href="' . $r['contact_url'] . '">' . $r['contact_name'] . '</a>'
          : '<span class="td-empty-val">—</span>';

        // Org link
        $org_cell = $r['org_name']
          ? '<a href="' . $r['org_url'] . '">' . $r['org_name'] . '</a>'
          : '<span class="td-empty-val">—</span>';

        // Actions
        $action_btns = '';
        if ($r['can_edit']) {
          $action_btns .= '<button class="btn-action btn-edit" title="Edit" onclick="CRMInlineEdit.openModal(' . $r['id'] . ',\'deal\')">'
            . '<i data-lucide="pencil"></i></button>';
          $action_btns .= '<button class="btn-action btn-delete" title="Delete" onclick="CRMInlineEdit.confirmDelete(' . $r['id'] . ',\'deal\',\'' . $r['title_js'] . '\')">'
            . '<i data-lucide="trash-2"></i></button>';
        }

        $html .= '<tr id="deal-row-' . $r['id'] . '">'
          . '<td><div class="td-name">'
          . '<div class="deal-avatar" style="background:' . $r['av_color'] . '">' . $r['initial'] . '</div>'
          . '<div class="deal-name-block">'
          . '<a href="' . $r['url'] . '" class="deal-name-link" title="' . $r['name'] . '">' . $r['name'] . '</a>'
          . '</div></div></td>'
          . '<td>' . $stage_badge . '</td>'
          . '<td class="td-amount">' . $r['amount_fmt'] . '</td>'
          . '<td class="td-ref">' . $contact_cell . '</td>'
          . '<td class="td-ref td-org-cell">' . $org_cell . '</td>'
          . '<td class="td-prob-cell">' . $prob_cell . '</td>'
          . '<td class="td-close-cell">' . $close_cell . '</td>'
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
        . '<span class="page-info">Page ' . ($page + 1) . ' of ' . $total_pages
        . ' &nbsp;·&nbsp; ' . $from_count . '–' . $to_count . ' of ' . $filtered_total . '</span>'
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
    $html .= '</div>'; // .deals-page

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
        'tags'     => ['node_list:deal'],
        'max-age'  => 300,
      ],
    ];
  }

}
