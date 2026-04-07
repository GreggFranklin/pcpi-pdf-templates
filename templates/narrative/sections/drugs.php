<h2>Use of Illegal Drugs and Narcotics</h2>
<?php
/**
 * Drugs — Legacy Narrative Style (full field coverage, $A standard)
 * - Keeps all existing field usage (including nested 315 and comments 319/633)
 * - Uses shorter, cleaner paragraphs like the old report
 */

/* ---------------------------------------------------------
 * 0) Brief definition paragraph (old-report style)
 * --------------------------------------------------------- */
$para(
	'For purposes of this report, “illegal drugs” includes the non-medical use of controlled substances and the misuse of prescription medications not prescribed to the applicant.'
);

/* ---------------------------------------------------------
 * 1) Listed drugs: Yes-only map + “last use approx” dates
 * --------------------------------------------------------- */
$drug_yes_only_map = [
	309 => [ 'Adderall (not prescribed)', 310 ],
	311 => [ 'other prescription drugs not prescribed to the applicant', 314 ],
	349 => [ 'cocaine', 350 ],
	352 => [ 'crack cocaine', 353 ],
	355 => [ 'speed (uppers)', 356 ],
	357 => [ 'methamphetamine (crank)', 358 ],
	359 => [ 'barbiturates (downers)', 360 ],
	361 => [ 'heroin', 362 ],
	363 => [ 'opium', 364 ],
	365 => [ 'codeine (not prescribed)', 366 ],
	367 => [ 'Demerol (not prescribed)', 368 ],
	369 => [ 'morphine (not prescribed)', 370 ],
	371 => [ 'Valium (not prescribed)', 372 ],
	373 => [ 'LSD', 374 ],
	375 => [ 'psychedelic mushrooms', 376 ],
	377 => [ 'mescaline', 378 ],
	379 => [ 'PCP (Angel Dust)', 380 ],
	381 => [ 'Quaaludes', 382 ],
	383 => [ 'sniffing glue or paint', 384 ],
];

$used_drugs = [];

foreach ( $drug_yes_only_map as $yn_id => $cfg ) {
	[ $label, $date_id ] = $cfg;

	if ( $yn( $yn_id ) !== 'Yes' ) {
		continue;
	}

	$item = $label;

	// These are LIST fields -> pretty list formatting
	if ( $date_id ) {
		$date_str = $list_field_dates_pretty( $date_id, 'F d, Y' );
		if ( $date_str !== '' ) {
			$item .= " (last use approximately {$date_str})";
		}
	}

	$used_drugs[] = $item;
}

/* ---------------------------------------------------------
 * 2) “Other drugs” nested form (315): collect drug + last use date
 * --------------------------------------------------------- */
$other_drugs_items = [];

$collector = function( array $child ) use ( &$other_drugs_items, $child_val, $fmt_date ) {

	$drug = trim( (string) $child_val( $child, '1' ) );
	$date = trim( (string) $child_val( $child, '3' ) );

	if ( $drug === '' && $date === '' ) {
		return '';
	}

	if ( $drug === '' ) {
		$drug = 'other drug';
	}

	if ( $date !== '' ) {
		$date = $fmt_date( $date, 'F d, Y' );
		$other_drugs_items[] = "{$drug} (last use approximately {$date})";
	} else {
		$other_drugs_items[] = $drug;
	}

	return '';
};

$para_nested( $entry, 315, $collector, 14 );

$other_drugs_items = array_values( array_filter( array_map( 'trim', $other_drugs_items ) ) );

/* ---------------------------------------------------------
 * 3) Other Y/N items with single event dates (not “last use”)
 * --------------------------------------------------------- */
$drug_other_yn_date_items = [
	[317, 'helping grow or harvest illegal drugs', 318],
	[385, 'helping manufacture illegal drugs', 386],
	[387, 'using, creating, or helping create a forged prescription for drugs', 388],
	[389, 'selling illegal drugs (including minor sales to friends)', 390],
	[391, 'causing someone to ingest a drug without their knowledge', 392],
	[393, 'helping transport illegal drugs or assisting another person in drug sales', 394],
	[395, 'pretending to use illegal narcotics, or stating they did when they did not', 396],
	[397, 'operating a motor vehicle while under the influence of illegal drugs or narcotics', 398],
];

$other_drug_admissions = [];

foreach ( $drug_other_yn_date_items as $cfg ) {
	[ $yn_id, $label, $date_id ] = $cfg;

	if ( $yn( $yn_id ) !== 'Yes' ) {
		continue;
	}

	$piece = $label;

	$date_raw = trim( (string) $val( (string) $date_id, '' ) );
	if ( $date_raw !== '' ) {
		$date_pretty = $fmt_date( $date_raw, 'F d, Y' );
		$piece      .= " (approximate date: {$date_pretty})";
	}

	$other_drug_admissions[] = $piece;
}

/* ---------------------------------------------------------
 * 4) Output: 2–3 short paragraphs (old-report feel)
 * --------------------------------------------------------- */
$has_any = ( ! empty( $used_drugs ) || ! empty( $other_drugs_items ) || ! empty( $other_drug_admissions ) );

if ( $has_any ) {

	// Paragraph A: what substances were used (or admitted)
	if ( ! empty( $used_drugs ) ) {
		$para( $A . ' admitted to the use of the following illegal or controlled substances: ' . $oxford_join( $used_drugs ) . '.' );
	} else {
		$para( $A . ' denied use of the listed illegal or controlled substances.' );
	}

	// Paragraph B: “Other drugs” (nested)
	if ( ! empty( $other_drugs_items ) ) {
		$para( 'The applicant additionally disclosed other substances: ' . $oxford_join( $other_drugs_items ) . '.' );
	}

	// Paragraph C: other drug-related conduct admissions
	if ( ! empty( $other_drug_admissions ) ) {
		$para( 'The applicant further admitted to the following drug-related conduct: ' . $oxford_join( $other_drug_admissions ) . '.' );
	}

} else {
	$para( $A . ' denied any use of illegal drugs or misuse of prescription medications.' );
}

/* ---------------------------------------------------------
 * 5) Comments (319) + Examiner comments (633)
 * --------------------------------------------------------- */
$para_comment( $drug_comments, 'Regarding illegal drugs and narcotics, ' . $A . ' noted:' );
$para_comment( $exam_drug_comments, 'Examiner noted:' );
?>