<?php
namespace Drupal\field_to_paragraph\Commands;

use Drush\Commands\DrushCommands;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * A custom Drush command for updating nodes to use paragraphs.
 *
 * @DrushCommands
 */
class FieldToParagraphCommand extends DrushCommands {

  /**
   * Copies the body field to a paragraph.
   *
   * @command field-to-paragraph
   * @aliases f2p
   */
  public function FieldToParagraph() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'afs_get_started')
      ->accessCheck(FALSE);

    $nids = $query->execute();

    if (!empty($nids)) {
      $nodes = Node::loadMultiple($nids);

      foreach ($nodes as $node) {
        $body_value = $node->get('body')->value;

        // Create the body paragraph component.
        $paragraphComponent = Paragraph::create([
          'type' => 'ama_body', // Replace with your actual body paragraph type machine name.
          'ama_body' => [
            'value' => $body_value,
            'format' => 'full_html',
          ],
        ]);
        $paragraphComponent->save();

        // Create the section paragraph.
        $paragraphSection = Paragraph::create([
          'type' => 'section', // Replace with your actual section paragraph type machine name.
        ]);

        // Set the layout_paragraphs behavior settings for the section.
        $paragraphSection->setBehaviorSettings('layout_paragraphs', [
          'layout' => 'layout_onecol', // Set your layout.
          'parent_uuid' => NULL,
          'region' => NULL,
        ]);
        $paragraphSection->save();

        // Set the behavior settings for the body paragraph component.
        $paragraphComponent->setBehaviorSettings('layout_paragraphs', [
          'region' => 'content', // Replace with your actual region where the paragraph should be placed.
          'parent_uuid' => $paragraphSection->uuid(),
        ]);
        $paragraphComponent->save();

        // Prepare the list of paragraphs to set on the node's field.
        $final_paragraph_reference_list = [
          [
            'target_id' => $paragraphSection->id(),
            'target_revision_id' => $paragraphSection->getRevisionId(),
          ],
          [
            'target_id' => $paragraphComponent->id(),
            'target_revision_id' => $paragraphComponent->getRevisionId(),
          ],
        ];

        // Replace 'field_components' with your actual main paragraph reference field machine name.
        $node->set('field_components', $final_paragraph_reference_list);
        $node->save();
      }
      $this->logger()->success(dt('Nodes have been updated with body paragraphs within layout sections.'));
    }
    else {
      $this->logger()->warning(dt('No nodes found to update.'));
    }
  }
}
