<h2>Residential History</h2>
<?php
/**
 * Residential History — Legacy Narrative Style (full field coverage, $A standard)
 * Fields:
 * 261–266 = Y/N admissions
 * 267     = applicant explanation -> $residential_explain (via _core-values)
 * 630     = examiner comments    -> $exam_residential_explain (adminLabel)
 */

$res_yn = [
	261 => 'being late paying rent or a mortgage payment',
	262 => 'moving without giving appropriate notice to a landlord',
	263 => 'being evicted from a residence',
	264 => 'forfeiting or being refused a return of a deposit by a landlord',
	265 => 'a landlord taking civil or criminal action for past-due rent',
	266 => 'receiving negative comments from a neighbor or former landlord',
];

/* ---------------------------------------------------------
 * Collect YES admissions only
 * --------------------------------------------------------- */
$admissions = [];
foreach ( $res_yn as $fid => $phrase ) {
	if ( $yn( $fid ) === 'Yes' ) {
		$admissions[] = $phrase;
	}
}

/* ---------------------------------------------------------
 * Narrative output
 * --------------------------------------------------------- */
if ( ! empty( $admissions ) ) {

	$para(
		$A .
		' reported the following residential history: ' .
		$oxford_join( $admissions ) .
		'. The applicant denied any other significant residential issues.'
	);

} else {

	$para(
		$A .
		' denied any significant residential issues, including late rent or mortgage payments, evictions, deposit disputes, landlord actions for past-due rent, or negative comments from neighbors or landlords.'
	);
}

/* ---------------------------------------------------------
 * Comments (267) + Examiner comments (630)
 * --------------------------------------------------------- */
$para_comment(
	$residential_explain,
	'Regarding residential history, ' . $A . ' noted:'
);

$para_comment(
	$exam_residential_explain,
	'Examiner noted:'
);
?>