<h2>Marital Status</h2>

<?php
/* ==================================================================
 * MARITAL / DOMESTIC HISTORY (Narrative)
 * - Cohabitants (nested: 223)
 * - Optional domestic/RO/LE items (9991–9996 placeholders)
 *
 * NOTE:
 * - The “delinquent alimony/child support” question (281 + 431/432)
 *   belongs in Financial History and should be handled there to avoid
 *   duplication and cross-section drift.
 * ================================================================== */

// -------------------- CONFIG --------------------
$REL_PARENT_FID = 223;

// Child field mapping inside the Relationships child form.
$REL_CHILD = [
	"first" => "1", // first name
	"last"  => "2", // last name
	"rel"   => "3", // relationship
];

// Placeholder IDs (replace after you add real fields)
$FID_RO_YN      = "9991";
$FID_RO_EXPLAIN = "9992";

$FID_DV_YN      = "9993";
$FID_DV_EXPLAIN = "9994";

$FID_LE_YN      = "9995";
$FID_LE_EXPLAIN = "9996";

// -------------------- Local helpers --------------------
$get_rel_name = function ( array $child ) use ( $REL_CHILD, $child_val, $child_name ) : string {

	// If a Name field is used in the child form, prefer $child_name()
	// (safe to keep; fallback handles separate first/last fields)
	if ( isset( $child_name ) && is_callable( $child_name ) ) {
		$maybe = trim( (string) $child_name( $child, (string) $REL_CHILD['first'] ) );
		if ( $maybe !== '' ) {
			return $maybe;
		}
	}

	$first = trim( (string) $child_val( $child, (string) $REL_CHILD['first'] ) );
	$last  = trim( (string) $child_val( $child, (string) $REL_CHILD['last'] ) );

	return trim( preg_replace( '/\s+/', ' ', $first . ' ' . $last ) );
};

// -------------------- 223: Cohabitants --------------------
$cohabitants = [];

$para_nested(
	$entry,
	$REL_PARENT_FID,
	function ( array $child ) use ( &$cohabitants, $get_rel_name, $REL_CHILD, $child_val ) : string {

		$name = $get_rel_name( $child );
		$rel  = trim( (string) $child_val( $child, (string) $REL_CHILD['rel'] ) );

		if ( $name === '' && $rel === '' ) {
			return '';
		}

		if ( $name !== '' && $rel !== '' ) {
			$cohabitants[] = $name . ' (' . $rel . ')';
		} else {
			$cohabitants[] = ( $name !== '' ) ? $name : $rel;
		}

		return '';
	}
);

$cohabitants = array_values( array_filter( array_map( 'trim', (array) $cohabitants ) ) );
if ( ! empty( $cohabitants ) ) {
	$cohabitants = array_values( array_unique( $cohabitants ) );
}

// -------------------- Narrative paragraph #1 --------------------
if ( ! empty( $cohabitants ) ) {
	$para( $A . ' currently resides with ' . $oxford_join( $cohabitants, 'and' ) . '.' );
} else {
	$para( $A . ' reports no current cohabitants.' );
}

// -------------------- Domestic / RO / LE (narrative, combined) --------------------
$events = [];
$details_blocks = [];

// Restraining order
$ro = $yn( $FID_RO_YN );
if ( $ro === 'Yes' ) {
	$events[] = 'having had a restraining order or stay-away order issued against them';
	$details_blocks[] = [ $val( $FID_RO_EXPLAIN, '' ), 'Restraining order details:' ];
}

// Domestic violence / physical confrontation
$dv = $yn( $FID_DV_YN );
if ( $dv === 'Yes' ) {
	$events[] = 'being involved in domestic violence or a physical confrontation with a domestic or romantic partner';
	$details_blocks[] = [ $val( $FID_DV_EXPLAIN, '' ), 'Domestic incident details:' ];
}

// Law enforcement response
$le = $yn( $FID_LE_YN );
if ( $le === 'Yes' ) {
	$events[] = 'having law enforcement respond due to a domestic dispute or argument involving a domestic or romantic partner';
	$details_blocks[] = [ $val( $FID_LE_EXPLAIN, '' ), 'Law enforcement response details:' ];
}

if ( ! empty( $events ) ) {

	$para( 'The applicant reports ' . $oxford_join( $events, 'and' ) . '.' );

	foreach ( $details_blocks as $row ) {
		$details = isset( $row[0] ) ? (string) $row[0] : '';
		$label   = isset( $row[1] ) ? (string) $row[1] : 'Details:';
		$para_comment( $details, $label );
	}

} else {

	// Only say a denial if the fields exist and were answered "No"
	$any_answered = in_array( $ro, [ 'Yes', 'No' ], true ) || in_array( $dv, [ 'Yes', 'No' ], true ) || in_array( $le, [ 'Yes', 'No' ], true );

	if ( $any_answered && $ro !== 'Yes' && $dv !== 'Yes' && $le !== 'Yes' ) {
		$para(
			'The applicant denies having had a restraining order issued against them, being involved in domestic violence, or having law enforcement respond due to a domestic dispute.'
		);
	}
}
?>