<h2>Law Enforcement History</h2>
<?php
/**
 * Law Enforcement History (Nested Form field: 222)
 * Child fields:
 *  - 1 = Agency
 *  - 3 = Date of Application
 *  - 4 = Status
 *
 * Comments:
 *  - 30  -> $law_enforcement_comments (applicant)
 *  - 610 -> $exam_law_enforcement_comments (examiner; adminLabel)
 */

$le_items      = [];
$le_has_status = false;

$collector = function ( $child ) use ( &$le_items, &$le_has_status, $child_val, $fmt_date ) {

	$agency = trim( (string) $child_val( $child, '1' ) );
	$date   = trim( (string) $child_val( $child, '3' ) );
	$status = trim( (string) $child_val( $child, '4' ) );

	if ( $status !== '' ) {
		$le_has_status = true;
	}

	$date_f = $date !== '' ? $fmt_date( $date ) : '';

	$bits = [];
	if ( $date_f !== '' ) {
		$bits[] = $date_f;
	}
	if ( $status !== '' ) {
		$bits[] = $status;
	}

	if ( $agency === '' && empty( $bits ) ) {
		return '';
	}

	$item = ( $agency !== '' ) ? $agency : 'Agency not specified';

	if ( ! empty( $bits ) ) {
		$item .= ' (' . implode( '; ', $bits ) . ')';
	}

	$le_items[] = $item;

	// one consolidated paragraph only
	return '';
};

$para_nested( $entry, 222, $collector );

// Normalize collected items
$le_items = array_values( array_filter( array_map( 'trim', $le_items ) ) );

if ( empty( $le_items ) ) {

	$para(
		$A . ' denied having any prior law enforcement applications.'
	);

} else {

	$sentence =
		$A .
		' reported applying to the following law enforcement agencies: ' .
		$oxford_join( $le_items ) .
		'.';

	if ( $le_has_status ) {
		$sentence .= ' The applicant provided status information for one or more of these applications.';
	}

	$para( $sentence );
}

// Comments
$para_comment(
	$law_enforcement_comments,
	'Regarding law enforcement history, ' . $A . ' noted:'
);

$para_comment(
	$exam_law_enforcement_comments,
	'Examiner noted:'
);
?>