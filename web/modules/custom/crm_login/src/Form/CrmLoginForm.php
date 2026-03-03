<?php

namespace Drupal\crm_login\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\user\UserAuthInterface;

/**
 * Custom CRM Login Form with Discord-style UI.
 */
class CrmLoginForm extends FormBase {

  /**
   * The user authentication service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * Constructs a new CrmLoginForm.
   */
  public function __construct(UserAuthInterface $user_auth, FloodInterface $flood) {
    $this->userAuth = $user_auth;
    $this->flood = $flood;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.auth'),
      $container->get('flood')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add custom CSS
    $form['#attached']['library'][] = 'crm_login/login_form';

    $form['#attributes']['class'][] = 'crm-login-form';

    // Card container
    $form['card'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['auth-card']],
    ];

    // Form column
    $form['card']['form_column'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['auth-form-column']],
    ];

    // Header with logo
    $form['card']['form_column']['header'] = [
      '#markup' => '<div class="auth-header">
        <a href="/" class="auth-logo">
          <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </a>
        <h1 class="auth-title">Chào mừng quay lại</h1>
        <p class="auth-subtitle">Đăng nhập vào tài khoản Open CRM của bạn</p>
      </div>',
    ];

    // Username
    $form['card']['form_column']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tên đăng nhập'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'john.nguyen',
        'autocomplete' => 'username',
        'class' => ['auth-input'],
      ],
    ];

    // Password
    $form['card']['form_column']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Mật khẩu'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Enter your password',
        'autocomplete' => 'current-password',
        'class' => ['auth-input'],
      ],
    ];

    // Submit button
    $form['card']['form_column']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['auth-actions']],
    ];

    $form['card']['form_column']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Đăng nhập'),
      '#attributes' => ['class' => ['btn-auth-submit']],
    ];

    // Register link
    $form['card']['form_column']['register_link'] = [
      '#markup' => '<div class="auth-link">Chưa có tài khoản? <a href="/register">Đăng ký</a></div>',
    ];

    // Footer terms (inside form column)
    $form['card']['form_column']['footer_terms'] = [
      '#markup' => '<div class="auth-footer-terms">
        Bằng cách tiếp tục, bạn đồng ý với <a href="/terms">Điều khoản dịch vụ</a> và <a href="/privacy">Chính sách bảo mật</a> của chúng tôi.
      </div>',
    ];

    // Image column
    $form['card']['image_column'] = [
      '#markup' => '<div class="auth-image-column">
        <img src="/modules/custom/crm_login/images/login-bg.svg" alt="" />
      </div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $username = $form_state->getValue('username');
    $password = $form_state->getValue('password');

    // Check flood control
    $flood_config = $this->config('user.flood');
    if (!$this->flood->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
      $form_state->setErrorByName('username', $this->t('Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau.'));
      return;
    }

    // Authenticate user
    $uid = $this->userAuth->authenticate($username, $password);
    
    if (!$uid) {
      // Register flood event
      $this->flood->register('user.failed_login_ip', $flood_config->get('ip_window'));
      $form_state->setErrorByName('username', $this->t('Tên đăng nhập hoặc mật khẩu không đúng'));
    }
    else {
      // Store UID for submission
      $form_state->set('uid', $uid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->get('uid');
    $user = User::load($uid);

    if ($user) {
      // Log the user in
      user_login_finalize($user);

      // Success message
      $this->messenger()->addStatus($this->t('Chào mừng trở lại, @name!', [
        '@name' => $user->getDisplayName(),
      ]));

      // Redirect to dashboard
      $form_state->setRedirect('crm_dashboard.dashboard');
    }
  }

}
