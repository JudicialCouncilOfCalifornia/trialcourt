<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\user\Entity\User;

/**
 * Created user account by email.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: user_by_email
 *     source: field_email_address
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "user_by_email"
 * )
 */
class UserByEmail extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $email = trim(strtolower($value));
    if (empty($email)) {
      return NULL;
    }

    // Try to find existing user by email.
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['mail' => $email]);

    if ($users) {
      $user = reset($users);
    }
    else {
      $username = $email;
      if (!empty($username)) {
        $existing_names = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $username]);
      }
      $suffix = 1;
      $base_name = $username;
      while ($existing_names) {
        $username = $base_name . $suffix;
        $existing_names = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $username]);
        $suffix++;
      }

      $user = User::create([
        'name' => $username,
        'mail' => $email,
        'status' => 1,
      ]);
      $user->save();
    }

    // Return the integer UID.
    return $user->id();
  }

}
