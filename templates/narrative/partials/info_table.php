<div class="container text-left">
<table class="info-table">
    <!-- <tr><th>File Record #:</th><td><?php echo esc_html( $file_record_no ); ?></td></tr> -->
    <tr><th>Date completed:</th><td>{date_created:time}</td></tr>
    <tr><th>Applicant:</th><td><?php echo esc_html($full_name); ?></td></tr>
    <tr><th>Date of Birth:</th><td><?php echo esc_html( $dob_formatted ); ?></td></tr>
    <tr><th>Position Applied:</th><td><?php echo esc_html( $position); ?></td></tr>
    <tr><th>Agency:</th><td><?php echo esc_html($agency); ?></td></tr>
    <tr><th>Examiner:</th><td>{created_by:display_name}<br>{created_by:title}</td></tr>
</table>
<!-- <p><strong>Results of Polygraph Examination:</strong> <span class="nsr"><?php echo esc_html( $result ); ?></span></p>
</div> --><!--- .container ---> 