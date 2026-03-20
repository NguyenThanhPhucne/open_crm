<?php

namespace Drupal\chat_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;
use Drupal\Core\Site\Settings;
use Firebase\JWT\JWT;

class AuthController extends ControllerBase {

  /**
   * ĐĂNG KÝ
   */
  public function signUp(Request $request) {
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $email = $data['email'] ?? '';
    $firstName = $data['firstName'] ?? '';
    $lastName = $data['lastName'] ?? '';

    if (empty($username) || empty($password) || empty($email)) {
      return new JsonResponse(['message' => 'Missing required information'], 400);
    }

    if (user_load_by_name($username)) {
      return new JsonResponse(['message' => 'Username already exists'], 409);
    }

    if (user_load_by_mail($email)) {
      return new JsonResponse(['message' => 'Email already exists'], 409);
    }

    try {
      $user = User::create([
        'name' => $username,
        'mail' => $email,
        'pass' => $password,
        'status' => 1,
      ]);
      $user->save();
      user_login_finalize($user);
      return new JsonResponse(NULL, 204);

    } catch (\Exception $e) {
      \Drupal::logger('chat_api')->error($e->getMessage());
      return new JsonResponse(['message' => 'System error'], 500);
    }
  }

  /**
   * ĐĂNG NHẬP
   */
  public function signIn(Request $request) {
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    $uid = \Drupal::service('user.auth')->authenticate($username, $password);

    if (!$uid) {
      return new JsonResponse(['message' => 'Invalid username or password'], 401);
    }

    $user = User::load($uid);
    
    // 1. CẤP COOKIE SESSION (Cho Drupal API)
    user_login_finalize($user);

    // 2. Lấy displayName và avatarUrl từ Drupal fields (đồng bộ với /api/users/me)
    $displayName = $user->getAccountName();
    if ($user->hasField('field_display_name') && !$user->get('field_display_name')->isEmpty()) {
      $displayName = (string) $user->get('field_display_name')->value;
    }

    $avatarUrl = null;
    if ($user->hasField('field_avatar_url') && !$user->get('field_avatar_url')->isEmpty()) {
      $avatarUrl = (string) $user->get('field_avatar_url')->value;
    }

    // 3. CẤP JWT TOKEN (Cho Node.js Chat) - dùng Firebase JWT library nhất quán
    $key = Settings::get('chat_api_access_token_secret', 'fallback_secret_key');
    $jwt = $this->generateJwt($user, $key);

    return new JsonResponse([
      'message' => "Login successful",
      'accessToken' => $jwt, 
      'user' => [
        '_id' => $user->id(),
        'uid' => $user->id(),
        'username' => $user->getAccountName(),
        'displayName' => $displayName,
        'avatarUrl' => $avatarUrl, 
        'csrf_token' => \Drupal::service('csrf_token')->get('rest'),
      ]
    ], 200);
  }

  /**
   * ĐĂNG XUẤT
   */
  public function signOut(Request $request) {
    user_logout();
    return new JsonResponse(NULL, 204);
  }

  /**
   * REFRESH TOKEN
   */
  public function refreshToken(Request $request) {
    $user = \Drupal::currentUser();
    if ($user->isAnonymous()) {
      return new JsonResponse(['message' => 'Session expired'], 401);
    }
    
    $account = User::load($user->id());
    $key = Settings::get('chat_api_access_token_secret', 'fallback_secret_key');
    $jwt = $this->generateJwt($account, $key);

    return new JsonResponse(['accessToken' => $jwt], 200);
  }

  /**
   * Helper: Tạo JWT dùng Firebase JWT library (nhất quán với FriendController decode).
   */
  private function generateJwt($user, $key): string {
    $payload = [
      'userId'   => $user->id(),
      'username' => $user->getAccountName(),
      'email'    => $user->getEmail(),
      // Include Drupal roles so the Node.js backend can enforce RBAC.
      'roles'    => method_exists($user, 'getRoles') ? $user->getRoles() : [],
      'iat'      => time(),
      'exp'      => time() + (60 * 60 * 24 * 14), // 14 ngày
    ];

    return JWT::encode($payload, $key, 'HS256');
  }
}
