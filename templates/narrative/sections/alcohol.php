<h2>Use of Alcohol</h2>
<?php
/**
 * Alcohol — Legacy Narrative Style (full field coverage, $A standard)
 * - Uses $A once, then "The applicant..."
 * - Negative compression: if no "Yes" answers, output a tight denial paragraph
 * - Includes missing field 316 (treatment/AA/rehab)
 */

$S = 'The applicant';

/* ---------------------------------------------------------
 * Paragraph 1 — Alcohol consumption description (296)
 * --------------------------------------------------------- */
$alcohol_consumption_raw = (string) $val( '296', '' );
$alcohol_consumption     = trim( $alcohol_consumption_raw );

if ( $alcohol_consumption !== '' ) {
	$para( $A . ' describes their alcohol use as "' . $alcohol_consumption . '".' );
}

/* ---------------------------------------------------------
 * Alcohol Y/N fields (297–307)
 * We only treat "Yes" as admissions, for narrative clarity.
 * --------------------------------------------------------- */
$alcohol_yes_only_map = [
	297 => 'considering themselves as having an alcohol problem',
	298 => 'others indicating they have a problem with alcohol',
	299 => 'operating a motor vehicle under the influence of alcohol',
	300 => 'operating a motor vehicle while “buzzed”',
	301 => 'committing crimes while under the influence of alcohol',
	302 => 'alcohol use interfering with work duties or activities',
	303 => 'consuming alcohol while at work',
	304 => 'alcohol use interfering with school activities',
	305 => 'arriving at work intoxicated or hung over',
	306 => 'consuming alcohol while underage',
	307 => 'providing an alcoholic beverage to a minor',
];

$admissions = [];
foreach ( $alcohol_yes_only_map as $fid => $phrase ) {
	if ( $yn( $fid ) === 'Yes' ) {
		$admissions[] = $phrase;
	}
}

/* ---------------------------------------------------------
 * Treatment / AA / rehab (316) — Yes-only
 * (this field was missing in your current file)
 * --------------------------------------------------------- */
$treatment = ( $yn( 316 ) === 'Yes' );

/* ---------------------------------------------------------
 * Narrative output
 * --------------------------------------------------------- */
$has_any_yes = ( ! empty( $admissions ) || $treatment );

if ( ! $has_any_yes ) {
	$para( $S . ' denied any alcohol-related problems, misuse, or alcohol-related misconduct.' );

	$para_comment( $alcohol_explain, 'Regarding alcohol history, ' . $A . ' noted:' );
	$para_comment( $exam_alcohol_explain, 'Examiner noted:' );

	return;
}

/* Admissions paragraph */
$parts = [];

if ( ! empty( $admissions ) ) {
	$parts[] = $S . ' admitted to ' . $oxford_join( $admissions ) . '.';
}

if ( $treatment ) {
	$parts[] = $S . ' also reported participation in alcohol treatment and/or a recovery program.';
}

// Tight denial after admissions (old-report style)
$parts[] = $S . ' denied any other significant alcohol-related issues.';

$para( implode( ' ', array_values( array_filter( array_map( 'trim', $parts ) ) ) ) );

/* ---------------------------------------------------------
 * Comments
 * --------------------------------------------------------- */
$para_comment( $alcohol_explain, 'Regarding alcohol history, ' . $A . ' noted:' );
$para_comment( $exam_alcohol_explain, 'Examiner noted:' );
?>