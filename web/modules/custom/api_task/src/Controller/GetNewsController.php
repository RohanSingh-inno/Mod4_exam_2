<?php

namespace Drupal\api_task\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\File\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
// Use Drupal\statistics\Views\NodeStatistics;.
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * This class will be used to return data in the form of API.
 */
class GetNewsController extends ControllerBase {

  /**
   * Manages entity type plugin definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  public $entityTypeManager;

  /**
   * Constructor for your class.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Container function.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   This is for dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Get all student details.
   */
  public function getAllNewsDetails() {
    $query = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'news')
      ->accessCheck(TRUE);

    $news_ids = $query->execute();

    $news = $this->entityTypeManager
      ->getStorage('node')
      ->loadMultiple($news_ids);

    return $news;
  }

  /**
   * Function to display all the details regarding students.
   */
  public function getProduct() {
    // Fetch and format student data here.
    $news_data = [];

    $all_news = $this->getAllNewsDetails();

    foreach ($all_news as $key => $new) {
      $news_data[$key]['News Title'] = $new->get('title')->getValue()[0]['value'];
      $temp = $new->get('body')->getValue()[0]['value'];
      // Split the string into an array of words.
      $words = preg_split('/\s+/', $temp);

      // Extract the first 10 words.
      $first_10_words = implode(' ', array_slice($words, 0, 10));

      $news_data[$key]['Body'] = $temp;
      $news_data[$key]['Summary'] = $first_10_words;

      $taxonomy_term_id = $new->get('field_news_tags')->getValue();
      $tags = [];

      foreach ($taxonomy_term_id as $id) {
        $term = Term::load($id['target_id']);
        if ($term) {
          $tags[] = $term->label();
        }
      }

      // Convert the array of term labels to a comma-separated string.
      $tags_string = implode(', ', $tags);

      $news_data[$key]['News Tags'] = $tags_string;

      $time = $new->get('published_at')->getValue()[0]['value'];
      $news_data[$key]['Published Date'] = date("Y-m-d H:i:s", $time);

      $node = Node::load($key);

      // If ($node) {
      // $node_statistics = new NodeStatistics($node);
      // $view_count = $node_statistics->getViewCount();
      // }
      $news_data[$key]['View Count'] = $node;

      $image_term_id = $new->get('field_news_image')->getValue();
      $images = [];

      foreach ($image_term_id as $id) {
        $pre = 'http://www.examtask.com/sites/default/files/2023-09/';
        $image = File::load($id['target_id'])->get('filename')->getValue()[0]['value'];
        $rohan = $pre . $image;

        $images[] = $rohan;
      }

      // Convert the array of image URLs to a comma-separated string.
      $images_string = implode(', ', $images);

      $news_data[$key]['News Image'] = $images_string;

    }

    return new JsonResponse($news_data);
  }

}
