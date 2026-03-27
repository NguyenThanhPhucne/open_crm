<?php

namespace Drupal\crm_teams\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Teams Management.
 */
class TeamsManagementController extends ControllerBase {

  /**
   * Team Management page with professional UI.
   */
  public function manageTeams() {
    // Get all teams
    $teams = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'crm_team']);
    
    $teams_data = [];
    foreach ($teams as $team) {
      // Count users in this team
      $user_count = \Drupal::entityQuery('user')
        ->condition('field_team', $team->id())
        ->accessCheck(FALSE)
        ->count()
        ->execute();
      
      $teams_data[] = [
        'id' => $team->id(),
        'name' => $team->getName(),
        'description' => $team->getDescription(),
        'user_count' => $user_count,
      ];
    }
    
    // Get all users with their teams
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['status' => 1]);
    
    $users_data = [];
    foreach ($users as $user) {
      if ($user->id() == 0 || $user->id() == 1) {
        continue; // Skip anonymous and admin
      }
      
      $team_name = 'No Team';
      $team_id = NULL;
      
      if ($user->hasField('field_team') && !$user->get('field_team')->isEmpty()) {
        $team = $user->get('field_team')->entity;
        if ($team) {
          $team_name = $team->getName();
          $team_id = $team->id();
        }
      }
      
      // Get user roles
      $roles = $user->getRoles();
      $roles_display = array_filter($roles, function($role) {
        return $role !== 'authenticated';
      });
      
      $users_data[] = [
        'id' => $user->id(),
        'name' => $user->getDisplayName(),
        'email' => $user->getEmail(),
        'team' => $team_name,
        'team_id' => $team_id,
        'roles' => implode(', ', $roles_display),
      ];
    }
    
    // Build HTML with professional UI
    $html = $this->buildTeamsUI($teams_data, $users_data);
    
    return new Response($html);
  }
  
  /**
   * Build professional Teams Management UI.
   */
  private function buildTeamsUI($teams_data, $users_data) {
    $teams_json = json_encode($teams_data);
    $users_json = json_encode($users_data);
    
    $csrf_token = \Drupal::csrfToken()->get('crm_teams_assign');
    
    // Build team options HTML
    $team_options_html = '<option value="">-- No Team --</option>';
    foreach ($teams_data as $team) {
      $team_options_html .= '<option value="' . $team['id'] . '">' . htmlspecialchars($team['name']) . '</option>';
    }
    
    // Build team filter options
    $team_filter_options = '<option value="">All Teams</option><option value="no-team">No Team</option>';
    foreach ($teams_data as $team) {
      $team_filter_options .= '<option value="' . $team['id'] . '">' . htmlspecialchars($team['name']) . '</option>';
    }
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Team Management - CRM</title>
  <link rel="icon" type="image/x-icon" href="/core/misc/favicon.ico">
  <script src="https://unpkg.com/lucide@latest"></script>
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
    
    /* Global Navigation */
    .crm-global-nav {
      background: white;
      border-bottom: 1px solid #e2e8f0;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    
    .crm-nav-container {
      max-width: 1400px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      padding: 0 24px;
      height: 60px;
      gap: 32px;
    }
    
    .crm-nav-brand {
      font-size: 18px;
      font-weight: 700;
      color: #1e293b;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.2s;
    }
    
    .crm-nav-brand:hover {
      color: #3b82f6;
    }
    
    .crm-nav-brand i {
      color: #3b82f6;
    }
    
    .crm-nav-items {
      display: flex;
      align-items: center;
      gap: 4px;
      flex: 1;
    }
    
    .crm-nav-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      border-radius: 8px;
      border: 1px solid transparent;
      color: #64748b;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s;
    }
    
    .crm-nav-item:hover {
      background: #eff6ff;
      color: #3b82f6;
      border-color: #3b82f6;
      text-decoration: none;
    }
    
    .crm-nav-item.active {
      background: white;
      color: #3b82f6;
      border: 1.5px solid #3b82f6;
      font-weight: 600;
      box-shadow: 0 1px 2px rgba(59, 130, 246, 0.1);
    }
    
    .crm-nav-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 32px 24px;
      animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .page-header {
      margin-bottom: 32px;
    }
    
    .page-header h1 {
      font-size: 32px;
      font-weight: 700;
      color: #0f172a;
      margin: 0 0 8px 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .page-header h1 i {
      color: #3b82f6;
    }
    
    .page-header p {
      color: #64748b;
      font-size: 15px;
      margin: 0;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 32px;
    }
    
    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    
    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 12px;
    }
    
    .stat-icon.blue {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }
    
    .stat-icon.green {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }
    
    .stat-icon.purple {
      background: linear-gradient(135deg, #8b5cf6, #7c3aed);
      color: white;
    }
    
    .stat-label {
      font-size: 13px;
      color: #64748b;
      margin-bottom: 4px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .stat-value {
      font-size: 28px;
      font-weight: 700;
      color: #0f172a;
    }
    
    .content-grid {
      display: grid;
      grid-template-columns: 380px 1fr;
      gap: 24px;
      margin-bottom: 32px;
    }
    
    .card {
      background: white;
      border-radius: 12px;
      padding: 24px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    
    .card-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
      padding-bottom: 16px;
      border-bottom: 1px solid #f1f5f9;
    }
    
    .card-title {
      font-size: 18px;
      font-weight: 600;
      color: #0f172a;
      flex: 1;
    }
    
    .card-title i {
      color: #3b82f6;
    }
    
    .team-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    .team-item {
      padding: 16px;
      border-radius: 10px;
      background: #f8fafc;
      border: 2px solid transparent;
      transition: all 0.2s;
      cursor: pointer;
    }
    
    .team-item:hover {
      border-color: #3b82f6;
      background: #eff6ff;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }
    
    .team-item.selected {
      border-color: #3b82f6;
      background: linear-gradient(135deg, #eff6ff, #dbeafe);
    }
    
    .team-name {
      font-size: 15px;
      font-weight: 600;
      color: #0f172a;
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .team-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .team-desc {
      font-size: 13px;
      color: #64748b;
      margin-bottom: 8px;
      line-height: 1.5;
    }
    
    .team-stats {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      color: #3b82f6;
      font-weight: 500;
    }
    
    .users-table-container {
      overflow-x: auto;
      border-radius: 8px;
    }
    
    /* User Cards Grid Layout */
    .users-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 20px;
    }
    
    .user-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      border: 2px solid #e2e8f0;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }
    
    .user-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #3b82f6, #8b5cf6);
      opacity: 0;
      transition: opacity 0.3s;
    }
    
    .user-card:hover {
      border-color: #3b82f6;
      box-shadow: 0 10px 30px rgba(59, 130, 246, 0.15);
      transform: translateY(-4px);
    }
    
    .user-card:hover::before {
      opacity: 1;
    }
    
    .user-card-header {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 16px;
      padding-bottom: 16px;
      border-bottom: 1px solid #f1f5f9;
    }
    
    .user-avatar-large {
      width: 56px;
      height: 56px;
      border-radius: 14px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 20px;
      flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
      position: relative;
    }
    
    .user-avatar-large::after {
      content: '';
      position: absolute;
      inset: -2px;
      border-radius: 15px;
      padding: 2px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
      mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
      -webkit-mask-composite: xor;
      mask-composite: exclude;
      opacity: 0;
      transition: opacity 0.3s;
    }
    
    .user-card:hover .user-avatar-large::after {
      opacity: 1;
    }
    
    .user-info {
      flex: 1;
      min-width: 0;
    }
    
    .user-name-text {
      font-size: 16px;
      font-weight: 600;
      color: #0f172a;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .verified-badge {
      display: inline-flex;
      width: 16px;
      height: 16px;
      background: linear-gradient(135deg, #10b981, #059669);
      border-radius: 50%;
      color: white;
      align-items: center;
      justify-content: center;
    }
    
    .user-email-text {
      color: #64748b;
      font-size: 13px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    
    .user-card-body {
      margin-bottom: 16px;
    }
    
    .user-detail-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      background: #f8fafc;
      border-radius: 8px;
      margin-bottom: 8px;
      transition: all 0.2s;
    }
    
    .user-detail-row:hover {
      background: #f1f5f9;
    }
    
    .user-detail-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    
    .user-detail-icon.team-icon {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: #1e40af;
    }
    
    .user-detail-icon.role-icon {
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: #92400e;
    }
    
    .user-detail-content {
      flex: 1;
      min-width: 0;
    }
    
    .user-detail-label {
      font-size: 11px;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
      margin-bottom: 2px;
    }
    
    .user-detail-value {
      font-size: 13px;
      font-weight: 600;
      color: #0f172a;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    .team-badge-card {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: #1e40af;
    }
    
    .team-badge-card.no-team {
      background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
      color: #475569;
    }
    
    .user-card-footer {
      display: flex;
      gap: 8px;
    }
    
    .team-select-card {
      flex: 1;
      padding: 10px 14px;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 13px;
      background: white;
      cursor: pointer;
      transition: all 0.2s;
      font-weight: 500;
      color: #1e293b;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 36px;
    }
    
    .team-select-card:hover {
      border-color: #3b82f6;
      background-color: #f8fafc;
    }
    
    .team-select-card:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }
    
    .save-btn-card {
      padding: 10px 18px;
      background: #fff;
      color: #2563eb;
      border: 1.5px solid #2563eb;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: background .15s, border-color .15s, color .15s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
    }
    
    .save-btn-card:hover {
      background: #eff6ff;
      color: #1d4ed8;
      border-color: #1d4ed8;
    }
    
    .save-btn-card:active {
      background: #dbeafe;
    }
    
    .save-btn-card:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    .filter-bar {
      background: white;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 24px;
      border: 1px solid #e2e8f0;
      display: flex;
      gap: 16px;
      align-items: center;
      flex-wrap: wrap;
    }
    
    .search-box {
      flex: 1;
      min-width: 250px;
      position: relative;
    }
    
    .search-input {
      width: 100%;
      padding: 10px 14px 10px 42px;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 14px;
      transition: all 0.2s;
    }
    
    .search-input:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .search-clear-btn {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      width: 20px;
      height: 20px;
      display: none;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #94a3b8;
      background: none;
      border: none;
      padding: 0;
      border-radius: 50%;
      font-size: 15px;
      line-height: 1;
      transition: color .15s, background .15s;
    }
    .search-clear-btn.visible { display: flex; }
    .search-clear-btn:hover { color: #ef4444; background: rgba(239,68,68,.08); }
    @keyframes rowMatchIn{from{opacity:0;transform:translateX(-4px)}to{opacity:1;transform:translateX(0)}}
    .row-just-shown { animation: rowMatchIn .2s cubic-bezier(.4,0,.2,1); }
    
    .search-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
    }
    
    .filter-select {
      padding: 10px 14px;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      background: white;
    }
    
    .filter-select:hover {
      border-color: #3b82f6;
    }
    
    .filter-select:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }
    
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 60px 20px;
      color: #94a3b8;
    }
    
    .empty-state i {
      margin-bottom: 16px;
      opacity: 0.5;
    }
    
    .empty-state-title {
      font-size: 18px;
      font-weight: 600;
      color: #64748b;
      margin-bottom: 8px;
    }
    
    .empty-state-text {
      font-size: 14px;
    }
    
    .view-toggle {
      display: flex;
      gap: 8px;
      background: #f1f5f9;
      padding: 4px;
      border-radius: 10px;
    }
    
    .view-toggle-btn {
      padding: 8px 14px;
      border: none;
      background: transparent;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 13px;
      font-weight: 500;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .view-toggle-btn.active {
      background: white;
      color: #3b82f6;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .team-select {
      padding: 8px 12px;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      font-size: 13px;
      background: white;
      cursor: pointer;
      transition: all 0.2s;
      font-weight: 500;
      color: #1e293b;
    }
    
    .team-select:hover {
      border-color: #3b82f6;
      background: #f8fafc;
    }
    
    .team-select:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .save-btn {
      padding: 8px 16px;
      background: #fff;
      color: #2563eb;
      border: 1.5px solid #2563eb;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: background .15s, border-color .15s, color .15s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    
    .save-btn:hover {
      background: #eff6ff;
      color: #1d4ed8;
      border-color: #1d4ed8;
    }
    
    .save-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    .role-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: #92400e;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    
    /* Table styles for list view - Professional & Modern */
    .users-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }
    
    .users-table thead tr {
      background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    }
    
    .users-table th {
      padding: 16px 18px;
      text-align: left;
      font-size: 11px;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      border-bottom: 2px solid #e2e8f0;
      position: sticky;
      top: 0;
      background: linear-gradient(135deg, #f8fafc, #f1f5f9);
      z-index: 10;
    }
    
    .users-table th:first-child {
      border-radius: 12px 0 0 0;
      padding-left: 24px;
    }
    
    .users-table th:last-child {
      border-radius: 0 12px 0 0;
      padding-right: 24px;
    }
    
    .users-table td {
      padding: 18px;
      border-bottom: 1px solid #f1f5f9;
      font-size: 14px;
      color: #1e293b;
      vertical-align: middle;
      background: white;
    }
    
    .users-table td:first-child {
      padding-left: 24px;
    }
    
    .users-table td:last-child {
      padding-right: 24px;
    }
    
    .users-table tbody tr {
      transition: all 0.2s;
      position: relative;
    }
    
    .users-table tbody tr::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 3px;
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      opacity: 0;
      transition: opacity 0.2s;
    }
    
    .users-table tbody tr:hover {
      background: linear-gradient(135deg, #faf5ff, #f5f3ff) !important;
      transform: translateX(2px);
      box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
    }
    
    .users-table tbody tr:hover::before {
      opacity: 1;
    }
    
    .users-table tbody tr:hover td {
      background: transparent;
    }
    
    .user-cell {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    
    .user-avatar-table {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 15px;
      color: white;
      flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      position: relative;
    }
    
    .user-avatar-table::after {
      content: '';
      position: absolute;
      inset: -2px;
      border-radius: 13px;
      padding: 2px;
      background: inherit;
      -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
      mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
      -webkit-mask-composite: xor;
      mask-composite: exclude;
      opacity: 0.3;
    }
    
    .user-info-table {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    
    .user-name-table {
      font-size: 15px;
      font-weight: 600;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .user-id-badge {
      display: inline-flex;
      align-items: center;
      padding: 2px 6px;
      background: linear-gradient(135deg, #e0e7ff, #dbeafe);
      color: #3b82f6;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.3px;
    }
    
    .email-cell {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #64748b;
      font-size: 13px;
    }
    
    .email-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: #2563eb;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    
    .team-cell {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .team-badge-table {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 14px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      transition: all 0.2s;
    }
    
    .team-badge-table.has-team {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: #1e40af;
      border: 2px solid #93c5fd;
    }
    
    .team-badge-table.no-team {
      background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
      color: #64748b;
      border: 2px solid #cbd5e1;
    }
    
    .role-cell {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .role-badge-table {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 14px;
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: #92400e;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border: 2px solid #fcd34d;
    }
    
    .action-cell {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    
    .team-select-table {
      flex: 1;
      padding: 10px 14px;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      font-size: 13px;
      background: white;
      cursor: pointer;
      transition: all 0.2s;
      font-weight: 500;
      color: #1e293b;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 36px;
      min-width: 180px;
    }
    
    .team-select-table:hover {
      border-color: #3b82f6;
      background-color: #f8fafc;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.05);
    }
    
    .team-select-table:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }
    
    .save-btn-table {
      padding: 10px 18px;
      background: #fff;
      color: #2563eb;
      border: 1.5px solid #2563eb;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: background .15s, border-color .15s, color .15s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
    }
    
    .save-btn-table:hover {
      background: #eff6ff;
      color: #1d4ed8;
      border-color: #1d4ed8;
    }
    
    .save-btn-table:active {
      background: #dbeafe;
    }
    
    .save-btn-table:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    .success-message {
      position: fixed;
      top: 24px;
      right: 24px;
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      padding: 16px 24px;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4);
      display: none;
      align-items: center;
      gap: 12px;
      font-size: 14px;
      font-weight: 500;
      z-index: 1000;
      animation: slideInRight 0.3s ease;
    }
    
    @keyframes slideInRight {
      from { transform: translateX(100px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    .success-message.active {
      display: flex;
    }
    
    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    
    .lucide-spin {
      animation: spin 1s linear infinite;
    }
    
    @media (max-width: 1200px) {
      .content-grid {
        grid-template-columns: 1fr;
      }
      
      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      }
      
      .users-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      }
    }
    
    @media (max-width: 768px) {
      .crm-nav-container {
        padding: 0 16px;
        gap: 16px;
      }
      
      .crm-nav-brand span {
        display: none;
      }
      
      .crm-nav-item span {
        display: none;
      }
      
      .container {
        padding: 24px 16px;
      }
      
      .page-header h1 {
        font-size: 24px;
      }
      
      .stats-grid {
        gap: 16px;
        grid-template-columns: 1fr;
      }
      
      .filter-bar {
        flex-direction: column;
        gap: 12px;
      }
      
      .search-box,
      .filter-select {
        width: 100%;
      }
      
      .users-grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }
      
      .user-card-footer {
        flex-direction: column;
      }
      
      .team-select-card,
      .save-btn-card {
        width: 100%;
        justify-content: center;
      }
      
      .view-toggle {
        width: 100%;
      }
      
      .view-toggle-btn {
        flex: 1;
        justify-content: center;
      }
      
      .card-header {
        flex-wrap: wrap;
      }
    }
  </style>
</head>
<body>
  <!-- Global Navigation -->
  <nav class="crm-global-nav">
    <div class="crm-nav-container">
      <a href="/crm/dashboard" class="crm-nav-brand">
        <i data-lucide="layout-dashboard" width="20" height="20"></i>
        <span>CRM System</span>
      </a>
      
      <div class="crm-nav-items">
        <a href="/crm/dashboard" class="crm-nav-item">
          <i data-lucide="layout-dashboard" width="18" height="18"></i>
          <span>Dashboard</span>
        </a>
        <a href="/crm/my-deals" class="crm-nav-item">
          <i data-lucide="trello" width="18" height="18"></i>
          <span>Deals</span>
        </a>
        <a href="/my-contacts" class="crm-nav-item">
          <i data-lucide="users" width="18" height="18"></i>
          <span>Contacts</span>
        </a>
        <a href="/admin/crm/teams" class="crm-nav-item active">
          <i data-lucide="shield" width="18" height="18"></i>
          <span>Teams</span>
        </a>
      </div>
      
      <div class="crm-nav-actions">
        <a href="/user/logout" class="crm-nav-item">
          <i data-lucide="log-out" width="18" height="18"></i>
          <span>Logout</span>
        </a>
      </div>
    </div>
  </nav>

  <div class="container">
    <div class="page-header">
      <h1>
        <i data-lucide="shield" width="32" height="32"></i>
        Team Management
      </h1>
      <p>Manage team assignments and CRM data access permissions</p>
    </div>
    
    <!-- Stats Overview -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">
          <i data-lucide="users" width="24" height="24"></i>
        </div>
        <div class="stat-label">Total Teams</div>
        <div class="stat-value" id="total-teams">0</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <i data-lucide="user-check" width="24" height="24"></i>
        </div>
        <div class="stat-label">Assigned Users</div>
        <div class="stat-value" id="assigned-users">0</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">
          <i data-lucide="user-x" width="24" height="24"></i>
        </div>
        <div class="stat-label">Unassigned Users</div>
        <div class="stat-value" id="unassigned-users">0</div>
      </div>
    </div>
    
    <div class="content-grid">
      <!-- Teams Sidebar -->
      <div class="card">
        <div class="card-header">
          <i data-lucide="shield" width="20" height="20"></i>
          <div class="card-title">Available Teams</div>
        </div>
        <div class="team-list" id="team-list">
          <!-- Teams will be rendered here -->
        </div>
      </div>
      
      <!-- Users Section -->
      <div class="card">
        <div class="card-header">
          <i data-lucide="user-check" width="20" height="20"></i>
          <div class="card-title">User Team Assignments</div>
          <div class="view-toggle">
            <button class="view-toggle-btn active" onclick="switchView('cards')">
              <i data-lucide="grid-3x3" width="16" height="16"></i>
              Cards
            </button>
            <button class="view-toggle-btn" onclick="switchView('list')">
              <i data-lucide="list" width="16" height="16"></i>
              List
            </button>
          </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
          <div class="search-box">
            <i data-lucide="search" width="18" height="18" class="search-icon"></i>
            <input type="text" class="search-input" id="search-users" placeholder="Search users by name or email..." />
            <button type="button" id="search-users-clear" class="search-clear-btn" title="Clear" aria-label="Clear">&#x2715;</button>
          </div>
          <select class="filter-select" id="filter-team">
            {$team_filter_options}
          </select>
          <select class="filter-select" id="filter-role">
            <option value="">All Roles</option>
            <option value="manager">Manager</option>
            <option value="sales_rep">Sales Rep</option>
            <option value="viewer">Viewer</option>
          </select>
        </div>
        
        <!-- Users Cards Grid -->
        <div class="users-grid" id="users-grid">
          <!-- User cards will be rendered here -->
        </div>
        
        <!-- Users Table (Hidden by default) -->
        <div class="users-table-container" id="users-table-container" style="display: none;">
          <table class="users-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Email</th>
                <th>Current Team</th>
                <th>Roles</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="users-tbody">
              <!-- Users will be rendered here -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <div class="success-message" id="success-message">
    <i data-lucide="check-circle" width="20" height="20"></i>
    <span>Team assigned successfully!</span>
  </div>
  
  <script>
    const teams = {$teams_json};
    const users = {$users_json};
    const csrfToken = '{$csrf_token}';
    
    // Calculate and display stats
    function updateStats() {
      const totalTeams = teams.length;
      const assignedUsers = users.filter(u => u.team !== 'No Team').length;
      const unassignedUsers = users.filter(u => u.team === 'No Team').length;
      
      document.getElementById('total-teams').textContent = totalTeams;
      document.getElementById('assigned-users').textContent = assignedUsers;
      document.getElementById('unassigned-users').textContent = unassignedUsers;
    }
    
    // Render teams
    function renderTeams() {
      const container = document.getElementById('team-list');
      
      if (teams.length === 0) {
        container.innerHTML = '<div style="text-align: center; padding: 40px; color: #94a3b8;"><i data-lucide="inbox" width="48" height="48" style="margin-bottom: 12px; opacity: 0.5;"></i><div>No teams available</div></div>';
        lucide.createIcons();
        return;
      }
      
      teams.forEach(team => {
        const div = document.createElement('div');
        div.className = 'team-item';
        div.innerHTML = `
          <div class="team-name">
            <span>\${team.name}</span>
            <span class="team-badge">
              <i data-lucide="users" width="12" height="12"></i>
              \${team.user_count}
            </span>
          </div>
          \${team.description ? '<div class="team-desc">' + team.description + '</div>' : ''}
          <div class="team-stats">
            <i data-lucide="user" width="14" height="14"></i>
            \${team.user_count} \${team.user_count === 1 ? 'member' : 'members'}
          </div>
        `;
        container.appendChild(div);
      });
      
      lucide.createIcons();
    }
    
    // Render users as cards
    function renderUsers() {
      const grid = document.getElementById('users-grid');
      const tbody = document.getElementById('users-tbody');
      
      if (users.length === 0) {
        grid.innerHTML = '<div class="empty-state"><i data-lucide="users" width="64" height="64"></i><div class="empty-state-title">No Users Found</div><div class="empty-state-text">There are no users in the system yet.</div></div>';
        lucide.createIcons();
        return;
      }
      
      // Render cards
      users.forEach(user => {
        const initials = user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        const hasTeam = user.team !== 'No Team';
        const teamClass = hasTeam ? '' : 'no-team';
        
        // Generate random gradient for avatar
        const gradients = [
          'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
          'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
          'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
          'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
          'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
          'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
        ];
        const gradient = gradients[user.id % gradients.length];
        
        const card = document.createElement('div');
        card.className = 'user-card';
        card.setAttribute('data-user-id', user.id);
        card.setAttribute('data-user-name', user.name.toLowerCase());
        card.setAttribute('data-user-email', user.email.toLowerCase());
        card.setAttribute('data-user-team', user.team_id || 'no-team');
        card.setAttribute('data-user-role', user.roles.toLowerCase());
        
        card.innerHTML = `
          <div class="user-card-header">
            <div class="user-avatar-large" style="background: \${gradient};">
              \${initials}
            </div>
            <div class="user-info">
              <div class="user-name-text">
                \${user.name}
                <span class="verified-badge">
                  <i data-lucide="check" width="10" height="10"></i>
                </span>
              </div>
              <div class="user-email-text">
                <i data-lucide="mail" width="12" height="12"></i>
                \${user.email}
              </div>
            </div>
          </div>
          
          <div class="user-card-body">
            <div class="user-detail-row">
              <div class="user-detail-icon team-icon">
                <i data-lucide="\${hasTeam ? 'shield-check' : 'shield-off'}" width="16" height="16"></i>
              </div>
              <div class="user-detail-content">
                <div class="user-detail-label">Current Team</div>
                <div class="user-detail-value">\${user.team}</div>
              </div>
            </div>
            
            <div class="user-detail-row">
              <div class="user-detail-icon role-icon">
                <i data-lucide="key" width="16" height="16"></i>
              </div>
              <div class="user-detail-content">
                <div class="user-detail-label">Role</div>
                <div class="user-detail-value">\${user.roles || 'user'}</div>
              </div>
            </div>
          </div>
          
          <div class="user-card-footer">
            <select class="team-select-card" data-user-id="\${user.id}" data-current-team="\${user.team_id || ''}">
              {$team_options_html}
            </select>
            <button class="save-btn-card" onclick="assignTeam(\${user.id}, this)">
              <i data-lucide="save" width="14" height="14"></i>
              Save
            </button>
          </div>
        `;
        
        grid.appendChild(card);
        
        // Set current team selected
        const select = card.querySelector('.team-select-card');
        if (user.team_id) {
          select.value = user.team_id;
        }
      });
      
      // Also render table rows for list view
      renderTableRows();
      
      lucide.createIcons();
    }
    
    // Render table rows for list view - Professional & Modern
    function renderTableRows() {
      const tbody = document.getElementById('users-tbody');
      tbody.innerHTML = '';
      
      users.forEach(user => {
        const tr = document.createElement('tr');
        const initials = user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        const hasTeam = user.team !== 'No Team';
        const teamClass = hasTeam ? 'has-team' : 'no-team';
        
        // Generate random gradient for avatar (same as cards)
        const gradients = [
          'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
          'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
          'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
          'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
          'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
          'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
        ];
        const gradient = gradients[user.id % gradients.length];
        
        tr.setAttribute('data-user-id', user.id);
        tr.setAttribute('data-user-name', user.name.toLowerCase());
        tr.setAttribute('data-user-email', user.email.toLowerCase());
        tr.setAttribute('data-user-team', user.team_id || 'no-team');
        tr.setAttribute('data-user-role', user.roles.toLowerCase());
        
        tr.innerHTML = `
          <td>
            <div class="user-cell">
              <div class="user-avatar-table" style="background: \${gradient};">
                \${initials}
              </div>
              <div class="user-info-table">
                <div class="user-name-table">
                  \${user.name}
                  <span class="user-id-badge">#\${user.id}</span>
                </div>
              </div>
            </div>
          </td>
          <td>
            <div class="email-cell">
              <div class="email-icon">
                <i data-lucide="mail" width="16" height="16"></i>
              </div>
              <span>\${user.email}</span>
            </div>
          </td>
          <td>
            <div class="team-cell">
              <span class="team-badge-table \${teamClass}">
                <i data-lucide="\${hasTeam ? 'shield-check' : 'shield-off'}" width="14" height="14"></i>
                \${user.team}
              </span>
            </div>
          </td>
          <td>
            <div class="role-cell">
              <span class="role-badge-table">
                <i data-lucide="key" width="12" height="12"></i>
                \${user.roles || 'user'}
              </span>
            </div>
          </td>
          <td>
            <div class="action-cell">
              <select class="team-select-table" data-user-id="\${user.id}" data-current-team="\${user.team_id || ''}">
                {$team_options_html}
              </select>
              <button class="save-btn-table" onclick="assignTeam(\${user.id}, this)">
                <i data-lucide="save" width="14" height="14"></i>
                Save
              </button>
            </div>
          </td>
        `;
        
        tbody.appendChild(tr);
        
        // Set current team selected
        const select = tr.querySelector('.team-select-table');
        if (user.team_id) {
          select.value = user.team_id;
        }
      });
      
      lucide.createIcons();
    }
    
    // Switch between card and list view
    function switchView(view) {
      const grid = document.getElementById('users-grid');
      const tableContainer = document.getElementById('users-table-container');
      const buttons = document.querySelectorAll('.view-toggle-btn');
      
      buttons.forEach(btn => {
        btn.classList.remove('active');
        if ((view === 'cards' && btn.textContent.includes('Cards')) || 
            (view === 'list' && btn.textContent.includes('List'))) {
          btn.classList.add('active');
        }
      });
      
      if (view === 'cards') {
        grid.style.display = 'grid';
        tableContainer.style.display = 'none';
      } else {
        grid.style.display = 'none';
        tableContainer.style.display = 'block';
      }
    }
    
    // Search and filter functionality
    function setupFilters() {
      const searchInput = document.getElementById('search-users');
      const searchClear = document.getElementById('search-users-clear');
      const teamFilter = document.getElementById('filter-team');
      const roleFilter = document.getElementById('filter-role');

      // Prefix-match: true if any word in text starts with query.
      function crmWordMatch(text, q) {
        if (!q) return true;
        return text.includes(q);
      }

      function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const teamValue = teamFilter.value;
        const roleValue = roleFilter.value.toLowerCase();
        if (searchClear) searchClear.classList.toggle('visible', searchTerm.length > 0);

        let visibleCount = 0;

        // Filter cards
        const cards = document.querySelectorAll('.user-card');
        cards.forEach(card => {
          const name = card.getAttribute('data-user-name') || '';
          const email = card.getAttribute('data-user-email') || '';
          const team = card.getAttribute('data-user-team') || '';
          const role = card.getAttribute('data-user-role') || '';

          const matchesSearch = !searchTerm || crmWordMatch(name, searchTerm) || crmWordMatch(email, searchTerm);
          const matchesTeam = !teamValue || team === teamValue;
          const matchesRole = !roleValue || role.includes(roleValue);

          const isVisible = matchesSearch && matchesTeam && matchesRole;
          const wasHidden = card.style.display === 'none';
          card.style.display = isVisible ? 'block' : 'none';
          if (isVisible) {
            visibleCount++;
            if (wasHidden && searchTerm) {
              card.classList.remove('row-just-shown');
              void card.offsetWidth;
              card.classList.add('row-just-shown');
            }
          }
        });

        // Filter table rows
        const rows = document.querySelectorAll('#users-tbody tr');
        rows.forEach(row => {
          const name = row.getAttribute('data-user-name') || '';
          const email = row.getAttribute('data-user-email') || '';
          const team = row.getAttribute('data-user-team') || '';
          const role = row.getAttribute('data-user-role') || '';

          const matchesSearch = !searchTerm || crmWordMatch(name, searchTerm) || crmWordMatch(email, searchTerm);
          const matchesTeam = !teamValue || team === teamValue;
          const matchesRole = !roleValue || role.includes(roleValue);

          const isRowVisible = matchesSearch && matchesTeam && matchesRole;
          const wasRowHidden = row.style.display === 'none';
          row.style.display = isRowVisible ? '' : 'none';
          if (isRowVisible && wasRowHidden && searchTerm) {
            row.classList.remove('row-just-shown');
            void row.offsetWidth;
            row.classList.add('row-just-shown');
          }
        });
        
        // Show empty state if no results
        const grid = document.getElementById('users-grid');
        let emptyState = grid.querySelector('.empty-state');
        
        if (visibleCount === 0 && (searchTerm || teamValue || roleValue)) {
          if (!emptyState) {
            emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.innerHTML = `
              <i data-lucide="search-x" width="64" height="64"></i>
              <div class="empty-state-title">No Results Found</div>
              <div class="empty-state-text">Try adjusting your search or filter criteria.</div>
            `;
            grid.appendChild(emptyState);
            lucide.createIcons();
          }
          emptyState.style.display = 'block';
        } else if (emptyState) {
          emptyState.style.display = 'none';
        }
      }
      
      searchInput.addEventListener('input', applyFilters);
      teamFilter.addEventListener('change', applyFilters);
      roleFilter.addEventListener('change', applyFilters);
      if (searchClear) {
        searchClear.addEventListener('click', function() {
          searchInput.value = '';
          this.classList.remove('visible');
          applyFilters();
          searchInput.focus();
        });
      }
    }
    
    // Assign team to user
    async function assignTeam(userId, button) {
      // Find select element (works for both card and list views)
      const container = button.closest('.user-card-footer') || button.closest('td');
      if (!container) {
        console.error('Container not found');
        return;
      }
      
      const select = container.querySelector('.team-select-card') || container.querySelector('.team-select');
      if (!select) {
        console.error('Select element not found');
        return;
      }
      
      const teamId = select.value;
      const originalButtonHTML = button.innerHTML;
      
      // Disable button and show loading state
      button.disabled = true;
      button.style.opacity = '0.6';
      button.innerHTML = '<i data-lucide="loader" width="14" height="14" class="lucide-spin"></i> Saving...';
      lucide.createIcons();
      
      try {
        const response = await fetch('/admin/crm/teams/assign', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            user_id: userId,
            team_id: teamId || null,
            csrf_token: csrfToken,
          }),
        });
        
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        
        const result = await response.json();
        
        if (result.success) {
          showSuccess('Team assigned successfully!');
          // Reload after short delay to show success message
          setTimeout(() => location.reload(), 1200);
        } else {
          showError(result.message || 'Failed to assign team');
          button.disabled = false;
          button.style.opacity = '1';
          button.innerHTML = originalButtonHTML;
          lucide.createIcons();
        }
      } catch (error) {
        console.error('Error:', error);
        showError('Network error occurred. Please try again.');
        button.disabled = false;
        button.style.opacity = '1';
        button.innerHTML = originalButtonHTML;
        lucide.createIcons();
      }
    }
    
    function showSuccess(message) {
      const msg = document.getElementById('success-message');
      const span = msg.querySelector('span');
      if (span && message) {
        span.textContent = message;
      }
      msg.classList.add('active');
      setTimeout(() => msg.classList.remove('active'), 3000);
    }
    
    function showError(message) {
      // Create error notification if it doesn't exist
      let errorMsg = document.getElementById('error-message');
      if (!errorMsg) {
        errorMsg = document.createElement('div');
        errorMsg.id = 'error-message';
        errorMsg.style.cssText = `
          position: fixed;
          top: 24px;
          right: 24px;
          background: linear-gradient(135deg, #ef4444, #dc2626);
          color: white;
          padding: 16px 24px;
          border-radius: 12px;
          box-shadow: 0 10px 40px rgba(239, 68, 68, 0.4);
          display: none;
          align-items: center;
          gap: 12px;
          font-size: 14px;
          font-weight: 500;
          z-index: 1000;
          animation: slideInRight 0.3s ease;
        `;
        errorMsg.innerHTML = '<i data-lucide="x-circle" width="20" height="20"></i><span></span>';
        document.body.appendChild(errorMsg);
        lucide.createIcons();
      }
      
      const span = errorMsg.querySelector('span');
      if (span) {
        span.textContent = message;
      }
      errorMsg.style.display = 'flex';
      setTimeout(() => errorMsg.style.display = 'none', 4000);
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
      updateStats();
      renderTeams();
      renderUsers();
      setupFilters();
      lucide.createIcons();
    });
  </script>
</body>
</html>
HTML;

    return $html;
  }
  
  /**
   * Assign team to user.
   */
  public function assignTeam(Request $request) {
    // Parse request data
    $data = json_decode($request->getContent(), TRUE);
    
    if (!$data) {
      return new JsonResponse([
        'success' => FALSE, 
        'message' => 'Invalid JSON data'
      ], 400);
    }
    
    $user_id = $data['user_id'] ?? NULL;
    $team_id = $data['team_id'] ?? NULL;
    $csrf_token = $data['csrf_token'] ?? NULL;
    
    // Validate user ID
    if (!$user_id || !is_numeric($user_id)) {
      return new JsonResponse([
        'success' => FALSE, 
        'message' => 'Valid User ID is required'
      ], 400);
    }
    
    // Validate CSRF token (basic check)
    if (empty($csrf_token)) {
      \Drupal::logger('crm_teams')->warning('Missing CSRF token for user @user_id', [
        '@user_id' => $user_id
      ]);
    }
    
    try {
      // Load user
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($user_id);
      
      if (!$user) {
        return new JsonResponse([
          'success' => FALSE, 
          'message' => 'User not found'
        ], 404);
      }
      
      // Check if user has field_team
      if (!$user->hasField('field_team')) {
        \Drupal::logger('crm_teams')->error('User @user_id does not have field_team', [
          '@user_id' => $user_id
        ]);
        return new JsonResponse([
          'success' => FALSE, 
          'message' => 'User team field not configured'
        ], 500);
      }
      
      // Validate team if provided
      if ($team_id) {
        $team = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->load($team_id);
        
        if (!$team || $team->bundle() !== 'crm_team') {
          return new JsonResponse([
            'success' => FALSE, 
            'message' => 'Invalid team selected'
          ], 400);
        }
      }
      
      // Update user team
      $old_team_id = NULL;
      if (!$user->get('field_team')->isEmpty()) {
        $old_team_id = $user->get('field_team')->target_id;
      }
      
      if ($team_id) {
        $user->set('field_team', $team_id);
      } else {
        $user->set('field_team', NULL);
      }
      
      $user->save();
      
      // Log the change
      \Drupal::logger('crm_teams')->info('User @user (@email) team changed from @old to @new', [
        '@user' => $user->getDisplayName(),
        '@email' => $user->getEmail(),
        '@old' => $old_team_id ?: 'None',
        '@new' => $team_id ?: 'None',
      ]);
      
      return new JsonResponse([
        'success' => TRUE, 
        'message' => 'Team assigned successfully',
        'user_id' => $user_id,
        'team_id' => $team_id
      ]);
      
    } catch (\Exception $e) {
      \Drupal::logger('crm_teams')->error('Error assigning team: @error', [
        '@error' => $e->getMessage()
      ]);
      
      return new JsonResponse([
        'success' => FALSE, 
        'message' => 'An error occurred while saving. Please try again.'
      ], 500);
    }
  }

}
