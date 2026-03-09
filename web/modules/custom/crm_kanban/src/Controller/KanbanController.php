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
    
    // Load pipeline stages dynamically from taxonomy.
    $stage_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'pipeline_stage']);

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
        ->sort('created', 'DESC');
      
      // Only filter by owner for non-admin users
      if (!$is_admin) {
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
    
    return [
      '#markup' => Markup::create($html),
      '#attached' => [
        'library' => [
          'core/drupal',
        ],
      ],
    ];
  }

  /**
   * Build Kanban HTML.
   */
  private function buildKanbanHtml($stages, $deals_by_stage, $totals_by_stage) {
    $html = <<<'HTML'
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
    
    .kanban-container {
      padding: 12px;
      padding-top: 12px;
      overflow-x: hidden;
      overflow-y: hidden;
      scroll-behavior: smooth;
      -webkit-overflow-scrolling: touch;
      width: 100%;
      height: calc(100vh - 60px);
      display: flex;
    }
    
    .kanban-container::-webkit-scrollbar {
      height: 0px;
    }
    
    .kanban-container::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 4px;
    }
    
    .kanban-container::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 4px;
    }
    
    .kanban-container::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }
    
    .kanban-board {
      display: flex;
      flex-wrap: nowrap;
      gap: 8px;
      padding-bottom: 12px;
      width: 100%;
      align-items: stretch;
    }
    
    .kanban-column {
      background: #f1f5f9;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      height: calc(100vh - 140px);
      overflow: hidden;
      flex: 1 1 0;
      min-width: 0;
    }
    
    .column-header {
      padding: 8px 6px;
      border-bottom: 2px solid;
      background: white;
      border-radius: 12px 12px 0 0;
      text-align: center;
    }
    
    .column-title {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 4px;
      flex-wrap: wrap;
      gap: 2px;
    }
    
    .column-title h3 {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      word-break: break-word;
    }
    
    .column-count {
      background: #f1f5f9;
      color: #64748b;
      padding: 1px 4px;
      border-radius: 10px;
      font-size: 10px;
      font-weight: 600;
      white-space: nowrap;
    }
    
    .column-total {
      font-size: 12px;
      font-weight: 700;
      margin-top: 2px;
      word-break: break-word;
      text-align: center;
    }
    
    .column-cards {
      padding: 4px 4px;
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      min-height: 80px;
      scroll-behavior: smooth;
      -webkit-overflow-scrolling: touch;
    }
    
    .column-cards::-webkit-scrollbar {
      width: 4px;
    }
    
    .column-cards::-webkit-scrollbar-track {
      background: transparent;
    }
    
    .column-cards::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 2px;
    }
    
    .column-cards::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }
    
    .deal-card {
      background: white;
      border-radius: 6px;
      padding: 6px;
      margin-bottom: 4px;
      cursor: move;
      border-left: 2px solid;
      box-shadow: 0 1px 2px rgba(0,0,0,0.05);
      transition: all 0.2s ease;
      box-sizing: border-box;
      width: 100%;
      overflow: hidden;
      text-align: center;
    }
    
    .deal-card:hover {
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      transform: translateY(-1px);
    }
    
    .deal-card.sortable-ghost {
      opacity: 0.4;
      background: #e2e8f0;
    }
    
    .deal-card.sortable-drag {
      opacity: 0.8;
      transform: rotate(2deg);
    }
    
    .deal-title {
      font-size: 12px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 2px;
      line-height: 1.2;
      word-break: break-word;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }
    
    .deal-value {
      font-size: 13px;
      font-weight: 700;
      margin-bottom: 3px;
      word-break: break-word;
    }
    
    .deal-meta {
      display: flex;
      flex-direction: column;
      gap: 1px;
      font-size: 10px;
      color: #64748b;
      align-items: center;
    }
    
    .deal-meta-row {
      display: flex;
      align-items: center;
      gap: 2px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    .deal-meta-row i {
      flex-shrink: 0;
      font-size: 10px;
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
    
    /* Deal Closing Modal */
    .deal-modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 2000;
      animation: fadeIn 0.2s ease;
      pointer-events: none;
    }
    
    .deal-modal-overlay.active {
      display: flex;
      align-items: center;
      justify-content: center;
      pointer-events: auto;
    }
    
    .deal-modal {
      background: white;
      border-radius: 16px;
      max-width: 500px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideUp 0.3s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .modal-header {
      padding: 24px;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .modal-header h2 {
      font-size: 20px;
      font-weight: 600;
      color: #1e293b;
      flex: 1;
      margin: 0;
    }
    
    .modal-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #10b981, #059669);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }
    
    .modal-body {
      padding: 24px;
    }
    
    .info-box {
      background: #fef3c7;
      border-left: 4px solid #f59e0b;
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      color: #92400e;
      display: flex;
      gap: 10px;
    }
    
    .info-box i {
      flex-shrink: 0;
      margin-top: 2px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-label {
      display: block;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 8px;
      font-size: 14px;
    }
    
    .form-label .required {
      color: #ef4444;
      margin-left: 2px;
    }
    
    .form-input {
      width: 100%;
      padding: 10px 14px;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.2s ease;
    }
    
    .form-input:focus {
      outline: none;
      border-color: #10b981;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .file-upload-zone {
      border: 2px dashed #cbd5e1;
      border-radius: 8px;
      padding: 24px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .file-upload-zone:hover {
      border-color: #10b981;
      background: #f0fdf4;
    }
    
    .file-upload-zone.has-file {
      border-color: #10b981;
      background: #f0fdf4;
    }
    
    .file-icon {
      width: 48px;
      height: 48px;
      background: #e2e8f0;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 12px;
      color: #64748b;
    }
    
    .file-upload-zone.has-file .file-icon {
      background: #d1fae5;
      color: #10b981;
    }
    
    .file-instructions {
      font-size: 14px;
      color: #64748b;
      margin-bottom: 4px;
    }
    
    .file-name {
      font-size: 13px;
      color: #10b981;
      font-weight: 600;
      margin-top: 8px;
    }
    
    .file-hint {
      font-size: 12px;
      color: #94a3b8;
    }
    
    .modal-actions {
      padding: 16px 24px;
      border-top: 1px solid #e2e8f0;
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }
    
    .btn {
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    
    .btn-cancel {
      background: #f1f5f9;
      color: #64748b;
    }
    
    .btn-cancel:hover {
      background: #e2e8f0;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }
    
    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .btn-primary:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    .btn-primary.loading {
      position: relative;
      color: transparent;
    }
    
    .btn-primary.loading::after {
      content: '';
      position: absolute;
      width: 16px;
      height: 16px;
      top: 50%;
      left: 50%;
      margin-left: -8px;
      margin-top: -8px;
      border: 2px solid white;
      border-radius: 50%;
      border-top-color: transparent;
      animation: spin 0.6s linear infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .error-message {
      background: #fee2e2;
      color: #991b1b;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 13px;
      margin-top: 16px;
      display: none;
    }
    
    .error-message.show {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    /* Responsive Design */
    
    /* Large Desktop (1900px+) */
    @media (min-width: 1900px) {
      .kanban-container {
        padding: 20px;
      }
      
      .kanban-board {
        grid-auto-columns: minmax(300px, 1fr);
        gap: 14px;
      }
      
      .kanban-column {
        height: calc(100vh - 140px);
      }
      
      .column-header {
        padding: 16px;
      }
      
      .column-cards {
        padding: 12px;
      }
      
      .deal-card {
        padding: 16px;
        margin-bottom: 12px;
      }
      
      .deal-title {
        font-size: 15px;
      }
      
      .deal-value {
        font-size: 18px;
      }
      
      .deal-meta {
        font-size: 13px;
      }
    }
    
    /* Standard Desktop (1400px - 1900px) */
    @media (max-width: 1899px) and (min-width: 1400px) {
      .kanban-container {
        padding: 18px;
      }
      
      .kanban-board {
        grid-auto-columns: minmax(270px, 1fr);
        gap: 12px;
      }
      
      .column-header {
        padding: 15px;
      }
      
      .column-cards {
        padding: 11px;
      }
      
      .deal-card {
        padding: 15px;
        margin-bottom: 11px;
      }
      
      .deal-title {
        font-size: 14px;
      }
      
      .deal-value {
        font-size: 17px;
      }
      
      .deal-meta {
        font-size: 12.5px;
      }
    }
    
    /* Laptop (1024px - 1400px) */
    @media (max-width: 1399px) and (min-width: 1024px) {
      .kanban-container {
        padding: 16px;
      }
      
      .kanban-board {
        grid-auto-columns: minmax(250px, 1fr);
        gap: 12px;
      }
      
      .column-header {
        padding: 14px;
      }
      
      .column-cards {
        padding: 10px;
      }
      
      .deal-card {
        padding: 14px;
        margin-bottom: 10px;
      }
      
      .deal-title {
        font-size: 13.5px;
        margin-bottom: 7px;
      }
      
      .deal-value {
        font-size: 16px;
        margin-bottom: 10px;
      }
      
      .deal-meta {
        font-size: 12px;
      }
      
      .column-total {
        font-size: 17px;
        margin-top: 3px;
      }
    }
    
    /* Tablet Landscape (768px - 1024px) */
    @media (max-width: 1023px) and (min-width: 768px) {
      .kanban-container {
        padding: 14px;
        height: calc(100vh - 50px);
      }
      
      .kanban-board {
        grid-auto-columns: minmax(230px, 1fr);
        gap: 11px;
      }
      
      .kanban-column {
        height: calc(100vh - 120px);
      }
      
      .column-header {
        padding: 13px;
      }
      
      .column-cards {
        padding: 9px;
      }
      
      .deal-card {
        padding: 13px;
        margin-bottom: 9px;
      }
      
      .deal-title {
        font-size: 13px;
        margin-bottom: 6px;
      }
      
      .deal-value {
        font-size: 15px;
        margin-bottom: 9px;
      }
      
      .deal-meta {
        font-size: 11.5px;
        gap: 5px;
      }
      
      .column-title h3 {
        font-size: 13px;
      }
      
      .column-count {
        font-size: 11px;
        padding: 2px 7px;
      }
      
      .column-total {
        font-size: 16px;
        margin-top: 3px;
      }
    }
    
    /* Mobile Landscape / Small Tablet (480px - 768px) */
    @media (max-width: 767px) and (min-width: 480px) {
      .kanban-container {
        padding: 12px;
        height: calc(100vh - 40px);
      }
      
      .kanban-board {
        grid-auto-columns: minmax(200px, 1fr);
        gap: 10px;
      }
      
      .kanban-column {
        height: calc(100vh - 100px);
      }
      
      .column-header {
        padding: 12px;
      }
      
      .column-cards {
        padding: 8px;
      }
      
      .deal-card {
        padding: 12px;
        margin-bottom: 8px;
      }
      
      .deal-title {
        font-size: 12.5px;
        margin-bottom: 5px;
      }
      
      .deal-value {
        font-size: 14px;
        margin-bottom: 8px;
      }
      
      .deal-meta {
        font-size: 11px;
        gap: 4px;
      }
      
      .column-title h3 {
        font-size: 12px;
      }
      
      .column-count {
        font-size: 10px;
        padding: 2px 6px;
      }
      
      .column-total {
        font-size: 15px;
        margin-top: 2px;
      }
    }
    
    /* Mobile Portrait (< 480px) */
    @media (max-width: 479px) {
      .kanban-container {
        padding: 10px;
        height: calc(100vh - 30px);
      }
      
      .kanban-board {
        grid-auto-columns: minmax(180px, 1fr);
        gap: 8px;
      }
      
      .kanban-column {
        height: calc(100vh - 80px);
      }
      
      .column-header {
        padding: 11px;
      }
      
      .column-cards {
        padding: 7px;
      }
      
      .deal-card {
        padding: 11px;
        margin-bottom: 7px;
      }
      
      .deal-title {
        font-size: 12px;
        margin-bottom: 4px;
      }
      
      .deal-value {
        font-size: 13px;
        margin-bottom: 7px;
      }
      
      .deal-meta {
        font-size: 10px;
        gap: 3px;
      }
      
      .column-title h3 {
        font-size: 11px;
      }
      
      .column-count {
        font-size: 9px;
        padding: 1px 5px;
      }
      
      .column-total {
        font-size: 14px;
        margin-top: 2px;
      }
    }
    .crm-toolbar {
      background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
      border-bottom: 2px solid #3b82f6;
      box-shadow: 0 2px 6px rgba(59, 130, 246, 0.15);
      height: 42px;
      margin: -24px -24px 24px -24px;
    }
    
    .crm-toolbar-lining {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 100%;
      padding: 0 1rem;
      max-width: 100%;
    }
    
    .crm-toolbar-menu {
      display: flex;
      align-items: center;
      gap: 0;
      height: 100%;
    }
    
    .crm-toolbar-brand {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 0 16px;
      font-size: 14px;
      font-weight: 600;
      color: #333;
      text-decoration: none;
      height: 100%;
      border-right: 1px solid #e5e7eb;
    }
    
    .crm-toolbar-brand:hover {
      background: rgba(0, 0, 0, 0.03);
      color: #0969da;
    }
    
    .crm-toolbar-item {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 0 14px;
      height: 100%;
      font-size: 13px;
      font-weight: 500;
      color: #4b5563;
      text-decoration: none;
      border-right: 1px solid #f3f4f6;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    
    .crm-toolbar-item:hover {
      background: rgba(59, 130, 246, 0.08);
      color: #2563eb;
    }
    
    .crm-toolbar-item.active {
      background: linear-gradient(180deg, rgba(255,255,255,0.8) 0%, rgba(243,244,246,0.9) 100%);
      color: #1e40af;
      font-weight: 600;
      border-left: 1px solid #e5e7eb;
    }
    
    .crm-toolbar-item svg {
      width: 16px;
      height: 16px;
      stroke-width: 2;
    }
    
    .crm-toolbar-actions {
      display: flex;
      align-items: center;
      gap: 0;
      height: 100%;
    }
    
    .crm-toolbar-btn {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 0 12px;
      height: 100%;
      font-size: 13px;
      font-weight: 600;
      color: #ffffff;
      text-decoration: none;
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
      border-left: 1px solid rgba(255,255,255,0.1);
    }
    
    .crm-toolbar-btn:hover {
      background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.1);
    }
    
    .crm-toolbar-btn:active {
      transform: translateY(0);
    }
    
    .crm-toolbar-btn svg {
      width: 16px;
      height: 16px;
      stroke-width: 2.5;
    }
  </style>
  
  <!-- Deal Closing Modal -->
  <div class="deal-modal-overlay" id="dealClosingModal">
    <div class="deal-modal">
      <div class="modal-header">
        <div class="modal-icon">
          <i data-lucide="trophy" width="24" height="24"></i>
        </div>
        <h2>Chốt Deal Thành Công</h2>
      </div>
      <div class="modal-body">
        <div class="info-box">
          <i data-lucide="info" width="18" height="18"></i>
          <div>Vui lòng nhập đầy đủ thông tin để hoàn tất việc chốt deal. Email thông báo sẽ được gửi đến quản lý.</div>
        </div>
        
        <form id="dealClosingForm">
          <input type="hidden" name="deal_id" id="modalDealId">
          <input type="hidden" name="stage_id" value="closed_won">
          
          <div class="form-group">
            <label class="form-label">
              <i data-lucide="calendar-check" width="16" height="16" style="vertical-align: middle;"></i>
              Ngày chốt deal <span class="required">*</span>
            </label>
            <input type="date" name="closing_date" class="form-input" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">
              <i data-lucide="file-text" width="16" height="16" style="vertical-align: middle;"></i>
              Hợp đồng đính kèm <span style="color: #94a3b8; font-size: 13px;">(tùy chọn)</span>
            </label>
            <div class="file-upload-zone" id="fileUploadZone" onclick="document.getElementById('contractFile').click()">
              <div class="file-icon">
                <i data-lucide="upload" width="24" height="24"></i>
              </div>
              <div class="file-instructions">Click để chọn file hợp đồng (không bắt buộc)</div>
              <div class="file-hint">PDF, DOC, DOCX (tối đa 10MB)</div>
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
        <button type="button" class="btn btn-cancel" onclick="closeDealModal()">
          <i data-lucide="x" width="16" height="16"></i>
          Hủy
        </button>
        <button type="button" class="btn btn-primary" onclick="submitDealClosing()">
          <i data-lucide="check" width="16" height="16"></i>
          Xác nhận chốt deal
        </button>
      </div>
    </div>
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
    
    // Quick Add dropdown toggle
    const quickAddToggle = document.getElementById('crm-quick-add-toggle');
    const quickAddMenu = document.getElementById('crm-quick-add-menu');
    
    if (quickAddToggle && quickAddMenu) {
      quickAddToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        quickAddMenu.classList.toggle('active');
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', function(e) {
        if (!quickAddToggle.contains(e.target) && !quickAddMenu.contains(e.target)) {
          quickAddMenu.classList.remove('active');
        }
      });
    }
    
    // Variables for reverting card movement
    let pendingMove = null;
    
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
        errorText.textContent = 'Vui lòng chọn ngày chốt deal';
        errorMsg.classList.add('show');
        lucide.createIcons();
        return;
      }
      
      // File size validation (only if file is selected)
      if (contractFile && contractFile.size > 10 * 1024 * 1024) {
        errorText.textContent = 'File vượt quá 10MB. Vui lòng chọn file nhỏ hơn.';
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
        
        const response = await fetch('/crm/pipeline/update-stage', {
          method: 'POST',
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
          successDiv.innerHTML = '<i data-lucide="check-circle" width="20" height="20"></i><span>✅ Đã chốt deal thành công!</span>';
          document.body.appendChild(successDiv);
          lucide.createIcons();
          
          setTimeout(() => {
            // Redirect to dashboard to see updated stats
            window.location.href = '/crm/dashboard';
          }, 1500);
        } else {
          errorText.textContent = result.message || 'Có lỗi xảy ra. Vui lòng thử lại.';
          errorMsg.classList.add('show');
          lucide.createIcons();
          submitBtn.classList.remove('loading');
          submitBtn.disabled = false;
        }
      } catch (error) {
        console.error('Error:', error);
        errorText.textContent = 'Có lỗi xảy ra. Vui lòng thử lại.';
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
    
    // Validate and map stage value (accept both numeric term IDs and string values)
    $valid_stages = ['qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];
    $stage_mapping = [1 => 'qualified', 2 => 'proposal', 3 => 'negotiation', 5 => 'closed_won', 6 => 'closed_lost'];
    
    // Convert numeric term ID to string value if needed
    if (is_numeric($stage_id)) {
      if (!isset($stage_mapping[$stage_id])) {
        return new JsonResponse(['success' => FALSE, 'message' => 'Invalid stage ID: ' . $stage_id], 400);
      }
      $stage_id = $stage_mapping[$stage_id];
    }
    
    // Validate string stage value
    if (!in_array($stage_id, $valid_stages)) {
      return new JsonResponse(['success' => FALSE, 'message' => 'Invalid stage value: ' . $stage_id], 400);
    }
    
    try {
      $deal = \Drupal::entityTypeManager()->getStorage('node')->load($deal_id);
      
      if (!$deal || $deal->bundle() !== 'deal') {
        return new JsonResponse(['success' => FALSE, 'message' => 'Deal not found'], 404);
      }
      
      // If moving to Won (closed_won)
      if ($stage_id === 'closed_won') {
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
      
      // Update stage (keep as string value for consistency)
      if ($deal->hasField('field_stage')) {
        $deal->set('field_stage', $stage_id);
      }
      $deal->save();
      
      // Clear entity cache so dashboard shows updated data
      \Drupal::entityTypeManager()->getStorage('node')->resetCache([$deal_id]);
      \Drupal\Core\Cache\Cache::invalidateTags(['node:' . $deal_id]);
      
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
