<h2>Examination Details</h2>
<?php
//$exam_date_long = "{date_created:l, F j, Y}"; // e.g., "Friday, January 2, 2026"
$exam_date_long = date_i18n( 'l, F j, Y', strtotime( rgar( $entry, 'date_created' ) ) );
$exam_location  = "{created_by:location}";

// Sentence 1
$para(
    "On " .
    $exam_date_long .
    ", " .
    $A .
    " was administered a Pre-Employment Screening Polygraph Examination in " .
    $exam_location .
    "."
);

// Sentence 2
$para(
    "The purpose of the polygraph examination was to determine whether " .
    $A .
    " has minimized, concealed, withheld, hidden, or lied about any information relating to the application packet and interviews " . // CHANGED: removed redundant concat
    "for the position of " .
    $position .
    " for the " .
    $agency .
    "."
);
?>
