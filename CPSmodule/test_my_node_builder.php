<?php
define('DRUPAL_ROOT', getcwd());
$_SERVER['REMOTE_ADDR'] = "localhost"; // Necessary if running from command line
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

$node = new stdClass(); // Create a new node object
$node->type = "property"; // Or page, or whatever content type you like
node_object_prepare($node); // Set some default values
// If you update an existing node instead of creating a new one,
// comment out the three lines above and uncomment the following:
// $node = node_load($nid); // ...where $nid is the node id

$node->title    = "Property Node Ref Test";
$estate_id = 7;
$owner_id = 8;
$node->language = LANGUAGE_NONE; // Or e.g. 'en' if locale is enabled
$node->field_estate[$node->language][0]['target_id'] = $estate_id;
$node->field_property_owner[$node->language][0]['target_id'] = $owner_id;
$node->uid = 1; // UID of the author of the node; or use $node->name

//Now let's fill in the address
$node->field_address[LANGUAGE_NONE][0] = array(
  'country' => 'GB',
  'thoroughfare' => '1',
  'premise' => 'Hamilton Court',
  'locality' => 'Rochester',
  'administrative_area' => 'Kent',
  'postal_code' => 'ME1 1ED',
);


// I prefer using pathauto, which would override the below path
$path = 'node_created_on' . date('YmdHis');
$node->path = array('alias' => $path);

if($node = node_submit($node)) { // Prepare node for saving
    node_save($node);
    echo "Node with nid " . $node->nid . " saved!\n";
}
?>
