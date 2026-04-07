<h2>Signature and Declaration</h2>

<?php
$EXAMINEE_SIG_FID = 101;
$EXAMINER_SIG_FID = 661;

/**
 * Get a signature image URL using GF merge tag processing (most reliable).
 * Supports tags that return either a URL or an <img> tag.
 */
$get_sig_url = static function ( int $field_id ) use ( $form, $entry ) : string {
	if ( ! class_exists( 'GFCommon' ) ) {
		return '';
	}

	// This is the exact tag you were already using in HTML: {:101}
	$tag     = '{:' . $field_id . '}';
	$render  = (string) GFCommon::replace_variables( $tag, $form, $entry, false, false, false, 'html' );
	$render  = trim( $render );

	if ( $render === '' ) {
		return '';
	}

	// If Gravity returned an <img>, extract src="..."
	if ( stripos( $render, '<img' ) !== false ) {
		if ( preg_match( '/src=["\']([^"\']+)["\']/', $render, $m ) ) {
			return trim( (string) $m[1] );
		}
		return '';
	}

	// Otherwise assume it's already a URL.
	return $render;
};

$examinee_sig_url = $get_sig_url( $EXAMINEE_SIG_FID );
$examiner_sig_url = $get_sig_url( $EXAMINER_SIG_FID );

$render_sig = static function ( string $label, string $url, string $alt ) : void {
	echo '<strong>' . esc_html( $label ) . '</strong> ';
	if ( $url ) {
		echo '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" class="signature-img" />';
	} else {
		echo '<span class="no-signature">Not provided</span>';
	}
	echo "<br>\n";
};
?>

<p>
	<?php $render_sig( 'Examinee Signature:', $examinee_sig_url, 'Examinee Signature' ); ?>
	<strong>Date:</strong> {Date:104}<br><br>

	<?php $render_sig( 'Examiner Signature:', $examiner_sig_url, 'Examiner Signature' ); ?>
	<strong>Date:</strong> {Date:103}
</p>

<div class="notice underline">
	<span class="full-caps">Notice:</span> <span class="small-caps">No Applicant Should Be Accepted Or Rejected Based Solely On</span><br>
	<span class="small-caps">The Results Of A Polygraph Examination.</span>
</div>