<?php
namespace Drupal\field_to_paragraph\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Custom Drush command for updating nodes to use paragraphs.
 *
 * @DrushCommand(
 *   name = "update-field-to-paragraph",
 *   description = "Update nodes by migrating data from body to paragraph field.",
 *   aliases = {"uf2p"}
 * )
 */
class UpdateFieldToParagraphCommand extends DrushCommands {

  /**
   * Executes the update command.
   *
   * @command update-field-to-paragraph
   * @aliases uf2p
   */
  public function updateFieldToParagraph() {
    // Load nodes of a specific content type.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->accessCheck(FALSE);

    $nids = $query->execute();

    if (!empty($nids)) {
      $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

      // Loop through nodes and migrate data.
      foreach ($nodes as $node) {
        // Get data from old body field.
        $body_value = $node->get('body')->value;

        // Create a new paragraph entity.
        $paragraph = \Drupal\paragraphs\Entity\Paragraph::create([
          'type' => 'ama_body',
          'ama_body' => [
            'value' => $body_value,
            'format' => 'full_html', // Set the desired text format
          ],
        ]);

        // Set the paragraph entity as a reference on the node.
        $node->set('field_components', $paragraph);

        // Save the node and paragraph.
        $node->save();
        $paragraph->save();
      }
    }
  }
}

