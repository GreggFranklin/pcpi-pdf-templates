<h2>Subversive Activities</h2>
<?php
/**
 * SUBVERSIVE / ASSOCIATIONS — NARRATIVE STYLE
 *
 * Approach:
 * - Collect all endorsed items first
 * - If none → one consolidated denial paragraph (negative compression)
 * - If any → one narrative paragraph with specifics
 */

$items = [];

/**
 * Collector helper
 */
$collect = function ( $yn_id, $label, $from_id = null, $to_id = null ) use ( $yn, $val, $fmt_date, &$items ) {

	if ( $yn( $yn_id ) !== 'Yes' ) {
		return;
	}

	$phrase = $label;

	$from_raw = $from_id ? trim( (string) $val( (string) $from_id, '' ) ) : '';
	$to_raw   = $to_id   ? trim( (string) $val( (string) $to_id, '' ) ) : '';

	// Pretty formatting (safe fallback if not parseable)
	$from = $from_raw !== '' ? $fmt_date( $from_raw, 'F d, Y' ) : '';
	$to   = $to_raw   !== '' ? $fmt_date( $to_raw, 'F d, Y' )   : '';

	if ( $from || $to ) {
		if ( $from && $to ) {
			$phrase .= " from {$from} to {$to}";
		} elseif ( $from ) {
			$phrase .= " beginning in {$from}";
		} elseif ( $to ) {
			$phrase .= " ending in {$to}";
		}
	}

	$items[] = $phrase;
};

/* ------------------------------------------------------------
 * Collect endorsements
 * ------------------------------------------------------------ */
$collect( 123, 'membership in a subversive organization', 124, 125 );
$collect( 127, 'membership in a hate or racist group', 128, 129 );
$collect( 130, 'membership in an organization that practices discrimination', 131, 132 );
$collect( 133, 'dealings with agents of a foreign government', 134, 135 );
$collect( 136, 'living with an individual actively involved in criminal activity', 137, 138 );
$collect( 139, 'living with an individual actively involved in illegal drug activity', 140, 141 );
$collect( 142, 'membership in a gang' );
$collect( 145, 'representing to others that they were a gang member' );

/* ------------------------------------------------------------
 * Narrative output
 * ------------------------------------------------------------ */
if ( empty( $items ) ) {

	// One-sentence negative compression (keeps scope, avoids repeating $A).
	$para(
		$A .
		' denies any past or present involvement with subversive organizations, hate-based or discriminatory groups, criminal gangs, dealings with agents of foreign governments, or associations with individuals engaged in criminal or illegal drug-related activities.'
	);

} else {

	$list = $oxford_join( $items, 'and' );

	$para(
		$A .
		' acknowledges involvement in the following activities: ' .
		$list .
		'.'
	);
}

/* ------------------------------------------------------------
 * Comments
 * ------------------------------------------------------------ */
$para_comment(
	$associations_comments,
	'Regarding subversive activities, ' . $A . ' noted:'
);
$para_comment( $exam_associations_comments, 'Examiner noted:' );
?>