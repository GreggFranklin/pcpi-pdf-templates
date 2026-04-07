<h2>Driving History</h2>
<?php
/**
 * Driving History — Legacy Narrative Style (project-standard: use $A)
 * - Use $A in the first sentence for consistent "Applicant {Full Name}" tone
 * - Then switch to "The applicant..." for the rest of the section
 * - Fixes double-period for insurance company (Inc..)
 */

/* ---------------------------------------------------------
 * Paragraph 1 — License & insurance status
 * --------------------------------------------------------- */
$sentences = [];

// Driver’s license
$license_ans = $yn( 245 );
if ( $license_ans === 'Yes' ) {
	$sentences[] = $A . ' possesses a valid driver’s license.';
} elseif ( $license_ans === 'No' ) {
	$sentences[] = $A . ' does not possess a valid driver’s license.';
}

// Restrictions/limitations
if ( $yn( 246 ) === 'Yes' ) {
	$sentences[] = 'The applicant indicated the license is subject to restrictions or limitations.';
}

// Insurance (+ company when provided)
$insurance_ans     = $yn( 247 );
$insurance_company = trim( (string) $val( 249, '' ) );
$insurance_company = rtrim( $insurance_company, ". \t\n\r\0\x0B" ); // prevents "Inc.."

if ( $insurance_ans === 'Yes' ) {
	$sentences[] = 'The applicant carries automobile liability insurance'
		. ( $insurance_company !== '' ? ' through ' . $insurance_company : '' )
		. '.';
} elseif ( $insurance_ans === 'No' ) {
	$sentences[] = 'The applicant does not carry automobile liability insurance.';
}

$sentences = array_values( array_filter( array_map( 'trim', $sentences ) ) );
if ( ! empty( $sentences ) ) {
	$para( implode( ' ', $sentences ) );
}

/* ---------------------------------------------------------
 * Paragraph 2 — Admissions / denials (Yes-only admissions)
 * --------------------------------------------------------- */
$negative_map = [
	254 => 'unresolved traffic citations within the past seven years',
	255 => 'failures to appear on traffic citations or traffic warrants within the past five years',
	257 => 'a drunk driving, reckless driving, speed contest, or exhibition of speed violation within the past five years',
	256 => 'being stopped by law enforcement within the past three years without receiving a citation',
	258 => 'attempting to evade a police officer either in a vehicle or on foot',
	259 => 'driving a motor vehicle within the past year while consuming an alcoholic beverage',
];

$admissions = [];
foreach ( $negative_map as $fid => $phrase ) {
	if ( $yn( $fid ) === 'Yes' ) {
		$admissions[] = $phrase;
	}
}

if ( ! empty( $admissions ) ) {
	$para( 'The applicant admitted to ' . $oxford_join( $admissions ) . '.' );
	$para( 'The applicant denied any other significant driving-related violations or issues.' );
} else {
	$para( 'The applicant denied any significant driving-related violations or issues.' );
}

/* ---------------------------------------------------------
 * Paragraph 3 — Seven-year totals (counts)
 * --------------------------------------------------------- */
$counts = [];

$bit = $count_phrase( trim( (string) $val( 250, '' ) ), 'moving traffic citation' );
if ( $bit !== '' ) { $counts[] = $bit; }

$bit = $count_phrase( trim( (string) $val( 251, '' ) ), 'equipment violation' );
if ( $bit !== '' ) { $counts[] = $bit; }

$bit = $count_phrase( trim( (string) $val( 252, '' ) ), 'traffic accident' );
if ( $bit !== '' ) { $counts[] = $bit; }

$bit = $count_phrase( trim( (string) $val( 253, '' ) ), 'parking citation' );
if ( $bit !== '' ) { $counts[] = $bit; }

if ( ! empty( $counts ) ) {
	$para( 'In the past seven years, the applicant reported ' . $oxford_join( $counts ) . '.' );
}

/* ---------------------------------------------------------
 * Applicant narrative (comments)
 * --------------------------------------------------------- */
$para_comment(
	$driving_comments,
	'Regarding driving history, the applicant explained:'
);

/* ---------------------------------------------------------
 * Examiner comments
 * --------------------------------------------------------- */
$para_comment(
	$exam_driving_comments,
	'Examiner noted:'
);
?>