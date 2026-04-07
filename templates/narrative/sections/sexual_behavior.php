<h2>Sexual Behavior</h2>
<?php
/**
 * Sexual Behavior — Narrative Summary (full field coverage, $A standard)
 * - Yes-only admissions
 * - Optional count + most-recent date (pretty)
 * - Uses $oxford_join() for consistent list joining
 */

$sexual_items = [
	[149, 'engaging in sex for pay (provided or received)', 170, 167],
	[150, 'aiding, abetting, or receiving compensation from another person who engaged in sex for pay', 172, 190],
	[151, 'sex with another person against their will (including threat, coercion, or duress)', 574, 575],
	[152, 'touching another person in a sexual manner without mutual consent', 176, 192],
	[153, 'sex with a person who was unable to give lawful consent due to unconsciousness or mental disability', 177, 193],
	[154, 'sex with a person under the age of 18', 178, 194],
	[155, 'sexually touching a person under the age of 18', 179, 195],
	[156, 'enticing or persuading a person under the age of 18 to sexually touch the applicant', 180, 196],
	[157, 'sexual behavior (alone or with another) in a public place', 181, 336],
	[158, 'exposing the genital area or buttocks while in public', 182, 337],
	[159, 'sexual activity while in public', 183, 338],
	[160, 'exposing the genital area or buttocks to children for sexual gratification', 184, 339],
	[161, 'exposing the genital area or buttocks to adults for sexual gratification without mutual consent', 185, 340],
	[162, '“Peeping Tom” behavior (attempting to watch others engaged in intimate activity from a hidden position or location not open to the public)', 186, 341],
	[163, 'sexual behavior involving the deliberate infliction of pain or injury without mutual consent', 187, 342],
	[164, 'sexual behavior involving an animal', 188, 343],
	[165, 'viewing child pornography in some form', 189, 344],
];

// ------------------------------------------------------------
// SUMMARY NARRATIVE (Yes-only + optional count/date)
// ------------------------------------------------------------
$sexual_admissions = [];

foreach ( $sexual_items as $cfg ) {
	[ $yn_id, $short_label, $count_id, $date_id ] = $cfg;

	if ( $yn( $yn_id ) !== 'Yes' ) {
		continue;
	}

	$piece = $short_label;

	// Optional count
	$count_raw = trim( (string) $val( (string) $count_id, '' ) );
	if ( $count_raw !== '' ) {

		$lc = strtolower( $count_raw );
		$is_zeroish = in_array( $lc, [ '0', 'none', 'never', 'n/a', 'na', '-' ], true );

		if ( ! $is_zeroish ) {
			// Pure numeric -> "(1 time)" / "(2 times)"
			if ( ctype_digit( $count_raw ) ) {
				$n = (int) $count_raw;
				if ( $n > 0 ) {
					$piece .= " ({$n} time" . ( $n === 1 ? '' : 's' ) . ')';
				}
			} else {
				// Descriptive -> "(multiple)", "(2 times)", etc.
				$piece .= " ({$count_raw})";
			}
		}
	}

	// Optional most recent date (pretty)
	$date_raw = trim( (string) $val( (string) $date_id, '' ) );
	if ( $date_raw !== '' ) {
		$date_pretty = $fmt_date( $date_raw, 'F d, Y' );
		if ( $date_pretty !== '' ) {
			$piece .= " (most recent approximately {$date_pretty})";
		}
	}

	$sexual_admissions[] = $piece;
}

if ( ! empty( $sexual_admissions ) ) {

	if ( count( $sexual_admissions ) === 1 ) {
		$para( $A . ' acknowledges ' . $sexual_admissions[0] . '.' );
	} else {
		$para(
			$A . ' acknowledges the following sexual behavior history: ' .
			$oxford_join( $sexual_admissions, 'and' ) .
			'.'
		);
	}

} else {
	$para( $A . ' denies any negative sexual behavior history.' );
}

// ------------------------------------------------------------
// COMMENTS
// ------------------------------------------------------------
$para_comment( $sexual_comments, 'Regarding sexual behavior history, ' . $A . ' noted:' );
$para_comment( $exam_sexual_comments, 'Examiner noted:' );
?>