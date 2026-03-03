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
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Team Management - CRM</title>
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
      padding: 32px 20px;
      color: #1e293b;
    }
    
    .container {
      max-width: 1400px;
      margin: 0 auto;
      animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .header {
      margin-bottom: 32px;
      padding-bottom: 20px;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .header-left h1 {
      font-size: 28px;
      font-weight: 600;
      color: #1e293b;
      margin: 0 0 6px 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .header-left p {
      color: #64748b;
      font-size: 14px;
      margin: 0;
    }
    
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 18px;
      background: white;
      color: #3b82f6;
      text-decoration: none;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s;
    }
    
    .back-btn:hover {
      background: #eff6ff;
      border-color: #3b82f6;
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
      color: #1e293b;
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
      border: 2px solid #f1f5f9;
      transition: all 0.2s;
      cursor: pointer;
    }
    
    .team-item:hover {
      border-color: #3b82f6;
      background: #eff6ff;
    }
    
    .team-item.selected {
      border-color: #3b82f6;
      background: linear-gradient(135deg, #eff6ff, #dbeafe);
    }
    
    .team-name {
      font-size: 15px;
      font-weight: 600;
      color: #0f172a;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .team-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      background: #3b82f6;
      color: white;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .team-desc {
      font-size: 13px;
      color: #64748b;
      margin-bottom: 8px;
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
    }
    
    .users-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .users-table thead tr {
      background: #f8fafc;
      border-bottom: 2px solid #e2e8f0;
    }
    
    .users-table th {
      padding: 12px 16px;
      text-align: left;
      font-size: 12px;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .users-table td {
      padding: 14px 16px;
      border-bottom: 1px solid #f1f5f9;
      font-size: 14px;
      color: #1e293b;
    }
    
    .users-table tbody tr {
      transition: background 0.2s;
    }
    
    .users-table tbody tr:hover {
      background: #f8fafc;
    }
    
    .user-name {
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .user-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: linear-gradient(135deg, #3b82f6, #8b5cf6);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 13px;
    }
    
    .user-email {
      color: #64748b;
      font-size: 13px;
    }
    
    .team-tag {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      background: #e0e7ff;
      color: #4338ca;
    }
    
    .team-tag.no-team {
      background: #f1f5f9;
      color: #64748b;
    }
    
    .team-select {
      padding: 6px 12px;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      font-size: 13px;
      background: white;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .team-select:hover {
      border-color: #3b82f6;
    }
    
    .team-select:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .save-btn {
      padding: 6px 14px;
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    
    .save-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .save-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }
    
    .role-badge {
      display: inline-block;
      padding: 2px 8px;
      background: #fef3c7;
      color: #92400e;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .success-message {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #10b981;
      color: white;
      padding: 16px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
      display: none;
      align-items: center;
      gap: 10px;
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
    
    @media (max-width: 1024px) {
      .content-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="header-left">
        <h1>
          <i data-lucide="users" width="28" height="28"></i>
          Team Management
        </h1>
        <p>Quản lý team và phân quyền truy cập CRM data</p>
      </div>
      <a href="/crm/dashboard" class="back-btn">
        <i data-lucide="arrow-left" width="16" height="16"></i>
        Back to Dashboard
      </a>
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
      
      <!-- Users Table -->
      <div class="card">
        <div class="card-header">
          <i data-lucide="user-check" width="20" height="20"></i>
          <div class="card-title">User Team Assignments</div>
        </div>
        <div class="users-table-container">
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
    
    // Render teams
    function renderTeams() {
      const container = document.getElementById('team-list');
      
      teams.forEach(team => {
        const div = document.createElement('div');
        div.className = 'team-item';
        div.innerHTML = `
          <div class="team-name">
            \${team.name}
            <span class="team-badge">
              <i data-lucide="users" width="12" height="12"></i>
              \${team.user_count}
            </span>
          </div>
          <div class="team-desc">\${team.description}</div>
          <div class="team-stats">
            <i data-lucide="user" width="14" height="14"></i>
            \${team.user_count} members
          </div>
        `;
        container.appendChild(div);
      });
      
      lucide.createIcons();
    }
    
    // Render users
    function renderUsers() {
      const tbody = document.getElementById('users-tbody');
      
      users.forEach(user => {
        const tr = document.createElement('tr');
        const initials = user.name.split(' ').map(n => n[0]).join('').toUpperCase();
        
        const teamColor = user.team !== 'No Team' ? '#e0e7ff' : '#f1f5f9';
        const teamTextColor = user.team !== 'No Team' ? '#4338ca' : '#64748b';
        
        tr.innerHTML = `
          <td>
            <div class="user-name">
              <div class="user-avatar">\${initials}</div>
              \${user.name}
            </div>
          </td>
          <td><div class="user-email">\${user.email}</div></td>
          <td>
            <span class="team-tag \${user.team === 'No Team' ? 'no-team' : ''}" style="background: \${teamColor}; color: \${teamTextColor};">
              \${user.team}
            </span>
          </td>
          <td>
            <span class="role-badge">\${user.roles || 'user'}</span>
          </td>
          <td>
            <select class="team-select" data-user-id="\${user.id}" data-current-team="\${user.team_id || ''}">
              {$team_options_html}
            </select>
            <button class="save-btn" onclick="assignTeam(\${user.id}, this)">
              <i data-lucide="save" width="14" height="14"></i>
              Save
            </button>
          </td>
        `;
        
        tbody.appendChild(tr);
        
        // Set current team selected
        const select = tr.querySelector('.team-select');
        if (user.team_id) {
          select.value = user.team_id;
        }
      });
      
      lucide.createIcons();
    }
    
    // Assign team to user
    async function assignTeam(userId, button) {
      const select = button.previousElementSibling;
      const teamId = select.value;
      
      button.disabled = true;
      button.innerHTML = '<i data-lucide="loader" width="14" height="14"></i> Saving...';
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
        
        const result = await response.json();
        
        if (result.success) {
          showSuccess();
          setTimeout(() => location.reload(), 1000);
        } else {
          alert('Error: ' + (result.message || 'Failed to assign team'));
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Network error occurred');
      } finally {
        button.disabled = false;
        button.innerHTML = '<i data-lucide="save" width="14" height="14"></i> Save';
        lucide.createIcons();
      }
    }
    
    function showSuccess() {
      const msg = document.getElementById('success-message');
      msg.classList.add('active');
      setTimeout(() => msg.classList.remove('active'), 3000);
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
      renderTeams();
      renderUsers();
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
    $data = json_decode($request->getContent(), TRUE);
    
    $user_id = $data['user_id'] ?? NULL;
    $team_id = $data['team_id'] ?? NULL;
    
    if (!$user_id) {
      return new JsonResponse(['success' => FALSE, 'message' => 'User ID required'], 400);
    }
    
    try {
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($user_id);
      
      if (!$user) {
        return new JsonResponse(['success' => FALSE, 'message' => 'User not found'], 404);
      }
      
      if ($user->hasField('field_team')) {
        if ($team_id) {
          $user->set('field_team', $team_id);
        } else {
          $user->set('field_team', NULL);
        }
        $user->save();
        
        return new JsonResponse(['success' => TRUE, 'message' => 'Team assigned successfully']);
      } else {
        return new JsonResponse(['success' => FALSE, 'message' => 'Field team not found'], 500);
      }
    } catch (\Exception $e) {
      \Drupal::logger('crm_teams')->error('Error assigning team: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'message' => $e->getMessage()], 500);
    }
  }

}
