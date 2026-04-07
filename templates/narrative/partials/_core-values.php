<?php
/* ------------------------------------------------------------------------
 * APPLICANT ENTRY RESOLUTION (no hard-coded legacy field IDs)
 *
 * This template is used in two modes:
 * 1) PDF generated from Questionnaire entry -> $entry already has questionnaire fields
 * 2) PDF generated from Review entry (comments-only form) -> _bootstrap resolves source questionnaire entry
 *
 * We resolve Applicant via Workflow Engine registry whenever possible.
 * ------------------------------------------------------------------------ */

$parent_applicant_eid = 0;

/**
 * Source questionnaire entry resolver (compat):
 * - preferred: $questionnaire_entry (newer bootstrap)
 * - fallback:  $pcpi_source_entry  (older combined-template bootstrap)
 * - final:     $entry (questionnaire-mode PDFs only)
 */
$pcpi_q_entry = null;

if ( isset( $questionnaire_entry ) && is_array( $questionnaire_entry ) && ! empty( $questionnaire_entry ) ) {
	$pcpi_q_entry = $questionnaire_entry;
} elseif ( isset( $pcpi_source_entry ) && is_array( $pcpi_source_entry ) && ! empty( $pcpi_source_entry ) ) {
	$pcpi_q_entry = $pcpi_source_entry;
}

if ( ! $pcpi_q_entry && isset( $entry ) && is_array( $entry ) ) {
	// Only safe when the PDF is generated from the Questionnaire form itself.
	$pcpi_q_entry = $entry;
}

/**
 * Preferred: resolve Applicant entry ID from Questionnaire entry using workflow registry.
 * (This is the “single source of truth” path.)
 */
if ( $pcpi_q_entry && isset( $pcpi_workflow ) && is_array( $pcpi_workflow ) ) {

	$q_parent_app_fid = absint( $pcpi_workflow['questionnaire_parent_applicant_field_id'] ?? 0 );

	if ( $q_parent_app_fid ) {
		$parent_applicant_eid = absint( rgar( $pcpi_q_entry, (string) $q_parent_app_fid ) );
	}
}

/**
 * Fallback: if we couldn't resolve Applicant via Questionnaire linkage, try reading it
 * from the Review entry using the workflow registry (comments-only review form).
 *
 * This avoids hard-coded legacy IDs and supports new Review forms.
 */
if ( ! $parent_applicant_eid && isset( $pcpi_workflow ) && is_array( $pcpi_workflow ) ) {

	$review_parent_app_fid = absint( $pcpi_workflow['review_parent_applicant_field_id'] ?? 0 );

	if ( $review_parent_app_fid ) {
		$parent_applicant_eid = absint( rgar( $entry, (string) $review_parent_app_fid ) );
	}
}

/**
 * LAST resort legacy fallback (keep only to support older historical entries/templates).
 * Older builds used a Review-form hidden field ID 579.
 */
if ( ! $parent_applicant_eid ) {
	$parent_applicant_eid = absint( rgar( $entry, '579' ) );
}

$applicant_entry = null;

if ( $parent_applicant_eid && class_exists( 'GFAPI' ) ) {
	$tmp = GFAPI::get_entry( $parent_applicant_eid );
	if ( ! is_wp_error( $tmp ) && ! empty( $tmp ) ) {
		$applicant_entry = $tmp;
	}
}

$app_val = function( $fid, $default = '' ) use ( &$applicant_entry ) {
	if ( empty( $applicant_entry ) ) {
		return $default;
	}
	$v = rgar( $applicant_entry, (string) $fid );
	return ( $v === null ) ? $default : $v;
};

/* ------------------------------------------------------------------------
 * CORE VALUES
 * ------------------------------------------------------------------------ */
$agency       = trim( (string) $app_val( '6', '' ) );
$agency_email = trim( (string) $app_val( '7', '' ) );
$position     = trim( (string) $app_val( '1003', '' ) );

$result        = "No Significant Reaction (NSR)";
$file_record_no = "24-1164";

$full_name     = trim( (string) $val( "40", "" ) );
$dob           = trim( (string) $val( "9", "" ) );
$email_current = trim( (string) $val( "15", "" ) );

/**
 * Applicant displays phrase used throughout.
 * Matches your example style: "Applicant John A. Doe"
 */
$A = $full_name !== "" ? "Applicant " . $full_name : "Applicant";

/**
 * DOB change format to Month, day, Year
 */
$dob_formatted = $fmt_date( $dob );

/* ------------------------------------------------------------------------
 * ADDRESS
 *
 * IMPORTANT:
 * Address lives on the Questionnaire entry, not the comments-only Review entry.
 * Since $val() is already Questionnaire-aware (via _bootstrap resolution),
 * always read the address via $val() so it stays correct in both modes.
 * ------------------------------------------------------------------------ */

$addr1   = trim( (string) $val( '10.1', '' ) );
$addr2   = trim( (string) $val( '10.2', '' ) );
$city    = trim( (string) $val( '10.3', '' ) );
$state   = trim( (string) $val( '10.4', '' ) );
$zip     = trim( (string) $val( '10.5', '' ) );
$country = trim( (string) $val( '10.6', '' ) );

$address_parts = array_filter( [
	$addr1,
	$addr2,
	trim(
		$city .
		( $state !== '' ? ', ' . $state : '' ) .
		( $zip !== '' ? ' ' . $zip : '' )
	),
	$country,
] );

$address_block = $address_parts ? implode( ', ', $address_parts ) : '';

/* Section comments (these are Questionnaire fields; keep using $val()) */
$general_comments          = trim( (string) $val( "23", "" ) );
$law_enforcement_comments  = trim( (string) $val( "30", "" ) );
$social_comments           = trim( (string) $val( "221", "" ) );
$edu_comments              = trim( (string) $val( "243", "" ) );
$driving_comments          = trim( (string) $val( "260", "" ) );
$residential_explain       = trim( (string) $val( "267", "" ) );
$financial_comments        = trim( (string) $val( "295", "" ) );
$alcohol_explain           = trim( (string) $val( "308", "" ) );
$drug_comments             = trim( (string) $val( "319", "" ) );
$gambling_comments         = trim( (string) $val( "327", "" ) );
$legal_comments            = trim( (string) $val( "401", "" ) );
$integrity_comments        = trim( (string) $val( "524", "" ) );
$aggressive_comments       = trim( (string) $val( "405", "" ) );
$weapons_comments          = trim( (string) $val( "555", "" ) );
$sexual_comments           = trim( (string) $val( "166", "" ) );
$associations_comments     = trim( (string) $val( "335", "" ) );
$additional_comments       = trim( (string) $val( "120", "" ) );

/**
 * Examiner comments (Review form only)
 * - resolved by Admin Label so Review field IDs can change safely
 */
$exam_pre_test_interview         = $by_label( 'exam_pre_test_interview', true );
$exam_general_comments           = $by_label( 'exam_general_comments', true );
$exam_law_enforcement_comments   = $by_label( 'exam_law_enforcement_comments', true );
$exam_military_comments          = $by_label( 'exam_military_comments', true );
$exam_social_comments            = $by_label( 'exam_social_comments', true );
$exam_edu_comments               = $by_label( 'exam_edu_comments', true );
$exam_driving_comments           = $by_label( 'exam_driving_comments', true );
$exam_residential_explain        = $by_label( 'exam_residential_explain', true );
$exam_employment_explain         = $by_label( 'exam_employment_explain', true );
$exam_financial_comments         = $by_label( 'exam_financial_comments', true );
$exam_alcohol_explain            = $by_label( 'exam_alcohol_explain', true );
$exam_drug_comments              = $by_label( 'exam_drug_comments', true );
$exam_gambling_comments          = $by_label( 'exam_gambling_comments', true );
$exam_legal_comments             = $by_label( 'exam_legal_comments', true );
$exam_integrity_comments         = $by_label( 'exam_integrity_comments', true );
$exam_aggressive_comments        = $by_label( 'exam_aggressive_comments', true );
$exam_weapons_comments           = $by_label( 'exam_weapons_comments', true );
$exam_sexual_comments            = $by_label( 'exam_sexual_comments', true );
$exam_associations_comments      = $by_label( 'exam_associations_comments', true );
$exam_additional_comments        = $by_label( 'exam_additional_comments', true );
//$exam_post_test_interview      = $by_label( 'exam_post_test_interview', true );

/* Military Service logic */
$honorable_like = $yn( "202" ) === "Yes" || $yn( "203" ) === "Yes";
$non_honorable =
	$yn( "204" ) === "Yes" ||
	$yn( "205" ) === "Yes" ||
	$yn( "206" ) === "Yes" ||
	$yn( "207" ) === "Yes";
$discharge_expl = trim( (string) $val( "208", "" ) );

$sel_service = $yn( "209" ) === "Yes";
$sel_number  = trim( (string) $val( "61", "" ) );
