<?php
function CPSmodule_form_charge_node_form_alter(&$form, &$form_state, $form_id) {
   //dpm("_charge_node_form_alter");
   //Hide the title and set it automatically on submit see: CPSmodule_node_submit()
   $form['title']['#access'] = 0;
}

function CPSmodule_form_property_node_form_alter(&$form, &$form_state, $form_id) {
   //dpm("_property_node_form_alter");
   $form['field_property_service_charge'][LANGUAGE_NONE]['#ajax'] = array(
    // When 'event' occurs, Drupal will perform an ajax request in the
    // background. Usually the default value is sufficient (eg. change for
    // select elements), but valid values include any jQuery event,
    // most notably 'mousedown', 'blur', and 'submit'.
    'event' => 'change',
    'callback' => 'CPSmodule_ajax_form_property_node_callback',
    'wrapper' => 'sc_payment_update',
  );
   $form['field_property_sc_frequency'][LANGUAGE_NONE]['#ajax'] = array(
    // When 'event' occurs, Drupal will perform an ajax request in the
    // background. Usually the default value is sufficient (eg. change for
    // select elements), but valid values include any jQuery event,
    // most notably 'mousedown', 'blur', and 'submit'.
    //'event' => 'change',
    'callback' => 'CPSmodule_ajax_form_property_node_callback',
    'wrapper' => 'sc_payment_update',
  );
  $form['field_property_sc_payments']['und']['#prefix'] =
				    '<div id="sc_payment_update">';
  $form['field_property_sc_payments']['und']['#suffix'] = '</div>';
  $form['field_property_sc_payments']['und'][0]['amount']['#default_value'] =
				CPSmodule_sc_payments_value($form, $form_state);
  $form_state['input']['field_property_sc_payments']['und'][0]['amount'] =
				CPSmodule_sc_payments_value($form, $form_state);
  $form['field_property_sc_payments']['und'][0]['currency']['#default_value'] =
									  "GBP";
  //dpm calls for debuging
  //dpm('form:');
  //dpm($form);
  //dpm('form_state[input]:');
  //dpm($form_state['input']);
  //$node = $form['#node'];
  //dpm('node:');
  //dpm($node);
}

function CPSmodule_form_payment_node_form_alter(&$form, &$form_state, $form_id) {
  //dpm("payment_node_form_alter");
  $form['title']['#access'] = 0;
  /*This field is currently not used. Maybe we want to assign payments to
   *particular charges in the future. For now, hide it on the form*/
  $form['field_payment_charge']['#access'] = 0;
  $node = $form['#node'];
  $onid = '';
  $arg_array = arg();
  $prop_nid = end($arg_array);
  if ($prop_nid == 'edit') {
    $onid = $node->field_payment_owner['und'][0]['target_id'];
  } else {
    if(ctype_digit($prop_nid)) { 
      $prop_node = node_load($prop_nid);
      $onid = $prop_node->field_property_owner['und'][0]['target_id'];
      $own_node = node_load($onid);
      $form['field_payment_owner'][LANGUAGE_NONE][0]['target_id']['#default_value'] = "$own_node->title ($own_node->nid)";
      $form['field_payment_property'][LANGUAGE_NONE]['#default_value'] = $prop_node->nid;
    }
  }
    // Get the list of options to populate the first dropdown.    
  $property_options = CPSmodule_property_dropdown_options($onid);
  $form['field_payment_property'][LANGUAGE_NONE]['#options'] = $property_options;
}

/*Helper function to set calulate the value to set the amount of service charge
payments depending on the frequency of payment agreed by CPS with the client.*/
function CPSmodule_sc_payments_value($form, $form_state) {
  //dpm('_sc_payments_value called');
  $sv = isset($form_state['values']['field_property_sc_frequency'][LANGUAGE_NONE][0]['value']) ?
           $form_state['values']['field_property_sc_frequency'][LANGUAGE_NONE][0]['value'] :
	   $form['#node']->field_property_sc_frequency['und'][0]['value'];
  $amount = isset($form_state['input']['field_property_service_charge']['und'][0]['amount']) ?
              $form_state['input']['field_property_service_charge']['und'][0]['amount'] :
	      $form['#node']->field_property_service_charge['und'][0]['amount'];
  switch ($sv) {
    case '0':
      return round($amount/12,2);
    case '1':
      return round($amount/2,2);
    case '2':
      return $amount;
  }
}
  
 /**
 * Helper function to populate the first dropdown. This gets
 * data from the database. We give the option to select any property
 * for a givn owner, however this might not be useful to the client
 * and so maybe removed in the future.
 *
 * @return array of options
 */
function CPSmodule_property_dropdown_options($key = '') {
  $query = new EntityFieldQuery();
  $return_values = array();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'property');
    if (!empty($key)) { $query->fieldCondition('field_property_owner', 'target_id', $key, '='); }
  $result = $query->execute();
  $node_list = $result['node'];
  foreach ($node_list as $node_obj) {
    $node_id = $node_obj -> nid;
    $sql = 'SELECT nid, title FROM {node} WHERE nid = :nids';
    $query_result = db_query($sql, array(':nids' => $node_id))->fetchAllAssoc('nid');
    foreach ($query_result as $val) {
      $return_values[$node_id] = $val -> title;
    }
  } 
  return $return_values;
}

/**
 * Returns changed part of the form.
 *
*/

function CPSmodule_ajax_form_property_node_callback($form, $form_state) {
  //dpm("Called: _ajax_form_property_node_callback");
  return $form['field_property_sc_payments'];
}
/**
 * Implements hook_node_submit(). Essentially we are building the node titles. 
 * @see CPSmodule_form_payment_node_form_alter()
 */
function CPSmodule_node_submit($node, $form, &$form_state) {
  //dpm("Called: _submit");
  $values = $form_state['values'];
  switch ($node->type) {
    case 'payment':
      $pnid = $values['field_payment_property']['und'][0]['target_id'];
      $pnode = node_load($pnid);
      $date =
        $form_state['input']['field_payment_date_received']['und'][0]['value']['date'];
      $node->title = $pnode->title.':
            £'.$values['field_payment_amount']['und'][0]['amount'].': '.$date;
      break;
    case 'charge':
      $pnid = $values['field_charge_property_id']['und'][0]['target_id'];
      $ctypetid = $values['field_charge_type']['und'][0]['tid'];
      $pnode = node_load($pnid);
      $ctype = taxonomy_term_load($ctypetid);
      $node->title = $pnode->title.' '.$ctype->name.':
                        £'.$values['field_charge_amount']['und'][0]['amount'];
      break;
  }
//dpm('_node_submit: ');
}

?>