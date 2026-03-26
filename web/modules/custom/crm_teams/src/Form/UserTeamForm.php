<?php

namespace Drupal\crm_teams\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to assign team to user.
 */
class UserTeamForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_teams_user_team_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL) {
    if (!$user) {
      $this->messenger()->addError('User not found.');
      return $form;
    }
    
    $form['user_id'] = [
      '#type' => 'hidden',
      '#value' => $user->id(),
    ];
    
    $form['user_info'] = [
      '#markup' => '<p><strong>User:</strong> ' . $user->getDisplayName() . ' (' . $user->getEmail() . ')</p>',
    ];
    
    // Get current team
    $current_team = NULL;
    if ($user->hasField('field_team') && !$user->get('field_team')->isEmpty()) {
      $current_team = $user->get('field_team')->target_id;
    }
    
    // Load all teams
    $teams = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'crm_team']);
    
    $team_options = ['' => '-- No Team --'];
    foreach ($teams as $team) {
      $team_options[$team->id()] = $team->getName();
    }
    
    $form['team'] = [
      '#type' => 'select',
      '#title' => $this->t('Assign Team'),
      '#description' => $this->t('Select the team this user belongs to'),
      '#options' => $team_options,
      '#default_value' => $current_team,
      '#required' => FALSE,
    ];
    
    $form['actions'] = [
      '#type' => 'actions',
    ];
    
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Team Assignment'),
      '#button_type' => 'primary',
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_id = $form_state->getValue('user_id');
    $team_id = $form_state->getValue('team');
    
    try {
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($user_id);
      
      if ($user && $user->hasField('field_team')) {
        if ($team_id) {
          $user->set('field_team', $team_id);
        } else {
          $user->set('field_team', NULL);
        }
        $user->save();
        
        $this->messenger()->addStatus($this->t('Team assigned successfully to @user', [
          '@user' => $user->getDisplayName(),
        ]));
        
        $form_state->setRedirect('crm_teams.settings');
      }
    } catch (\Exception $e) {
      // Log full error but show generic message to user for security
      \Drupal::logger('crm_teams')->error('Error in UserTeamForm: @error', [
        '@error' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('An error occurred while assigning the team. Please try again or contact support.'));
    }
  }

}
