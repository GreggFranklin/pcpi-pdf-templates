<h2>Education</h2>
<?php
/**
 * Education (Report-like) — Option A (Narrative list of schools)
 * - Summary sentence (YES-only credentials)
 * - One narrative “details” paragraph (POST certs + CO academy + what lists follow)
 * - One narrative paragraph describing school/academy attendance (no data-dump lines)
 *
 * FIX: Nested child-field keys updated to match Form 23 gpnfFields:
 * - 235 uses 1,5,6
 * - 240 uses 1,3,4,5
 * - 242 uses 1,3,4
 */

/* ---------------------------------------------------------
 * Small local helper
 * --------------------------------------------------------- */
$clean = function( $s ) {
	$s = trim( wp_strip_all_tags( (string) $s ) );
	$s = preg_replace( '/\s+/', ' ', $s );
	return $s;
};

/* ---------------------------------------------------------
 * YES/NO → summary sentence (YES only)
 * --------------------------------------------------------- */
$edu_yn_yes_phrases = [
	225 => 'having a High School Diploma',
	227 => 'having a GED',
	226 => 'having a Proficiency Certificate',
	229 => 'having a College Degree',
	230 => 'having a Graduate Degree',
	232 => 'having a Doctorate Degree',
	231 => 'having completed a POST training academy',
	233 => 'having started but not completed a POST training academy',
	234 => 'having completed a certified fire academy',
];

$edu_yes_bits   = [];
$edu_any_answer = false;

foreach ( $edu_yn_yes_phrases as $fid => $phrase ) {
	$ans = $yn( $fid );
	if ( $ans === '' ) {
		continue;
	}
	$edu_any_answer = true;

	if ( $ans === 'Yes' ) {
		$edu_yes_bits[] = $phrase;
	}
}

if ( ! empty( $edu_yes_bits ) ) {
	$para( $A . ' reports ' . $oxford_join( $edu_yes_bits ) . '.' );
} elseif ( $edu_any_answer ) {
	// Optional: remove this line if you prefer silence when no YES credentials are endorsed
	$para( $A . ' reports no additional education credentials beyond the information provided.' );
}

/* ---------------------------------------------------------
 * Details fields (simple)
 * --------------------------------------------------------- */
$post_certs = $clean( $val( '63', '' ) );

$co_academy_raw = $clean( $val( '238', '' ) );
$co_date_raw    = $clean( $val( '239', '' ) );
$co_date_fmt    = $co_date_raw !== '' ? $fmt_date( $co_date_raw ) : '';

/* ---------------------------------------------------------
 * Nested collectors
 * Parent field IDs:
 * - 235: POST Training Academies (Academy Name, Type, Date Completed Training)
 * - 240: College Degrees (Institution, Date of degree, Degree Level, Majors)
 * - 242: College Attendance (No Degree) (Institution, Major, Units)
 * --------------------------------------------------------- */
$post_academy_lines   = [];
$college_degree_lines = [];
$college_attend_lines = [];

/** POST Training Academies (parent 235)
 * Form 23 gpnfFields: 1,5,6
 */
$post_builder = function( array $child ) use ( &$post_academy_lines, $child_val, $fmt_date, $clean ) {

	$academy = $clean( $child_val( $child, '1' ) );
	$type    = $clean( $child_val( $child, '5' ) );
	$date    = $clean( $child_val( $child, '6' ) );

	if ( $academy === '' && $type === '' && $date === '' ) {
		return '';
	}

	$bits = [];
	if ( $academy !== '' ) { $bits[] = $academy; }
	if ( $type !== '' )    { $bits[] = $type; }
	if ( $date !== '' )    { $bits[] = $fmt_date( $date ); }

	$post_academy_lines[] = 'POST academy: ' . implode( ' — ', $bits ) . '.';
	return '';
};
$para_nested( $entry, 235, $post_builder, 0 );

/** College Degrees (parent 240)
 * Form 23 gpnfFields: 1,3,4,5
 */
$degree_builder = function( array $child ) use ( &$college_degree_lines, $child_val, $fmt_date, $clean ) {

	$inst   = $clean( $child_val( $child, '1' ) );
	$date   = $clean( $child_val( $child, '3' ) );
	$level  = $clean( $child_val( $child, '4' ) );
	$majors = $clean( $child_val( $child, '5' ) );

	if ( $inst === '' && $date === '' && $level === '' && $majors === '' ) {
		return '';
	}

	$bits = [];
	if ( $inst !== '' )   { $bits[] = $inst; }
	if ( $level !== '' )  { $bits[] = $level; }
	if ( $majors !== '' ) { $bits[] = 'Majors: ' . $majors; }
	if ( $date !== '' )   { $bits[] = 'Date: ' . $fmt_date( $date ); }

	$college_degree_lines[] = 'College degree: ' . implode( ' — ', $bits ) . '.';
	return '';
};
$para_nested( $entry, 240, $degree_builder, 0 );

/** College Attendance (No Degree) (parent 242)
 * Form 23 gpnfFields: 1,3,4
 */
$attend_builder = function( array $child ) use ( &$college_attend_lines, $child_val, $clean ) {

	$inst  = $clean( $child_val( $child, '1' ) );
	$major = $clean( $child_val( $child, '3' ) );
	$units = $clean( $child_val( $child, '4' ) );

	if ( $inst === '' && $major === '' && $units === '' ) {
		return '';
	}

	$bits = [];
	if ( $inst !== '' )  { $bits[] = $inst; }
	if ( $major !== '' ) { $bits[] = 'Major: ' . $major; }
	if ( $units !== '' ) { $bits[] = 'Units: ' . $units; }

	$college_attend_lines[] = 'College attendance: ' . implode( ' — ', $bits ) . '.';
	return '';
};
$para_nested( $entry, 242, $attend_builder, 0 );

/* ---------------------------------------------------------
 * ONE narrative “details” paragraph (no repetitive headers)
 * --------------------------------------------------------- */
$clauses = [];

// POST certs clause
if ( $post_certs !== '' ) {
	$clauses[] = 'reports the following POST certificates: ' . $post_certs;
}

// Correctional academy clause
if ( $co_academy_raw !== '' || $co_date_fmt !== '' ) {
	$c = 'reports completing a correctional officer academy';
	if ( $co_academy_raw !== '' ) {
		$c .= ' (' . $co_academy_raw . ')';
	}
	if ( $co_date_fmt !== '' ) {
		$c .= ' on approximately ' . $co_date_fmt;
	}
	$clauses[] = $c;
}

// Which lists follow (only if they have rows)
$list_bits = [];
if ( ! empty( $post_academy_lines ) )   { $list_bits[] = 'POST training academy information'; }
if ( ! empty( $college_degree_lines ) ) { $list_bits[] = 'college degree information'; }
if ( ! empty( $college_attend_lines ) ) { $list_bits[] = 'college attendance information (no degree indicated)'; }

if ( ! empty( $list_bits ) ) {
	$clauses[] = 'provided additional details below, including ' . $oxford_join( $list_bits );
}

if ( ! empty( $clauses ) ) {
	$first    = array_shift( $clauses );
	$sentence = $A . ' ' . rtrim( $first, '.' );

	if ( ! empty( $clauses ) ) {
		$sentence .= '. The applicant ' . rtrim( $oxford_join( $clauses ), '.' );
	}

	$para( $sentence . '.' );
}

/* ---------------------------------------------------------
 * Narrative paragraph describing schools/academies attended
 * --------------------------------------------------------- */
$edu_narrative_bits = [];

// POST academies
if ( ! empty( $post_academy_lines ) ) {
	$academies = [];

	foreach ( $post_academy_lines as $line ) {
		$academies[] = rtrim( preg_replace( '/^POST academy:\s*/i', '', $line ), '.' );
	}

	$edu_narrative_bits[] =
	'attendance at a POST training academy: ' . $oxford_join( $academies );
}

// College degrees
if ( ! empty( $college_degree_lines ) ) {
	$degrees = [];

	foreach ( $college_degree_lines as $line ) {
		$degrees[] = rtrim( preg_replace( '/^College degree:\s*/i', '', $line ), '.' );
	}

	$edu_narrative_bits[] =
	'completion of a college degree: ' . $oxford_join( $degrees );
}

// College attendance (no degree)
if ( ! empty( $college_attend_lines ) ) {
	$attended = [];

	foreach ( $college_attend_lines as $line ) {
		$attended[] = rtrim( preg_replace( '/^College attendance:\s*/i', '', $line ), '.' );
	}

	$edu_narrative_bits[] =
	'additional college attendance: ' . $oxford_join( $attended );
}

if ( ! empty( $edu_narrative_bits ) ) {
	$para(
		'The applicant’s education history includes ' .
		$oxford_join( $edu_narrative_bits ) .
		'.'
	);
}

/* ---------------------------------------------------------
 * Comments
 * --------------------------------------------------------- */
$para_comment( $edu_comments, 'Regarding education, ' . $A . ' noted:' );
$para_comment( $exam_edu_comments, 'Examiner noted:' );
?>