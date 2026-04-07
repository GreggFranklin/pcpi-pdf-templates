<h2>Integrity</h2>
<?php
$integrity_items = [
	449 => [ 'engaging in theft of merchandise from a current or previous job', 451 ],
	452 => [ 'engaging in theft of money from a current or previous job', 453 ],
	454 => [ 'engaging in shoplifting', 455 ],
	457 => [ 'engaging in price switching', 456 ],
	458 => [ 'engaging in illegal refunding', 459 ],
	461 => [ 'engaging in burglary from a residence', 460 ],
	462 => [ 'engaging in burglary from a business', 463 ],
	464 => [ 'engaging in robbery', 465 ],
	467 => [ 'engaging in purse snatching', 466 ],
	468 => [ 'engaging in pick pocketing', 469 ],
	471 => [ 'engaging in theft of a car, boat, or motorcycle', 473 ],
	472 => [ 'engaging in theft of parts or merchandise from a car, boat, or motorcycle', 470 ],
	474 => [ 'engaging in theft of mail', 475 ],
	477 => [ 'engaging in theft from a construction site', 476 ],
	478 => [ 'engaging in theft from a junk yard or auto wrecking yard', 479 ],
	480 => [ 'engaging in theft from a storage yard or facility', 481 ],
	482 => [ 'engaging in theft of a bicycle or bicycle parts', 483 ],
	484 => [ 'buying or receiving stolen merchandise', 485 ],
	486 => [ 'helping another person to steal', 487 ],
	488 => [ 'other theft-related activity', 489 ],
	490 => [ 'engaging in credit card fraud (using another’s card without permission)', 491 ],
	492 => [ 'engaging in check fraud', 493 ],
	494 => [ 'engaging in insurance fraud (submitting a false or exaggerated claim)', 495 ],
	496 => [ 'engaging in worker’s compensation fraud', 497 ],
	498 => [ 'engaging in unemployment insurance fraud (collecting payments while working)', 499 ],
	500 => [ 'engaging in welfare fraud (misrepresenting dependents / failure to report income)', 501 ],
	502 => [ 'engaging in cheating on income tax reports', 503 ],
	504 => [ 'engaging in cheating on police or correctional academy examinations', 505 ],
	506 => [ 'engaging in lying in court', 507 ],
	508 => [ 'engaging in making false statements on official reports', 509 ],
	510 => [ 'engaging in making a false crime report', 511 ],
	512 => [ 'engaging in making a false report or setting a false alarm for emergency services', 513 ],
	514 => [ 'creating, using, or possessing altered or fraudulent identification', 515 ],
	516 => [ 'illegally downloading software, games, music, movies, or other items to avoid paying for them', 517 ],
	518 => [ 'purchasing pirated items (software, games, music, movies, or other items)', 519 ],
	521 => [ 'a history of lying to people who trust them', 522 ],
];

// ------------------------------------------------------------------
// ONE COMBINED NARRATIVE PARAGRAPH (Yes-only list + rating)
// ------------------------------------------------------------------
$integrity_admissions = [];

foreach ( $integrity_items as $yn_id => $cfg ) {
	[ $label, $dates_fid ] = $cfg;

	if ( $yn( $yn_id ) !== 'Yes' ) {
		continue;
	}

	$piece = $label;

	// Pretty list dates + correct singular/plural label
	$date_items = $list_field_items( $dates_fid );
	$date_str   = $list_field_dates_pretty( $dates_fid, 'F d, Y' );

	if ( $date_str !== '' ) {
		$date_label = ( count( $date_items ) > 1 ) ? 'dates' : 'date';
		$piece     .= " ({$date_label}: {$date_str})";
	}

	$integrity_admissions[] = $piece;
}

$integrity_rating = trim( (string) $val( '523', '' ) );

if ( ! empty( $integrity_admissions ) || $integrity_rating !== '' ) {

	$text_parts = [];

	if ( ! empty( $integrity_admissions ) ) {

		if ( count( $integrity_admissions ) === 1 ) {
			$text_parts[] = $A . ' reports the following integrity history: ' . $integrity_admissions[0] . '.';
		} else {
			$tmp  = $integrity_admissions;
			$last = array_pop( $tmp );
			$list = ( count( $tmp ) > 1 )
				? implode( ', ', $tmp ) . ', and ' . $last
				: $tmp[0] . ' and ' . $last;

			$text_parts[] = $A . ' reports the following integrity history: ' . $list . '.';
		}

	} else {
		$text_parts[] = $A . ' reports no integrity concerns.';
	}

	if ( $integrity_rating !== '' ) {
		$text_parts[] = 'The applicant rates their level of integrity as ' . $integrity_rating . ' out of 10.';
	}

	$para( implode( ' ', $text_parts ) );

} else {
	$para( $A . ' reports no negative integrity history.' );
}

// ------------------------------------------------------------------
// COMMENTS
// ------------------------------------------------------------------
$para_comment( $integrity_comments, 'Regarding integrity history, ' . $A . ' noted:' );
$para_comment( $exam_integrity_comments, 'Examiner noted:' );
?>