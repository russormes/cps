<?php
function CPSmodule_export_stuff() {
  module_load_include('inc', 'phpexcel');
  $data = array();
  $headers = array();
  // First worksheet
  // Get the nodes
  $result = db_select('node', 'n')
                 ->fields('n', array('nid', 'type', 'status'))
                 ->execute();
  while($row = $result->fetchAssoc()) {
    if (!count($headers)) {
      // Add the headers for the first worksheet
      $headers['Nodes'] = array_keys($row);
    }
    // Add the data
    $data['Nodes'][] = array_values($row);
  }
  // Second worksheet
  // Get the latest revisions
  $query = db_select('node_revision', 'v');
  $query->leftJoin('node', 'n', 'n.vid = v.vid');
  $result = $query->fields('v', array('nid', 'vid', 'title'))
                 ->execute();
  while($row = $result->fetchAssoc()) {
    if (count($headers) == 1) {
      // Add the headers for the second worksheet
      $headers['Revisions'] = array_keys($row);
    }
    // Add the data
    $data['Revisions'][] = array_values($row);
  }
  // Store the file in sites/default/files
  $dir = file_stream_wrapper_get_instance_by_uri('public://')->realpath();
  $filename = 'export.xls';
  $path = "$dir/$filename";
  // Use the .xls format
  $options = array('format' => 'xls');
  $result = phpexcel_export($headers, $data, $path, $options);
  if ($result == PHPEXCEL_SUCCESS) {
    drupal_set_message(t("We did it !"));
  }
  else {
    drupal_set_message(t("Oops ! An error occured !"), 'error');
  }
}
?>