<?php

namespace Drupal\crm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\Request;

/**
 * Professional All Deals list controller — UI consistent with AllContactsController.
 */
class AllDealsController extends ControllerBase {

  /**
   * Access check for deals pages.
   * 
   * Rules:
   * - /crm/my-deals: All logged-in users can view
   * - /crm/all-deals: Only admin/manager can view
   */
  public function accessView(Request $request, AccountInterface $account) {
    $current_path = $request->getPathInfo();
    $is_my_view = str_contains($current_path, 'my-deals');
    
    // My-deals: all logged-in users can view their own deals
    if ($is_my_view) {
      return $account->isAuthenticated() ? AccessResult::allowed() : AccessResult::forbidden();
    }
    
    // All-deals: only admin/manager can view all deals
    $is_admin = in_array('administrator', $account->getRoles()) || $account->id() == 1;
    $is_manager = in_array('sales_manager', $account->getRoles());
    
    return ($is_admin || $is_manager) ? AccessResult::allowed() : AccessResult::forbidden();
  }

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
    $current_path = $request->getPathInfo();
    $is_my_view   = str_contains($current_path, 'my-deals');
    $deal_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'deal');
    $has_deleted_at = isset($deal_fields['field_deleted_at']);

    // ── Filter parameters ─────────────────────────────────────────────────────
    $search_name  = trim($request->query->get('search', ''));
    $sort_field   = $request->query->get('sort', 'changed');
    $sort_dir     = strtoupper($request->query->get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $per_page     = max(10, min(100, (int) $request->query->get('per_page', 25)));
    $filter_stage = (int) $request->query->get('stage', 0);
    $page         = max(0, (int) $request->query->get('page', 0));
    if (!in_array($sort_field, ['title', 'changed'])) { $sort_field = 'changed'; }

    // ── Query builder ─────────────────────────────────────────────────────────
    $build_query = function () use ($search_name, $filter_stage, $can_manage, $is_my_view, $user_id, $has_deleted_at) {
      $q = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->accessCheck(TRUE);
      if ($has_deleted_at) {
        $q->notExists('field_deleted_at');
      }
      if ($search_name) {
        $q->condition('title', $search_name . '%', 'LIKE');
      }
      if ($filter_stage > 0) {
        $q->condition('field_stage', $filter_stage);
      }
      if ($is_my_view || (!$can_manage && $user_id > 0)) {
        $q->condition('field_owner', $user_id);
      }
      return $q;
    };

    // ── Stats ─────────────────────────────────────────────────────────────────
    $now         = \Drupal::time()->getCurrentTime();
    $month_start = mktime(0, 0, 0, (int) date('n', $now), 1);

    $all_q = \Drupal::entityQuery('node')->condition('type', 'deal')->accessCheck(TRUE);
    if ($has_deleted_at) { $all_q->notExists('field_deleted_at'); }
    if ($is_my_view || (!$can_manage && $user_id > 0)) { $all_q->condition('field_owner', $user_id); }
    $total_all = (int) $all_q->count()->execute();

    // Pipeline value: sum of all open deal amounts
    $db = \Drupal::database();
    $pipeline_alias = $db->select('node_field_data', 'n');
    $pipeline_alias->join('node__field_amount', 'fa', 'fa.entity_id = n.nid AND fa.deleted = 0');
    $pipeline_alias->join('node__field_stage', 'fs', 'fs.entity_id = n.nid AND fs.deleted = 0');
    $pipeline_alias->condition('n.type', 'deal');
    $pipeline_alias->condition('fs.field_stage_target_id', [5, 6], 'NOT IN'); // exclude Won/Lost
    if ($is_my_view || (!$can_manage && $user_id > 0)) {
      $pipeline_alias->join('node__field_owner', 'fo', 'fo.entity_id = n.nid AND fo.deleted = 0');
      $pipeline_alias->condition('fo.field_owner_target_id', $user_id);
    }
    $pipeline_alias->addExpression('SUM(fa.field_amount_value)', 'total');
    $pipeline_value = (float) ($pipeline_alias->execute()->fetchField() ?? 0);

    // Won this month
    $won_q = $db->select('node_field_data', 'n');
    $won_q->join('node__field_stage', 'fs', 'fs.entity_id = n.nid AND fs.deleted = 0');
    $won_q->condition('n.type', 'deal');
    $won_q->condition('fs.field_stage_target_id', 5);
    $won_q->condition('n.changed', $month_start, '>=');
    if ($is_my_view || (!$can_manage && $user_id > 0)) {
      $won_q->join('node__field_owner', 'fo', 'fo.entity_id = n.nid AND fo.deleted = 0');
      $won_q->condition('fo.field_owner_target_id', $user_id);
    }
    $won_this_month = (int) $won_q->countQuery()->execute()->fetchField();

    // ── Paged results ─────────────────────────────────────────────────────────
    $filtered_total = (int) $build_query()->count()->execute();
    $total_pages    = max(1, (int) ceil($filtered_total / $per_page));
    $page           = min($page, $total_pages - 1);

    $ids   = $build_query()->sort($sort_field, $sort_dir)->range($page * $per_page, $per_page)->execute();
    $deals = !empty($ids) ? \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids) : [];

    // ── Stage map ─────────────────────────────────────────────────────────────
    $stage_map = self::stageMap();

    // ── Avatar colors (by initial) ────────────────────────────────────────────
    $avatar_pairs = [
      'A' => ['#dbeafe','#2563eb'],'B' => ['#dbeafe','#2563eb'],'C' => ['#dbeafe','#2563eb'],'D' => ['#dbeafe','#2563eb'],
      'E' => ['#ede9fe','#6d28d9'],'F' => ['#ede9fe','#6d28d9'],'G' => ['#ede9fe','#6d28d9'],
      'H' => ['#dcfce7','#059669'],'I' => ['#dcfce7','#059669'],'J' => ['#dcfce7','#059669'],'K' => ['#dcfce7','#059669'],
      'L' => ['#fef9c3','#b45309'],'M' => ['#fef9c3','#b45309'],'N' => ['#fef9c3','#b45309'],
      'O' => ['#fce7f3','#be185d'],'P' => ['#fce7f3','#be185d'],'Q' => ['#fce7f3','#be185d'],
      'R' => ['#ccfbf1','#0d9488'],'S' => ['#ccfbf1','#0d9488'],'T' => ['#ccfbf1','#0d9488'],
      'U' => ['#fee2e2','#dc2626'],'V' => ['#fee2e2','#dc2626'],'W' => ['#fee2e2','#dc2626'],
      'X' => ['#e0e7ff','#4338ca'],'Y' => ['#e0e7ff','#4338ca'],'Z' => ['#e0e7ff','#4338ca'],
    ];

    // ── Format rows ───────────────────────────────────────────────────────────
    $rows = [];
    foreach ($deals as $deal) {
      if ($has_deleted_at && $deal->hasField('field_deleted_at') && !$deal->get('field_deleted_at')->isEmpty()) {
        continue;
      }
      $did     = $deal->id();
      $name    = $deal->getTitle();
      $initial = strtoupper(mb_substr($name, 0, 1));
      $av_pair  = $avatar_pairs[$initial] ?? ['#f1f5f9', '#64748b'];
      $av_style = "background:linear-gradient(135deg,{$av_pair[0]} 0%,#fff 80%);color:{$av_pair[1]};border-color:{$av_pair[1]}4d";
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

      // Use Drupal entity access for exact action permissions.
      $can_edit = $deal->access('update', $this->currentUser());
      $can_delete = $deal->access('delete', $this->currentUser());

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
        'av_style'     => $av_style,
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
        'can_delete'   => $can_delete,
        'title_js'     => addslashes($name),
      ];
    }

    // ── URL helpers ───────────────────────────────────────────────────────────
    $current_path = $request->getPathInfo();
    $deals_url    = $current_path;
    $add_url      = '/crm/add/deal';
    $pipeline_url = $can_manage ? '/crm/all-pipeline' : '/crm/my-pipeline';

    $page_url = function ($p) use ($search_name, $filter_stage, $sort_field, $sort_dir, $per_page, $current_path) {
      $params = ['page' => $p, 'sort' => $sort_field, 'dir' => $sort_dir, 'per_page' => $per_page];
      if ($search_name)  { $params['search'] = $search_name; }
      if ($filter_stage) { $params['stage']  = $filter_stage; }
      return $current_path . '?' . http_build_query($params);
    };
    $sort_url = function ($field) use ($sort_field, $sort_dir, $search_name, $filter_stage, $per_page, $current_path): string {
      $nd = ($sort_field === $field && $sort_dir === 'DESC') ? 'ASC' : 'DESC';
      $p  = ['sort' => $field, 'dir' => $nd, 'per_page' => $per_page, 'page' => 0];
      if ($search_name)  { $p['search'] = $search_name; }
      if ($filter_stage) { $p['stage']  = $filter_stage; }
      return $current_path . '?' . http_build_query($p);
    };
    $sort_ic = function ($field) use ($sort_field, $sort_dir): string {
      if ($sort_field !== $field) return '<svg class="sort-ic" viewBox="0 0 10 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M5 1v12M2 4l3-3 3 3M2 10l3 3 3-3"/></svg>';
      return $sort_dir === 'ASC'
        ? '<svg class="sort-ic asc" viewBox="0 0 10 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 13V1M2 4l3-3 3 3"/></svg>'
        : '<svg class="sort-ic desc" viewBox="0 0 10 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 1v12M2 10l3 3 3-3"/></svg>';
    };
    $th_cls = fn($f) => $sort_field === $f ? ' class="th-sort th-sorted"' : ' class="th-sort"';

    $filter_url = function ($extra = []) use ($search_name, $filter_stage, $sort_field, $sort_dir, $per_page, $current_path) {
      $params = ['sort' => $sort_field, 'dir' => $sort_dir, 'per_page' => $per_page];
      if ($search_name)  { $params['search'] = $search_name; }
      if ($filter_stage) { $params['stage']  = $filter_stage; }
      return $current_path . '?' . http_build_query(array_merge($params, $extra));
    };

    $e_search = Html::escape($search_name);
    $pipeline_value_fmt = self::formatAmount((string) $pipeline_value);

    // ── Active filter chips ───────────────────────────────────────────────────
    $chips_html = '';
    if ($search_name || $filter_stage) {
      $sm = self::stageMap();
      $mk_chip = function ($label, $skip) use ($search_name, $filter_stage, $sort_field, $sort_dir, $per_page, $current_path): string {
        $p = array_filter(['search' => $search_name, 'stage' => $filter_stage, 'sort' => $sort_field, 'dir' => $sort_dir, 'per_page' => $per_page]);
        unset($p[$skip]);
        return '<span class="filter-chip">' . Html::escape($label) . '<a href="' . $current_path . '?' . http_build_query($p) . '" class="chip-x" title="Remove">×</a></span>';
      };
      $chips_html = '<div class="filter-chips"><span class="chips-lbl">Active filters:</span>';
      if ($search_name)  { $chips_html .= $mk_chip('Name: ' . $search_name, 'search'); }
      if ($filter_stage) { $chips_html .= $mk_chip('Stage: ' . ($sm[$filter_stage]['label'] ?? 'Stage'), 'stage'); }
      $chips_html .= '</div>';
    }

    // ── Sort pre-computations (used in heredoc) ────────────────────────────────
    $su_nm = $sort_url('title');   $si_nm = $sort_ic('title');   $tc_nm = $th_cls('title');
    $su_up = $sort_url('changed'); $si_up = $sort_ic('changed'); $tc_up = $th_cls('changed');
    $per_page_sel = '';
    foreach ([10, 25, 50, 100] as $_n) {
      $per_page_sel .= '<option value="' . $_n . '"' . ($_n == $per_page ? ' selected' : '') . '>' . $_n . '</option>';
    }

    // ── HTML ──────────────────────────────────────────────────────────────────
    $html = <<<HTML
<script src="https://unpkg.com/lucide@latest"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;background:#f8fafc;color:#1e293b}

  .deals-page{max-width:1400px;margin:0 auto;animation:fadeIn .3s ease}
  @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

  /* Stats */
  .stats-bar{display:flex;align-items:center;gap:16px;margin-bottom:24px;flex-wrap:wrap}
  .stat-chip{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;border:1px solid;min-width:160px}
  .stat-chip.blue{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
  .stat-chip.green{background:#ecfdf5;color:#15803d;border-color:#bbf7d0}
  .stat-chip.amber{background:#fffbeb;color:#b45309;border-color:#fde68a}
  .stat-chip i{width:14px;height:14px}

  /* Header */
  .page-header{background:#fff;border:1px solid #e2e8f0;border-radius: 16px;padding:20px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;box-shadow: 0 1px 2px 0 rgba(0,0,0,0.03), 0 4px 12px 0 rgba(0,0,0,0.04);flex-wrap:wrap}
  .page-header-left{display:flex;flex-direction:column;gap:6px}
  .page-title{font-size:22px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:10px;letter-spacing:-.02em}
  .page-title i{color:#3b82f6;width:24px;height:24px}
  .page-subtitle{font-size:13px;color:#64748b}
  .page-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto}

  /* Buttons */
  .btn-primary,.btn-secondary,.btn-generate{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius: 16px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s;white-space:nowrap}
  .btn-primary {color:#2563eb !important;border:1.5px solid #2563eb !important;background:#ffffff !important;box-shadow:0 1px 2px rgba(0,0,0,0.05);transition:all 0.2s cubic-bezier(0.4,0,0.2,1);}
  .btn-primary:hover {background:#eff6ff !important;border-color:#1d4ed8 !important;color:#1d4ed8 !important;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,0.15);}
  .btn-primary i, .btn-primary svg { color: #2563eb !important; stroke: #2563eb !important; }
  .btn-primary:hover i, .btn-primary:hover svg { color: #1d4ed8 !important; stroke: #1d4ed8 !important; }
  .btn-secondary{color:#475569;border:1.5px solid #e2e8f0;background:#fff}
  .btn-secondary:hover{background:#f8fafc;border-color:#cbd5e1;color:#1e293b}
  .btn-generate{color:#7c3aed;border:1.5px solid #7c3aed;background:#fff}
  .btn-generate:hover{background:#f5f3ff;border-color:#6d28d9;color:#6d28d9}
  .btn-primary i,.btn-secondary i,.btn-generate i{width:15px;height:15px;color:inherit}

  /* Filter bar */
  .filter-bar{background:#fff;border:1px solid #e2e8f0;border-radius: 16px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;box-shadow:0 1px 3px rgba(0,0,0,.04)}
  .filter-input-wrap{position:relative;flex:1;min-width:160px;max-width:280px;border:1px solid #e5e7eb;border-radius: 16px;background:#fff;transition:border-color .15s,box-shadow .15s}
  .filter-input-wrap:focus-within{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
  .filter-input-wrap i,.filter-input-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#3b82f6;pointer-events:none;z-index:2;flex-shrink:0}
  .filter-input{width:100%;height:40px !important;padding:0 12px 0 36px !important;margin:0;border:none !important;font-size:14px !important;color:#1e293b;outline:none;box-sizing:border-box !important;background:transparent !important;display:block;box-shadow:none !important}
  .filter-input:focus{outline:none}
  .filter-input::placeholder{color:#9ca3af}
  .filter-select-wrap{display:flex;align-items:center;height:40px;min-width:160px;border:1px solid #e5e7eb;border-radius: 16px;background:#fff;transition:border-color .15s,box-shadow .15s;overflow:hidden}
  .filter-select-wrap:focus-within{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
  .flt-sel-ico{display:flex;align-items:center;justify-content:center;padding:0 6px 0 10px;flex-shrink:0;color:#3b82f6;pointer-events:none}.flt-sel-ico i,.flt-sel-ico svg{width:15px;height:15px;display:block;flex-shrink:0;stroke-width:2.2}
  .flt-sel-arr{display:flex;align-items:center;padding:0 9px 0 2px;flex-shrink:0;color:#9ca3af;pointer-events:none}.flt-sel-arr svg{width:13px;height:13px;display:block}
  .filter-select{flex:1;height:100%;min-width:0;border:none !important;border-radius:0 !important;padding:0 2px !important;font-size:14px !important;color:#1e293b;background:transparent;outline:none !important;cursor:pointer;appearance:none;-webkit-appearance:none;box-shadow:none !important}
  .filter-select:focus{border:none !important;box-shadow:none !important}
  .btn-filter-apply {display:inline-flex;align-items:center;justify-content:center;gap:6px;height:40px;padding:0 16px;background:#ffffff !important;color:#2563eb !important;border:1.5px solid #2563eb !important;border-radius:16px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.2s ease;white-space:nowrap;flex-shrink:0;box-shadow:0 1px 2px rgba(0,0,0,0.05);}
  .btn-filter-apply:hover {background:#eff6ff !important;color:#1d4ed8 !important;border-color:#1d4ed8 !important;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,0.15);}
  .btn-filter-apply i, .btn-filter-apply svg { color:#1d4ed8 !important; stroke:#1d4ed8 !important; }
  .btn-filter-apply i{width:15px;height:15px;color:inherit;flex-shrink:0}
  .btn-filter-clear{display:inline-flex;align-items:center;justify-content:center;gap:5px;height:40px;padding:0 12px;background:transparent;color:#94a3b8;border:1px solid transparent;border-radius: 16px;font-size:14px;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap;flex-shrink:0}
  .btn-filter-clear:hover{color:#ef4444;border-color:#fee2e2;background:#fef2f2}
  .filter-count{font-size:12px;color:#64748b;font-weight:500;white-space:nowrap;margin-left:auto}

  /* Table */
  .table-card{background:#fff;border:1px solid #e2e8f0;border-radius: 16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)}
  .deals-table{width:100%;border-collapse:collapse;font-size:12px;table-layout:fixed}
  .deals-table thead tr { background:rgba(248,250,252,0.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:2px solid #e2e8f0 }
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
  .deal-avatar{width:32px;height:32px;border-radius: 16px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;border:1.5px solid transparent;box-shadow:0 2px 8px rgba(0,0,0,.06),inset 0 1px 0 rgba(255,255,255,.85)}
  .deal-name-block{display:flex;flex-direction:column;gap:1px;min-width:0;overflow:hidden}
  .deal-name-link{font-weight:600;color:#0f172a;text-decoration:none;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}
  .deal-name-link:hover{color:#3b82f6;text-decoration:underline}

  /* Badge */
  .badge{display:inline-block;padding:2px 8px;border-radius: 16px;font-size:11px;font-weight:600;letter-spacing:.02em;white-space:nowrap}
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
  .crm-row-action{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;border:1.5px solid #dbe3ef;background:#fff;color:#475569;cursor:pointer;transition:all .15s;text-decoration:none;flex-shrink:0}
  .crm-row-action.btn-edit{border-color:#bfdbfe;background:#eff6ff;color:#1d4ed8}
  .crm-row-action.btn-delete{border-color:#fecaca;background:#fef2f2;color:#b91c1c}
  .crm-row-action:hover.btn-edit{border-color:#2563eb;background:#eff6ff;color:#2563eb}
  .crm-row-action:hover.btn-delete{border-color:#dc2626;background:#fef2f2;color:#dc2626}
  .crm-row-action i,.crm-row-action svg{width:15px;height:15px;min-width:15px;color:inherit;stroke:currentColor;stroke-width:2.3;opacity:1;display:inline-block;vertical-align:middle;flex-shrink:0}
  .crm-row-action.btn-edit,.crm-row-action.btn-edit svg{color:#64748b !important;stroke:currentColor !important;fill:none !important}
  .crm-row-action.btn-delete,.crm-row-action.btn-delete svg{color:#64748b !important;stroke:currentColor !important;fill:none !important}
  .crm-row-action.btn-edit,.crm-row-action.btn-edit svg{color:#1d4ed8 !important;stroke:#1d4ed8 !important}
  .crm-row-action.btn-delete,.crm-row-action.btn-delete svg{color:#b91c1c !important;stroke:#b91c1c !important}
  .deals-table tbody tr:hover .crm-row-action.btn-edit,.deals-table tbody tr:hover .crm-row-action.btn-edit svg{color:#2563eb;stroke:#2563eb !important}
  .deals-table tbody tr:hover .crm-row-action.btn-delete,.deals-table tbody tr:hover .crm-row-action.btn-delete svg{color:#dc2626;stroke:#dc2626 !important}

  /* Empty state */
  .empty-state{padding:54px 24px;text-align:center}
  .empty-state-panel{max-width:620px;margin:0 auto;padding:28px 26px;border:1px solid #fde7c7;border-radius:16px;background:linear-gradient(180deg,#fffdf8 0%,#fff7ed 100%);box-shadow:0 12px 30px rgba(124,45,18,.08);position:relative;overflow:hidden}
  .empty-state-panel:before{content:"";position:absolute;inset:auto -70px -90px auto;width:220px;height:220px;background:radial-gradient(circle,#fed7aa 0%,rgba(254,215,170,0) 72%);pointer-events:none}
  .empty-state-icon{width:74px;height:74px;background:linear-gradient(145deg,#fff7ed 0%,#ffedd5 100%);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;border:1px solid #fdba74;box-shadow:0 8px 18px rgba(234,88,12,.14)}
  .empty-state-icon i{width:34px;height:34px;color:#ea580c}
  .empty-state-title{font-size:24px;line-height:1.15;font-weight:800;color:#0f172a;margin-bottom:8px;letter-spacing:-.01em}
  .empty-state-sub{font-size:14px;color:#64748b;margin:0 auto 16px;max-width:480px}
  .empty-state-tips{display:flex;justify-content:center;margin:0;padding:0;list-style:none}
  .empty-state-tips li{display:block}
  .empty-state-tips a{display:inline-flex;align-items:center;justify-content:center;gap:7px;height:42px;padding:0 16px;border:1px solid #fed7aa;border-radius: 16px;background:#fff7ed;font-size:13px;font-weight:700;color:#9a3412;text-decoration:none;transition:all .15s}
  .empty-state-tips a:hover{background:#ffedd5;border-color:#fdba74;color:#c2410c;transform:translateY(-1px)}

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
  /* ── ClickUp-inspired UX additions ── */
  .deals-table thead tr { position:sticky;top:0;z-index:10;box-shadow:0 1px 0 #e2e8f0 }
  .th-sort{cursor:pointer;user-select:none;white-space:nowrap}.th-sort:hover{color:#3b82f6;background:rgba(59,130,246,.04)}.th-sorted{color:#2563eb !important}
  .sort-ic{width:9px;height:12px;margin-left:4px;vertical-align:-1px;color:#cbd5e1;transition:color .12s}.th-sort:hover .sort-ic,.th-sorted .sort-ic,.sort-ic.asc,.sort-ic.desc{color:#3b82f6}
  .th-sort a,.th-sort a:visited{color:inherit;text-decoration:none;display:flex;align-items:center;gap:0}
  .col-chk{width:40px}.th-chk,.td-chk{padding:10px 4px 10px 14px !important;box-sizing:border-box}
  .row-chk,.chk-all{width:15px;height:15px;border-radius: 16px;cursor:pointer;accent-color:#3b82f6;flex-shrink:0}
  .cell-actions .crm-row-action{opacity:1;pointer-events:auto;transform:none;transition:opacity .12s,transform .12s}
  .deals-table tbody tr:hover .cell-actions .crm-row-action{opacity:1;pointer-events:auto;transform:translateX(0)}
  .deals-table tbody tr:hover .cell-actions .crm-row-action.btn-edit{color:#2563eb}
  .deals-table tbody tr:hover .cell-actions .crm-row-action.btn-delete{color:#dc2626}
  #bulk-bar{position:fixed;bottom:32px;left:50%;transform:translateX(-50%) translateY(16px);background:#1e293b;color:#fff;border-radius: 16px;padding:10px 18px;display:flex;align-items:center;gap:10px;box-shadow:0 8px 32px rgba(0,0,0,.3);z-index:9000;font-size:13px;opacity:0;pointer-events:none;transition:opacity .2s,transform .2s;white-space:nowrap}
  #bulk-bar.show{opacity:1;pointer-events:auto;transform:translateX(-50%) translateY(0)}
  .bk-ct{font-weight:700;color:#93c5fd;min-width:70px}.bk-sep{width:1px;height:20px;background:rgba(255,255,255,.15);flex-shrink:0}
  .btn-bulk{display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:7px;border:none;background:transparent;color:#e2e8f0;font-size:12px;font-weight:500;cursor:pointer;transition:background .12s;white-space:nowrap}.btn-bulk:hover{background:rgba(255,255,255,.12)}.btn-bulk svg{width:13px;height:13px;color:inherit;flex-shrink:0}
  .filter-chips{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:14px}
  .chips-lbl{font-size:12px;color:#94a3b8;font-weight:500;white-space:nowrap}
  .filter-chip{display:inline-flex;align-items:center;gap:3px;padding:3px 4px 3px 10px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:20px;font-size:12px;font-weight:500;line-height:1}
  .chip-x{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;text-decoration:none;color:#1d4ed8;font-size:15px;line-height:1;transition:background .12s}.chip-x:hover{background:#bfdbfe}
  .dn-wrap{display:flex;gap:2px;margin-right:4px}.dn-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius: 16px;border:1.5px solid #e2e8f0;background:#fff;cursor:pointer;transition:all .15s;padding:0;color:#94a3b8}.dn-btn:hover,.dn-btn.on{border-color:#2563eb;color:#2563eb;background:#eff6ff}.dn-btn svg{pointer-events:none;width:12px;height:12px}
  .is-compact .deals-table td,.is-compact .deals-table th{padding-top:5px !important;padding-bottom:5px !important}
  .is-roomy .deals-table td,.is-roomy .deals-table th{padding-top:14px !important;padding-bottom:14px !important}
  .pg-sz{display:flex;align-items:center;gap:6px;font-size:12px;color:#64748b}.pg-sz select{height:28px;padding:0 6px;border:1px solid #e2e8f0;border-radius: 16px;font-size:12px;color:#374151;background:#fff;cursor:pointer;outline:none}.pg-sz select:focus{border-color:#3b82f6}
</style>
HTML;

    // ── Stats bar ─────────────────────────────────────────────────────────────
    $html .= <<<HTML
<div class="deals-page" id="pg-wrap">

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
      <div class="dn-wrap">
        <button class="dn-btn" data-dn="compact" title="Compact rows"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="1" y1="3" x2="13" y2="3"/><line x1="1" y1="6" x2="13" y2="6"/><line x1="1" y1="9" x2="13" y2="9"/><line x1="1" y1="12" x2="13" y2="12"/></svg></button>
        <button class="dn-btn on" data-dn="default" title="Default rows"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="1" y1="2.5" x2="13" y2="2.5"/><line x1="1" y1="7" x2="13" y2="7"/><line x1="1" y1="11.5" x2="13" y2="11.5"/></svg></button>
        <button class="dn-btn" data-dn="roomy" title="Roomy rows"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="1" y1="2" x2="13" y2="2"/><line x1="1" y1="8" x2="13" y2="8"/></svg></button>
      </div>
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
      . '<input id="crm-search-input" class="filter-input" type="text" name="search" placeholder="Search by deal name…" value="' . $e_search . '"></div>';

    // Stage dropdown
    $html .= '<div class="filter-select-wrap"><span class="flt-sel-ico"><i data-lucide="layers"></i></span>'
      . '<select class="filter-select" name="stage">'
      . '<option value="0"' . ($filter_stage === 0 ? ' selected' : '') . '>All stages</option>';
    foreach ($stage_map as $tid => $s) {
      $sel = $filter_stage === $tid ? ' selected' : '';
      $html .= '<option value="' . $tid . '"' . $sel . '>' . $s['label'] . '</option>';
    }
    $html .= '</select><span class="flt-sel-arr"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 4 7 9 12 4"/></svg></span></div>';

    $html .= '<button type="submit" class="btn-filter-apply"><i data-lucide="filter"></i>Apply</button>';

    if ($search_name || $filter_stage > 0) {
      $html .= '<a href="' . $deals_url . '" class="btn-filter-clear"><i data-lucide="x"></i> Clear</a>';
    }

    $from_label = $filtered_total === 0 ? 0 : $page * $per_page + 1;
    $to_label   = min(($page + 1) * $per_page, $filtered_total);
    $html .= '<span class="filter-count">Showing ' . $from_label . '–' . $to_label . ' of ' . $filtered_total . '</span>';
    $html .= '</form>';
    $html .= '<div id="crm-results-wrap">';
    $html .= $chips_html;
    // ── Table ─────────────────────────────────────────────────────────────────
    $html .= <<<HTML
  <div class="table-card">
    <table class="deals-table">
      <colgroup>
        <col class="col-chk">
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
          <th class="th-chk"><input type="checkbox" class="chk-all" id="chk-all"></th>
          <th{$tc_nm}><a href="{$su_nm}">Deal{$si_nm}</a></th>
          <th>Stage</th>
          <th>Amount</th>
          <th>Contact</th>
          <th class="th-org">Organization</th>
          <th class="th-prob">Prob.</th>
          <th class="th-close">Close Date</th>
          <th class="th-owner">Owner</th>
          <th{$tc_up}><a href="{$su_up}">Updated{$si_up}</a></th>
          <th class="th-action">Actions</th>
        </tr>
      </thead>
      <tbody>
HTML;

    if (empty($rows)) {
      $html .= <<<EMPTY
      <tr><td colspan="11">
        <div class="empty-state">
          <div class="empty-state-panel">
            <div class="empty-state-icon"><i data-lucide="search-x"></i></div>
            <div class="empty-state-title">No deals found</div>
            <div class="empty-state-sub">No deals match your current filters right now. You can quickly create a new deal and keep your pipeline moving.</div>
            <ul class="empty-state-tips">
              <li><a href="{$add_url}"><i data-lucide="plus-circle"></i> Create new deal</a></li>
            </ul>
          </div>
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
          $action_btns .= '<button class="crm-row-action btn-edit" title="Edit" onclick="CRMInlineEdit.openModal(' . $r['id'] . ',\'deal\')">'
            . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg></button>';
        }
        if ($r['can_delete']) {
          $action_btns .= '<button class="crm-row-action btn-delete" title="Delete" onclick="CRMInlineEdit.confirmDelete(' . $r['id'] . ',\'deal\',\'' . $r['title_js'] . '\')">'
            . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg></button>';
        }

        $html .= '<tr id="deal-row-' . $r['id'] . '">'
          . '<td class="td-chk"><input type="checkbox" class="row-chk" value="' . $r['id'] . '"></td>'
          . '<td><div class="td-name">'
          . '<div class="deal-avatar" style="' . $r['av_style'] . '">' . $r['initial'] . '</div>'
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
    $html .= '<div class="pagination"><div class="pg-sz"><label for="pg-sz-sel">Rows:</label><select id="pg-sz-sel">' . $per_page_sel . '</select></div>';
    if ($total_pages > 1) {
      $from_count = $filtered_total === 0 ? 0 : $page * $per_page + 1;
      $to_count   = min(($page + 1) * $per_page, $filtered_total);
      $html .= '<span class="page-info">Page ' . ($page + 1) . ' of ' . $total_pages
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

      $html .= '</div>'; // .page-links
    }
    $html .= '</div>'; // .pagination
    $html .= '</div>'; // .table-card
    $html .= '</div>'; // #crm-results-wrap
    $html .= '<div id="bulk-bar"><span id="bk-ct" class="bk-ct">0 selected</span><span class="bk-sep"></span>'
      . '<button class="btn-bulk" id="bulk-edit-btn" title="Edit selected">Edit selected</button>'
      . '<button class="btn-bulk" id="bulk-delete-btn" title="Delete selected">Delete selected</button>'
      . '<button class="btn-bulk" id="bulk-clear-btn" title="Clear selection">'
      . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
      . ' Clear selection</button></div>';
    $html .= '</div>'; // .deals-page

    $html .= <<<JS
<script>
  function ensureLucideReady(callback) {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      callback();
      return;
    }
    var existing = document.querySelector('script[data-lucide-fallback="1"]');
    if (existing) {
      existing.addEventListener('load', function () {
        if (window.lucide && typeof window.lucide.createIcons === 'function') callback();
      }, { once: true });
      return;
    }
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js';
    script.defer = true;
    script.setAttribute('data-lucide-fallback', '1');
    script.onload = function () {
      if (window.lucide && typeof window.lucide.createIcons === 'function') callback();
    };
    document.head.appendChild(script);
  }

  document.addEventListener('DOMContentLoaded', function () {
    ensureLucideReady(function () { window.lucide.createIcons(); });
    if (window.CRM) {
      CRM.initRealtimeSearch('crm-search-input');
      CRM.initKeyboardShortcuts({ addUrl: '{$add_url}', searchId: 'crm-search-input' });
      CRM.initBulkActions({ entityType: 'deal' });
    }
  });
  document.addEventListener('crm:icons-refresh', function () {
    ensureLucideReady(function () { window.lucide.createIcons(); });
  });
  // Density toggle
  (function() {
    var pgWrap  = document.getElementById('pg-wrap');
    if (!pgWrap) return;
    var savedDn = localStorage.getItem('crm_dn') || 'default';
    if (savedDn !== 'default') pgWrap.classList.add('is-' + savedDn);
    document.querySelectorAll('.dn-btn').forEach(function(btn) {
      if (btn.dataset.dn === savedDn) { btn.classList.add('on'); } else { btn.classList.remove('on'); }
      btn.addEventListener('click', function() {
        ['compact','roomy'].forEach(function(k) { pgWrap.classList.remove('is-' + k); });
        if (btn.dataset.dn !== 'default') pgWrap.classList.add('is-' + btn.dataset.dn);
        document.querySelectorAll('.dn-btn').forEach(function(b) { b.classList.toggle('on', b === btn); });
        localStorage.setItem('crm_dn', btn.dataset.dn);
      });
    });
  })();
  // Page size — handled by CRM.initRealtimeSearch event delegation
</script>
JS;

    return [
      '#markup' => Markup::create($html),
      '#attached' => [
        'library' => [
          'core/drupal',          'crm/crm_shared',          'crm_edit/inline_edit',
          'crm_ai_autocomplete/ai-generate-button',
        ],
      ],
      '#cache' => [
        'contexts' => ['user', 'url.query_args'],
        'tags'     => ['node_list:deal'],
        'max-age'  => 60,
      ],
    ];
  }

}
