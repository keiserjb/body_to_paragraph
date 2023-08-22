<?php

namespace Drupal\field_to_paragraph\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field_to_paragraph\ParagraphTypeLoadByFieldService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FieldToParagraphMigrationForm extends ConfigFormBase {

  protected $entityFieldManager;
  protected $entityTypeManager;
  protected $paragraphTypeLoadByFieldService;

  public function __construct(EntityFieldManagerInterface $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, ParagraphTypeLoadByFieldService $paragraphTypeLoadByFieldService) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->paragraphTypeLoadByFieldService = $paragraphTypeLoadByFieldService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('field_to_paragraph.paragraph_type_load_by_field_service')
    );
  }

  public function getFormId() {
    return 'field_to_paragraph_migration_form';
  }

  public function getEditableConfigNames() {
    return ['field_to_paragraph.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all available content types
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    // Add checkboxes for content types and corresponding paragraph field selectors
    foreach ($content_types as $content_type) {
      $form['content_types'][$content_type->id()] = [
        '#type'          => 'checkbox',
        '#title'         => $content_type->label(),
        '#default_value' => $this->config('field_to_paragraph.settings')->get(
          'content_types.' . $content_type->id(),
          []
        ),
      ];

      // Add a paragraph field selector for each content type
      $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $content_type->id());

      $options = [];
      foreach ($field_definitions as $field_name => $field_definition) {
        if ($field_definition->getType() === 'entity_reference_revisions') {
          $options[$field_name] = $field_definition->getLabel();
        }
      }

      $form['paragraph_field_' . $content_type->id()] = [
        '#type' => 'select',
        '#title' => $this->t('Select paragraph field for ') . $content_type->label(),
        '#options' => $options,
        '#default_value' => $this->config('field_to_paragraph.settings')->get('paragraph_field_' . $content_type->id(), ''),
        '#states' => [
          'visible' => [
            ':input[name="content_types[' . $content_type->id() . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];
      // Get paragraph types containing a text_with_summary field
      $paragraph_type_options = [];
      $paragraph_types = $this->paragraphTypeLoadByFieldService->loadByField('field_name', 'text_with_summary_field_name');

      foreach ($paragraph_types as $paragraph_type) {
        $paragraph_type_options[$paragraph_type->id()] = $paragraph_type->label();
      }
      // Add dropdown for paragraph types containing a text_with_summary field
      $form['paragraph_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Select paragraph type with text_with_summary field'),
        '#options' => $paragraph_type_options,
        '#default_value' => $this->config('field_to_paragraph.settings')->get('paragraph_type', ''),
      ];
    }

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Run Migration'),
    ];

    return parent::buildForm($form, $form_state);
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form values and trigger migration logic
    $content_types = array_filter($form_state->getValue('content_types'));

    // Trigger migration logic for each selected content type
    foreach ($content_types as $content_type => $checked) {
      if ($checked) {
        // Get selected paragraph field for this content type
        $paragraph_field = $form_state->getValue('paragraph_field_' . $content_type);

        // Perform migration logic using the values collected from the form
        // You can use the same logic you've implemented for the Drush command.
      }
    }
  }

}

