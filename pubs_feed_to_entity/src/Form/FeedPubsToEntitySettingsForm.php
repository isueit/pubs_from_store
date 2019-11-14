<?php
namespace Drupal\pubs_feed_to_entity\Form;

use \Drupal\Core\Form\ConfigFormBase;
use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Config\ConfigFactoryInterface;
use \Drupal\taxonomy\Entity\Term;

/**
 * Class FeedPubsToEntitySettingsForm.
 */
class FeedPubsToEntitySettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pubs_feed_to_entity.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pubs_feed_to_entity_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    FeedPubsToEntitySettingsForm::createPubsFromUrl($form_state->getValue('url'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Json Feed Url'),
      '#description' => $this->t('Feed url to pull objects from.'),
      '#maxlength' => 64,
      '#size' => 64,
    );

    return parent::buildForm($form, $form_state);
  }


  /**
   * Pull in pubs entities from feed based on feed url
   */
  public function createPubsFromUrl($url) {
    $url_host = parse_url($url, PHP_URL_HOST);
    //Only allow approved hosts
    if ($url_host == 'store.extension.iastate.edu' || $url_host == 'localhost') {
      try {
        $raw = file_get_contents($url);
        $items = json_decode($raw, TRUE)['pubs'];
        foreach ($items as $item) {
          //Prevent duplicates
          $existing = \Drupal::entityTypeManager()->getStorage('pubs_entity')->loadByProperties(['field_product_id' => $item['productID']]);
          if (count($existing) == 0) {
            $date = explode('/', $item['pubDate']);
            $formatDate = $date[1] . '-' . (($date[0] < 10) ? '0' . $date[0] : $date[0]) . '-01';
            $newEntity = \Drupal\pubs_entity_type\Entity\PubsEntity::create([
              'title' => $item['title'],
              'field_product_id' => $item['productID'],
              'field_image_url' => $item['image'],
              'field_publication_date' => $formatDate,
            ]);
            $newEntity->setPublished();
            $newEntity->save();
          }
        }
      } catch (\Exception $e) {
        drupal_set_message(t('An Error occured pulling data from the given url'), 'error');
      }
    } else {
      drupal_set_message(t('Only feeds from Extension Store allowed'), 'error');
    }
  }
}
