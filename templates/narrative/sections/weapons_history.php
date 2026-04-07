<h2>Weapons History</h2>
<?php
/**
 * Weapons History — Narrative (Yes/No + List-date coverage)
 *
 * Covers:
 * Y/N: 345, 347, 420, 549, 550, 551, 552, 553, 554
 * Dates (List): 564–572 (mapped below)
 * Comments: 555 ($weapons_comments), 638 ($exam_weapons_comments)
 */

$weapons_items = [
	345 => [ 'possessing illegal firearms', 564 ],
	347 => [ 'threatening another person with a firearm or pointing a firearm at another person', 565 ],
	420 => [ 'accidentally discharging a firearm or illegally firing a weapon', 566 ],
	549 => [ 'illegally carrying or transporting a loaded or concealed firearm', 567 ],
	550 => [ 'using a firearm in the commission of a crime', 568 ],
	551 => [ 'threatening anyone with a firearm', 569 ],
	552 => [ 'being involved in a shooting incident', 570 ],
	553 => [ 'brandishing a firearm or weapon in a threatening manner', 571 ],
	554 => [ 'threatening anyone with a knife', 572 ],
];

/* ---------------------------------------------------------
 * Build one narrative paragraph (Yes-only + date/dates)
 * --------------------------------------------------------- */
$admissions = [];

foreach ( $weapons_items as $yn_id => $cfg ) {
	[ $label, $dates_fid ] = $cfg;

	if ( $yn( $yn_id ) !== 'Yes' ) {
		continue;
	}

	$piece = $label;

	// List field -> pull items and format as pretty dates
	$date_items = $list_field_items( $dates_fid );
	$date_str   = $list_field_dates_pretty( $dates_fid, 'F d, Y' );

	if ( $date_str !== '' ) {
		$date_label = ( count( $date_items ) > 1 ) ? 'dates' : 'date';
		$piece     .= " ({$date_label}: {$date_str})";
	}

	$admissions[] = $piece;
}

$admissions = array_values( array_filter( array_map( 'trim', $admissions ) ) );

if ( ! empty( $admissions ) ) {
	$para(
		$A .
		' reports the following weapons history: ' .
		$oxford_join( $admissions, 'and' ) .
		'.'
	);
} else {
	$para( $A . ' reports no weapons-related misconduct or weapon-related threats.' );
}

/* ---------------------------------------------------------
 * Comments
 * --------------------------------------------------------- */
$para_comment( $weapons_comments, 'Regarding weapons history, ' . $A . ' noted:' );
$para_comment( $exam_weapons_comments, 'Examiner noted:' );
?>