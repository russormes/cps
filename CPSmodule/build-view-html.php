/*A group of helper functions to build the HTML for the account summary table.
so far we ignore CSS and just default to the theme settings. */
function html_build_table_header($table_type){
  $h_col1 = '';
  $h_col2 = '';
  $h_col3 = '';
  //Set column names depending on table type. 
  switch ($table_type) {
    case 'payment':
      $h_col1 = 'Payment';
      $h_col2 = 'Date of payment';
      $h_col3 = 'Amount';
      break;
    case 'charge':
      $h_col1 = 'Charge';
      $h_col2 = 'Type';
      $h_col3 = 'Amount';
  }
  return '
    <div>
      <table>
	<colgroup>
	  <col span="1" style="width: 40%;">
	  <col span="1" style="width: 40%;">
	  <col span="1" style="width: 20%;">
	  </colgroup>
	<thead>
	  <tr>
	    <th>
	      '.$h_col1.'
	    </th>
	    <th>
	      '.$h_col2.'
	    </th>
	    <th>
	      '.$h_col3.'
	    </th>
	  </tr>
	</thead>
	<tbody>';
}

/*It was decided not to store service charges and charge nodes and to simply
generate the service charge balance when required from the service charge amount
frequency of payment and current month. This may change in the future,
depending on client feedback.*/
function html_build_service_charge_rows($prop_node, $s_date, $f_date) {
  $sc_amount = $prop_node->field_property_sc_payments['und'][0]['amount'];
  //So far we ignore this option. Needs updating. 
  $sc_freq = $prop_node->field_property_sc_frequency['und'][0]['value'];
  $interval = $s_date->diff($f_date);
  $noc = $interval->m;
  $html_string = '';
  //Add one service charge payment for each month. 
  for ($i=0; $i<=$noc; $i++) {
    $html_string .= '<tr>
	<td>
	  Service Charge : '.$s_date->format('F').'
	</td>
	<td>
	  <a href="/cps/?q=taxonomy/term/2">Service Charge</a>
	</td>
	<td>
	  '.$sc_amount.'
	</td>
      </tr>';
     $s_date->modify('+1 month');
  }
  return $html_string;
}

/*Helper function to build individual rows in the html table from charge
or payment nodes.*/
function html_build_table_row($node) {
  if ($node->type == 'charge') {
    $term = taxonomy_term_load($node->field_charge_type['und'][0]['tid']);
    $term_name = $term->name;
  } else {
    $date = date_create($node->field_payment_date_received['und'][0]['value']);
  }
  $html_string ='
      <tr>
	<td>
	  <a href="/cps/?q=node/'.$node->nid.'">'.$node->title.'</a>
	</td>
	<td>
	  '.($node->type == 'payment' ?
	      date_format($date, 'd/m/Y') : '<a href="/cps/?q=taxonomy/term/'.
		  $node->field_charge_type['und'][0]['tid'].'">'.$term_name).'</a>
	</td>
	<td>
	  £'.($node->type == 'payment' ?
	       $node->field_payment_amount['und'][0]['amount'] :
	        $node->field_charge_amount['und'][0]['amount']).'
	</td>
      </tr>';
  return $html_string;
}

/*Add the balance to the table footer*/
function html_build_table_footer($total) {
  return '
	</tbody>
	<tfoot>
	  <tr>
	    <th></th>
	    <th></th>
	    <th>
	      £'.$total.'
	    </th>
	  </tr>
	</tfoot>
      </table>
    </div>';
}