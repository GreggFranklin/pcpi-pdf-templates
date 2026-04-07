<h2>Social Media</h2>
<?php
/**
 * Social Media – Narrative Report Style
 * - Single subject (Applicant {Name})
 * - Gerund-based clauses
 * - Includes platform list (Field 580)
 * - Oxford comma handling
 * - CA Labor Code §980 compliant
 *
 * Coverage:
 * - 213, 214, 217, 216, 218, 219, 220, 580
 * - Comments via variables (likely 221 / 627 in _core-values)
 */

// Gate question: current social media use
$ans_213 = $yn( 213 );

// If applicant explicitly says "No"
if ( $ans_213 === 'No' ) {

	$para( $A . ' reports not currently using social media.' );

	$para_comment(
		$social_comments,
		'Regarding social media, ' . $A . ' noted:'
	);
	$para_comment( $exam_social_comments, 'Examiner noted:' );

	return;
}

// If unanswered, but we still want graceful behavior:
// only proceed if we have any Social Media data at all; otherwise keep it quiet.
$platforms_value = $val( '580', '' );
$has_any_social_data = (
	trim( (string) $platforms_value ) !== '' ||
	$yn( 214 ) !== '' ||
	$yn( 217 ) !== '' ||
	$yn( 216 ) !== '' ||
	$yn( 218 ) !== '' ||
	$yn( 219 ) !== '' ||
	$yn( 220 ) !== '' ||
	trim( (string) $social_comments ) !== '' ||
	trim( (string) $exam_social_comments ) !== ''
);

if ( $ans_213 === '' && ! $has_any_social_data ) {
	// Nothing to report.
	return;
}

// ------------------------------------------------------------
// Normalize Social Media Platforms (Checkbox field 580)
// ------------------------------------------------------------
$platform_items = [];

if ( is_array( $platforms_value ) ) {
	$platform_items = $platforms_value;
} else {
	$s = trim( (string) $platforms_value );

	// Normalize separators
	$s = str_replace( [ "\r\n", "\n", "\r", '|' ], ',', $s );
	$parts = preg_split( '/\s*,\s*/', $s, -1, PREG_SPLIT_NO_EMPTY );

	// Fallback if GF returns space-separated values
	if ( count( $parts ) === 1 && strpos( $parts[0], ' ' ) !== false ) {
		$parts = preg_split( '/\s+/', $parts[0], -1, PREG_SPLIT_NO_EMPTY );
	}

	$platform_items = $parts;
}

// Clean + unique
$platform_items = array_values(
	array_unique(
		array_filter(
			array_map( 'trim', (array) $platform_items )
		)
	)
);

$platforms = ! empty( $platform_items )
	? $oxford_join( $platform_items, 'and' )
	: 'social media';

// ------------------------------------------------------------
// Build narrative clauses (gerund-based)
// ------------------------------------------------------------
$clauses = [];

// Opening clause (ONLY assert "currently" if 213 is Yes; otherwise stay neutral)
if ( $ans_213 === 'Yes' ) {
	$clauses[] = 'currently using ' . $platforms;
} else {
	$clauses[] = 'reporting social media history involving ' . $platforms;
}

// Accounts in own name/email
$ans_214 = $yn( 214 );
if ( $ans_214 === 'Yes' ) {
	$clauses[] = 'maintaining accounts registered in their name and/or email address';
} elseif ( $ans_214 === 'No' ) {
	$clauses[] = 'not maintaining accounts registered in their name and/or email address';
}

// Accounts in someone else’s name
$ans_217 = $yn( 217 );
if ( $ans_217 === 'Yes' ) {
	$clauses[] = 'utilizing accounts registered in someone else’s name and/or email address';
} elseif ( $ans_217 === 'No' ) {
	$clauses[] = 'not utilizing social media accounts registered to others';
}

// Interview discussion (ONLY if there is narrative evidence)
$has_social_narrative = ( trim( (string) $social_comments ) !== '' || trim( (string) $exam_social_comments ) !== '' );
if ( $has_social_narrative ) {
	$clauses[] = 'discussing social media activity during the interview';
}

// Offensive / derogatory content
$ans_216 = $yn( 216 );
if ( $ans_216 === 'Yes' ) {
	$clauses[] = 'having posted racist, prejudiced, offensive, or derogatory content';
} elseif ( $ans_216 === 'No' ) {
	$clauses[] = 'denying the posting of racist, prejudiced, offensive, or derogatory content';
}

// Misinterpretable content
$ans_218 = $yn( 218 );
if ( $ans_218 === 'Yes' ) {
	$clauses[] = 'having posted content that could be misinterpreted as racist, prejudiced, offensive, or derogatory';
} elseif ( $ans_218 === 'No' ) {
	$clauses[] = 'denying the posting of content believed to be misinterpretable as racist, prejudiced, offensive, or derogatory';
}

// Delayed posts
$ans_219 = $yn( 219 );
if ( $ans_219 === 'Yes' ) {
	$clauses[] = 'delaying posts since beginning the application process due to concerns they may be viewed as inappropriate or disqualifying';
} elseif ( $ans_219 === 'No' ) {
	$clauses[] = 'not delaying posts since beginning the application process for that reason';
}

// Disqualifying content
$ans_220 = $yn( 220 );
if ( $ans_220 === 'Yes' ) {
	$clauses[] = 'having posted content believed to be potentially disqualifying';
} elseif ( $ans_220 === 'No' ) {
	$clauses[] = 'denying posting content believed to be disqualifying';
}

// ------------------------------------------------------------
// Oxford-join clauses and output narrative
// ------------------------------------------------------------
$sentence = $oxford_join( $clauses, 'and' );
$para( $A . ' ' . $sentence . '.' );

// ------------------------------------------------------------
// CA Labor Code §980 disclosure
// ------------------------------------------------------------
$para_html(
	'California Labor Code §980 prohibits an employer from requiring or requesting an employee or applicant for employment ' .
	'to disclose a username or password for the purpose of accessing personal social media. ' .
	'Accordingly, I did not request ' . esc_html( $A ) . ' to provide usernames or passwords for any social media accounts.'
);

// ------------------------------------------------------------
// Comments
// ------------------------------------------------------------
$para_comment(
	$social_comments,
	'Regarding social media, ' . $A . ' noted:'
);
$para_comment( $exam_social_comments, 'Examiner noted:' );
?>