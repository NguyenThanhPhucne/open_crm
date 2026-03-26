<?php

namespace Drupal\crm_kanban\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\crm\Helper\CrmActionHelper;

/**
 * Controller for CRM Kanban Pipeline.
 */
class KanbanController extends ControllerBase {

  /**
   * Access check for pipeline pages.
   *
   * Rules:
   * - /crm/my-pipeline: All logged-in users can view
   * - /crm/all-pipeline: Only admin/manager can view
   */
  public function accessView(AccountInterface $account): AccessResult {
    $current_path = \Drupal::request()->getPathInfo();
    $is_my_view = str_contains($current_path, 'my-pipeline');

    // My-pipeline: all logged-in users can view their own pipeline.
    if ($is_my_view) {
      return $account->isAuthenticated()
        ? AccessResult::allowed()->cachePerUser()
        : AccessResult::forbidden()->cachePerUser();
    }

    // All-pipeline: only admin/manager can view all pipeline.
    $is_admin = $account->hasRole('administrator');
    $is_manager = in_array('sales_manager', $account->getRoles(), TRUE);

    return ($is_admin || $is_manager)
      ? AccessResult::allowed()->cachePerUser()
      : AccessResult::forbidden()->cachePerUser();
  }

  /**
   * Display the Kanban board.
   */
  public function view() {
    // Get current user.
    $current_user = \Drupal::currentUser();

    // Load pipeline stages dynamically from taxonomy, sorted by weight.
    $stage_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('pipeline_stage', 0, NULL, TRUE);

    $stages = [];
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

    // Get deals grouped by stage.
    $deals_by_stage = [];
    $totals_by_stage = [];

    foreach ($stages as $stage_id => $stage_info) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('field_stage', $stage_id)
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range(0, 200);

      $nids = $query->execute();
      $deals = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);

      $deals_by_stage[$stage_id] = [];
      $totals_by_stage[$stage_id] = 0;

      foreach ($deals as $deal) {
        $value = $deal->get('field_amount')->value ?? 0;
        $totals_by_stage[$stage_id] += $value;

        // Get organization name.
        $org_name = '';
        if ($deal->hasField('field_organization') && !$deal->get('field_organization')->isEmpty()) {
          $org = $deal->get('field_organization')->entity;
          if ($org) {
            // Nếu organization là custom entity, label() an toàn hơn getTitle().
            $org_name = method_exists($org, 'label') ? $org->label() : '';
          }
        }

        // Get owner name.
        $owner_name = '';
        if ($deal->hasField('field_owner') && !$deal->get('field_owner')->isEmpty()) {
          $owner = $deal->get('field_owner')->entity;
          if ($owner) {
            $owner_name = $owner->getDisplayName();
          }
        }

        // Compute owner initials.
        $owner_initials = '';
        if ($owner_name) {
          $parts = preg_split('/\s+/', trim($owner_name));
          $owner_initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
          if (count($parts) > 1) {
            $owner_initials .= mb_strtoupper(mb_substr(end($parts), 0, 1));
          }
        }

        // Get closing date if available.
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

    // Compute summary stats.
    $total_count = 0;
    $pipeline_value = 0;
    $won_count = 0;
    foreach ($stages as $sid => $sinfo) {
      $cnt = count($deals_by_stage[$sid]);
      $total_count += $cnt;
      $sname_lower = strtolower($sinfo['name']);
      if (str_contains($sname_lower, 'won')) {
        $won_count += $cnt;
      }
      elseif (!str_contains($sname_lower, 'lost') && !str_contains($sname_lower, 'closed')) {
        $pipeline_value += $totals_by_stage[$sid];
      }
    }

    // Determine page context.
    $current_path = \Drupal::service('path.current')->getPath();
    $is_all_pipeline = str_contains($current_path, 'all-pipeline');

    // Used only for UI/stats; actual deal filtering is handled elsewhere.
    $can_see_all = $is_all_pipeline && ($current_user->hasRole('administrator') || in_array('sales_manager', $current_user->getRoles(), TRUE));
    $page_title = $is_all_pipeline ? 'All Pipeline' : 'My Pipeline';
    $list_url   = $is_all_pipeline ? '/crm/all-deals' : '/crm/my-deals';

    // ── Build dynamic actions ───────────────────────────────────────────────
    $actions_html = CrmActionHelper::renderActions('deal', [
      'list' => [
        'label' => 'List view',
        'url' => $list_url,
        'icon' => 'list',
        'class' => 'btn-secondary',
      ],
      'add' => [
        'label' => 'Add Deal',
        'url' => '/crm/add/deal',
        'icon' => 'plus-circle',
        'class' => 'btn-primary',
      ],
    ]);

    $fmt_pipeline = '$' . (
      $pipeline_value >= 1000000
        ? number_format($pipeline_value / 1000000, 1) . 'M'
        : ($pipeline_value >= 1000 ? number_format($pipeline_value / 1000, 0) . 'K' : number_format($pipeline_value, 0))
    );

    $stats = [
      'total_count'   => $total_count,
      'fmt_pipeline'  => $fmt_pipeline,
      'won_count'     => $won_count,
      'page_title'    => $page_title,
      'list_url'      => $list_url,
      'is_admin'      => $can_see_all,
      'actions_html'  => $actions_html,
    ];

    $html = $this->buildKanbanHtml($stages, $deals_by_stage, $totals_by_stage, $stats);

    return [
      '#markup' => Markup::create($html),
      '#attached' => [
        'library' => [
          'core/drupal',
          'crm/crm_shared',
          'crm/crm_layout',
          'crm_edit/inline_edit',
          'crm_kanban/kanban_board',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags'     => ['node_list:deal'],
        'max-age'  => 0,
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
    $actions_html = $stats['actions_html']  ?? '';

    // Build stage options for filter dropdown.
    $stage_options_html = '';
    foreach ($stages as $stage_id => $stage_info) {
      $stage_options_html .= '<option value="' . htmlspecialchars((string) $stage_id, ENT_QUOTES) . '">' . htmlspecialchars($stage_info['name'], ENT_QUOTES) . '</option>';
    }

    $html = <<<'HTML'
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">


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
              <i data-lucide="calendar-check" width="16" height="16" class="lucide-v-middle"></i>
              Close Date <span class="required">*</span>
            </label>
            <input type="date" name="closing_date" class="form-input" required>
          </div>

          <div class="form-group">
            <label class="form-label">
              <i data-lucide="file-text" width="16" height="16" class="lucide-v-middle"></i>
              Attached Contract <span class="help-text-optional">(optional)</span>
            </label>
            <div class="file-upload-zone" id="fileUploadZone" onclick="document.getElementById('contractFile').click()">
              <div class="file-icon">
                <i data-lucide="upload" width="24" height="24"></i>
              </div>
              <div class="file-instructions">Click to select contract file (optional)</div>
              <div class="file-hint">PDF, DOC, DOCX (max 10MB)</div>
              <div class="file-name crm-hidden" id="fileName"></div>
            </div>
            <input type="file" id="contractFile" name="contract" accept=".pdf,.doc,.docx" class="crm-hidden">
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

  <div class="deal-modal-overlay" id="dealLostModal">
    <div class="deal-modal">
      <div class="modal-header">
        <div class="modal-icon crm-icon-danger">
          <i data-lucide="x-circle" width="24" height="24"></i>
        </div>
        <h2>Mark Deal as Lost</h2>
      </div>
      <div class="modal-body">
        <div class="info-box crm-box-danger">
          <i data-lucide="alert-circle" width="18" height="18"></i>
          <div>Please select a reason and optionally add notes before marking this deal as lost.</div>
        </div>
        <form id="dealLostForm">
          <input type="hidden" name="deal_id" id="lostModalDealId">
          <div class="form-group">
            <label class="form-label">
              <i data-lucide="help-circle" width="16" height="16" class="lucide-v-middle"></i>
              Lost Reason <span class="required">*</span>
            </label>
            <select name="lost_reason" id="lostReasonSelect" class="form-input" required>
              <option value="">Select a reason…</option>
              <option value="price">Price too high</option>
              <option value="competitor">Chose a competitor</option>
              <option value="budget">No budget</option>
              <option value="timing">Bad timing</option>
              <option value="no_response">No response from prospect</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">
              <i data-lucide="file-text" width="16" height="16" class="lucide-v-middle"></i>
              Notes <span class="help-text-optional">(optional)</span>
            </label>
            <textarea name="lost_notes" class="form-input crm-resize-v" rows="3" placeholder="Any additional context…"></textarea>
          </div>
          <div class="error-message" id="lostErrorMessage">
            <i data-lucide="alert-circle" width="16" height="16"></i>
            <span id="lostErrorText"></span>
          </div>
        </form>
      </div>
      <div class="modal-actions">
        <button type="button" class="modal-btn modal-btn-cancel" onclick="closeLostModal()">
          <i data-lucide="x"></i> Cancel
        </button>
        <button type="button" class="modal-btn crm-btn-danger" onclick="submitDealLost()">
          <i data-lucide="x-circle"></i> Confirm Lost
        </button>
      </div>
    </div>
  </div>

HTML;

    $html .= <<<HTML
<div class="pipeline-page" id="pg-wrap">
  <div class="stats-bar">
    <span class="stat-chip blue" id="kanban-total-chip"><i data-lucide="kanban-square"></i>{$total_count} total deals</span>
    <span class="stat-chip green" id="kanban-pipeline-chip"><i data-lucide="trending-up"></i>{$fmt_pipeline} in pipeline</span>
    <span class="stat-chip purple" id="kanban-won-chip"><i data-lucide="trophy"></i>{$won_count} won</span>
  </div>

  <div class="page-header">
    <div class="page-header-left">
      <div class="page-title"><i data-lucide="kanban-square" width="22" height="22"></i>{$page_title}</div>
      <div class="page-subtitle">Drag cards between columns to update deal stages</div>
    </div>
    <div class="page-actions">
      {$actions_html}
    </div>
  </div>

  <div class="filter-bar">
    <div class="filter-input-wrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="text" id="kanban-search" class="filter-input" placeholder="Search deals…" autocomplete="off">
      <button type="button" id="kanban-search-clear" class="filter-input-clear" title="Clear search" aria-label="Clear">&#x2715;</button>
    </div>
    <div class="filter-select-wrap">
      <span class="flt-sel-ico"><i data-lucide="layers"></i></span>
      <select class="filter-select" id="kanban-stage-filter">
        <option value="">All stages</option>
        {$stage_options_html}
      </select>
      <span class="flt-sel-arr"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 4 7 9 12 4"/></svg></span>
    </div>
    <span class="stat-chip blue crm-chip-lg" id="kanban-filter-total-chip"><i data-lucide="kanban-square"></i>{$total_count} deals</span>
    <span class="stat-chip green crm-chip-lg" id="kanban-filter-pipeline-chip"><i data-lucide="trending-up"></i>{$fmt_pipeline}</span>
    <span id="filter-count" class="filter-count"></span>
  </div>

  <div class="kanban-container">
    <div class="kanban-board">
HTML;

    foreach ($stages as $stage_id => $stage_info) {
      $deals = $deals_by_stage[$stage_id] ?? [];
      $total = $totals_by_stage[$stage_id] ?? 0;
      $count = count($deals);
      $total_formatted = $total >= 1000000
        ? '$' . number_format($total / 1000000, 1) . 'M'
        : ($total >= 1000 ? '$' . number_format($total / 1000, 0) . 'K' : '$' . number_format($total, 0));

      $html .= <<<HTML

      <div class="kanban-column" data-stage="{$stage_id}" data-stage-name="{$stage_info['name']}">
        <div class="column-header" style="border-color:{$stage_info['color']}">
          <div class="column-title">
            <span class="col-dot" style="background:{$stage_info['color']}"></span>
            <h3>{$stage_info['name']}</h3>
            <span class="column-count">{$count}</span>
          </div>
          <div class="column-total" data-total-value="{$total}" style="color:{$stage_info['color']}">{$total_formatted}</div>
        </div>
        <div class="column-cards" data-stage="{$stage_id}" data-stage-name="{$stage_info['name']}" data-stage-color="{$stage_info['color']}">
HTML;

      if (empty($deals)) {
        $html .= <<<HTML
          <div class="empty-state">
            <i data-lucide="inbox" width="32" height="32"></i>
            <div>No deals</div>
          </div>
HTML;
      }
      else {
        foreach ($deals as $deal) {
          $val = (float) $deal['value'];
          $value_formatted = $val >= 1000000
            ? '$' . number_format($val / 1000000, 1) . 'M'
            : ($val >= 1000 ? '$' . number_format($val / 1000, 0) . 'K' : '$' . number_format($val, 0));
          $org_display   = $deal['organization'] ?: 'No organization';
          $owner_display = $deal['owner'] ?: 'Unassigned';
          $owner_av      = $deal['owner_initials'] ?: '?';
          $card_color    = $stage_info['color'];
          $created_ts    = $deal['created'] ?? 0;

          $due_badge_html = '';
          if (!empty($deal['closing_date'])) {
            $close_ts  = strtotime($deal['closing_date']);
            $days_left = (int) (($close_ts - time()) / 86400);
            if ($days_left < 0) {
              $abs = abs($days_left);
              $due_badge_html = '<span class="due-badge urgent"><i data-lucide="alert-circle"></i>' . $abs . 'd overdue</span>';
            }
            elseif ($days_left <= 3) {
              $due_badge_html = '<span class="due-badge urgent"><i data-lucide="clock"></i>' . $days_left . 'd left</span>';
            }
            elseif ($days_left <= 14) {
              $due_badge_html = '<span class="due-badge soon"><i data-lucide="clock"></i>' . $days_left . 'd left</span>';
            }
          }

          $search_text = htmlspecialchars(strtolower($deal['title'] . ' ' . $org_display . ' ' . $owner_display), ENT_QUOTES, 'UTF-8');

          $html .= <<<HTML
          <div class="deal-card" style="border-left-color:{$card_color}" data-deal-id="{$deal['nid']}" data-deal-value="{$val}" data-search-text="{$search_text}">
            <div class="card-actions">
              <a href="/node/{$deal['nid']}" class="ca-btn" title="View"><i data-lucide="eye"></i></a>
              <button type="button" class="ca-btn" title="Edit" onclick="if(window.CRMInlineEdit)CRMInlineEdit.openModal({$deal['nid']},'deal');else location='/node/{$deal['nid']}/edit';"><i data-lucide="pencil"></i></button>
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
    lucide.createIcons();
    if (window.CRM) {
      CRM.initKeyboardShortcuts({ addUrl: '/crm/add/deal', searchId: null });
      CRM.renderShortcutHints([{ key: 'N', label: 'New deal' }]);
    }

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

    const _ks = document.getElementById('kanban-search');
    const _kstage = document.getElementById('kanban-stage-filter');
    const _kc = document.getElementById('filter-count');
    const _ksClear = document.getElementById('kanban-search-clear');
    const _kanbanTotalChip = document.getElementById('kanban-total-chip');
    const _kanbanPipelineChip = document.getElementById('kanban-pipeline-chip');
    const _kanbanWonChip = document.getElementById('kanban-won-chip');

    function crmWordMatch(text, q) {
      if (!q) return true;
      if (text.startsWith(q)) return true;
      return text.split(/[\s\-_\/]+/).some(function(w){ return w.startsWith(q); });
    }

    function formatCurrencyShort(value) {
      if (value >= 1000000) return '$' + (value / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
      if (value >= 1000) return '$' + Math.round(value / 1000) + 'K';
      return '$' + Math.round(value);
    }

    function getColumnCards(cardOrColumn) {
      if (!cardOrColumn) return null;
      return cardOrColumn.classList && cardOrColumn.classList.contains('column-cards')
        ? cardOrColumn
        : cardOrColumn.closest('.column-cards');
    }

    function getStageMeta(columnCards) {
      const stageName = (columnCards?.dataset.stageName || '').toLowerCase();
      return {
        isWon: stageName.includes('won'),
        isLost: stageName.includes('lost'),
        isClosed: stageName.includes('closed'),
      };
    }

    function applyCardStageAppearance(card) {
      const columnCards = getColumnCards(card);
      if (!columnCards) return;
      const stageColor = columnCards.dataset.stageColor || '#3b82f6';
      card.style.borderLeftColor = stageColor;
      const valueEl = card.querySelector('.deal-value');
      if (valueEl) valueEl.style.color = stageColor;
    }

    function ensureColumnEmptyState(columnCards) {
      if (!columnCards) return;
      const cards = columnCards.querySelectorAll('.deal-card');
      const emptyState = columnCards.querySelector('.empty-state');
      if (cards.length === 0 && !emptyState) {
        const empty = document.createElement('div');
        empty.className = 'empty-state';
        empty.innerHTML = '<i data-lucide="inbox" width="32" height="32"></i><div>No deals</div>';
        columnCards.appendChild(empty);
        lucide.createIcons();
      }
      if (cards.length > 0 && emptyState) {
        emptyState.remove();
      }
    }

    function updateColumnSummary(columnCards) {
      if (!columnCards) return;
      const column = columnCards.closest('.kanban-column');
      if (!column) return;
      const cards = Array.from(columnCards.querySelectorAll('.deal-card'));
      const countEl = column.querySelector('.column-count');
      const totalEl = column.querySelector('.column-total');
      const totalValue = cards.reduce(function(sum, card) {
        return sum + (parseFloat(card.dataset.dealValue || '0') || 0);
      }, 0);
      if (countEl) {
        countEl.textContent = cards.filter(function(card) {
          return !card.classList.contains('card-hidden');
        }).length;
      }
      if (totalEl) {
        totalEl.dataset.totalValue = String(totalValue);
        totalEl.textContent = formatCurrencyShort(totalValue);
      }
      ensureColumnEmptyState(columnCards);
    }

    function updateBoardSummary() {
      const cards = Array.from(document.querySelectorAll('.deal-card'));
      let totalDeals = 0;
      let wonDeals = 0;
      let pipelineValue = 0;

      cards.forEach(function(card) {
        const columnCards = getColumnCards(card);
        if (!columnCards) return;
        const value = parseFloat(card.dataset.dealValue || '0') || 0;
        const stageMeta = getStageMeta(columnCards);
        totalDeals += 1;
        if (stageMeta.isWon) {
          wonDeals += 1;
        }
        else if (!stageMeta.isLost && !stageMeta.isClosed) {
          pipelineValue += value;
        }
      });

      if (_kanbanTotalChip) _kanbanTotalChip.innerHTML = '<i data-lucide="kanban-square"></i>' + totalDeals + ' total deals';
      if (_kanbanPipelineChip) _kanbanPipelineChip.innerHTML = '<i data-lucide="trending-up"></i>' + formatCurrencyShort(pipelineValue) + ' in pipeline';
      if (_kanbanWonChip) _kanbanWonChip.innerHTML = '<i data-lucide="trophy"></i>' + wonDeals + ' won';
      lucide.createIcons();
    }

    function syncBoardState(columns) {
      const columnList = columns && columns.length ? Array.from(columns) : Array.from(document.querySelectorAll('.column-cards'));
      columnList.forEach(function(columnCards) {
        Array.from(columnCards.querySelectorAll('.deal-card')).forEach(applyCardStageAppearance);
        updateColumnSummary(columnCards);
      });
      updateBoardSummary();
    }

    function showToast(message, tone) {
      const existing = document.querySelector('.crm-kanban-toast');
      if (existing) existing.remove();
      const toast = document.createElement('div');
      const isError = tone === 'error';
      toast.className = 'crm-kanban-toast';
      toast.style.cssText = 'position:fixed;top:20px;right:20px;display:flex;align-items:center;gap:10px;padding:14px 18px;border-radius:20px;box-shadow:0 12px 30px rgba(15,23,42,.16);z-index:9999;background:' + (isError ? '#dc2626' : '#0f766e') + ';color:#fff;font-weight:600;';
      toast.innerHTML = '<i data-lucide="' + (isError ? 'alert-circle' : 'check-circle') + '" width="18" height="18"></i><span>' + message + '</span>';
      document.body.appendChild(toast);
      lucide.createIcons();
      window.setTimeout(function() {
        toast.remove();
      }, 2200);
    }

    function applyKanbanFilter() {
      const q = (_ks ? _ks.value.trim().toLowerCase() : '');
      const stage = (_kstage ? _kstage.value : '');
      if (_ksClear) _ksClear.classList.toggle('visible', q.length > 0);
      let vis = 0, tot = 0;
      document.querySelectorAll('.deal-card').forEach(c => {
        tot++;
        const searchText = c.dataset.searchText || c.querySelector('.deal-title')?.textContent.toLowerCase() || c.textContent.toLowerCase();
        const matchText = !q || crmWordMatch(searchText, q);
        const col = c.closest('.column-cards');
        const matchStage = !stage || (col && col.dataset.stage === stage);
        const match = matchText && matchStage;
        const wasHidden = c.classList.contains('card-hidden');
        c.classList.toggle('card-hidden', !match);
        if (match) {
          vis++;
          if (wasHidden) {
            c.classList.remove('card-just-shown');
            void c.offsetWidth;
            c.classList.add('card-just-shown');
          }
        }
      });
      if (_kc) _kc.textContent = (q || stage) ? vis + ' of ' + tot + ' deals' : '';
      document.querySelectorAll('.kanban-column').forEach(col => {
        const cnt = col.querySelectorAll('.deal-card:not(.card-hidden)').length;
        const badge = col.querySelector('.column-count');
        if (badge) badge.textContent = cnt;
      });
    }

    if (_ks) { _ks.addEventListener('input', applyKanbanFilter); }
    if (_kstage) { _kstage.addEventListener('change', applyKanbanFilter); }
    if (_ksClear) {
      _ksClear.addEventListener('click', function(){
        if (_ks) { _ks.value = ''; }
        applyKanbanFilter();
        if (_ks) { _ks.focus(); }
      });
    }

    syncBoardState();

    let pendingMove = null;
    let _boardCsrfToken = null;

    async function getCsrfToken() {
      if (_boardCsrfToken) return _boardCsrfToken;
      const r = await fetch('/session/token', { credentials: 'same-origin' });
      _boardCsrfToken = (await r.text()).trim();
      return _boardCsrfToken;
    }

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
      if (pendingMove) {
        pendingMove.from.appendChild(pendingMove.item);
        syncBoardState([pendingMove.from, pendingMove.to]);
        applyKanbanFilter();
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

      if (!closingDate) {
        errorText.textContent = 'Please select the close date';
        errorMsg.classList.add('show');
        lucide.createIcons();
        return;
      }

      if (contractFile && contractFile.size > 10 * 1024 * 1024) {
        errorText.textContent = 'File exceeds 10MB. Please choose a smaller file.';
        errorMsg.classList.add('show');
        lucide.createIcons();
        return;
      }

      submitBtn.classList.add('loading');
      submitBtn.disabled = true;
      errorMsg.classList.remove('show');

      try {
        const formData = new FormData();
        formData.append('deal_id', dealId);
        formData.append('stage_id', 'closed_won');
        formData.append('closing_date', closingDate);

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
          const movedColumns = pendingMove ? [pendingMove.from, pendingMove.to] : [];
          pendingMove = null;
          document.getElementById('dealClosingModal').classList.remove('active');
          submitBtn.classList.remove('loading');
          submitBtn.disabled = false;
          syncBoardState(movedColumns);
          applyKanbanFilter();
          showToast('Deal closed successfully');
        }
        else {
          errorText.textContent = result.message || 'An error occurred. Please try again.';
          errorMsg.classList.add('show');
          lucide.createIcons();
          submitBtn.classList.remove('loading');
          submitBtn.disabled = false;
        }
      }
      catch (error) {
        console.error('Error submitting deal closing:', error);
        errorText.textContent = 'An error occurred. Please try again.';
        errorMsg.classList.add('show');
        lucide.createIcons();
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
      }
    }

    function showLostModal(dealId) {
      document.getElementById('lostModalDealId').value = dealId;
      document.getElementById('lostReasonSelect').value = '';
      document.querySelector('#dealLostForm textarea').value = '';
      document.getElementById('lostErrorMessage').classList.remove('show');
      document.getElementById('dealLostModal').classList.add('active');
      lucide.createIcons();
    }

    function closeLostModal() {
      document.getElementById('dealLostModal').classList.remove('active');
      if (pendingMove) {
        pendingMove.from.appendChild(pendingMove.item);
        syncBoardState([pendingMove.from, pendingMove.to]);
        applyKanbanFilter();
        pendingMove = null;
      }
    }

    async function submitDealLost() {
      const dealId = document.getElementById('lostModalDealId').value;
      const reason = document.getElementById('lostReasonSelect').value;
      const notes = document.querySelector('#dealLostForm textarea').value.trim();
      const errorMsg = document.getElementById('lostErrorMessage');
      const errorText = document.getElementById('lostErrorText');
      const submitBtn = event.target;

      if (!reason) {
        errorText.textContent = 'Please select a lost reason.';
        errorMsg.classList.add('show');
        lucide.createIcons();
        return;
      }

      submitBtn.disabled = true;
      errorMsg.classList.remove('show');

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
            stage_id: pendingMove ? pendingMove.to.dataset.stage : '',
            lost_reason: reason,
            lost_notes: notes,
          }),
        });

        const result = await response.json();

        if (result.success) {
          const movedColumns = pendingMove ? [pendingMove.from, pendingMove.to] : [];
          pendingMove = null;
          document.getElementById('dealLostModal').classList.remove('active');
          submitBtn.disabled = false;
          syncBoardState(movedColumns);
          applyKanbanFilter();
          showToast('Deal marked as lost.');
        }
        else {
          errorText.textContent = result.message || 'An error occurred. Please try again.';
          errorMsg.classList.add('show');
          lucide.createIcons();
          submitBtn.disabled = false;
        }
      }
      catch (error) {
        console.error('Error submitting deal lost:', error);
        errorText.textContent = 'An error occurred. Please try again.';
        errorMsg.classList.add('show');
        lucide.createIcons();
        submitBtn.disabled = false;
      }
    }

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

    document.getElementById('dealClosingModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeDealModal();
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        if (document.getElementById('dealClosingModal').classList.contains('active')) {
          closeDealModal();
        }
        if (document.getElementById('dealLostModal').classList.contains('active')) {
          closeLostModal();
        }
      }
    });

    document.getElementById('dealLostModal').addEventListener('click', function(e) {
      if (e.target === this) closeLostModal();
    });

    window.addEventListener('load', function() {
      ['dealClosingModal', 'dealLostModal'].forEach(function(id) {
        const modal = document.getElementById(id);
        if (modal && modal.classList.contains('active')) {
          modal.classList.remove('active');
        }
      });
    });

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
            pendingMove = {
              item: evt.item,
              from: evt.from,
              to: evt.to
            };
            syncBoardState([evt.from, evt.to]);
            applyKanbanFilter();

            const toStageName = (evt.to.dataset.stageName || '').toLowerCase();
            const isWonStage  = toStageName.includes('won');
            const isLostStage = toStageName.includes('lost');

            if (isWonStage) {
              showDealModal(dealId);
              return;
            }

            if (isLostStage) {
              showLostModal(dealId);
              return;
            }

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
                pendingMove = null;
                syncBoardState([evt.from, evt.to]);
                applyKanbanFilter();
                showToast('Deal stage updated');
              }
              else {
                evt.from.appendChild(evt.item);
                syncBoardState([evt.from, evt.to]);
                applyKanbanFilter();
                showToast(result.message || 'Error updating deal stage', 'error');
                pendingMove = null;
              }
            }
            catch (error) {
              console.error('Error:', error);
              evt.from.appendChild(evt.item);
              syncBoardState([evt.from, evt.to]);
              applyKanbanFilter();
              showToast('Error updating deal stage', 'error');
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
    $content_type = $request->headers->get('Content-Type', '');
    $is_form_submission = strpos($content_type, 'form') !== FALSE ||
                          strpos($content_type, 'multipart') !== FALSE;

    if ($is_form_submission) {
      $deal_id = (int) $request->request->get('deal_id');
      $stage_id = $request->request->get('stage_id');
      $closing_date = $request->request->get('closing_date');
      $file = $request->files->get('contract');
    }
    else {
      $data = json_decode($request->getContent(), TRUE);
      $deal_id = isset($data['deal_id']) ? (int) $data['deal_id'] : NULL;
      $stage_id = isset($data['stage_id']) ? $data['stage_id'] : NULL;
      $closing_date = NULL;
      $file = NULL;
    }

    if (!$deal_id || !$stage_id) {
      return new JsonResponse(['success' => FALSE, 'message' => 'Missing parameters: deal_id=' . $deal_id . ', stage_id=' . $stage_id], 400);
    }

    $token = $request->headers->get('X-CSRF-Token');
    if (empty($token) || !\Drupal::service('csrf_token')->validate($token, CsrfRequestHeaderAccessCheck::TOKEN_KEY)) {
      return new JsonResponse(['success' => FALSE, 'message' => 'CSRF validation failed'], 403);
    }

    if ($stage_id === 'closed_won') {
      $numeric_stage_id = 5;
    }
    elseif (is_numeric($stage_id)) {
      $numeric_stage_id = (int) $stage_id;
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($numeric_stage_id);
      if (!$term || $term->bundle() !== 'pipeline_stage') {
        return new JsonResponse(['success' => FALSE, 'message' => 'Invalid stage ID: ' . $stage_id], 400);
      }
    }
    else {
      return new JsonResponse(['success' => FALSE, 'message' => 'Invalid stage value: ' . $stage_id], 400);
    }

    try {
      $deal = \Drupal::entityTypeManager()->getStorage('node')->load($deal_id);

      if (!$deal || $deal->bundle() !== 'deal') {
        return new JsonResponse(['success' => FALSE, 'message' => 'Deal not found'], 404);
      }

      $current_user = \Drupal::currentUser();
      if (!$deal->access('update', $current_user)) {
        return new JsonResponse(['success' => FALSE, 'message' => 'Access denied. You do not have permission to modify this deal.'], 403);
      }

      if ($numeric_stage_id === 5) {
        if (!$closing_date) {
          return new JsonResponse(['success' => FALSE, 'message' => 'Closing date is required'], 400);
        }

        if ($deal->hasField('field_closing_date')) {
          $deal->set('field_closing_date', $closing_date);
        }

        if ($file) {
          try {
            $validators = [
              'file_validate_extensions' => ['pdf doc docx xls xlsx'],
              'file_validate_size' => [10 * 1024 * 1024],
            ];

            $file_entity = file_save_upload('contract', $validators, 'private://contracts', 0, \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME);

            if (!$file_entity) {
              return new JsonResponse(['success' => FALSE, 'message' => 'File upload failed. Please check file type and size.'], 400);
            }

            $file_entity->setPermanent();
            $file_entity->save();

            if ($deal->hasField('field_contract_file')) {
              $deal->set('field_contract_file', [
                'target_id' => $file_entity->id(),
                'description' => 'Contract signed on ' . $closing_date,
              ]);
            }

            \Drupal::logger('crm_kanban')->notice('Deal @deal_id closed with contract @file', [
              '@deal_id' => $deal_id,
              '@file' => $file_entity->getFilename(),
            ]);
          }
          catch (\Exception $file_error) {
            \Drupal::logger('crm_kanban')->warning('File upload error for deal @deal_id: @error', [
              '@deal_id' => $deal_id,
              '@error' => $file_error->getMessage(),
            ]);
          }
        }
        else {
          \Drupal::logger('crm_kanban')->notice('Deal @deal_id closed without contract on @date', [
            '@deal_id' => $deal_id,
            '@date' => $closing_date,
          ]);
        }
      }

      if ($deal->hasField('field_stage')) {
        $deal->set('field_stage', ['target_id' => $numeric_stage_id]);
      }
      $deal->save();

      \Drupal::entityTypeManager()->getStorage('node')->resetCache([$deal_id]);
      \Drupal\Core\Cache\Cache::invalidateTags(['node:' . $deal_id, 'node_list']);

      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Deal stage updated successfully',
        'deal_id' => $deal_id,
        'stage_id' => $stage_id,
      ]);

    }
    catch (\Exception $e) {
      \Drupal::logger('crm_kanban')->error('Error updating deal stage: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Error updating deal: ' . $e->getMessage(),
      ], 500);
    }
  }

}