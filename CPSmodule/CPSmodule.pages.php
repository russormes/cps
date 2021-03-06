<?php
 /**
* Define a form. The entry form for adding the apartment code for payment. 
*/
function add_payment_by_code_form() {
  $form['add_payment'] = array(
    '#title' => t('Property code'),
    '#type' => 'textfield',
    '#description' => t('Please enter the CPS property/payment code.'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );

  return $form;
}

/** When the form is submitted we get the nid for the property that the payment
 * is made against pass it to the add payment form via the URL.
 **/
function add_payment_by_code_form_submit($form, &$form_state) {
  $title = $form_state['values']['add_payment'];
  $type = 'property';
  /*This relies on property titles being unique. However, we do not enforce
  uniqueness of property title anywhere (although it should be the case from the
  policy of CPS)*/
  $result = db_query("SELECT n.nid FROM {node} n WHERE n.title = :title AND
		     n.type = :type", array(":title"=> $title, ":type"=> $type));  
  $nid = $result->fetchField();
  if($nid !== FALSE) { 
    $form_state['redirect'] = 'node/add/payment/'.$nid;
  } else {
    drupal_set_message("There is no property registered to that CPS code");
  }
}

/**
* Define a form. The entry form for listing the payments from the property code. 
*/
function list_payments_by_prop_form() {
  $form['list_payments_by_prop'] = array(
    '#title' => t('Property code'),
    '#type' => 'textfield',
    '#description' => t('Please enter the CPS property/payment code.'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );

  return $form;
}

/** When the form is submitted we add the property code to the URL  
 * so the view is rendered using the context from the URL.
 **/
function list_payments_by_prop_form_submit($form, &$form_state) {
  $title = $form_state['values']['list_payments_by_prop'];
  $type = 'property';
  $result = db_query("SELECT n.nid FROM {node} n WHERE n.title = :title AND
		     n.type = :type", array(":title"=> $title, ":type"=> $type));  
  $nid = $result->fetchField();
  if($nid !== FALSE) { 
    $form_state['redirect'] = 'payment-by-property/'.$nid;
  } else {
    drupal_set_message("There is no property registered to that CPS code");
  }
}

/**
* Define a form. The entry form for listing the charges from the property code. 
*/
function list_charges_by_prop_form() {
  $form['list_charges_by_prop'] = array(
    '#title' => t('Property code'),
    '#type' => 'textfield',
    '#description' => t('Please enter the CPS property/payment code.'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );

  return $form;
}

/** When the form is submitted we add the property code to the URL  
 * so the view is rendered using the context from the URL.
 **/
function list_charges_by_prop_form_submit($form, &$form_state) {
  $title = $form_state['values']['list_charges_by_prop'];
  $type = 'property';
  $result = db_query("SELECT n.nid FROM {node} n WHERE n.title = :title AND
		     n.type = :type", array(":title"=> $title, ":type"=> $type));  
  $nid = $result->fetchField();
  if($nid !== FALSE) { 
    $form_state['redirect'] = 'charge-by-property/'.$nid;
  } else {
    drupal_set_message("There is no property registered to that CPS code");
  }
}

/**
* Define a form. Here we require more information to be passed to the view.
* Dates are entered using calls to th date field api, available as
* the date field contribute module has been installed. 
*/
function account_summary_by_code_form() {
  $form['prop_code'] = array(
    '#title' => t('Property code'),
    '#type' => 'textfield',
    '#description' => t('Please enter the CPS property/payment code.'),
  );
  $form['report_start_date'] = array(
    '#title' => t('Start date'),
    '#type' => 'date_popup',
    '#description' => t('Please enter start date of the report.'),
    //Store the date in the correct format for the database.
    '#default_value' => date('Y-m-d H:i', mktime(0, 0, 0, 1, 1, 2014)),
    '#date_format' => 'd-m-Y', //Present the date in the UK format. 
    '#description' => t('Please enter start date of the report.'), 
  );
  $form['report_end_date'] = array(
    '#title' => t('End date'),
    '#type' => 'date_popup',
    '#description' => t('Please enter end date of the report.'),
    '#default_value' => date('Y-m-d H:i'),
    '#date_format' => 'd-m-Y',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('View report')
  );
  return $form;
}

function export_form() {
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Export')
    );
  return $form;
}

/** When the form is submitted we add the property nid and the report dates
 * to the URL for use in building the report.
 * See: CPSmodule_property_account_summary_view()
 **/
function account_summary_by_code_form_submit($form, &$form_state) {
  //Grab the values from the form. 
  $prop_code = $form_state['values']['prop_code'];
  $start_date = $form_state['values']['report_start_date'];
  $end_date = $form_state['values']['report_end_date'];
  $type = 'property';
  $result = db_query("SELECT n.nid FROM {node} n WHERE n.title = :title AND
		     n.type = :type", array(":title"=> $prop_code, ":type"=> $type));  
  $nid = $result->fetchField();
  if($nid !== FALSE) { //Check there is a property with the given code
    $form_state['redirect'] = //build the url with the input data
      'property_account_summary/'.$nid.'/'.$start_date.'/'.$end_date;
  } else {
    drupal_set_message("There is no property registered to that CPS code");
  }
}

/* Function to build a page to view payment and charge totals for a given
   property. Will build a table of payments with a total, a table of charges
   with a total and produce a balance*/
function CPSmodule_property_account_summary_view($prop_nid, $start_date,
						  $end_date) {
  $prop_node = node_load($prop_nid); //Bring in the property node
  /*By making date objects we can manipulate the dates as needed when
  building the report.*/
  $date1 = new DateTime($start_date);
  $date2 = new DateTime($end_date);
  $output = array(
    'report_heading' => array(
      '#type' => 'markup',
      '#markup' => '<h2>Report from: '.$date1->format('d/m/Y').' to: ' .
						$date2->format('d/m/Y').'</h2>',
    )
  );
  //First we build a table of payments
  $payment_total = 0; //keep a running total of payments made
  /*Build the database query using the property nodeId and the start and end dates
   *provided from the calling URL*/
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'payment') //We want data from payment nodes
    //related to the property code given by the user. 
    ->fieldCondition('field_payment_property', 'target_id', $prop_nid, '=')
    ->fieldCondition('field_payment_date_received', 'value',
                        $date1->format('Y-m-d'), '>=') //restrict to start 
    ->fieldCondition('field_payment_date_received', 'value',
                        $date2->format('Y-m-d'), '<=') // and end dates. 
    //order by date the payment was made.
    ->fieldOrderBy('field_payment_date_received', 'value', 'ASC');
  $result = $query->execute();
  //Only build the payments table is some payments exit.
  $colgroups = array(
		 array('span' => array('1'), 'style' => array('width: 40%')),
		 array('span' => array('1'), 'style' => array('width: 40%')),
		 array('span' => array('1'), 'style' => array('width: 20%')),
	       );
  if (isset($result['node'])) { 
    $node_list = $result['node'];
    $output['payment_table'] = array(
      '#theme' => 'table',
      '#header' => array(t('Payment'), t('Date of payment'), t('Amount')),
      '#rows' => array(),
      '#colgroups' => array($colgroups),
    );
    /*Each property will not have many associated payments so we can go ahead
     *and load the nodes.*/
    foreach ($node_list as $node_obj) {
      $pay_node = node_load($node_obj->nid);
      $payment_total += $pay_node->field_payment_amount['und'][0]['amount'];
      $cell1 = '<a href="/cps/?q=node/' . $pay_node->nid . '">' .
						      $pay_node->title.'</a>';
      $cell2 = date_format(
		date_create(
		  $pay_node->field_payment_date_received['und'][0]['value']
		), 'd/m/Y'
	      );
      $cell3 = '£'. $pay_node->field_payment_amount['und'][0]['amount'];
      $output['payment_table']['#rows'][] = array($cell1, $cell2, $cell3);
    }
    $output['payment_total'] = array(
      '#type' => 'markup',
      '#markup' => '<div align="right">Total: £'.$payment_total.'</div>',
    );
  }
  
  //Now we build a table of charges
  $charge_total = 0; //Keep running total of charges.
  /*There will be at least one charge so go ahead and build the table.
  The report adds one service charge payment for each month on the report, so
  we need to calculate the difference btween start and end date in months*/
  $output['charge_table'] = array(
      '#theme' => 'table',
      '#header' => array(t('Charge'), t('Type'), t('Amount')),
      '#rows' => array(),
      '#colgroups' => array($colgroups),
    );
  /*It was decided not to store service charges and charge nodes and to simply
    generate the service charge balance when required from the service charge amount
    frequency of payment and current month. This may change in the future,
    depending on client feedback.*/
  $interval = $date1->diff($date2);
  $no_of_sc = $interval->m;
  $sc_amount = $prop_node->field_property_sc_payments['und'][0]['amount'];
  //This ignores the option to pay the sc other than monthly. Needs updating. 
  $charge_total += ($sc_amount * $no_of_sc); 
  for ($i=0; $i<=$no_of_sc; $i++) {
    $cell1 = 'Service Charge : '.$date1->format('F');
    $cell2 = '<a href="/cps/?q=taxonomy/term/2">Service Charge</a>';
    $cell3 = '£'. $sc_amount;
    $output['charge_table']['#rows'][] = array($cell1, $cell2, $cell3);
    $date1->modify('+1 month');
  }
  //Get the nids for all charges between the two dates. 
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'charge')
    ->fieldCondition('field_charge_property_id', 'target_id', $prop_nid, '=')
    ->fieldCondition('field_charge_date', 'value', $start_date, '>=')
    ->fieldCondition('field_charge_date', 'value', $end_date, '<=');
  $result = $query->execute();
  if (isset($result['node'])) {
    $node_list = $result['node'];
    foreach ($node_list as $node_obj) {
      $charge_node = node_load($node_obj -> nid);
      $charge_total += $charge_node->field_charge_amount['und'][0]['amount'];
      $term = taxonomy_term_load($charge_node->field_charge_type['und'][0]['tid']);
      $term_name = $term->name;
      $cell1 = '<a href="/cps/?q=node/' . $charge_node->nid . '">' .
						      $charge_node->title.'</a>';
      $cell2 = '<a href="/cps/?q=taxonomy/term/' .
		  $charge_node->field_charge_type['und'][0]['tid'] .
		  '">'.$term_name.'</a>';
      $cell3 = '£'. $charge_node->field_charge_amount['und'][0]['amount'];
      $output['charge_table']['#rows'][] = array($cell1, $cell2, $cell3);
    }
  }
  //Add the charge total below the table. 
  $output['charge_total'] = array(
      '#type' => 'markup',
      '#markup' => '<div align="right">Total: £'.$charge_total.'</div>',
    );
    //Add the account balance. 
  $output['report_total'] = array(
      '#type' => 'markup',
      '#markup' => '<p align="right">Balance: £'.($payment_total -
                                                   $charge_total).'</p>',
    );
  $export_form = drupal_get_form('export_form');
  $output['export_report'] = array(
      '#type' => 'markup',
      '#markup' => '<div align="right">' . drupal_render($export_form) . '</div>',
    );
  
  return $output;
}

?>