<h2>General Information</h2>
<?php
/* ------------------------------------------------------------
 * PARAGRAPH: Date/Time applicant took questionnaire
 * ------------------------------------------------------------ */

$form1_entry_id = rgar( $entry, '1004' ); // hidden field holding Form 1 entry ID

$form1_entry = GFAPI::get_entry( absint( $form1_entry_id ) );
if ( is_wp_error( $form1_entry ) || ! is_array( $form1_entry ) ) {
	$form1_entry = [];
}

// Use shared formatter (safe if empty/missing)
$form1_time = $fmt_date( rgar( $form1_entry, 'date_created' ), 'F j, Y g:i A' );
$form2_time = $fmt_date( rgar( $entry, 'date_created' ), 'F j, Y g:i A' );

$sent_phrase = $form1_time !== '' ? ( 'was sent link on ' . $form1_time ) : 'was sent the questionnaire link';
$done_phrase = $form2_time !== '' ? ( 'completed the questionnaire on ' . $form2_time ) : 'completed the questionnaire';

$appt = $A . ' booked an appointment on X Day and ' . $sent_phrase . ', and ' . $done_phrase . '.';

$para( $appt );

/* ------------------------------------------------------------
 * PARAGRAPH 1: Identity, Residence, DOB, Agency, Position
 * ------------------------------------------------------------ */
$bits = [];

// Residence + DOB
$sentence = $A;

if ( $address_block ) {
	$sentence .= ' currently resides at ' . $address_block;
}

if ( $dob_formatted ) {
	$sentence .= ( $address_block ? ', and was born on ' : ' was born on ' ) . $dob_formatted;
}

$sentence .= '.';

// Agency / position
$ap = [];
if ( ! empty( $agency ) ) {
	$ap[] = 'the ' . $agency;
}
if ( ! empty( $position ) ) {
	$ap[] = 'the position of ' . $position;
}

if ( ! empty( $ap ) ) {
	if ( count( $ap ) === 2 ) {
		$sentence .= ' The applicant is applying with ' . $ap[0] . ' for ' . $ap[1] . '.';
	} else {
		$sentence .= ' The applicant is applying for ' . $ap[0] . '.';
	}
}

$para( $sentence );

/* ------------------------------------------------------------
 * PARAGRAPH 2: Other names + emails
 * ------------------------------------------------------------ */
$aka_names  = [];
$all_emails = [];

// Current email
if ( ! empty( $email_current ) ) {
	$all_emails[] = $email_current;
}

// Other names
$para_nested( $entry, 197, function( $child ) use ( &$aka_names, $child_name ) {
	$n = trim( (string) $child_name( $child, '1' ) );
	if ( $n !== '' ) {
		$aka_names[] = $n;
	}
} );

// Other emails
$para_nested( $entry, 224, function( $child ) use ( &$all_emails, $child_val ) {
	//$para_nested( $entry, 196, function( $child ) use ( &$all_emails, $child_val ) {
	$e = trim( (string) $child_val( $child, '1' ) );
	//$e = trim( (string) $child_val( $child, '2' ) );
	if ( $e !== '' ) {
		$all_emails[] = $e;
	}
} );

$aka_names  = array_unique( $aka_names );
$all_emails = array_unique( $all_emails );

if ( ! empty( $aka_names ) || ! empty( $all_emails ) ) {

	$parts = [];

	if ( ! empty( $aka_names ) ) {
		$parts[] = 'has also been known as ' . $oxford_join( $aka_names );
	}

	if ( ! empty( $all_emails ) ) {
		$parts[] = 'has used the following email address' . ( count( $all_emails ) > 1 ? 'es' : '' ) . ': ' . $oxford_join( $all_emails );
	}

	$para( 'The applicant ' . implode( ' and ', $parts ) . '.' );
}

/* ------------------------------------------------------------
 * PARAGRAPH 3: Prior exams + citizenship
 * ------------------------------------------------------------ */
$exam_parts = [];

// Prior exams
$cvsa = $count_phrase( $val( '112', '' ), 'CVSA examination' );
$poly = $count_phrase( $val( '113', '' ), 'polygraph examination' );

if ( $cvsa !== '' || $poly !== '' ) {
	$exam_parts[] = 'reports having previously taken ' . $oxford_join( array_filter( [ $cvsa, $poly ] ) );
}

// Citizenship
$citizen = trim( (string) $val( '185', '' ) );
if ( $citizen !== '' ) {
	$exam_parts[] = $citizen === 'United States'
		? 'is a United States citizen'
		: 'is a citizen of ' . $citizen;
}

if ( ! empty( $exam_parts ) ) {
	$para( 'The applicant ' . implode( ' and ', $exam_parts ) . '.' );
}

/* ------------------------------------------------------------
 * PARAGRAPH 4: Accept employment
 * ------------------------------------------------------------ */
$accept = trim( (string) $val( '187', '' ) );

if ( $accept !== '' ) {
	$para(
		$accept === 'Yes'
			? 'If offered employment by this agency, the applicant states they would accept the position.'
			: 'If offered employment by this agency, the applicant states they would not accept the position.'
	);
}

/* ------------------------------------------------------------
 * PARAGRAPH 5: General comments
 * ------------------------------------------------------------ */
if ( $general_comments !== '' ) {
	$para( 'Regarding general information, the applicant noted: ' . $general_comments );
}
