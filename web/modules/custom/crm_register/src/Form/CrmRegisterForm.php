<?php

namespace Drupal\crm_register\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Custom CRM Registration Form.
 */
class CrmRegisterForm extends FormBase {

  /**
   * The password hasher.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordHasher;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a new CrmRegisterForm.
   */
  public function __construct(PasswordInterface $password_hasher, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager) {
    $this->passwordHasher = $password_hasher;
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('password'),
      $container->get('language_manager'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add custom CSS
    $form['#attached']['library'][] = 'crm_register/register_form';

    $form['#attributes']['class'][] = 'crm-register-form';

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
        <h1 class="auth-title">Create your Open CRM account</h1>
        <p class="auth-subtitle">Join us today and get started!</p>
      </div>',
    ];

    // Last Name and First Name (2 columns)
    $form['card']['form_column']['name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['name-row']],
    ];

    $form['card']['form_column']['name_wrapper']['lastname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Enter your last name',
        'class' => ['auth-input'],
      ],
    ];

    $form['card']['form_column']['name_wrapper']['firstname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Enter your first name',
        'class' => ['auth-input'],
      ],
    ];

    // Username
    $form['card']['form_column']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Enter your username (e.g. john.nguyen)',
        'autocomplete' => 'username',
        'class' => ['auth-input'],
      ],
    ];

    // Email
    $form['card']['form_column']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Enter your email address',
        'autocomplete' => 'email',
        'class' => ['auth-input'],
      ],
    ];

    // Password
    $form['card']['form_column']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Create a password (min. 5 characters)',
        'autocomplete' => 'new-password',
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
      '#value' => $this->t('Create Account'),
      '#attributes' => ['class' => ['btn-auth-submit']],
    ];

    // Login link
    $form['card']['form_column']['login_link'] = [
      '#markup' => '<div class="auth-link">Already have an account? <a href="/login">Sign in</a></div>',
    ];

    // Footer terms (inside form column)
    $form['card']['form_column']['footer_terms'] = [
      '#markup' => '<div class="auth-footer-terms">
        By continuing, you agree to our <a href="/terms">Terms of Service</a> and <a href="/privacy">Privacy Policy</a>.
      </div>',
    ];

    // Image column
    $form['card']['image_column'] = [
      '#markup' => '<div class="auth-image-column">
        <img src="/modules/custom/crm_register/images/register-bg.svg" alt="" />
      </div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate username
    $username = $form_state->getValue('username');
    if (!preg_match('/^[a-zA-Z0-9._]{3,}$/', $username)) {
      $form_state->setErrorByName('username', $this->t('Username must be at least 3 characters and contain only letters, numbers, dots, and underscores.'));
    }

    // Check if username exists
    $existing_user = user_load_by_name($username);
    if ($existing_user) {
      $form_state->setErrorByName('username', $this->t('This username is already taken. Please choose a different one.'));
    }

    // Validate email
    $email = $form_state->getValue('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address (e.g. user@example.com).'));
    }

    // Check if email exists
    $existing_email = user_load_by_mail($email);
    if ($existing_email) {
      $form_state->setErrorByName('email', $this->t('This email address is already registered. Please use a different email or try logging in.'));
    }

    // Validate password (min 5 chars like Discord)
    $password = $form_state->getValue('password');
    if (strlen($password) < 5) {
      $form_state->setErrorByName('password', $this->t('Password must be at least 5 characters long.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $full_name = trim($values['lastname'] . ' ' . $values['firstname']);

    try {
      // Create user account
      $user = User::create([
        'name' => $values['username'],
        'mail' => $values['email'],
        'pass' => $values['password'],
        'status' => 1, // Active
        'init' => $values['email'],
        'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
      ]);

      // Set full name
      if ($user->hasField('field_full_name')) {
        $user->set('field_full_name', $full_name);
      }

      // Default role: sales_rep
      $user->addRole('sales_rep');

      // Save user
      $user->save();

      // Log the user in automatically
      user_login_finalize($user);

      // Success message
      $this->messenger()->addStatus($this->t('Welcome, @name! Your account has been created successfully.', [
        '@name' => $full_name,
      ]));

      // Redirect to dashboard
      $form_state->setRedirect('crm_dashboard.dashboard');

    }
    catch (\Exception $e) {
      // Log the full error for debugging but show generic message to user
      \Drupal::logger('crm_register')->error('Registration error: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      $this->messenger()->addError($this->t('An error occurred while creating your account. Please try again later or contact support if the problem persists.'));
    }
  }

}
