<?php

namespace Drupal\crm_advanced_filters\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for SavedFilter entities.
 */
interface SavedFilterInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Gets the filter name.
   *
   * @return string
   *   The filter name.
   */
  public function getName();

  /**
   * Sets the filter name.
   *
   * @param string $name
   *   The filter name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the filter description.
   *
   * @return string
   *   The filter description.
   */
  public function getDescription();

  /**
   * Sets the filter description.
   *
   * @param string $description
   *   The description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the entity type being filtered.
   *
   * @return string
   *   The entity type.
   */
  public function getEntityType();

  /**
   * Sets the entity type being filtered.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return $this
   */
  public function setEntityType($entity_type);

  /**
   * Gets the filter definition.
   *
   * @return array
   *   The filter definition.
   */
  public function getFilterDefinition();

  /**
   * Sets the filter definition.
   *
   * @param array $definition
   *   The filter definition.
   *
   * @return $this
   */
  public function setFilterDefinition(array $definition);

  /**
   * Checks if the filter is public.
   *
   * @return bool
   *   TRUE if public, FALSE otherwise.
   */
  public function isPublic();

  /**
   * Sets the public status.
   *
   * @param bool $public
   *   TRUE to make public, FALSE to make private.
   *
   * @return $this
   */
  public function setPublic($public = TRUE);

  /**
   * Gets the creation time.
   *
   * @return int
   *   The creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the creation time.
   *
   * @param int $timestamp
   *   The creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the last used time.
   *
   * @return int
   *   The last used timestamp.
   */
  public function getLastUsedTime();

  /**
   * Sets the last used time.
   *
   * @param int $timestamp
   *   The last used timestamp.
   *
   * @return $this
   */
  public function setLastUsedTime($timestamp);

}
