<?php

namespace Drupal\commerce_order\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Form\ProfileForm;

class ProfileAddressBookForm extends ProfileForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    switch ($this->entity->save()) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Address %label has been created.', ['%label' => $this->entity->label()]));
        break;

      case SAVED_UPDATED:
        $this->messenger()->addMessage($this->t('Address %label has been updated.', ['%label' => $this->entity->label()]));
        break;
    }

    $form_state->setRedirect('commerce_order.user_address_book', [
      'user' => $this->entity->getOwnerId(),
    ]);
  }

}
