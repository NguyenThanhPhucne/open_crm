<?php

namespace Drupal\crm_kanban\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Cache\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for CRM Kanban Pipeline.
 */
class KanbanController extends ControllerBase {

  /**
   * Display the Kanban board.
   */
  public function view() {
    // Get current user
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    
    // Check if user is administrator
    $is_admin = in_array('administrator', $current_user->getRoles()) || $user_id == 1;
    
    // Load pipeline stages dynamically from taxonomy, sorted by weight.
    $stage_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('pipeline_stage', 0, NULL, TRUE);

    $stages = [];
    // Dynamic color palette (cycles through colors based on stage order).
    $color_palette = [
      '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#10b981', '#ef4444',
      '#06b6d4', '#84cc16', '#f97316', '#a855f7', '#14b8a6', '#f43f5e',
    ];
    $color_index = 0;

    foreach ($stage_terms as $term) {
      $stage_id = $term->id();
      $stage_name = $term->getName();
      $stages[$stage_id] = [
        'name' => $stage_name,
        'color' => $color_palette[$color_index % count($color_palette)],
      ];
      $color_index++;
    }

    // Get deals grouped by stage (filtered by current user for non-admins)
    $deals_by_stage = [];
    $totals_by_stage = [];
    
    foreach ($stages as $stage_id => $stage_info) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('field_stage', $stage_id)
        ->accessCheck(FALSE)
        ->sort('created', 'DESC')
        ->range(0, 200);
      
      // Only filter by owner for non-admin, authenticated users.
      if (!$is_admin && !$current_user->isAnonymous()) {
        $query->condition('field_owner', $user_id);
      }
      
      $nids = $query->execute();
      $deals = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
      
      $deals_by_stage[$stage_id] = [];
      $totals_by_stage[$stage_id] = 0;
      
      foreach ($deals as $deal) {
        $value = $deal->get('field_amount')->value ?? 0;
        $totals_by_stage[$stage_id] += $value;
        
        // Get organization name
        $org_name = '';
        if ($deal->hasField('field_organization') && !$deal->get('field_organization')->isEmpty()) {
          $org = $deal->get('field_organization')->entity;
          if ($org) {
            $org_name = $org->getTitle();
          }
        }
        
        // Get owner name
        $owner_name = '';
        if ($deal->hasField('field_owner') && !$deal->get('field_owner')->isEmpty()) {
          $owner = $deal->get('field_owner')->entity;
          if ($owner) {
            $owner_name = $owner->getDisplayName();
          }
        }
        
        // Compute owner initials
        $owner_initials = '';
        if ($owner_name) {
          $parts = preg_split('/\s+/', trim($owner_name));
          $owner_initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
          if (count($parts) > 1) {
            $owner_initials .= mb_strtoupper(mb_substr(end($parts), 0, 1));
          }
        }

        // Get closing date if available
        $closing_date = '';
        if ($deal->hasField('field_closing_date') && !$deal->get('field_closing_date')->isEmpty()) {
          $closing_date = $deal->get('field_closing_date')->value;
        }

        $deals_by_stage[$stage_id][] = [
          'nid'            => $deal->id(),
          'title'          => $deal->getTitle(),
          'value'          => $value,
          'organization'   => $org_name,
          'owner'          => $owner_name,
          'owner_initials' => $owner_initials,
          'created'        => $deal->getCreatedTime(),
          'changed'        => $deal->getChangedTime(),
          'closing_date'   => $closing_date,
        ];
      }
    }

    // Compute summary stats from loaded data
    $total_count = 0;
    $pipeline_value = 0;
    $won_count = 0;
    foreach ($stages as $sid => $sinfo) {
      $cnt = count($deals_by_stage[$sid]);
      $total_count += $cnt;
      $sname_lower = strtolower($sinfo['name']);
      if (str_contains($sname_lower, 'won')) {
        $won_count += $cnt;
      } elseif (!str_contains($sname_lower, 'lost') && !str_contains($sname_lower, 'closed')) {
        $pipeline_value += $totals_by_stage[$sid];
      }
    }
    $total_value_all = array_sum($totals_by_stage);

    // Format values
    $fmt_pipeline = '$' . ($pipeline_value >= 1000000
      ? number_format($pipeline_value / 1000000, 1) . 'M'
      : ($pipeline_value >= 1000 ? number_format($pipeline_value / 1000, 0) . 'K' : number_format($pipeline_value, 0)));

    // Determine page context
    $current_path = \Drupal::service('path.current')->getPath();
    $is_all_pipeline = str_contains($current_path, 'all-pipeline');
    $page_title = $is_all_pipeline ? 'All Pipeline' : 'My Pipeline';
    $list_url   = $is_all_pipeline ? '/crm/all-deals' : '/crm/my-deals';

    $stats = [
      'total_count'  => $total_count,
      'fmt_pipeline' => $fmt_pipeline,
      'won_count'    => $won_count,
      'page_title'   => $page_title,
      'list_url'     => $list_url,
      'is_admin'     => $is_admin,
    ];

    // Build Kanban HTML
    $html = $this->buildKanbanHtml($stages, $deals_by_stage, $totals_by_stage, $stats);
    
    return [
      '#markup' => Markup::create($html),
      '#attached' => [
        'library' => [
          'core/drupal',
          'crm/crm_shared',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags'     => ['node_list:deal'],
        'max-age'  => 300,
      ],
    ];
  }

  /**
   * Build Kanban HTML.
   */
  private function buildKanbanHtml($stages, $deals_by_stage, $totals_by_stage, array $stats = []) {
    $page_title   = $stats['page_title']   ?? 'Sales Pipeline';
    $list_url     = $stats['list_url']     ?? '/crm/all-deals';
    $total_count  = $stats['total_count']  ?? 0;
    $fmt_pipeline = $stats['fmt_pipeline'] ?? '$0';
    $won_count    = $stats['won_count']    ?? 0;

    $html = <<<'HTML'
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f8fafc;color:#1e293b}
  /* ── Page wrapper ── */
  .pipeline-page{padding:0;animation:fadeIn .3s ease}
  @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
  /* ── Stats bar ── */
  .stats-bar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap}
  .stat-chip{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;border:1px solid}
  .stat-chip.blue{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
  .stat-chip.green{background:#ecfdf5;color:#15803d;border-color:#bbf7d0}
  .stat-chip.amber{background:#fffbeb;color:#b45309;border-color:#fde68a}
  .stat-chip i{width:14px;height:14px;flex-shrink:0}
  /* ── Page header ── */
  .page-header{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:16px;box-shadow:0 1px 3px rgba(0,0,0,.05);flex-wrap:wrap}
  .page-header-left{display:flex;flex-direction:column;gap:4px}
  .page-title{font-size:20px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:9px;letter-spacing:-.02em}
  .page-title i{color:#3b82f6;width:22px;height:22px}
  .page-subtitle{font-size:12px;color:#64748b}
  .page-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto}
  .btn-primary,.btn-secondary{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s;white-space:nowrap;border:1.5px solid}
  .btn-primary{color:#2563eb;border-color:#2563eb;background:#fff}
  .btn-primary:hover{background:#eff6ff;border-color:#1d4ed8;color:#1d4ed8}
  .btn-secondary{color:#475569;border-color:#e2e8f0;background:#fff}
  .btn-secondary:hover{background:#f8fafc;border-color:#cbd5e1;color:#1e293b}
  .btn-primary i,.btn-secondary i{width:15px;height:15px;color:inherit}
  /* ── Kanban layout ── */
  .kanban-container{overflow-x:auto;overflow-y:hidden;width:100%;display:flex;padding-bottom:8px}
  .kanban-container::-webkit-scrollbar{height:6px}
  .kanban-container::-webkit-scrollbar-track{background:#f1f5f9;border-radius:4px}
  .kanban-container::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}
  .kanban-container::-webkit-scrollbar-thumb:hover{background:#94a3b8}
  .kanban-board{display:flex;flex-wrap:nowrap;gap:10px;padding-bottom:4px;width:100%;align-items:stretch}
  .kanban-column{background:#f1f5f9;border-radius:12px;display:flex;flex-direction:column;min-height:300px;height:calc(100vh - 310px);overflow:hidden;flex:1 1 0;min-width:180px}
  /* ── Column header ── */
  .column-header{padding:10px 10px 8px;border-bottom:2px solid;background:#fff;border-radius:12px 12px 0 0}
  .column-title{display:flex;align-items:center;gap:6px;margin-bottom:4px}
  .col-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
  .column-title h3{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#374151;flex:1;word-break:break-word}
  .column-count{background:#e2e8f0;color:#64748b;padding:1px 6px;border-radius:10px;font-size:10px;font-weight:700;white-space:nowrap;flex-shrink:0}
  .column-total{font-size:13px;font-weight:700;margin-top:0;padding-left:14px}
  /* ── Column cards ── */
  .column-cards{padding:6px;flex:1;overflow-y:auto;overflow-x:hidden;min-height:60px}
  .column-cards::-webkit-scrollbar{width:3px}
  .column-cards::-webkit-scrollbar-track{background:transparent}
  .column-cards::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:2px}
  /* ── Deal card ── */
  .deal-card{position:relative;background:#fff;border-radius:10px;padding:10px 12px 8px;margin-bottom:7px;cursor:grab;border-left:3px solid;box-shadow:0 1px 4px rgba(0,0,0,.07);transition:box-shadow .15s,transform .15s;width:100%;overflow:hidden}
  .deal-card:hover{box-shadow:0 5px 15px rgba(0,0,0,.11);transform:translateY(-2px)}
  .deal-card.sortable-ghost{opacity:.4;background:#e2e8f0}
  .deal-card.sortable-drag{opacity:.85;transform:rotate(1.5deg);cursor:grabbing}
  .deal-title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:6px;line-height:1.35;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;padding-right:36px}
  .card-value-row{display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:8px}
  .deal-value{font-size:15px;font-weight:800}
  .card-footer{display:flex;align-items:center;justify-content:space-between;gap:4px;border-top:1px solid #f1f5f9;padding-top:6px;margin-top:2px}
  .card-footer-left{display:flex;align-items:center;gap:4px;font-size:10.5px;color:#64748b;overflow:hidden;min-width:0}
  .card-footer-left i,.card-footer-left svg{width:10px;height:10px;flex-shrink:0;stroke-width:2;color:#94a3b8}
  .card-footer-left span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .card-footer-right{display:flex;align-items:center;gap:5px;flex-shrink:0}
  .owner-av{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#eff6ff;color:#2563eb;font-size:8px;font-weight:700;flex-shrink:0;border:1.5px solid #bfdbfe}
  /* ── Hover-reveal card actions ── */
  .card-actions{position:absolute;top:5px;right:5px;display:flex;gap:2px;opacity:0;transition:opacity .15s}
  .deal-card:hover .card-actions{opacity:1}
  .ca-btn{display:flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:5px;color:#94a3b8;text-decoration:none;transition:background .12s,color .12s;background:rgba(255,255,255,.9)}
  .ca-btn:hover{background:#eff6ff;color:#2563eb}
  .ca-btn i{width:12px;height:12px;flex-shrink:0}
  /* ── Empty state ── */
  .empty-state{text-align:center;padding:28px 12px;color:#cbd5e1;font-size:12px}
  .empty-state i{display:block;margin:0 auto 6px;opacity:.5}
  /* ── Modal ── */
  .deal-modal-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:2000;pointer-events:none}
  .deal-modal-overlay.active{display:flex;align-items:center;justify-content:center;pointer-events:auto}
  .deal-modal{background:#fff;border-radius:16px;max-width:500px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);animation:slideUp .3s ease}
  @keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
  .modal-header{padding:20px 24px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:12px}
  .modal-header h2{font-size:18px;font-weight:700;color:#1e293b;flex:1;margin:0}
  .modal-icon{width:36px;height:36px;background:linear-gradient(135deg,#10b981,#059669);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff}
  .modal-icon i{width:18px;height:18px}
  .modal-body{padding:20px 24px}
  .info-box{background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;color:#92400e;display:flex;gap:10px}
  .info-box i{flex-shrink:0;margin-top:2px;width:16px;height:16px}
  .form-group{margin-bottom:16px}
  .form-label{display:flex;align-items:center;gap:6px;font-weight:600;color:#1e293b;margin-bottom:6px;font-size:13px}
  .form-label .required{color:#ef4444}
  .form-input{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;transition:border-color .2s,box-shadow .2s;outline:none}
  .form-input:focus{border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.1)}
  .file-upload-zone{border:2px dashed #cbd5e1;border-radius:8px;padding:20px;text-align:center;cursor:pointer;transition:all .2s}
  .file-upload-zone:hover,.file-upload-zone.has-file{border-color:#10b981;background:#f0fdf4}
  .file-icon{width:44px;height:44px;background:#e2e8f0;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;color:#64748b}
  .file-upload-zone.has-file .file-icon{background:#d1fae5;color:#10b981}
  .file-icon i{width:22px;height:22px}
  .file-instructions{font-size:13px;color:#64748b;margin-bottom:4px}
  .file-name{font-size:13px;color:#10b981;font-weight:600;margin-top:6px}
  .file-hint{font-size:12px;color:#94a3b8}
  .modal-actions{padding:16px 24px;border-top:1px solid #e2e8f0;display:flex;gap:10px;justify-content:flex-end}
  .modal-btn{padding:9px 18px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s,border-color .15s,color .15s;border:1.5px solid;display:inline-flex;align-items:center;gap:7px}
  .modal-btn i{width:14px;height:14px}
  .modal-btn-cancel{background:#fff;color:#64748b;border-color:#cbd5e1}
  .modal-btn-cancel:hover{background:#f8fafc;border-color:#94a3b8}
  .modal-btn-confirm{background:#fff;color:#2563eb;border-color:#2563eb}
  .modal-btn-confirm:hover{background:#eff6ff;color:#1d4ed8;border-color:#1d4ed8}
  .modal-btn-confirm:disabled{opacity:.5;cursor:not-allowed}
  .modal-btn-confirm.loading{position:relative;color:transparent}
  .modal-btn-confirm.loading::after{content:'';position:absolute;width:14px;height:14px;top:50%;left:50%;margin:-7px 0 0 -7px;border:2px solid #2563eb;border-radius:50%;border-top-color:transparent;animation:spin .6s linear infinite}
  @keyframes spin{to{transform:rotate(360deg)}}
  .error-message{background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:13px;margin-top:12px;display:none;align-items:center;gap:8px}
  .error-message.show{display:flex}
  .error-message i{width:14px;height:14px;flex-shrink:0}
  /* ── Time ago ── */
  .time-ago{font-size:9.5px;color:#94a3b8;line-height:1;white-space:nowrap}
  /* ── Due badge ── */
  .due-badge{display:inline-flex;align-items:center;gap:3px;font-size:9.5px;font-weight:700;padding:2px 7px;border-radius:10px;letter-spacing:.02em;white-space:nowrap;flex-shrink:0}
  .due-badge i{width:9px;height:9px;flex-shrink:0}
  .due-badge.urgent{background:#fef2f2;color:#dc2626}
  .due-badge.soon{background:#fffbeb;color:#d97706}
  /* ── Board filter bar ── */
  .kanban-filter{display:flex;align-items:center;gap:10px;margin-bottom:10px}
  .kf-wrap{position:relative;display:inline-flex;align-items:center}
  .kf-wrap i{position:absolute;left:9px;width:14px;height:14px;color:#94a3b8;pointer-events:none;flex-shrink:0}
  .kf-wrap input{padding:7px 12px 7px 30px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;outline:none;background:#fff;width:210px;transition:border-color .15s,width .2s}
  .kf-wrap input:focus{border-color:#3b82f6;width:260px}
  .filter-hint{font-size:12px;color:#94a3b8}
  .card-hidden{display:none!important}
</style>
  
  <!-- Deal Closing Modal -->
  <div class="deal-modal-overlay" id="dealClosingModal">
    <div class="deal-modal">
      <div class="modal-header">
        <div class="modal-icon">
          <i data-lucide="trophy" width="24" height="24"></i>
        </div>
        <h2>Close Deal</h2>
      </div>
      <div class="modal-body">
        <div class="info-box">
          <i data-lucide="info" width="18" height="18"></i>
          <div>Please fill in all required information to close the deal. A notification email will be sent to the manager.</div>
        </div>
        
        <form id="dealClosingForm">
          <input type="hidden" name="deal_id" id="modalDealId">
          <input type="hidden" name="stage_id" value="closed_won">
          
          <div class="form-group">
            <label class="form-label">
              <i data-lucide="calendar-check" width="16" height="16" style="vertical-align: middle;"></i>
              Close Date <span class="required">*</span>
            </label>
            <input type="date" name="closing_date" class="form-input" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">
              <i data-lucide="file-text" width="16" height="16" style="vertical-align: middle;"></i>
              Attached Contract <span style="color: #94a3b8; font-size: 13px;">(optional)</span>
            </label>
            <div class="file-upload-zone" id="fileUploadZone" onclick="document.getElementById('contractFile').click()">
              <div class="file-icon">
                <i data-lucide="upload" width="24" height="24"></i>
              </div>
              <div class="file-instructions">Click to select contract file (optional)</div>
              <div class="file-hint">PDF, DOC, DOCX (max 10MB)</div>
              <div class="file-name" id="fileName" style="display: none;"></div>
            </div>
            <input type="file" id="contractFile" name="contract" accept=".pdf,.doc,.docx" style="display: none;">
          </div>
          
          <div class="error-message" id="errorMessage">
            <i data-lucide="alert-circle" width="16" height="16"></i>
            <span id="errorText"></span>
          </div>
        </form>
      </div>
      <div class="modal-actions">
        <button type="button" class="modal-btn modal-btn-cancel" onclick="closeDealModal()">
          <i data-lucide="x"></i>
          Cancel
        </button>
        <button type="button" class="modal-btn modal-btn-confirm" onclick="submitDealClosing()">
          <i data-lucide="check"></i>
          Confirm Close Deal
        </button>
      </div>
    </div>
  </div>

HTML;

    $html .= <<<HTML
<div class="pipeline-page" id="pg-wrap">
  <div class="stats-bar">
    <span class="stat-chip blue"><i data-lucide="kanban-square"></i>{$total_count} total deals</span>
    <span class="stat-chip amber"><i data-lucide="trending-up"></i>{$fmt_pipeline} in pipeline</span>
    <span class="stat-chip green"><i data-lucide="trophy"></i>{$won_count} won</span>
  </div>

  <div class="page-header">
    <div class="page-header-left">
      <div class="page-title"><i data-lucide="kanban-square" width="22" height="22"></i>{$page_title}</div>
      <div class="page-subtitle">Drag cards between columns to update deal stages</div>
    </div>
    <div class="page-actions">
      <a href="{$list_url}" class="btn-secondary"><i data-lucide="list"></i> List view</a>
      <a href="/crm/add/deal" class="btn-primary"><i data-lucide="plus-circle"></i> Add Deal</a>
    </div>
  </div>

  <div class="kanban-filter">
    <div class="kf-wrap">
      <i data-lucide="search"></i>
      <input type="text" id="kanban-search" placeholder="Filter deals…" autocomplete="off">
    </div>
    <span id="filter-count" class="filter-hint"></span>
  </div>

  <div class="kanban-container">
    <div class="kanban-board">
HTML;

    // Build columns
    foreach ($stages as $stage_id => $stage_info) {
      $deals = $deals_by_stage[$stage_id] ?? [];
      $total = $totals_by_stage[$stage_id] ?? 0;
      $count = count($deals);
      $total_formatted = $total >= 1000000
        ? '$' . number_format($total / 1000000, 1) . 'M'
        : ($total >= 1000 ? '$' . number_format($total / 1000, 0) . 'K' : '$' . number_format($total, 0));
      
      $html .= <<<HTML
      
      <div class="kanban-column">
        <div class="column-header" style="border-color:{$stage_info['color']}">
          <div class="column-title">
            <span class="col-dot" style="background:{$stage_info['color']}"></span>
            <h3>{$stage_info['name']}</h3>
            <span class="column-count">{$count}</span>
          </div>
          <div class="column-total" style="color:{$stage_info['color']}">{$total_formatted}</div>
        </div>
        <div class="column-cards" data-stage="{$stage_id}">
HTML;

      if (empty($deals)) {
        $html .= <<<HTML
          <div class="empty-state">
            <i data-lucide="inbox" width="32" height="32"></i>
            <div>No deals</div>
          </div>
HTML;
      } else {
        foreach ($deals as $deal) {
          $val = (float)$deal['value'];
          $value_formatted = $val >= 1000000
            ? '$' . number_format($val / 1000000, 1) . 'M'
            : ($val >= 1000 ? '$' . number_format($val / 1000, 0) . 'K' : '$' . number_format($val, 0));
          $org_display   = $deal['organization'] ?: 'No organization';
          $owner_display = $deal['owner'] ?: 'Unassigned';
          $owner_av      = $deal['owner_initials'] ?: '?';
          $card_color    = $stage_info['color'];
          $created_ts    = $deal['created'] ?? 0;

          // Compute close-date badge
          $due_badge_html = '';
          if (!empty($deal['closing_date'])) {
            $close_ts  = strtotime($deal['closing_date']);
            $days_left = (int)(($close_ts - time()) / 86400);
            if ($days_left < 0) {
              $abs = abs($days_left);
              $due_badge_html = '<span class="due-badge urgent"><i data-lucide="alert-circle"></i>' . $abs . 'd overdue</span>';
            } elseif ($days_left <= 3) {
              $due_badge_html = '<span class="due-badge urgent"><i data-lucide="clock"></i>' . $days_left . 'd left</span>';
            } elseif ($days_left <= 14) {
              $due_badge_html = '<span class="due-badge soon"><i data-lucide="clock"></i>' . $days_left . 'd left</span>';
            }
          }

          $html .= <<<HTML
          <div class="deal-card" style="border-left-color:{$card_color}" data-deal-id="{$deal['nid']}">
            <div class="card-actions">
              <a href="/node/{$deal['nid']}" class="ca-btn" title="View"><i data-lucide="eye"></i></a>
              <a href="/node/{$deal['nid']}/edit" class="ca-btn" title="Edit"><i data-lucide="pencil"></i></a>
            </div>
            <div class="deal-title">{$deal['title']}</div>
            <div class="card-value-row">
              <span class="deal-value" style="color:{$card_color}">{$value_formatted}</span>
              {$due_badge_html}
            </div>
            <div class="card-footer">
              <div class="card-footer-left">
                <i data-lucide="building-2"></i>
                <span title="{$org_display}">{$org_display}</span>
              </div>
              <div class="card-footer-right">
                <span class="owner-av" title="{$owner_display}">{$owner_av}</span>
                <span class="time-ago"><span data-timestamp="{$created_ts}"></span></span>
              </div>
            </div>
          </div>
HTML;
        }
      }

      $html .= <<<HTML
        </div>
      </div>
HTML;
    }

    $html .= <<<'HTML'
    </div>
  </div>
</div>

  <script>
    // Initialize Lucide icons
    lucide.createIcons();
    if (window.CRM) {
      CRM.initKeyboardShortcuts({ addUrl: '/crm/add/deal', searchId: null });
      CRM.renderShortcutHints([{ key: 'N', label: 'New deal' }]);
    }

    // ── Time-ago relative timestamps ──────────────────────────────────
    function timeAgo(ts) {
      const s = Math.floor(Date.now() / 1000 - ts);
      if (s < 60)      return 'just now';
      if (s < 3600)    return Math.floor(s / 60) + 'm ago';
      if (s < 86400)   return Math.floor(s / 3600) + 'h ago';
      if (s < 604800)  return Math.floor(s / 86400) + 'd ago';
      if (s < 2592000) return Math.floor(s / 604800) + 'w ago';
      return Math.floor(s / 2592000) + 'mo ago';
    }
    function renderTimeAgo() {
      document.querySelectorAll('[data-timestamp]').forEach(el => {
        const ts = parseInt(el.dataset.timestamp, 10);
        if (ts) el.textContent = timeAgo(ts);
      });
    }
    renderTimeAgo();
    setInterval(renderTimeAgo, 60000);

    // ── Live board filter ─────────────────────────────────────────────
    const _ks = document.getElementById('kanban-search');
    const _kc = document.getElementById('filter-count');
    if (_ks) {
      _ks.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        let vis = 0, tot = 0;
        document.querySelectorAll('.deal-card').forEach(c => {
          tot++;
          const match = !q || c.textContent.toLowerCase().includes(q);
          c.classList.toggle('card-hidden', !match);
          if (match) vis++;
        });
        _kc.textContent = q ? vis + ' of ' + tot + ' deals' : '';
        document.querySelectorAll('.kanban-column').forEach(col => {
          const cnt = col.querySelectorAll('.deal-card:not(.card-hidden)').length;
          const badge = col.querySelector('.column-count');
          if (badge) badge.textContent = cnt;
        });
      });
    }

    // Variables for reverting card movement
    let pendingMove = null;

    // CSRF token helper for authenticated AJAX calls.
    async function getCsrfToken() {
      const r = await fetch('/session/token');
      return await r.text();
    }
    
    // Deal Closing Modal Functions
    function showDealModal(dealId) {
      document.getElementById('modalDealId').value = dealId;
      document.getElementById('dealClosingModal').classList.add('active');
      document.querySelector('input[name="closing_date"]').value = new Date().toISOString().split('T')[0];
      document.getElementById('contractFile').value = '';
      document.getElementById('fileUploadZone').classList.remove('has-file');
      document.getElementById('fileName').style.display = 'none';
      document.getElementById('errorMessage').classList.remove('show');
      lucide.createIcons();
    }
    
    function closeDealModal() {
      document.getElementById('dealClosingModal').classList.remove('active');
      
      // Revert the card if pending
      if (pendingMove) {
        pendingMove.from.appendChild(pendingMove.item);
        pendingMove = null;
      }
    }
    
    async function submitDealClosing() {
      const form = document.getElementById('dealClosingForm');
      const dealId = document.getElementById('modalDealId').value;
      const closingDate = form.querySelector('input[name="closing_date"]').value;
      const contractFile = document.getElementById('contractFile').files[0];
      const submitBtn = event.target;
      const errorMsg = document.getElementById('errorMessage');
      const errorText = document.getElementById('errorText');
      
      // Validation
      if (!closingDate) {
        errorText.textContent = 'Please select the close date';
        errorMsg.classList.add('show');
        lucide.createIcons();
        return;
      }
      
      // File size validation (only if file is selected)
      if (contractFile && contractFile.size > 10 * 1024 * 1024) {
        errorText.textContent = 'File exceeds 10MB. Please choose a smaller file.';
        errorMsg.classList.add('show');
        lucide.createIcons();
        return;
      }
      
      // Show loading
      submitBtn.classList.add('loading');
      submitBtn.disabled = true;
      errorMsg.classList.remove('show');
      
      try {
        // Create FormData for file upload
        const formData = new FormData();
        formData.append('deal_id', dealId);
        formData.append('stage_id', 'closed_won');
        formData.append('closing_date', closingDate);
        
        // Only append contract if file is selected
        if (contractFile) {
          formData.append('contract', contractFile);
        }
        
        const csrfToken = await getCsrfToken();
        const response = await fetch('/crm/my-pipeline/update-stage', {
          method: 'POST',
          headers: { 'X-CSRF-Token': csrfToken },
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          // Clear pending move
          pendingMove = null;
          
          // Close modal and reload
          document.getElementById('dealClosingModal').classList.remove('active');
          
          // Show success message
          const successDiv = document.createElement('div');
          successDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 9999; display: flex; align-items: center; gap: 10px;';
          successDiv.innerHTML = '<i data-lucide="check-circle" width="20" height="20"></i><span>✅ Deal closed successfully!</span>';
          document.body.appendChild(successDiv);
          lucide.createIcons();
          
          setTimeout(() => {
            // Redirect to dashboard to see updated stats
            window.location.href = '/crm/dashboard';
          }, 1500);
        } else {
          errorText.textContent = result.message || 'An error occurred. Please try again.';
          errorMsg.classList.add('show');
          lucide.createIcons();
          submitBtn.classList.remove('loading');
          submitBtn.disabled = false;
        }
      } catch (error) {
        console.error('Error:', error);
        errorText.textContent = 'An error occurred. Please try again.';
        errorMsg.classList.add('show');
        lucide.createIcons();
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
      }
    }
    
    // File upload handler
    document.getElementById('contractFile').addEventListener('change', function(e) {
      const file = e.target.files[0];
      const fileNameDisplay = document.getElementById('fileName');
      const uploadZone = document.getElementById('fileUploadZone');
      
      if (file) {
        fileNameDisplay.textContent = '📄 ' + file.name;
        fileNameDisplay.style.display = 'block';
        uploadZone.classList.add('has-file');
        lucide.createIcons();
      }
    });
    
    // Close modal on overlay click
    document.getElementById('dealClosingModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeDealModal();
      }
    });
    
    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const modal = document.getElementById('dealClosingModal');
        if (modal.classList.contains('active')) {
          closeDealModal();
        }
      }
    });
    
    // Debug: Check for stuck modals on page load
    window.addEventListener('load', function() {
      const modal = document.getElementById('dealClosingModal');
      if (modal && modal.classList.contains('active')) {
        console.warn('Modal was stuck open, closing...');
        modal.classList.remove('active');
      }
    });
    
    // Initialize Sortable for each column
    document.querySelectorAll('.column-cards').forEach(column => {
      new Sortable(column, {
        group: 'deals',
        animation: 200,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd: async function(evt) {
          const dealId = evt.item.dataset.dealId;
          const newStage = evt.to.dataset.stage;
          const oldStage = evt.from.dataset.stage;
          
          if (newStage !== oldStage) {
            // Store move info for potential revert
            pendingMove = {
              item: evt.item,
              from: evt.from,
              to: evt.to
            };
            
            // If moving to Won (stage 5), show modal
            if (newStage === '5') {
              showDealModal(dealId);
              return;
            }
            
            // Otherwise, update immediately
            try {
              const csrfToken = await getCsrfToken();
              const response = await fetch('/crm/my-pipeline/update-stage', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                  deal_id: dealId,
                  stage_id: newStage
                })
              });
              
              const result = await response.json();
              
              if (result.success) {
                // Clear pending move
                pendingMove = null;
                // Reload page to update totals
                window.location.reload();
              } else {
                alert('Error updating deal stage');
                // Revert the move
                evt.from.appendChild(evt.item);
                pendingMove = null;
              }
            } catch (error) {
              console.error('Error:', error);
              alert('Error updating deal stage');
              // Revert the move
              evt.from.appendChild(evt.item);
              pendingMove = null;
            }
          }
        }
      });
    });
  </script>
HTML;

    return $html;
  }

  /**
   * AJAX endpoint to update deal stage.
   */
  public function updateStage(Request $request) {
    // Check if this is a form submission (multipart/form-data or application/x-www-form-urlencoded)
    $content_type = $request->headers->get('Content-Type', '');
    $is_form_submission = strpos($content_type, 'form') !== FALSE || 
                          strpos($content_type, 'multipart') !== FALSE;
    
    if ($is_form_submission) {
      // Handle form submission (with or without file)
      $deal_id = (int)$request->request->get('deal_id');
      $stage_id = $request->request->get('stage_id');
      $closing_date = $request->request->get('closing_date');
      $file = $request->files->get('contract');
    } else {
      // Handle regular JSON stage update
      $data = json_decode($request->getContent(), TRUE);
      $deal_id = isset($data['deal_id']) ? (int)$data['deal_id'] : NULL;
      $stage_id = isset($data['stage_id']) ? $data['stage_id'] : NULL;
      $closing_date = NULL;
      $file = NULL;
    }
    
    if (!$deal_id || !$stage_id) {
      return new JsonResponse(['success' => FALSE, 'message' => 'Missing parameters: deal_id=' . $deal_id . ', stage_id=' . $stage_id], 400);
    }

    // CSRF validation.
    $token = $request->headers->get('X-CSRF-Token');
    if (empty($token) || !\Drupal::service('csrf_token')->validate($token)) {
      return new JsonResponse(['success' => FALSE, 'message' => 'CSRF validation failed'], 403);
    }

    // Resolve stage_id to a numeric taxonomy term ID.
    // Regular drag-drop sends numeric string term IDs; the closing modal sends 'closed_won'.
    if ($stage_id === 'closed_won') {
      $numeric_stage_id = 5; // Term ID 5 = Won in pipeline_stage vocab
    } elseif (is_numeric($stage_id)) {
      $numeric_stage_id = (int) $stage_id;
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($numeric_stage_id);
      if (!$term || $term->bundle() !== 'pipeline_stage') {
        return new JsonResponse(['success' => FALSE, 'message' => 'Invalid stage ID: ' . $stage_id], 400);
      }
    } else {
      return new JsonResponse(['success' => FALSE, 'message' => 'Invalid stage value: ' . $stage_id], 400);
    }
    
    try {
      $deal = \Drupal::entityTypeManager()->getStorage('node')->load($deal_id);
      
      if (!$deal || $deal->bundle() !== 'deal') {
        return new JsonResponse(['success' => FALSE, 'message' => 'Deal not found'], 404);
      }
      
      // If moving to Won (term ID 5 in pipeline_stage vocabulary).
      if ($numeric_stage_id === 5) {
        // Validate closing date
        if (!$closing_date) {
          return new JsonResponse(['success' => FALSE, 'message' => 'Closing date is required'], 400);
        }
        
        // Update deal with closing date (if field exists)
        if ($deal->hasField('field_closing_date')) {
          $deal->set('field_closing_date', $closing_date);
        }
        
        // Handle optional file upload
        if ($file) {
          try {
            // Validate and save file
            $validators = [
              'file_validate_extensions' => ['pdf doc docx xls xlsx'],
              'file_validate_size' => [10 * 1024 * 1024], // 10MB
            ];
            
            $file_entity = file_save_upload('contract', $validators, 'private://contracts', 0, \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME);
            
            if (!$file_entity) {
              return new JsonResponse(['success' => FALSE, 'message' => 'File upload failed. Please check file type and size.'], 400);
            }
            
            // Make file permanent
            $file_entity->setPermanent();
            $file_entity->save();
            
            // Update deal with contract if field exists
            if ($deal->hasField('field_contract')) {
              $deal->set('field_contract', [
                'target_id' => $file_entity->id(),
                'description' => 'Contract signed on ' . $closing_date,
              ]);
            }
            
            // Log activity
            \Drupal::logger('crm_kanban')->notice('Deal @deal_id closed with contract @file', [
              '@deal_id' => $deal_id,
              '@file' => $file_entity->getFilename(),
            ]);
          } catch (\Exception $file_error) {
            \Drupal::logger('crm_kanban')->warning('File upload error for deal @deal_id: @error', [
              '@deal_id' => $deal_id,
              '@error' => $file_error->getMessage(),
            ]);
            // Continue without file - it's optional anyway
          }
        } else {
          // Log activity without contract
          \Drupal::logger('crm_kanban')->notice('Deal @deal_id closed without contract on @date', [
            '@deal_id' => $deal_id,
            '@date' => $closing_date,
          ]);
        }
      }
      
      // Update stage as entity_reference (field_stage targets taxonomy_term, vocab pipeline_stage).
      if ($deal->hasField('field_stage')) {
        $deal->set('field_stage', ['target_id' => $numeric_stage_id]);
      }
      $deal->save();

      // Clear entity cache so dashboard and views show updated data immediately.
      \Drupal::entityTypeManager()->getStorage('node')->resetCache([$deal_id]);
      \Drupal\Core\Cache\Cache::invalidateTags(['node:' . $deal_id, 'node_list']);
      
      // TODO: Send email notification to manager when deal is won
      // Can be implemented using Drupal's Mail API or Rules module
      
      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Deal stage updated successfully',
        'deal_id' => $deal_id,
        'stage_id' => $stage_id,
      ]);
      
    } catch (\Exception $e) {
      \Drupal::logger('crm_kanban')->error('Error updating deal stage: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Error updating deal: ' . $e->getMessage(),
      ], 500);
    }
  }

}
