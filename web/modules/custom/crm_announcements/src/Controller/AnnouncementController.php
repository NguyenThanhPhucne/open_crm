<?php

namespace Drupal\crm_announcements\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for rendering the internal announcements off-canvas feed.
 */
class AnnouncementController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new AnnouncementController.
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * Builds the off-canvas feed.
   */
  public function build() {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['crm-announcements-feed']],
      '#attached' => [
        'library' => ['crm_announcements/crm_announcements.toolbar'],
      ],
    ];

    // Query nodes of type crm_announcement.
    // If the content type doesn't exist yet, this will just return empty.
    $nids = [];
    try {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'crm_announcement')
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->range(0, 10)
        ->accessCheck(TRUE);
      $nids = $query->execute();
    } catch (\Exception $e) {
      // Ignore if node type doesn't exist yet.
    }

    if (empty($nids)) {
      $build['empty'] = [
        '#markup' => '<div class="crm-announcements-empty"><p>' . $this->t('No internal announcements found.') . '</p></div>',
      ];
      // Note: we can provide a link to create an announcement if they have access!
      if (\Drupal::currentUser()->hasPermission('create crm_announcement content')) {
         $build['create_link'] = [
           '#type' => 'link',
           '#title' => $this->t('Create first announcement'),
           '#url' => \Drupal\Core\Url::fromRoute('node.add', ['node_type' => 'crm_announcement']),
           '#attributes' => ['class' => ['button', 'button--primary', 'crm-announcements-create-btn']],
         ];
      }
      return $build;
    }

    $nodes = Node::loadMultiple($nids);
    $items = [];

    foreach ($nodes as $node) {
      $level = $node->get('field_announcement_level')->value ?? 'info';
      $target = $node->get('field_announcement_target')->value ?? 'all';
      
      $level_label = $node->get('field_announcement_level')->getFieldDefinition()->getSetting('allowed_values')[$level] ?? $level;
      $target_label = $node->get('field_announcement_target')->getFieldDefinition()->getSetting('allowed_values')[$target] ?? $target;

      $items[] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['crm-announcement-item', 'level-' . $level]],
        'meta' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['crm-announcement-meta']],
          'badge' => [
            '#markup' => '<span class="crm-announcement-badge badge-' . $level . '">' . $level_label . '</span>',
          ],
          'target' => [
            '#markup' => '<span class="crm-announcement-target-badge">' . $target_label . '</span>',
          ],
        ],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $node->toLink($node->getTitle())->toString(),
          '#attributes' => ['class' => ['crm-announcement-title']],
        ],
        'date' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->dateFormatter->format($node->getCreatedTime(), 'custom', 'j M Y - H:i'),
          '#attributes' => ['class' => ['crm-announcement-date']],
        ],
        'body' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => check_markup(
            $node->get('body')->value ?? '',
            $node->get('body')->format ?? 'basic_html'
          ),
          '#attributes' => ['class' => ['crm-announcement-body']],
        ],
      ];
    }

    $build['list'] = $items;

    if (\Drupal::currentUser()->hasPermission('create crm_announcement content')) {
      $build['actions'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['crm-announcements-actions']],
        'link' => [
           '#type' => 'link',
           '#title' => $this->t('+ New Announcement'),
           '#url' => \Drupal\Core\Url::fromRoute('node.add', ['node_type' => 'crm_announcement']),
           '#attributes' => ['class' => ['button', 'button--action']],
        ],
      ];
    }

    return $build;
  }

}
