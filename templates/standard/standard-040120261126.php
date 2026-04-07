<?php
/**
 * Template Name: Standard
 * Version: 1.7
 * Description: Production-ready Q&A PDF template with GF-consistent rendering
 * Author: Gregg Franklin, Marc Benzakein
 * Group: PCPI
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * EXCLUDE EMAIL FIELD
 */
$exclude = [];
foreach ( $form['fields'] as $f ) {
    if ( $f->type === 'email' ) {
        $exclude[] = $f->id;
        break;
    }
}

/**
 * STYLES
 */
?>
<style>
<?php echo file_get_contents(__DIR__ . "/standard.css"); ?>
</style>

<?php
/**
 * HEADER
 */
$agency_id = function_exists('pcpi_get_agency_id_from_entry') ? pcpi_get_agency_id_from_entry( $entry ) : null;
$agency    = function_exists('pcpi_get_agency_data') ? pcpi_get_agency_data( $agency_id ) : [];
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
/**
 * APPLICANT DETAILS
 */
$name_field  = null;
$email_field = null;

foreach ( $form['fields'] as $field ) {
    if ( ! $name_field && $field->type === 'name' ) $name_field = $field;
    if ( ! $email_field && $field->type === 'email' ) $email_field = $field;
}

$applicant_name = '';
if ( $name_field ) {
    $first = rgar( $entry, $name_field->id . '.3' );
    $last  = rgar( $entry, $name_field->id . '.6' );
    $applicant_name = trim( $first . ' ' . $last );
}
?>

<h1>Pre-Polygraph Questionnaire</h1>

<p><strong>Applicant:</strong> <?php echo esc_html( $applicant_name ); ?></p>
<p><strong>Email:</strong> <?php echo esc_html( rgar( $entry, $email_field->id ) ); ?></p>
<p><strong>Submitted:</strong> <?php echo esc_html( date( 'F j, Y g:i A', strtotime( rgar($entry,'date_created') ) ) ); ?></p>

<hr>

<?php
/**
 * FIELD LOOP
 */
foreach ( $form['fields'] as $field ){

    if ( in_array( $field->id, $exclude ) ) continue;
    if ( in_array( $field->type, ['page','hidden','html'] ) ) continue;

    if ( $field->type === 'section' ){
        echo '<h2>' . esc_html( $field->label ) . '</h2>';
        continue;
    }

    $value = '';

    /**
     * MULTIPLE CHOICE (checkbox + multi_choice)
     */
    if ( in_array( $field->type, ['checkbox','multiselect','radio','multi_choice'] ) ) {

        $selected = [];

        if ( ! empty( $field->inputs ) ) {
            foreach ( $field->inputs as $input ) {
                $raw = rgar( $entry, $input['id'] );
                if ( $raw ) {
                    $selected[] = ($raw === '1') ? $input['label'] : $raw;
                }
            }
        }

        if ( empty( $selected ) ) {
            $raw = rgar( $entry, $field->id );
            if ( $raw ) {
                $selected = is_array($raw) ? $raw : explode(',', $raw);
            }
        }

        if ( $selected ) {
            $value = implode(', ', array_map('esc_html',$selected));
        }
    }

    /**
     * SIGNATURE
     */
    elseif ( $field->type === 'signature' ) {

        $sig = rgar( $entry, $field->id );

        if ( $sig && is_string($sig) ) {
            $upload = wp_upload_dir();
            $url = $upload['baseurl'] . '/gravity_forms/signatures/' . ltrim($sig,'/');
            $value = '<img src="' . esc_url($url) . '" style="max-width:200px;">';
        }
    }

    /**
     * NESTED FORMS (MATCHES GF LIST STYLE)
     */
    elseif ( $field->type === 'form' ) {

        $ids = rgar( $entry, $field->id );
        $ids = is_array($ids) ? $ids : explode(',', $ids);

        if ( ! empty( $ids ) ) {

            $child_form = GFAPI::get_form( $field->gpnfForm );
            $rows = '';

            foreach ( $ids as $id ) {

                $child = GFAPI::get_entry( $id );
                if ( is_wp_error($child) ) continue;

                $row = '';
                $has_data = false;

                foreach ( $child_form['fields'] as $cf ) {

                    if ( in_array( $cf->type, ['html','section','page'] ) ) continue;

                    if ( $cf->type === 'name' ) {
                        $first = rgar($child, $cf->id . '.3');
                        $last  = rgar($child, $cf->id . '.6');
                        $val = trim($first . ' ' . $last);
                    } else {
                        $val = rgar( $child, $cf->id );
                    }

                    if ( empty($val) ) {
                        $row .= '<td style="padding:8px; border:1px solid #ddd;"></td>';
                    } else {
                        $has_data = true;
                        $row .= '<td style="padding:8px; border:1px solid #ddd;">' . esc_html($val) . '</td>';
                    }
                }

                if ( $has_data ) {
                    $rows .= '<tr>' . $row . '</tr>';
                }
            }

            if ( ! empty( $rows ) ) {

                $value  = '<table style="width:100%; border-collapse:collapse; border:1px solid #ddd; margin-top:5px;">';
                $value .= '<thead><tr>';

                foreach ( $child_form['fields'] as $cf ) {

                    if ( in_array( $cf->type, ['html','section','page'] ) ) continue;

                    $value .= '<th style="
                        text-align:left;
                        padding:8px;
                        border:1px solid #ddd;
                        background:#eee;
                        font-weight:bold;
                    ">' . esc_html($cf->label) . '</th>';
                }

                $value .= '</tr></thead>';
                $value .= '<tbody>' . $rows . '</tbody>';
                $value .= '</table>';
            }
        }
    }

    /**
     * DEFAULT FIELDS
     */
    else {

        try {
            $value = GFCommon::get_lead_field_display(
                $field,
                rgar( $entry, $field->id ),
                $entry,
                $form
            );
        } catch ( Throwable $e ) {
            $value = esc_html( rgar( $entry, $field->id ) );
        }
    }

    // Skip empty answers
    if ( empty( trim( wp_strip_all_tags( $value ) ) ) ) continue;
?>

<div class="qa-block">
    <div class="question"><?php echo esc_html( $field->label ); ?></div>
    <div class="answer"><?php echo wp_kses_post( $value ); ?></div>
</div>

<?php } ?>