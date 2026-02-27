<?php

namespace Drupal\crm_kanban\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
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
    // Define pipeline stages
    $stages = [
      1 => ['name' => 'New', 'color' => '#3b82f6'],
      2 => ['name' => 'Qualified', 'color' => '#8b5cf6'],
      3 => ['name' => 'Proposal', 'color' => '#f59e0b'],
      4 => ['name' => 'Negotiation', 'color' => '#ec4899'],
      5 => ['name' => 'Won', 'color' => '#10b981'],
      6 => ['name' => 'Lost', 'color' => '#ef4444'],
    ];

    // Get deals grouped by stage
    $deals_by_stage = [];
    $totals_by_stage = [];
    
    foreach ($stages as $stage_id => $stage_info) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('field_stage', $stage_id)
        ->accessCheck(FALSE)
        ->sort('created', 'DESC');
      
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
        
        $deals_by_stage[$stage_id][] = [
          'nid' => $deal->id(),
          'title' => $deal->getTitle(),
          'value' => $value,
          'organization' => $org_name,
          'owner' => $owner_name,
        ];
      }
    }

    // Build Kanban HTML
    $html = $this->buildKanbanHtml($stages, $deals_by_stage, $totals_by_stage);
    
    return new Response($html);
  }

  /**
   * Build Kanban HTML.
   */
  private function buildKanbanHtml($stages, $deals_by_stage, $totals_by_stage) {
    $html = <<<'HTML'
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales Pipeline - Kanban</title>
  <link rel="icon" type="image/x-icon" href="/core/misc/favicon.ico">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f8fafc;
      min-height: 100vh;
      color: #1e293b;
    }
    
    .kanban-header {
      background: white;
      border-bottom: 1px solid #e2e8f0;
      padding: 20px 24px;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    .kanban-header h1 {
      font-size: 24px;
      font-weight: 600;
      color: #1e293b;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .kanban-container {
      padding: 24px;
      overflow-x: auto;
    }
    
    .kanban-board {
      display: flex;
      gap: 16px;
      min-width: max-content;
      padding-bottom: 24px;
    }
    
    .kanban-column {
      background: #f1f5f9;
      border-radius: 12px;
      width: 320px;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      max-height: calc(100vh - 140px);
    }
    
    .column-header {
      padding: 16px;
      border-bottom: 2px solid;
      background: white;
      border-radius: 12px 12px 0 0;
    }
    
    .column-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 8px;
    }
    
    .column-title h3 {
      font-size: 14px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .column-count {
      background: #f1f5f9;
      color: #64748b;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .column-total {
      font-size: 18px;
      font-weight: 700;
      margin-top: 4px;
    }
    
    .column-cards {
      padding: 12px;
      flex: 1;
      overflow-y: auto;
      min-height: 100px;
    }
    
    .deal-card {
      background: white;
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 12px;
      cursor: move;
      border-left: 3px solid;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      transition: all 0.2s ease;
    }
    
    .deal-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
      transform: translateY(-2px);
    }
    
    .deal-card.sortable-ghost {
      opacity: 0.4;
      background: #e2e8f0;
    }
    
    .deal-card.sortable-drag {
      opacity: 0.8;
      transform: rotate(3deg);
    }
    
    .deal-title {
      font-size: 15px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 8px;
      line-height: 1.4;
    }
    
    .deal-value {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 12px;
    }
    
    .deal-meta {
      display: flex;
      flex-direction: column;
      gap: 6px;
      font-size: 13px;
      color: #64748b;
    }
    
    .deal-meta-row {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .deal-meta-row i {
      flex-shrink: 0;
    }
    
    /* Stage colors */
    .stage-1 { border-color: #3b82f6; color: #3b82f6; }
    .stage-2 { border-color: #8b5cf6; color: #8b5cf6; }
    .stage-3 { border-color: #f59e0b; color: #f59e0b; }
    .stage-4 { border-color: #ec4899; color: #ec4899; }
    .stage-5 { border-color: #10b981; color: #10b981; }
    .stage-6 { border-color: #ef4444; color: #ef4444; }
    
    .empty-state {
      text-align: center;
      padding: 32px 16px;
      color: #94a3b8;
      font-size: 14px;
    }
    
    .empty-state i {
      margin-bottom: 8px;
      opacity: 0.5;
    }
  </style>
</head>
<body>
  <div class="kanban-header">
    <h1>
      <i data-lucide="kanban-square" width="28" height="28" style="color: #3b82f6;"></i>
      Sales Pipeline
    </h1>
  </div>
  
  <div class="kanban-container">
    <div class="kanban-board">
HTML;

    // Build columns
    foreach ($stages as $stage_id => $stage_info) {
      $deals = $deals_by_stage[$stage_id] ?? [];
      $total = $totals_by_stage[$stage_id] ?? 0;
      $count = count($deals);
      $total_formatted = '$' . number_format($total / 1000000, 1) . 'M';
      
      $html .= <<<HTML
      
      <div class="kanban-column">
        <div class="column-header stage-{$stage_id}">
          <div class="column-title">
            <h3>{$stage_info['name']}</h3>
            <span class="column-count">{$count}</span>
          </div>
          <div class="column-total stage-{$stage_id}">{$total_formatted}</div>
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
          $value_formatted = '$' . number_format($deal['value'] / 1000000, 2) . 'M';
          $org_display = $deal['organization'] ? $deal['organization'] : 'No organization';
          $owner_display = $deal['owner'] ? $deal['owner'] : 'Unassigned';
          
          $html .= <<<HTML
          <div class="deal-card stage-{$stage_id}" data-deal-id="{$deal['nid']}">
            <div class="deal-title">{$deal['title']}</div>
            <div class="deal-value stage-{$stage_id}">{$value_formatted}</div>
            <div class="deal-meta">
              <div class="deal-meta-row">
                <i data-lucide="building-2" width="14" height="14"></i>
                <span>{$org_display}</span>
              </div>
              <div class="deal-meta-row">
                <i data-lucide="user" width="14" height="14"></i>
                <span>{$owner_display}</span>
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

  <script>
    // Initialize Lucide icons
    lucide.createIcons();
    
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
            // Update deal stage via AJAX
            try {
              const response = await fetch('/crm/pipeline/update-stage', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                  deal_id: dealId,
                  stage_id: newStage
                })
              });
              
              const result = await response.json();
              
              if (result.success) {
                // Reload page to update totals
                window.location.reload();
              } else {
                alert('Error updating deal stage');
                // Revert the move
                evt.from.appendChild(evt.item);
              }
            } catch (error) {
              console.error('Error:', error);
              alert('Error updating deal stage');
              // Revert the move
              evt.from.appendChild(evt.item);
            }
          }
        }
      });
    });
  </script>
</body>
</html>
HTML;

    return $html;
  }

  /**
   * AJAX endpoint to update deal stage.
   */
  public function updateStage(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $deal_id = $data['deal_id'] ?? NULL;
    $stage_id = $data['stage_id'] ?? NULL;
    
    if (!$deal_id || !$stage_id) {
      return new JsonResponse(['success' => FALSE, 'message' => 'Missing parameters'], 400);
    }
    
    try {
      $deal = \Drupal::entityTypeManager()->getStorage('node')->load($deal_id);
      
      if (!$deal || $deal->bundle() !== 'deal') {
        return new JsonResponse(['success' => FALSE, 'message' => 'Deal not found'], 404);
      }
      
      // Update stage
      $deal->set('field_stage', $stage_id);
      $deal->save();
      
      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Deal stage updated successfully',
        'deal_id' => $deal_id,
        'stage_id' => $stage_id,
      ]);
      
    } catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Error updating deal: ' . $e->getMessage(),
      ], 500);
    }
  }

}
