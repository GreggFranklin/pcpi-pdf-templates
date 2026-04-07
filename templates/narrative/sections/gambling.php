<h2>Gambling</h2>
<?php
/**
 * Gambling — Legacy Narrative Style (full field coverage, $A standard)
 * - Uses $A once, then "The applicant..."
 * - Tight denial when no "Yes" answers
 * - Still reports loss amounts if provided
 * - Uses $oxford_join for clean narrative flow
 */

/* ---------------------------------------------------------
 * Helper: money formatting
 * Accepts: 50000, 50,000, $50,000, 50,000.00, etc.
 * Returns: $50,000 (no decimals) or '' if empty/unparseable.
 * --------------------------------------------------------- */
$format_money = function ( $raw ): string {
	$raw = trim( (string) $raw );
	if ( $raw === '' ) {
		return '';
	}

	$clean = preg_replace( '/[^0-9.\-]/', '', $raw );
	if ( $clean === '' || $clean === '-' || $clean === '.' ) {
		return '';
	}

	$num      = (float) $clean;
	$decimals = ( abs( $num - round( $num ) ) < 0.00001 ) ? 0 : 2;

	return '$' . number_format( $num, $decimals );
};

/* ---------------------------------------------------------
 * Y/N + date-list map (Yes-only admissions)
 * --------------------------------------------------------- */
$gambling_items = [
	[320, 'engaging in illegal gambling',           560],
	[329, 'owing money to a bookmaker',             561],
	[323, 'owing gambling debts',                   562],
	[333, 'diverting or borrowing money to gamble', 563],
];

$admissions = [];

foreach ( $gambling_items as $cfg ) {
	[ $yn_id, $label, $date_id ] = $cfg;

	if ( $yn( $yn_id ) !== 'Yes' ) {
		continue;
	}

	$piece = $label;

	// Pretty list dates + correct singular/plural label (lowercase)
	$date_raw   = $list_field_dates_pretty( $date_id, 'F d, Y' );
	$date_items = $list_field_items( $date_id );

	if ( $date_raw !== '' ) {
		$date_label = ( count( $date_items ) > 1 ) ? 'dates' : 'date';
		$piece     .= " ({$date_label}: {$date_raw})";
	}

	$admissions[] = $piece;
}

/* ---------------------------------------------------------
 * Loss amounts (583 / 584)
 * --------------------------------------------------------- */
$largest_loss   = $format_money( $val( 583, '' ) );
$total_loss_12m = $format_money( $val( 584, '' ) );

/* ---------------------------------------------------------
 * Output (legacy narrative)
 * --------------------------------------------------------- */
if ( ! empty( $admissions ) ) {

	$text = $A . ' reported the following gambling history: ' . $oxford_join( $admissions ) . '.';

	// Loss sentence(s) — keep $A only once
	if ( $largest_loss !== '' && $total_loss_12m !== '' ) {
		$text .= ' The applicant indicated a largest cumulative gambling loss of ' . $largest_loss .
			' and an approximate total gambling loss of ' . $total_loss_12m . ' over the past 12 months.';
	} elseif ( $largest_loss !== '' ) {
		$text .= ' The applicant indicated a largest cumulative gambling loss of ' . $largest_loss . '.';
	} elseif ( $total_loss_12m !== '' ) {
		$text .= ' The applicant indicated an approximate total gambling loss of ' . $total_loss_12m .
			' over the past 12 months.';
	}

	$para( $text );

} else {

	// Tight denial (covers 320/323/329/333)
	$para(
		$A .
		' denied engaging in illegal gambling, owing money to a bookmaker, owing gambling debts, or diverting or borrowing money to gamble.'
	);

	// If loss amounts exist, still report them (without implying illegality)
	$loss_bits = [];
	if ( $largest_loss !== '' ) {
		$loss_bits[] = 'a largest cumulative gambling loss of ' . $largest_loss;
	}
	if ( $total_loss_12m !== '' ) {
		$loss_bits[] = 'an approximate total gambling loss of ' . $total_loss_12m . ' over the past 12 months';
	}

	if ( ! empty( $loss_bits ) ) {
		$para( 'The applicant also reported ' . $oxford_join( $loss_bits ) . '.' );
	}
}

/* ---------------------------------------------------------
 * Comments (327) + Examiner comments (634)
 * --------------------------------------------------------- */
$para_comment( $gambling_comments, 'Regarding gambling history, ' . $A . ' noted:' );
$para_comment( $exam_gambling_comments, 'Examiner noted:' );
?>