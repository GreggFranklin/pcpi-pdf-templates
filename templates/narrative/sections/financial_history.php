<h2>Financial History</h2>
<?php
$sentences = [];

/* ---------------------------------------------------------
 * FINANCIAL YES-ONLY ADMISSIONS
 * --------------------------------------------------------- */
$financial_map = [
	360 => 'filing for bankruptcy',
	361 => 'having wages garnished',
	362 => 'having tax liens filed',
	363 => 'having property repossessed',
	364 => 'having a foreclosure',
	365 => 'having civil judgments entered',
	366 => 'having delinquent or collection accounts',
	367 => 'failing to pay financial obligations as agreed',
	368 => 'financial problems interfering with personal or professional responsibilities',
];

$admissions = [];

foreach ( $financial_map as $fid => $phrase ) {
	if ( $yn( $fid ) === 'Yes' ) {
		$admissions[] = $phrase;
	}
}

/* ---------------------------------------------------------
 * NARRATIVE OUTPUT
 * --------------------------------------------------------- */
if ( empty( $admissions ) ) {

	// Clean negative compression
	$sentences[] =
		$A .
		' denies any significant financial difficulties, including bankruptcy, wage garnishments, tax liens, civil judgments, delinquent accounts, or other unresolved financial obligations.';

} else {

	// Enumerate only what was admitted
	$sentences[] =
		$A .
		' reported the following financial history: ' .
		$oxford_join( $admissions, 'and' ) .
		'.';

	$sentences[] =
		'The applicant denied any other significant financial difficulties.';
}

/* ---------------------------------------------------------
 * OUTPUT
 * --------------------------------------------------------- */
if ( ! empty( $sentences ) ) {
	$para( implode( ' ', $sentences ) );
}

/* ---------------------------------------------------------
 * COMMENTS
 * --------------------------------------------------------- */
$para_comment(
	$financial_comments,
	'Regarding financial history, ' . $A . ' noted:'
);

$para_comment(
	$exam_financial_comments,
	'Examiner noted:'
);
?>