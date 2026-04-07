<h2>Aggressive Behavior</h2>
<?php
$aggressive_items = [
	525 => [ 'being involved in murder', 526 ],
	527 => [ 'causing the death of another under some circumstance(s)', 528 ],
	529 => [ 'committing a physical assault with a weapon', 530 ],
	531 => [ 'hitting, kicking, or striking another person outside of sanctioned duty, training, or play fighting', 532 ],
	533 => [ 'engaging in physical aggression toward a spouse or partner outside of play fighting', 534 ],
	535 => [ 'inflicting discipline on a child resulting in bruises or injury', 536 ],
	537 => [ 'intentionally inflicting pain, injury, or death on an animal outside of legal hunting', 538 ],
	539 => [ 'intentionally causing physical harm to another person', 540 ],
	541 => [ 'destroying or damaging property in anger', 542 ],
	543 => [ 'engaging in vandalism', 544 ],
	545 => [ 'committing arson (intentionally setting a fire that caused damage)', 546 ],
	547 => [ 'participating in a riot involving violence or destruction of property', 548 ],
];

/**
 * Robust date list formatter for GF List fields (single-column “Dates” list).
 * Uses $list_field_items() (which you already have) and formats each value.
 */
$fmt_list_dates = function( $list_field_id ) use ( $list_field_items, $fmt_date ) : array {

	$rows = $list_field_items( $list_field_id );
	if ( empty( $rows ) || ! is_array( $rows ) ) {
		return [];
	}

	$out = [];

	foreach ( $rows as $row ) {

		// GF list rows can be:
		// - string (single col)
		// - array with numeric keys
		// - array with labeled keys
		$raw = '';

		if ( is_string( $row ) ) {
			$raw = $row;
		} elseif ( is_array( $row ) ) {
			// Prefer first non-empty cell
			foreach ( $row as $cell ) {
				$cell = trim( (string) $cell );
				if ( $cell !== '' ) {
					$raw = $cell;
					break;
				}
			}
		}

		$raw = trim( (string) $raw );
		if ( $raw === '' ) {
			continue;
		}

		// Use your existing date formatter (handles mm/dd/yyyy consistently across sections)
		$pretty = $fmt_date( $raw );
		$out[]  = $pretty !== '' ? $pretty : $raw;
	}

	$out = array_values( array_filter( array_map( 'trim', $out ) ) );

	// De-dupe while preserving order
	$seen = [];
	$uniq = [];
	foreach ( $out as $d ) {
		if ( isset( $seen[ $d ] ) ) {
			continue;
		}
		$seen[ $d ] = true;
		$uniq[]     = $d;
	}

	return $uniq;
};

// ------------------------------------------------------------------
// ONE COMBINED NARRATIVE PARAGRAPH (Yes-only + pretty date/dates)
// ------------------------------------------------------------------
$aggressive_admissions = [];

foreach ( $aggressive_items as $yn_id => $cfg ) {
	[ $label, $dates_fid ] = $cfg;

	if ( $yn( $yn_id ) !== 'Yes' ) {
		continue;
	}

	$piece = $label;

	$dates = $fmt_list_dates( $dates_fid );

	if ( ! empty( $dates ) ) {
		$date_label = ( count( $dates ) > 1 ) ? 'dates' : 'date';
		$piece     .= ' (' . $date_label . ': ' . implode( '; ', $dates ) . ')';
	}

	$aggressive_admissions[] = $piece;
}

$aggressive_admissions = array_values( array_filter( array_map( 'trim', $aggressive_admissions ) ) );

if ( ! empty( $aggressive_admissions ) ) {

	// Use your existing join style (works fine)
	if ( count( $aggressive_admissions ) === 1 ) {
		$text = $A . ' reports the following aggressive behavior history: ' . $aggressive_admissions[0] . '.';
	} else {
		$tmp  = $aggressive_admissions;
		$last = array_pop( $tmp );
		$list = ( count( $tmp ) > 1 )
			? implode( ', ', $tmp ) . ', and ' . $last
			: $tmp[0] . ' and ' . $last;

		$text = $A . ' reports the following aggressive behavior history: ' . $list . '.';
	}

	$para( $text );

} else {
	$para( $A . ' reports no aggressive behavior.' );
}

// ------------------------------------------------------------------
// COMMENTS
// ------------------------------------------------------------------
$para_comment( $aggressive_comments, 'Regarding aggressive behavior, ' . $A . ' noted:' );
$para_comment( $exam_aggressive_comments, 'Examiner noted:' );
?>