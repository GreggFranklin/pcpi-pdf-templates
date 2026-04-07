<h2>Legal</h2>
<?php
/**
 * LEGAL (Nested Forms)
 *
 * Parent fields on the Review form store child entry IDs:
 * - 585: Charges        (child keys: 1=Charge, 3=Year, 4=Disposition)
 * - 587: Charges Other  (child keys: 1=Charge, 3=Year)
 * - 588: Lawsuit        (child keys: 1=Description, 3=Year, 4=Plaintiff/Defendant)
 *
 * Output: one narrative paragraph (Applicant said once).
 */

/* ------------------------------------------------------------
 * CONFIG — Parent field IDs
 * ------------------------------------------------------------ */
$PARENT_CHARGES_FIELD_ID       = 585;
$PARENT_CHARGES_OTHER_FIELD_ID = 587;
$PARENT_LAWSUITS_FIELD_ID      = 588;

// If you want to hard-filter by child form_id, set these. Otherwise keep 0.
$CHARGES_CHILD_FORM_ID       = 0;
$CHARGES_OTHER_CHILD_FORM_ID = 0;
$LAWSUITS_CHILD_FORM_ID      = 0;

/* ------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------ */
$pretty_year = function ( $raw ) {
	$raw = trim( (string) $raw );
	return preg_match( '/^\d{4}$/', $raw ) ? $raw : $raw;
};

/* ------------------------------------------------------------
 * Collect items
 * ------------------------------------------------------------ */
$charges_items       = [];
$charges_other_items = [];
$lawsuit_items       = [];

// Charges (585): 1=charge, 3=year, 4=disposition
$collect_charge = function( array $child ) use ( &$charges_items, $child_val, $pretty_year ) {

	$desc = trim( (string) $child_val( $child, '1' ) );
	$year = $pretty_year( $child_val( $child, '3' ) );
	$disp = trim( (string) $child_val( $child, '4' ) );

	if ( $desc === '' && $year === '' && $disp === '' ) {
		return '';
	}

	$line = $desc !== '' ? $desc : 'Charge';

	$details = [];
	if ( $year !== '' ) {
		$details[] = $year;
	}
	if ( $disp !== '' ) {
		$details[] = $disp;
	}

	if ( ! empty( $details ) ) {
		$line .= ' (' . implode( ' — ', $details ) . ')';
	}

	$charges_items[] = $line;
	return '';
};

$para_nested( $entry, $PARENT_CHARGES_FIELD_ID, $collect_charge, $CHARGES_CHILD_FORM_ID );


// Charges Other (587): 1=charge, 3=year
$collect_charge_other = function( array $child ) use ( &$charges_other_items, $child_val, $pretty_year ) {

	$desc = trim( (string) $child_val( $child, '1' ) );
	$year = $pretty_year( $child_val( $child, '3' ) );

	if ( $desc === '' && $year === '' ) {
		return '';
	}

	$line = $desc !== '' ? $desc : 'Charge';

	if ( $year !== '' ) {
		$line .= ' (' . $year . ')';
	}

	$charges_other_items[] = $line;
	return '';
};

$para_nested( $entry, $PARENT_CHARGES_OTHER_FIELD_ID, $collect_charge_other, $CHARGES_OTHER_CHILD_FORM_ID );


// Lawsuits (588): 1=desc, 3=year, 4=plaintiff/defendant
$collect_lawsuit = function( array $child ) use ( &$lawsuit_items, $child_val, $pretty_year ) {

	$desc = trim( (string) $child_val( $child, '1' ) );
	$year = $pretty_year( $child_val( $child, '3' ) );
	$role = trim( (string) $child_val( $child, '4' ) );

	if ( $desc === '' && $year === '' && $role === '' ) {
		return '';
	}

	$line = $desc !== '' ? $desc : 'Lawsuit';

	$details = [];
	if ( $year !== '' ) {
		$details[] = $year;
	}
	if ( $role !== '' ) {
		$details[] = $role;
	}

	if ( ! empty( $details ) ) {
		$line .= ' (' . implode( ' — ', $details ) . ')';
	}

	$lawsuit_items[] = $line;
	return '';
};

$para_nested( $entry, $PARENT_LAWSUITS_FIELD_ID, $collect_lawsuit, $LAWSUITS_CHILD_FORM_ID );


// Clean arrays
$charges_items       = array_values( array_filter( array_map( 'trim', $charges_items ) ) );
$charges_other_items = array_values( array_filter( array_map( 'trim', $charges_other_items ) ) );
$lawsuit_items       = array_values( array_filter( array_map( 'trim', $lawsuit_items ) ) );


/* ------------------------------------------------------------
 * One combined narrative paragraph
 * ------------------------------------------------------------ */
if ( ! empty( $charges_items ) || ! empty( $charges_other_items ) || ! empty( $lawsuit_items ) ) {

	$parts = [];

	$parts[] = 'Beginning with the most recent, ' . $A . ' reports the following legal history:';

	if ( ! empty( $charges_items ) ) {
		$parts[] = 'Charges: ' . $oxford_join( $charges_items, 'and' ) . '.';
	}

	if ( ! empty( $charges_other_items ) ) {
		$parts[] = 'Other charges: ' . $oxford_join( $charges_other_items, 'and' ) . '.';
	}

	if ( ! empty( $lawsuit_items ) ) {
		$parts[] = 'Lawsuits: ' . $oxford_join( $lawsuit_items, 'and' ) . '.';
	}

	$para( implode( ' ', $parts ) );

} else {
	$para( $A . ' reports no legal history.' );
}


// Comments
$para_comment( $legal_comments, 'Regarding legal history, ' . $A . ' noted:' );
$para_comment( $exam_legal_comments, 'Examiner noted:' );
?>