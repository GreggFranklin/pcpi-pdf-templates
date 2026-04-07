<h2>Military Service</h2>
<?php
/**
 * MILITARY SERVICE — narrative (drop-in)
 *
 * Improvements:
 * - Discharge explanation now comes from the correct field (208 via $discharge_expl from _core-values)
 * - Discharge type is derived from the actual discharge radio fields (202–207)
 * - Explanation is only shown when meaningful and relevant (non-honorable/other discharge)
 *
 * Assumes from bootstrap:
 * - $A, $entry
 * - $yn(), $val(), $para(), $para_comment()
 * - $fmt_date(), $oxford_join(), $para_nested(), $child_val()
 * - From _core-values: $honorable_like, $non_honorable, $discharge_expl, $sel_service, $sel_number, $exam_military_comments
 */

/* ---------------------------------------------------------
 * Field IDs (Form 23)
 * --------------------------------------------------------- */
$service_yn_fid      = 33;   // Yes/No: applied/served in Armed Forces
$service_nested_fid  = 198;  // Nested service rows (branch + from/to)

$discharge_year_fid  = 201;  // Discharge year (dropdown on your form)

$sel_service_yn_fid  = 209;  // Selective Service Yes/No (fallback if $sel_service not set)
$sel_service_num_fid = 61;   // Selective Service number (fallback if $sel_number not set)

/* Discharge type radios (Form 23) */
$FID_DISCH_HONORABLE = 202;
$FID_DISCH_GENERAL   = 203;
$FID_DISCH_BADCOND   = 204;
$FID_DISCH_OTH       = 205;
$FID_DISCH_DISHON    = 206;
$FID_DISCH_OTHER     = 207;

$served_ans = $yn( $service_yn_fid );

/* ---------------------------------------------------------
 * Collect nested service rows
 * Child keys assumed:
 * - 1 = Branch
 * - 3 = From date
 * - 4 = To date
 * --------------------------------------------------------- */
$service_items = [];

$collector = function ( array $child ) use ( &$service_items, $child_val, $fmt_date ) {

	$branch = trim( (string) $child_val( $child, '1' ) );
	$from   = trim( (string) $child_val( $child, '3' ) );
	$to     = trim( (string) $child_val( $child, '4' ) );

	$from_f = ( $from !== '' ) ? $fmt_date( $from ) : '';
	$to_f   = ( $to   !== '' ) ? $fmt_date( $to )   : '';

	// Skip empty rows.
	if ( $branch === '' && $from_f === '' && $to_f === '' ) {
		return '';
	}

	$item = ( $branch !== '' ) ? $branch : 'a branch of service';

	// Branch + from/to in one unit.
	if ( $from_f !== '' && $to_f !== '' ) {
		$item .= ' (' . $from_f . ' to ' . $to_f . ')';
	} elseif ( $from_f !== '' ) {
		$item .= ' (from ' . $from_f . ')';
	} elseif ( $to_f !== '' ) {
		$item .= ' (to ' . $to_f . ')';
	}

	$service_items[] = $item;

	return '';
};

$para_nested( $entry, $service_nested_fid, $collector, 0 );

$service_items = array_values( array_filter( array_map( 'trim', $service_items ) ) );

/* ---------------------------------------------------------
 * Discharge + Selective Service values
 * --------------------------------------------------------- */
$discharge_year = trim( (string) $val( $discharge_year_fid, '' ) );

/**
 * Determine discharge label from the actual radio fields.
 * (Prefer a specific label over generic honorable_like/non_honorable.)
 */
$discharge_label = '';
if ( $yn( $FID_DISCH_HONORABLE ) === 'Yes' ) {
	$discharge_label = 'an Honorable Discharge';
} elseif ( $yn( $FID_DISCH_GENERAL ) === 'Yes' ) {
	$discharge_label = 'a General Discharge under Honorable conditions';
} elseif ( $yn( $FID_DISCH_OTH ) === 'Yes' ) {
	$discharge_label = 'an Other than Honorable Discharge (OTH)';
} elseif ( $yn( $FID_DISCH_BADCOND ) === 'Yes' ) {
	$discharge_label = 'a Bad Conduct Discharge';
} elseif ( $yn( $FID_DISCH_DISHON ) === 'Yes' ) {
	$discharge_label = 'a Dishonorable Discharge';
} elseif ( $yn( $FID_DISCH_OTHER ) === 'Yes' ) {
	$discharge_label = 'another type of discharge';
}

/**
 * Use the already-prepared values from _core-values when present,
 * but keep safe fallbacks.
 */
$sel_ans = isset( $sel_service ) ? ( $sel_service ? 'Yes' : 'No' ) : $yn( $sel_service_yn_fid );
$sel_num = isset( $sel_number ) ? trim( (string) $sel_number ) : trim( (string) $val( $sel_service_num_fid, '' ) );

// Explanation text should come from _core-values (field 208)
$expl = isset( $discharge_expl ) ? trim( (string) $discharge_expl ) : '';
$expl_lc = strtolower( trim( $expl ) );

/* ---------------------------------------------------------
 * Build paragraph (use $A once; "The applicant..." for sentence 2)
 * --------------------------------------------------------- */
$sentence_1 = '';
$sentence_2_bits = [];

// Sentence 1: military service (preferred anchor for $A)
if ( ! empty( $service_items ) ) {
	$sentence_1 = $A . ' reported military service in ' . rtrim( $oxford_join( $service_items ), '.' ) . '.';
} elseif ( $served_ans === 'No' ) {
	$sentence_1 = $A . ' reported no military service in the United States Armed Forces.';
} elseif ( $served_ans === 'Yes' ) {
	$sentence_1 = $A . ' reported prior military service.';
}

// Discharge characterization + year (only when there is service context)
$has_service_context = ( ! empty( $service_items ) || $served_ans === 'Yes' );

if ( $has_service_context ) {

	if ( $discharge_label !== '' && $discharge_year !== '' ) {
		$sentence_2_bits[] = $discharge_label . ' in ' . $discharge_year;
	} elseif ( $discharge_label !== '' ) {
		$sentence_2_bits[] = 'receiving ' . $discharge_label;
	} elseif ( $discharge_year !== '' ) {
		$sentence_2_bits[] = 'being discharged in ' . $discharge_year;
	}
}

// Selective Service + number
if ( $sel_ans === 'Yes' ) {
	$bit = 'being registered with the Selective Service';
	if ( $sel_num !== '' ) {
		$bit .= ' (number: ' . $sel_num . ')';
	}
	$sentence_2_bits[] = $bit;
} elseif ( $sel_ans === 'No' ) {
	$sentence_2_bits[] = 'not being registered with the Selective Service';
}

/* Output combined paragraph */
if ( $sentence_1 !== '' || ! empty( $sentence_2_bits ) ) {

	$text = '';

	// Ensure $A appears once.
	if ( $sentence_1 !== '' ) {
		$text .= $sentence_1;
	} else {
		$text .= $A . ' provided military service information.';
	}

	if ( ! empty( $sentence_2_bits ) ) {
		$text .= ' The applicant reported ' . rtrim( $oxford_join( $sentence_2_bits ), '.' ) . '.';
	}

	$para( $text );
}

/* Optional: discharge explanation (only when relevant + meaningful) */
$non_honorable_flag = isset( $non_honorable ) ? (bool) $non_honorable : false;

// Avoid dumb outputs like "Yes"/"No" or empty strings.
$expl_is_meaningful = ( $expl !== '' && $expl_lc !== 'yes' && $expl_lc !== 'no' );

if ( $non_honorable_flag && $expl_is_meaningful ) {
	$para_comment( $expl, 'Regarding military discharge, the applicant explained:' );
}

/* Examiner note (only if present) */
if ( isset( $exam_military_comments ) && trim( (string) $exam_military_comments ) !== '' ) {
	$para_comment( $exam_military_comments, 'Examiner noted:' );
}
?>