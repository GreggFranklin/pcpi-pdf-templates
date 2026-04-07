<?php
/**
 * Template Name: Standard
 * Version: 1.0
 * Description: Clean, structured question-and-answer format — bold questions, plain answers, section headers with dividers, a watermark logo, and signature blocks
 * Author: Gregg Franklin, Marc Benzakein
 * Group: PCPI
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Find email field dynamically
$email_field = null;
foreach ( $form['fields'] as $field ) {
    if ( $field->type === 'email' ) {
        $email_field = $field;
        break;
    }
}

// Fields we do NOT want printed in the loop
$exclude = [];
if ( $email_field ) $exclude[] = $email_field->id;

// Remove "View Entry" links from nested form field output
add_filter( 'gfpdf_field_html_value', function( $html, $value, $show_label, $entry, $form, $field, $gfpdf ) {
    if ( $field->type === 'form' ) {
        $html = preg_replace( '/<a\b[^>]*>View Entry<\/a>/i', '', $html );
    }
    return $html;
}, 10, 7 );

?>

<style>
<?php echo file_get_contents(__DIR__ . "/standard.css"); ?>
</style>

<!-- HEADER -->

<?php

$agency_id = pcpi_get_agency_id_from_entry( $entry );
$agency    = pcpi_get_agency_data( $agency_id );

?>

<table class="header-table" width="100%" style="margin-bottom:20px;">
<tr>

<td width="90">
<?php if ( ! empty( $agency['logo'] ) ): ?>
    <img src="<?php echo esc_url( $agency['logo'] ); ?>" width="60">
<?php endif; ?>
</td>

<td>

<?php if ( ! empty( $agency['name'] ) ): ?>
<strong><?php echo esc_html( $agency['name'] ); ?></strong><br>
<?php endif; ?>

<?php if ( ! empty( $agency['address'] ) ): ?>
<?php echo nl2br( esc_html( $agency['address'] ) ); ?><br>
<?php endif; ?>

<?php if ( ! empty( $agency['phone'] ) ): ?>
Phone: <?php echo esc_html( $agency['phone'] ); ?>
<?php endif; ?>

</td>

</tr>
</table>

<hr>

<?php
// Dynamically locate key fields
$name_field  = null;
$email_field = null;

foreach ( $form['fields'] as $field ) {
    if ( ! $name_field  && $field->type === 'name' )  $name_field  = $field;
    if ( ! $email_field && $field->type === 'email' ) $email_field = $field;
    if ( $name_field && $email_field ) break;
}

// Build applicant name
$applicant_name = '';
if ( $name_field ) {
    $first = rgar( $entry, $name_field->id . '.3' );
    $last  = rgar( $entry, $name_field->id . '.6' );
    $applicant_name = trim( $first . ' ' . $last );
}
?>

<h1>Pre-Polygraph Questionnaire</h1>
<p><strong>Applicant:</strong> <?php echo esc_html( $applicant_name ); ?></p>
<p><strong>Email:</strong> <?php echo $email_field ? esc_html( rgar( $entry, $email_field->id ) ) : ''; ?></p>
<p><strong>Submitted:</strong> <?php echo esc_html( date( 'F j, Y \a\t g:i A', strtotime( rgar($entry,'date_created') ) ) ); ?></p>
<hr>

<?php

foreach ( $form['fields'] as $field ){

    // Skip excluded fields
    if ( in_array( $field->id, $exclude ) ){
        continue;
    }

    // Skip system fields
    if ( in_array( $field->type, ['page','hidden','html'] ) ){
        continue;
    }

    // SECTION HEADERS
    if ( $field->type == 'section' ){
        echo '<h2>'. esc_html($field->label) .'</h2>';
        continue;
    }

    $value = GFCommon::get_lead_field_display(
        $field,
        rgar( $entry, $field->id ),
        $entry,
        $form
    );

    // Strip "View Entry" links injected by GP Nested Forms
    if ( $field->type === 'form' ) {
        // Remove the entire gpnf-row-actions cell (contains the View Entry link)
        $value = preg_replace( '/<td[^>]*class="gpnf-row-actions"[^>]*>.*?<\/td>/is', '', $value );
        // Also strip any stray View Entry anchors
        $value = preg_replace( '/<a\b[^>]*>.*?View Entry.*?<\/a>/is', '', $value );
        // Remove the gpnf-actions div
        $value = preg_replace( '/<div[^>]*class="gpnf-actions"[^>]*>.*?<\/div>/is', '', $value );

        // If the nested form is empty, show a default "None" answer
        if ( empty( trim( strip_tags( $value ) ) ) ) {
            $value = '<em>None</em>';
        }
    }

    // FORMAT DATE FIELDS (like Birthday)
    if ( $field->type === 'date' ) {
        $raw = rgar( $entry, $field->id );
        if ( ! empty( $raw ) ) {
            $timestamp = strtotime( $raw );
            if ( $timestamp ) {
                $value = date( 'F j, Y', $timestamp );
            }
        }
    }

    if ( empty( $value ) ){
        continue;
    }
    
?>

<div class="qa-block">

    <div class="question">
        <?php echo esc_html($field->label); ?>
    </div>

    <div class="answer">
        <?php echo wp_kses_post($value); ?>
    </div>

</div>

<?php

}

?>
