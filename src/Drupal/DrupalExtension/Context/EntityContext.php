<?php

namespace Drupal\DrupalExtension\Context;

use Behat\Behat\Context\TranslatableContext;
use Behat\Mink\Element\Element;

use Behat\Gherkin\Node\TableNode;

/**
 * Provides pre-built step definitions for testing any Drupal entity.
 */
class EntityContext extends RawDrupalContext implements TranslatableContext
{

  /**
   * Returns list of definition translation resources paths.
   *
   * @return array
   */
  public static function getTranslationResources()
  {
    return glob(__DIR__ . '/../../../../i18n/*.xliff');
  }

  /**
   * @Given I am viewing a/an :entity_type entity/item with the :label_name :label
   * @Given I am viewing a/an :bundle :entity_type entity/item with the :label_name :label
   */
  public function createEntity($entity_type, $bundle = NULL, $label_name, $label)
  {
    $entity = (object)array(
        'type' => $bundle,
        $label_name => $label,
    );
    $saved = $this->entityCreate($entity_type, $entity);
    // Set internal browser on the new entity.
    //@todo: use the entity's toUrl()
    $this->getSession()->visit($this->locatePath('/' . $entity_type . '/' . $saved->id));
  }

  /**
   * Creates content of a given type provided in the form:
   * | title    | author     | status | created           |
   * | My title | Joe Editor | 1      | 2014-10-17 8:00am |
   * | ...      | ...        | ...    | ...               |
   *
   * @Given a/an :entity_type entity/item:
   * @Given :entity_type entities/items:
   * @Given a/an :bundle :entity_type entity/item:
   * @Given :bundle :entity_type entities/items:
   */
   public function createEntities($entity_type, $bundle = NULL, TableNode $entitiesTable)
   {
     foreach ($entitiesTable->getHash() as $entityHash) {
       $entity = (object)$entityHash;
       if (!isset($entity->type)) $entity->type = $bundle;
       $this->entityCreate($entity_type, $entity);
     }
   }

  /**
   * Creates content of the given type, provided in the form:
   * | title     | My entity        |
   * | Field One | My field value |
   * | author    | Joe Editor     |
   * | status    | 1              |
   * | ...       | ...            |
   *
   * @Given I am viewing a/an :entity_type entity/item:
   * @Given I am viewing a/an :bundle :entity_type entity/item:
   */
  public function assertViewingEntity($entity_type, $bundle = NULL, TableNode $fields)
  {
    $entity = (object)array();
    foreach ($fields->getRowsHash() as $field => $value) {
      $entity->{$field} = $value;
    }
    if (!isset($entity->type)) $entity->type = $bundle;

    $saved = $this->entityCreate($entity_type, $entity);

    // Set internal browser on the new entity.
    //@todo: use the entity's toUrl()
    $this->getSession()->visit($this->locatePath('/' . $entity_type . '/' . $saved->id));
  }

}

