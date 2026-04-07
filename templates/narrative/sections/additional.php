<h2>Additional Information</h2>
<?php
/**
 * Additional Information — Narrative
 * - One flowing paragraph when applicable
 * - $A once, then "The applicant..."
 * - Full field coverage preserved
 * - Adds negative compression so the section never prints empty
 */

/* ---------------------------------------------------------
 * Collect admissions (Yes-only)
 * --------------------------------------------------------- */
$items = [
	114 => 'having been the subject of an internal affairs investigation',
	115 => 'having been accused of misconduct',
	116 => 'having been investigated for misconduct',
	117 => 'having been disciplined by an employer',
	118 => 'having resigned in lieu of termination',
	119 => 'having been terminated from employment',
	121 => 'having been the subject of a civil lawsuit',
	122 => 'having been named in a criminal or administrative proceeding',
];

$admissions = [];

foreach ( $items as $fid => $phrase ) {
	if ( $yn( $fid ) === 'Yes' ) {
		$admissions[] = $phrase;
	}
}

/* ---------------------------------------------------------
 * Dropdown / text fields (112 / 113)
 * --------------------------------------------------------- */
$additional_bits = [];

$v112 = trim( (string) $val( 112, '' ) );
if ( $v112 !== '' ) {
	$additional_bits[] = $v112;
}

$v113 = trim( (string) $val( 113, '' ) );
if ( $v113 !== '' ) {
	$additional_bits[] = $v113;
}

/* ---------------------------------------------------------
 * Build narrative paragraph
 * --------------------------------------------------------- */
$sentences = [];

// If nothing is endorsed and no disclosures in 112/113, print one denial sentence.
if ( empty( $admissions ) && empty( $additional_bits ) ) {

	$sentences[] =
		$A .
		' denied any additional reportable issues such as internal affairs investigations, misconduct allegations or discipline, resigning in lieu of termination, termination from employment, or involvement in civil, criminal, or administrative proceedings.';

} else {

	// Sentence 1 — admissions (anchor with $A)
	if ( ! empty( $admissions ) ) {
		$sentences[] =
			$A .
			' reported ' .
			$oxford_join( $admissions ) .
			'.';
	}

	// Sentence 2 — additional dropdown/text disclosures
	if ( ! empty( $additional_bits ) ) {
		$sentences[] =
			'The applicant additionally reported ' .
			$oxford_join( $additional_bits ) .
			'.';
	}
}

// Output paragraph
if ( ! empty( $sentences ) ) {
	$para( implode( ' ', $sentences ) );
}

/* ---------------------------------------------------------
 * Explanation textarea (334)
 * --------------------------------------------------------- */
$additional_explain = trim( (string) $val( 334, '' ) );
if ( $additional_explain !== '' ) {
	$para_comment(
		$additional_explain,
		'Regarding these matters, ' . $A . ' explained:'
	);
}

/* ---------------------------------------------------------
 * Applicant comments (120 via _core-values)
 * --------------------------------------------------------- */
$para_comment(
	$additional_comments,
	'Regarding additional information, ' . $A . ' noted:'
);

/* ---------------------------------------------------------
 * Examiner comments (640 via adminLabel)
 * --------------------------------------------------------- */
$para_comment(
	$exam_additional_comments,
	'Examiner noted:'
);
?>