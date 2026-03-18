<?php

namespace Drupal\chat_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;

/**
 * Admin API Controller for AJAX requests.
 */
class AdminApiController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an AdminApiController object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Block a user via AJAX.
   */
  public function blockUser(Request $request) {
    // Validate CSRF token
    $token = $request->headers->get('X-CSRF-Token');
    if (!\Drupal::csrfToken()->validate($token, 'rest')) {
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Invalid CSRF token'),
      ], 403);
    }

    // Check permissions
    if (!$this->currentUser()->hasPermission('administer users')) {
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Access denied'),
      ], 403);
    }
    
    $uid = $request->request->get('uid');
    
    if (!$uid || $uid <= 1) {
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Invalid user ID'),
      ], 400);
    }

    try {
      $user = User::load($uid);
      
      if (!$user) {
        return new JsonResponse([
          'success' => false,
          'message' => $this->t('User not found'),
        ], 404);
      }

      // Block the user
      $user->block();
      $user->save();

      return new JsonResponse([
        'success' => true,
        'message' => $this->t('User @name has been blocked', ['@name' => $user->getAccountName()]),
        'user' => [
          'uid' => $user->id(),
          'name' => $user->getAccountName(),
          'status' => 0,
        ],
      ]);

    } catch (\Exception $e) {
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Error blocking user: @error', ['@error' => $e->getMessage()]),
      ], 500);
    }
  }

  /**
   * Unblock a user via AJAX.
   */
  public function unblockUser(Request $request) {
    // Validate CSRF token
    $token = $request->headers->get('X-CSRF-Token');
    if (!\Drupal::csrfToken()->validate($token, 'rest')) {
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Invalid CSRF token'),
      ], 403);
    }

    // Check permissions
    if (!$this->currentUser()->hasPermission('administer users')) {
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Access denied'),
      ], 403);
    }
    
    $uid = $request->request->get('uid');
    
    if (!$uid || $uid <= 1) {
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Invalid user ID'),
      ], 400);
    }

    try {
      $user = User::load($uid);
      
      if (!$user) {
        return new JsonResponse([
          'success' => false,
          'message' => $this->t('User not found'),
        ], 404);
      }

      // Unblock the user
      $user->activate();
      $user->save();

      return new JsonResponse([
        'success' => true,
        'message' => $this->t('User @name has been unblocked', ['@name' => $user->getAccountName()]),
        'user' => [
          'uid' => $user->id(),
          'name' => $user->getAccountName(),
          'status' => 1,
        ],
      ]);

    } catch (\Exception $e) {
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Error unblocking user: @error', ['@error' => $e->getMessage()]),
      ], 500);
    }
  }

  /**
   * Get user detail data as JSON for auto-refresh.
   * Used by user-detail-refresh.js to check for real-time updates.
   */
  public function getUserDetailJson($uid) {
    try {
      // Load user
      $user = User::load($uid);
      if (!$user) {
        return new JsonResponse([
          'success' => false,
          'message' => 'User not found',
        ], 404);
      }

      // Get avatar
      $avatarUrl = null;
      if ($user->hasField('field_avatar_url') && !$user->get('field_avatar_url')->isEmpty()) {
        $avatarUrl = $user->get('field_avatar_url')->value;
      }

      // Get basic user info
      $user_info = [
        'uid' => $user->id(),
        'name' => $user->getAccountName(),
        'email' => $user->getEmail(),
        'created' => $user->getCreatedTime(),
        'access' => $user->getLastAccessedTime(),
        'login' => $user->getLastLoginTime(),
        'status' => $user->isActive(),
        'avatarUrl' => $avatarUrl,
      ];

      // Get stats - reuse logic from AdminController
      $stats = [
        'friends_count' => $this->getUserFriendCount($uid),
        'pending_sent' => $this->getUserPendingRequestsSent($uid),
        'pending_received' => $this->getUserPendingRequestsReceived($uid),
        'days_registered' => floor((time() - $user->getCreatedTime()) / 86400),
        'last_seen_days' => $user->getLastAccessedTime() > 0 ? 
          floor((time() - $user->getLastAccessedTime()) / 86400) : null,
      ];

      return new JsonResponse([
        'success' => true,
        'user_info' => $user_info,
        'stats' => $stats,
      ]);

    } catch (\Exception $e) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get friend count for a user.
   */
  private function getUserFriendCount($uid) {
    // Count user_a cases
    $count_a = $this->database->select('chat_friend', 'cf')
      ->fields('cf', ['id'])
      ->condition('cf.user_a', $uid)
      ->countQuery()
      ->execute()
      ->fetchField();
      
    // Count user_b cases
    $count_b = $this->database->select('chat_friend', 'cf')
      ->fields('cf', ['id'])
      ->condition('cf.user_b', $uid)
      ->countQuery()
      ->execute()
      ->fetchField();
      
    return (int) $count_a + (int) $count_b;
  }

  /**
   * Get pending requests sent by user.
   */
  private function getUserPendingRequestsSent($uid) {
    return (int) $this->database->select('chat_friend_request', 'cfr')
      ->fields('cfr', ['id'])
      ->condition('cfr.from_user', $uid)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Get pending requests received by user.
   */
  private function getUserPendingRequestsReceived($uid) {
    return (int) $this->database->select('chat_friend_request', 'cfr')
      ->fields('cfr', ['id'])
      ->condition('cfr.to_user', $uid)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Get statistics data for dashboard.
   */
  public function getStats() {
    // Collect Drupal DB Stats
    $today = strtotime('today');
    $week_ago = strtotime('-7 days');
    
    $total_users = $this->database->select('users_field_data', 'u')->condition('uid', 0, '>')->countQuery()->execute()->fetchField();
    $active_today = $this->database->select('users_field_data', 'u')->condition('access', $today, '>=')->countQuery()->execute()->fetchField();
    $active_week = $this->database->select('users_field_data', 'u')->condition('access', $week_ago, '>=')->countQuery()->execute()->fetchField();
    
    $total_friends = $this->database->select('chat_friend', 'cf')->countQuery()->execute()->fetchField();
    $pending_requests = $this->database->select('chat_friend_request', 'cfr')->countQuery()->execute()->fetchField();

    // Fetch Node.js Stats
    $node_stats = [
      'totalMessages' => 0, 'todayMessages' => 0, 
      'totalConversations' => 0, 'activeConversations' => 0
    ];
    
    try {
      $response = \Drupal::httpClient()->get('http://localhost:5001/api/conversations/admin/conversations', ['timeout' => 2]);
      $data = json_decode($response->getBody()->getContents(), TRUE);
      if ($data && !empty($data['stats'])) {
         $node_stats = $data['stats'];
      }
    } catch (\Exception $e) {}

    // Mock Chart Data for now (since we don't have historical message data easily accessible)
    $labels = [];
    $messages_data = [];
    $users_data = [];
    
    for ($i = 6; $i >= 0; $i--) {
      $date = strtotime("-$i days");
      $labels[] = date('D', $date);
      $users_data[] = $this->database->select('users_field_data', 'u')
        ->condition('created', strtotime('today', $date), '>=')
        ->condition('created', strtotime('tomorrow', $date) - 1, '<=')
        ->countQuery()->execute()->fetchField();
      $messages_data[] = rand(10, 100); // Simulated message data
    }

    $stats = [
      'success' => true,
      'data' => [
        'users' => [
          'total' => (int)$total_users,
          'active_today' => (int)$active_today,
          'active_this_week' => (int)$active_week,
        ],
        'messages' => [
          'total' => $node_stats['totalMessages'] ?? 0,
          'today' => $node_stats['todayMessages'] ?? 0,
        ],
        'conversations' => [
          'total' => $node_stats['totalConversations'] ?? 0,
          'active' => $node_stats['activeTodayCount'] ?? 0,
        ],
        'friends' => [
          'total' => (int)$total_friends,
          'pending_requests' => (int)$pending_requests,
        ],
      ],
      'chart_data' => [
        'labels' => $labels,
        'messages_per_day' => $messages_data,
        'new_users_per_day' => $users_data,
      ],
    ];
    
    return new JsonResponse($stats);
  }

  /**
   * Proxy endpoint to get conversations from Node.js API.
   * This allows browser to call Drupal API which then forwards to Node.js.
   */
  public function getConversationsProxy() {
    // Check permissions
    if (!$this->currentUser()->hasPermission('moderate chat')) {
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Access denied'),
      ], 403);
    }

    $node_api_url = 'http://localhost:5001/api/conversations/admin/conversations';
    
    try {
      $response = \Drupal::httpClient()->get($node_api_url, [
        'timeout' => 10,
      ]);
      
      $statusCode = $response->getStatusCode();
      $body = $response->getBody()->getContents();
      
      // Return the response as-is from Node.js
      return new JsonResponse(
        json_decode($body, TRUE),
        $statusCode
      );
      
    } catch (\Exception $e) {
      \Drupal::logger('chat_api')->error('❌ Proxy error: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Failed to connect to Node.js server: @error', [
          '@error' => $e->getMessage(),
        ]),
        'data' => [],
        'stats' => [
          'totalConversations' => 0,
          'privateConversations' => 0,
          'groupConversations' => 0,
          'activeTodayCount' => 0,
          'totalMessages' => 0,
          'avgParticipants' => 0,
        ],
      ], 500);
    }
  }

  /**
   * Proxy endpoint to delete conversation via Node.js API.
   */
  public function deleteConversationProxy($conversation_id) {
    // Check permissions
    if (!$this->currentUser()->hasPermission('administer chat')) {
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Access denied'),
      ], 403);
    }

    $node_api_url = 'http://localhost:5001/api/conversations/admin/' . $conversation_id;
    
    try {
      $response = \Drupal::httpClient()->delete($node_api_url, [
        'timeout' => 10,
      ]);
      
      $statusCode = $response->getStatusCode();
      $body = $response->getBody()->getContents();
      
      return new JsonResponse(
        json_decode($body, TRUE),
        $statusCode
      );
      
    } catch (\Exception $e) {
      \Drupal::logger('chat_api')->error('❌ Delete proxy error: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      return new JsonResponse([
        'success' => false,
        'message' => $this->t('Failed to delete conversation: @error', [
          '@error' => $e->getMessage(),
        ]),
      ], 500);
    }
  }

  /**
   * SSE Stream Endpoint for Real-time Dashboard Updates.
   */
  public function streamConversations(Request $request) {
    // Disable time limit for the stream
    set_time_limit(0);

    $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () {
      // Clear output buffers to ensure pushing words
      while (ob_get_level()) {
        ob_end_clean();
      }
      
      $state = \Drupal::state();
      // Track the last processed event ID from the state system
      $last_id = $state->get('chat_api.last_webhook_event_id', 0);

      // Loop to keep the connection alive (60 iterations * 1s = 60s stream max; browser auto-reconnects)
      for ($i = 0; $i < 60; $i++) {
        
        // Fetch new events
        $current_id = $state->get('chat_api.last_webhook_event_id', 0);
        if ($current_id > $last_id) {
           $event_data = $state->get('chat_api.webhook_event_data_' . $current_id);
           if ($event_data) {
              echo "event: message\n";
              echo "data: " . json_encode($event_data) . "\n\n";
              flush();
              $last_id = $current_id;
           }
        }

        // Send a ping every 10 seconds to keep connection alive
        if ($i % 10 == 0) {
          echo ": ping\n\n";
          flush();
        }
        
        // Break if connection is aborted
        if (connection_aborted()) {
          break;
        }

        sleep(1);
      }
    });

    $response->headers->set('Content-Type', 'text/event-stream');
    $response->headers->set('Cache-Control', 'no-cache');
    $response->headers->set('Connection', 'keep-alive');
    $response->headers->set('X-Accel-Buffering', 'no'); // Important for Nginx

    return $response;
  }

  /**
   * Webhook receiver for Node.js push events.
   */
  public function receiveWebhook(Request $request) {
    // Only allow local network (simple security check)
    $client_ip = $request->getClientIp();
    if (!in_array($client_ip, ['127.0.0.1', '::1']) && strpos($client_ip, '192.168.') !== 0) {
       return new JsonResponse(['error' => 'Unauthorized'], 403);
    }

    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    if ($data) {
       $state = \Drupal::state();
       // Increment event ID
       $id = $state->get('chat_api.last_webhook_event_id', 0) + 1;
       
       // Store the event data temporarily
       $state->set('chat_api.last_webhook_event_id', $id);
       $state->set('chat_api.webhook_event_data_' . $id, $data);
       
       // Clean up old events (keep only last 10)
       if ($id > 10) {
         $state->delete('chat_api.webhook_event_data_' . ($id - 10));
       }

       return new JsonResponse(['success' => true, 'id' => $id]);
    }

    return new JsonResponse(['error' => 'Invalid data format'], 400);
  }

}
