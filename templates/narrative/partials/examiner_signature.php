<div class="signature-block">
  <div>Submitted by,</div>

  <div class="signature-line">
    [gravityforms action="conditional" merge_tag="{created_by:pcpi_signature_url}" condition="isnot" value=""]
      <img class="signature-img" src="{created_by:pcpi_signature_url}" alt="Signature">
    [/gravityforms]

    [gravityforms action="conditional" merge_tag="{created_by:pcpi_signature_url}" condition="is" value=""]
      <div class="signature-fallback">____________________________</div>
    [/gravityforms]
  </div>

  <div>{created_by:display_name}</div>
  <div>{created_by:pcpi_title}</div>
  
 <?php
$created_by = absint( rgar( $entry, 'created_by' ) );
$member     = $created_by ? (string) get_user_meta( $created_by, 'pcpi_member', true ) : '';
$member     = trim( $member );
?>

 <?php if ( $member !== '' ) : ?>
<table class="member-table">
  <tr>
    <td class="label">Member:</td>
    <td class="items">
     
        <div class="member-lines"><?php echo nl2br( esc_html( $member ) ); ?></div>
      
    </td>
  </tr>
</table>
<?php endif; ?>

