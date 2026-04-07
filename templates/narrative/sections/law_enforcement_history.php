<h2>Law Enforcement History</h2>
<?php
/**
 * Law Enforcement History (Nested Form field: 222)
 * Child fields:
 *  - 1 = Agency
 *  - 3 = Date of Application
 *  - 4 = Status
 *
 * Comments:
 *  - 30  -> $law_enforcement_comments (applicant)
 *  - 610 -> $exam_law_enforcement_comments (examiner; adminLabel)
 */

$le_items      = [];
$le_has_status = false;

$collector = function ( $child ) use ( &$le_items, &$le_has_status, $child_val, $fmt_date ) {

	$agency = trim( (string) $child_val( $child, '1' ) );
	$date   = trim( (string) $child_val( $child, '3' ) );
	$status = trim( (string) $child_val( $child, '4' ) );

	if ( $status !== '' ) {
		$le_has_status = true;
	}

	$date_f = $date !== '' ? $fmt_date( $date ) : '';

	$bits = [];
	if ( $date_f !== '' ) {
		$bits[] = $date_f;
	}
	if ( $status !== '' ) {
		$bits[] = $status;
	}

	if ( $agency === '' && empty( $bits ) ) {
		return '';
	}

	$item = ( $agency !== '' ) ? $agency : 'Agency not specified';

	if ( ! empty( $bits ) ) {
		$item .= ' (' . implode( '; ', $bits ) . ')';
	}

	$le_items[] = $item;

	// one consolidated paragraph only
	return '';
};

$para_nested( $entry, 222, $collector );

// Normalize collected items
$le_items = array_values( array_filter( array_map( 'trim', $le_items ) ) );

if ( empty( $le_items ) ) {

	$para(
		$A . ' denied having any prior law enforcement applications.'
	);

} else {

	$sentence =
		$A .
		' reported applying to the following law enforcement agencies: ' .
		$oxford_join( $le_items ) .
		'.';

	if ( $le_has_status ) {
		$sentence .= ' The applicant provided status information for one or more of these applications.';
	}

	$para( $sentence );
}

// Comments
$para_comment(
	$law_enforcement_comments,
	'Regarding law enforcement history, ' . $A . ' noted:'
);

$para_comment(
	$exam_law_enforcement_comments,
	'Examiner noted:'
);
/* ------------------------------------------------------------
 * NEW: Law Enforcement Employment / Conduct Questions (592+)
 * ------------------------------------------------------------ */

$le_worked = trim( (string) $val( '592', '' ) ); // Yes/No radio

// Small helpers (local to this section)
$yn = function( string $fid ) use ( $val ): string {
	$v = trim( (string) $val( $fid, '' ) );
	if ( $v === '' ) { return ''; }
	$v_l = strtolower( $v );
	if ( $v_l === 'yes' ) { return 'Yes'; }
	if ( $v_l === 'no' ) { return 'No'; }
	return $v;
};

$is_positive_count = function( $v ): bool {
	$v = trim( (string) $v );
	if ( $v === '' ) { return false; }
	// common select values: "0", "1", "2", "3", "4+", etc.
	if ( is_numeric( $v ) ) { return (int) $v > 0; }
	return $v !== '0' && strtolower( $v ) !== 'none';
};

// If they never worked LE, single sentence and skip the rest.
if ( $le_worked === 'No' ) {

	$para( $A . ' reported having no prior law enforcement employment.' );

} elseif ( $le_worked === 'Yes' ) {

		/* ----------------------------------------
	 * Employment list (591) — List field (rows only)
	 * Uses _bootstrap helper: $get_list_rows()
	 * ---------------------------------------- */
	$emp_lines = [];
	$rows_591  = $get_list_rows( '591' );

	if ( ! empty( $rows_591 ) && is_array( $rows_591 ) ) {

		foreach ( $rows_591 as $row ) {
			if ( ! is_array( $row ) ) { continue; }

			$values = [];

			foreach ( $row as $cell ) {
				$cell = trim( wp_strip_all_tags( (string) $cell ) );
				if ( $cell !== '' ) {
					$values[] = $cell;
				}
			}

			if ( ! empty( $values ) ) {
				$emp_lines[] = implode( ', ', $values );
			}
		}
	}

	$emp_lines = array_values( array_filter( array_map( 'trim', (array) $emp_lines ) ) );

	if ( empty( $emp_lines ) ) {
		$para( $A . ' reported prior law enforcement employment, but did not list the agencies and dates of employment.' );
	} else {
		$para( $A . ' reported prior law enforcement employment with: ' . $oxford_join( $emp_lines ) . '.' );
	}

/* ----------------------------------------
	 * Group A: Separation / complaints / discipline (593/594/597/596/598/599/600 + 608)
	 * ---------------------------------------- */
	$gA_bits = [];

	if ( $yn( '593' ) === 'Yes' ) { $gA_bits[] = 'was terminated, asked to resign, or allowed to resign in lieu of termination'; }
	if ( $yn( '594' ) === 'Yes' ) { $gA_bits[] = 'anticipated negative information from supervisors or co-workers'; }
	if ( $yn( '597' ) === 'Yes' ) { $gA_bits[] = 'reported being the subject of criminal prosecution or civil suit related to law enforcement employment'; }

	$complaints_formal    = (string) $val( '596', '' );
	$complaints_sustained = (string) $val( '598', '' );
	$reprimands_written   = (string) $val( '600', '' );

	if ( $is_positive_count( $complaints_formal ) )    { $gA_bits[] = 'reported ' . $complaints_formal . ' formal complaint(s)'; }
	if ( $is_positive_count( $complaints_sustained ) ) { $gA_bits[] = 'reported ' . $complaints_sustained . ' sustained complaint(s)'; }
	if ( $yn( '599' ) === 'Yes' ) { $gA_bits[] = 'reported receiving disciplinary action (e.g., suspension, demotion, disciplinary transfer)'; }
	if ( $is_positive_count( $reprimands_written ) )   { $gA_bits[] = 'reported ' . $reprimands_written . ' written reprimand(s)'; }

	if ( ! empty( $gA_bits ) ) {
		$para( 'Regarding separation and discipline, ' . $A . ' ' . $oxford_join( $gA_bits ) . '.' );
	}

	$para_comment( $val( '608', '' ), 'Regarding separation and discipline, ' . $A . ' noted:' );

	/* ----------------------------------------
	 * Group B: Personnel / integrity (601/607/606/605/604/603/602 + 595)
	 * ---------------------------------------- */
	$gB_bits = [];
	$mapB = [
		'601' => 'left or considered leaving a law enforcement position due to personal problems',
		'607' => 'was advised or counseled regarding problems with fellow employees or supervisors',
		'606' => 'was currently experiencing personnel problems in a law enforcement position',
		'605' => 'had been uncomfortable with the actions or statements of a fellow officer or supervisor',
		'604' => 'was asked to lie, cover up, or withhold information regarding the actions or statements of a fellow officer or supervisor',
		'603' => 'lied, covered up, or withheld information for another officer or supervisor',
		'602' => 'lied to a supervisor or investigator regarding department- or agency-related issues',
	];
	foreach ( $mapB as $fid => $phrase ) {
		if ( $yn( $fid ) === 'Yes' ) { $gB_bits[] = $phrase; }
	}
	if ( ! empty( $gB_bits ) ) {
		$para( 'Regarding personnel and integrity, ' . $A . ' reported having ' . $oxford_join( $gB_bits ) . '.' );
	}
	$para_comment( $val( '595', '' ), 'Regarding personnel and integrity, ' . $A . ' noted:' );

	/* ----------------------------------------
	 * Group C: Use of force / altercations (609/610/614/613/612/611 + 615)
	 * ---------------------------------------- */
	$gC_bits = [];
	$altercations_duty = (string) $val( '609', '' );
	if ( $is_positive_count( $altercations_duty ) ) { $gC_bits[] = 'reported ' . $altercations_duty . ' on-duty physical altercation(s) (other than simple restraint)'; }
	if ( $yn( '610' ) === 'Yes' ) { $gC_bits[] = 'reported using or being accused of using more force than necessary to control a suspect or effect an arrest'; }
	if ( $yn( '614' ) === 'Yes' ) { $gC_bits[] = 'reported being involved in an off-duty physical altercation while employed in law enforcement'; }
	if ( $yn( '613' ) === 'Yes' ) { $gC_bits[] = 'reported assisting another officer in an incident of excessive force'; }
	if ( $yn( '612' ) === 'Yes' ) { $gC_bits[] = 'reported striking an individual after the person was controlled or arrested'; }
	if ( $yn( '611' ) === 'Yes' ) { $gC_bits[] = 'reported taunting, demeaning, or otherwise “jacking up” an individual after the person was controlled or arrested'; }

	if ( ! empty( $gC_bits ) ) {
		$para( 'Regarding use of force and altercations, ' . $A . ' ' . $oxford_join( $gC_bits ) . '.' );
	}
	$para_comment( $val( '615', '' ), 'Regarding use of force and altercations, ' . $A . ' noted:' );

	/* ----------------------------------------
	 * Group D: Weapons / firearms (616/621/620/619/618 + 622)
	 * ---------------------------------------- */
	$gD_bits = [];
	$mapD = [
		'616' => 'carried an unauthorized weapon or unapproved ammunition while on duty',
		'621' => 'fired a weapon on duty (other than on a firing range)',
		'620' => 'fired a weapon accidentally or illegally while off duty',
		'619' => 'carried a firearm or other weapon in a weapons restricted area (intentionally or accidentally)',
		'618' => 'owned illegal firearms or other illegal weapons',
	];
	foreach ( $mapD as $fid => $phrase ) {
		if ( $yn( $fid ) === 'Yes' ) { $gD_bits[] = $phrase; }
	}
	if ( ! empty( $gD_bits ) ) {
		$para( 'Regarding weapons and firearms, ' . $A . ' reported having ' . $oxford_join( $gD_bits ) . '.' );
	}
	$para_comment( $val( '622', '' ), 'Regarding weapons and firearms, ' . $A . ' noted:' );

	/* ----------------------------------------
	 * Group E: Evidence / theft / contraband / bribes (623/629/628/627/626/625/624/630 + 631)
	 * ---------------------------------------- */
	$gE_bits = [];
	$mapE = [
		'623' => 'stolen an item of value from an agency or department',
		'629' => 'kept or distributed property or evidence obtained through employment',
		'628' => 'kept or distributed illegal drugs obtained through employment',
		'627' => 'kept or distributed alcohol confiscated as a result of employment',
		'626' => 'stolen or deliberately mishandled an item of value obtained during a search',
		'625' => 'been offered a bribe',
		'624' => 'accepted a bribe',
		'630' => 'brought contraband into a jail, prison, or other custodial facility',
	];
	foreach ( $mapE as $fid => $phrase ) {
		if ( $yn( $fid ) === 'Yes' ) { $gE_bits[] = $phrase; }
	}
	if ( ! empty( $gE_bits ) ) {
		$para( 'Regarding evidence handling and integrity, ' . $A . ' reported having ' . $oxford_join( $gE_bits ) . '.' );
	}
	$para_comment( $val( '631', '' ), 'Regarding evidence handling and integrity, ' . $A . ' noted:' );

	/* ----------------------------------------
	 * Group F: Reports / records / confidentiality (633/639/638/637/636/635/634 + 632)
	 * ---------------------------------------- */
	$gF_bits = [];
	$mapF = [
		'633' => 'lied on arrest, citation, or crime reports or while testifying in court',
		'639' => 'exaggerated, “colored,” or withheld information on reports or in court',
		'638' => 'put false information in a report under the direction of a superior',
		'637' => 'been accused of falsifying an official document, report, or statement',
		'636' => 'used department computers or information resources for personal reasons',
		'635' => 'ran records for unauthorized persons or unauthorized purposes',
		'634' => 'leaked confidential law enforcement information to the press or to unauthorized persons',
	];
	foreach ( $mapF as $fid => $phrase ) {
		if ( $yn( $fid ) === 'Yes' ) { $gF_bits[] = $phrase; }
	}
	if ( ! empty( $gF_bits ) ) {
		$para( 'Regarding reporting and confidentiality, ' . $A . ' reported having ' . $oxford_join( $gF_bits ) . '.' );
	}
	$para_comment( $val( '632', '' ), 'Regarding reporting and confidentiality, ' . $A . ' noted:' );

	/* ----------------------------------------
	 * Group G: Drugs (640/644/643 + 645)
	 * ---------------------------------------- */
	$gG_bits = [];
	$mapG = [
		'640' => 'used illegal drugs since being employed in law enforcement',
		'644' => 'been present when others were using illegal drugs (outside of an official capacity)',
		'643' => 'sold or furnished illegal drugs since being employed in law enforcement',
	];
	foreach ( $mapG as $fid => $phrase ) {
		if ( $yn( $fid ) === 'Yes' ) { $gG_bits[] = $phrase; }
	}
	if ( ! empty( $gG_bits ) ) {
		$para( 'Regarding illegal drugs, ' . $A . ' reported having ' . $oxford_join( $gG_bits ) . '.' );
	}
	$para_comment( $val( '645', '' ), 'Regarding illegal drugs, ' . $A . ' noted:' );

	/* ----------------------------------------
	 * Group H: Alcohol / gambling / sexual conduct (651/650/648/647/646 + 652)
	 * ---------------------------------------- */
	$gH_bits = [];
	$mapH = [
		'651' => 'consumed alcohol while on duty (outside of an official investigation)',
		'650' => 'reported to duty within three hours of consuming alcohol',
		'648' => 'engaged in on-duty sexual activity',
		'647' => 'dated a citizen informant, former inmate, suspect, or defendant initially met while on duty',
		'646' => 'engaged in illegal gambling while on duty',
	];
	foreach ( $mapH as $fid => $phrase ) {
		if ( $yn( $fid ) === 'Yes' ) { $gH_bits[] = $phrase; }
	}
	if ( ! empty( $gH_bits ) ) {
		$para( 'Regarding conduct while employed in law enforcement, ' . $A . ' reported having ' . $oxford_join( $gH_bits ) . '.' );
	}
	$para_comment( $val( '652', '' ), 'Regarding conduct while employed in law enforcement, ' . $A . ' noted:' );

	/* ----------------------------------------
	 * Group I: Value/items, accidents, performance, sleeping (653/658/655/654 + 657)
	 * ---------------------------------------- */
	$gI_bits = [];

	if ( $yn( '653' ) === 'Yes' ) { $gI_bits[] = 'accepted or solicited an item of value while on duty or by identifying as an officer while off duty'; }

	$accidents = (string) $val( '658', '' );
	if ( $is_positive_count( $accidents ) ) { $gI_bits[] = 'reported ' . $accidents . ' on-duty traffic accident(s)'; }

	if ( $yn( '655' ) === 'Yes' ) { $gI_bits[] = 'written fictitious or inaccurate citations to meet a performance standard'; }
	if ( $yn( '654' ) === 'Yes' ) { $gI_bits[] = 'slept while on duty'; }

	if ( ! empty( $gI_bits ) ) {
		$para( 'Regarding conduct and performance, ' . $A . ' reported having ' . $oxford_join( $gI_bits ) . '.' );
	}
	$para_comment( $val( '657', '' ), 'Regarding conduct and performance, ' . $A . ' noted:' );

} else {
	// Unanswered: do nothing (avoid misleading narrative).
}

// Field 659 appears not scoped by conditional logic; include if answered.
$alcohol_violations = trim( (string) $val( '659', '' ) );
if ( $alcohol_violations !== '' ) {
	$para( 'In the last five years, the applicant reported ' . $alcohol_violations . ' alcohol-related violation(s).' );
}

// General LE history comments (641)
$para_comment( $val( '641', '' ), 'Regarding law enforcement history, ' . $A . ' noted:' );

