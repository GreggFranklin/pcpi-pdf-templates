<?php 
/* ------------------------------------------------------------------------
 * ESCAPE / OUTPUT HELPERS
 * ------------------------------------------------------------------------ */
$esc = function ($v) {
    return esc_html((string) $v);
};

$para = function ($text) use ($esc) {
    $text = trim((string) $text);
    if ($text === "") {
        return;
    }
    echo "<p>" . $esc($text) . "</p>";
};

$para_html = function ($html) {
    $html = trim((string) $html);
    if ($html === "") {
        return;
    }
    echo "<p>" . wp_kses_post($html) . "</p>";
};

$para_detail = function ($label, $detail) use ($esc) {
    $detail = trim((string) $detail);
    if ($detail === "") {
        return;
    }
    echo "<p><strong>" .
        $esc($label) .
        ":</strong><br>" .
        nl2br($esc($detail)) .
        "</p>";
};

/* -------------------------------------------------------------------------
 * DATE FORMATTER (global, consistent)
 * - Converts "06/01/1949" -> "June 01, 1949"
 * - Leaves already-text dates alone if parsing fails
 * ------------------------------------------------------------------------ */
$fmt_date = function ($raw, $format = "F d, Y") {
    $raw = trim((string) $raw);
    if ($raw === "") {
        return "";
    }

    // If it's already something like "June 01, 1949", strtotime will still parse it fine.
    $ts = strtotime($raw);
    if (!$ts) {
        return $raw; // fallback: don't break output
    }

    return wp_date($format, $ts);
};

/**
 * Narrative comments (no label).
 * Use a lead-in only if you want it to read more like a report.
 */
$para_comment = function ($text, $lead_in = "") use ($para) {
    $text = trim((string) $text);
    if ($text === "") {
        return;
    }

    if ($lead_in !== "") {
        $para($lead_in . " " . $text);
        return;
    }

    $para($text);
};

/* ------------------------------------------------------------------------
 * CAPTURED-SCOPE FIELD GETTER (Gravity PDF safe)
 * ------------------------------------------------------------------------ */
$val = function ($id, $default = "") use (&$form_data, &$entry, &$form) {
    static $field_cache = [];

    $key = (string) $id;

    // Method 1: processed form_data values
    if (
        isset($form_data["field"][$key]) &&
        $form_data["field"][$key] !== "" &&
        $form_data["field"][$key] !== null
    ) {
        $v = $form_data["field"][$key];

        // Name/Address/etc. can come through as arrays in form_data.
        // Convert arrays to a readable string instead of returning "Array".
        if (is_array($v)) {
            // If this looks like a Name field keyed by sub-input IDs, join in a sane order.
            $preferred = [
                "{$key}.2",
                "{$key}.3",
                "{$key}.4",
                "{$key}.6",
                "{$key}.8",
            ]; // prefix, first, middle, last, suffix
            $parts = [];

            $has_sub_keys = false;
            foreach ($preferred as $sub_key) {
                if (array_key_exists($sub_key, $v)) {
                    $has_sub_keys = true;
                    $piece = trim((string) $v[$sub_key]);
                    if ($piece !== "") {
                        $parts[] = $piece;
                    }
                }
            }

            // Fallback: flatten whatever values exist
            if (!$has_sub_keys) {
                foreach ($v as $piece) {
                    $piece = trim((string) $piece);
                    if ($piece !== "") {
                        $parts[] = $piece;
                    }
                }
            }

            $parts = array_values(array_unique($parts));

            return trim(preg_replace("/\s+/", " ", implode(" ", $parts)));
        }

        return $v;
    }

    // Method 2: raw entry (fast path)
    $raw = rgar($entry, $key);
    if ($raw !== null && $raw !== "") {
        return $raw;
    }

    // Method 3: Gravity Forms display formatting (name/address/etc.)
    if (!empty($form["fields"])) {
        // If sub-input passed (e.g. "40.3") GFAPI::get_field() wants the base ID.
        $lookup_id = $key;
        if (strpos($lookup_id, ".") !== false) {
            $lookup_id = strtok($lookup_id, ".");
        }

        if (!isset($field_cache[$lookup_id])) {
            $field_cache[$lookup_id] = GFAPI::get_field($form, $lookup_id);
        }

        $field = $field_cache[$lookup_id];

        if ($field) {
            $value = GFFormsModel::get_lead_field_value($entry, $field);
            if ($value !== null && $value !== "") {
                $currency = rgar($entry, "currency");
                $currency = $currency ? $currency : "USD";
                return GFCommon::get_lead_field_display(
                    $field,
                    $value,
                    $currency
                );
            }
        }
    }

    return $default;
};

/* ------------------------------------------------------------------------
 * YES/NO + SENTENCE HELPERS
 * ------------------------------------------------------------------------ */

/**
 * Normalize Yes/No.
 * Returns: 'Yes', 'No', or '' (unanswered/unknown)
 */
$yn = function ($id) use ($val) {
    $v = $val($id, "");

    // Normalize arrays (rare but possible)
    if (is_array($v)) {
        $v = implode(" ", array_map("strval", $v));
    }

    $v = strtolower(trim((string) $v));
    if ($v === "") {
        return "";
    }

    // Some odd formats can include multiple tokens; keep first token
    $first = preg_split("/\s+/", $v);
    $first = strtolower(trim((string) ($first[0] ?? "")));

    if (in_array($first, ["yes", "y", "1", "true", "on", "checked"], true)) {
        return "Yes";
    }
    if (in_array($first, ["no", "n", "0", "false", "off"], true)) {
        return "No";
    }

    return "";
};

/**
 * Replace leading "The applicant" with "Applicant {Full Name}" for report tone.
 */
$as_applicant = function ($sentence, $A) {
    $s = trim((string) $sentence);
    if ($s === "") {
        return "";
    }

    // Only touch the *start* of the sentence to avoid weird mid-sentence edits.
    $s = preg_replace("/^The applicant\b/i", $A, $s);
    $s = preg_replace("/^the applicant\b/i", $A, $s);

    return $s;
};

/**
 * Create a sentence based on Yes/No. Returns '' when unanswered.
 * Also upgrades the sentence opener to "Applicant {Name}" when it begins with "The applicant".
 */
$say_yn = function ($id, $yes_text, $no_text) use ($yn, $as_applicant, &$A) {
    $ans = $yn($id);
    if ($ans === "Yes") {
        return $as_applicant((string) $yes_text, $A);
    }
    if ($ans === "No") {
        return $as_applicant((string) $no_text, $A);
    }
    return "";
};

/*-------------------------------------------------------------------------
 * Reusable helper for “Yes + date pair fields”
 * ------------------------------------------------------------------------ */
$para_yn_with_date = function (
    $yn_id,
    $yes_text,
    $no_text,
    $month_id,
    $year_id,
    $label = "The approximate date is:"
) use ($yn, $val, $para, $as_applicant, &$A) {
    $ans = $yn($yn_id);
    if ($ans === "") {
        return;
    }

    if ($ans === "No") {
        $para($as_applicant($no_text, $A));
        return;
    }

    $month = trim((string) $val((string) $month_id, ""));
    $year = trim((string) $val((string) $year_id, ""));

    $date_parts = array_filter([$month, $year]);

    // Prefer "December 2005" rather than "December, 2005"
    $date_str = "";
    if (!empty($date_parts)) {
        $date_str = trim(implode(" ", $date_parts));
    }

    $text = (string) $yes_text;
    if ($date_str !== "") {
        $text .= " " . trim((string) $label) . " " . $date_str . ".";
    }

    $para($as_applicant($text, $A));
};

/* -------------------------------------------------------------------------
 * Yes/No + Dates (List field) narrative helper
 * ------------------------------------------------------------------------ */
$para_yn_with_single_date = function (
    $yn_id,
    $yes_text,
    $no_text,
    $date_field_id,
    $date_label = "Dates:"
) use ($yn, $entry, $val, $para, $as_applicant, &$A) {
    $ans = $yn($yn_id);
    if ($ans === "") {
        return;
    }

    if ($ans === "No") {
        $no_text = trim((string) $no_text);
        if ($no_text !== "") {
            $para($as_applicant($no_text, $A));
        }
        return;
    }

    $text = (string) $yes_text;

    // Pull RAW entry value first (prevents unexpected GFPDF formatted output)
    $date_raw = rgar($entry, (string) $date_field_id);
    if ($date_raw === null || $date_raw === "") {
        $date_raw = $val((string) $date_field_id, "");
    }

    $list = maybe_unserialize($date_raw);
    $date_str = "";

    if (is_array($list)) {
        $items = [];
        foreach ($list as $row) {
            if (is_array($row)) {
                foreach ($row as $cell) {
                    $cell = trim(wp_strip_all_tags((string) $cell));
                    if ($cell !== "") {
                        $items[] = $cell;
                    }
                }
            } else {
                $row = trim(wp_strip_all_tags((string) $row));
                if ($row !== "") {
                    $items[] = $row;
                }
            }
        }
        $items = array_values(array_unique($items));
        $date_str = implode(", ", $items);
    } else {
        $s = trim(wp_strip_all_tags((string) $date_raw));
        if ($s !== "") {
            $s = str_replace(["|", "\n", "\r"], ", ", $s);
            $s = preg_replace("/\s*,\s*/", ", ", $s);
            $s = trim($s, ", \t\n\r\0\x0B");
            $date_str = $s;
        }
    }

    if ($date_str !== "") {
        $text .= " " . trim((string) $date_label) . " " . $date_str . ".";
    }

    $para($as_applicant($text, $A));
};

/* -------------------------------------------------------------------------
 * Drug narrative helper (YES only)
 * ------------------------------------------------------------------------ */
$para_drug_yes_only = function (
    $yn_id,
    $drug_label,
    $date_field_id = null
) use ($yn, $val, $para, &$A) {
    if ($yn($yn_id) !== "Yes") {
        return;
    }

    $text = $A . " reports use of " . (string) $drug_label . ".";

    if ($date_field_id) {
        $date = trim((string) $val((string) $date_field_id, ""));
        if ($date !== "") {
            $text .= " Approximate date of last use: " . $date . ".";
        }
    }

    $para($text);
};

/* -------------------------------------------------------------------------
 * Render a Gravity Forms List field as narrative text.
 * ------------------------------------------------------------------------ */
$para_list_narrative = function ($field_id, $intro = "") use ($entry, $para) {
    $raw = rgar($entry, (string) $field_id);
    if (empty($raw)) {
        return;
    }

    $list = maybe_unserialize($raw);
    if (!is_array($list) || empty($list)) {
        return;
    }

    if ($intro !== "") {
        $para($intro);
    }

    foreach ($list as $row) {
        if (!is_array($row)) {
            continue;
        }

        // Trim + drop empties
        $values = array_values(
            array_filter(array_map("trim", $row), function ($v) {
                return (string) $v !== "";
            })
        );

        if (empty($values)) {
            continue;
        }

        if (count($values) === 2) {
            $text = sprintf("%s (%s).", $values[0], $values[1]);
        } elseif (count($values) === 3) {
            $text = sprintf(
                "%s (%s)%s.",
                $values[0],
                $values[1],
                strtoupper($values[2]) === "C" ? " — Convicted" : ""
            );
        } else {
            $text = implode(", ", $values) . ".";
        }

        $para($text);
    }
};

/**
 * Render a GF List field as a single narrative sentence (Drugs/Gambling style).
 *
 * Assumes each list row is something like:
 * - Column 1: Description/Offense
 * - Column 2: Date/Year (optional)
 *
 * Output example:
 * "Beginning with the most recent, Applicant X reports the following arrests or charges: Petty Theft (1997), Car Jacking (1995)."
 */
$para_list_sentence = function( $field_id, $lead_in = '' ) use ( $entry, $para, $esc ) {

	$raw = rgar( $entry, (string) $field_id );
	if ( empty( $raw ) ) {
		return;
	}

	$list = maybe_unserialize( $raw );
	if ( ! is_array( $list ) || empty( $list ) ) {
		return;
	}

	$items = [];

	foreach ( $list as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		// Pull first two non-empty cells (handles “any column names”)
		$cells = [];
		foreach ( $row as $cell ) {
			$cell = trim( wp_strip_all_tags( (string) $cell ) );
			if ( $cell !== '' ) {
				$cells[] = $cell;
			}
		}

		if ( empty( $cells ) ) {
			continue;
		}

		// Format as "Thing (Year)" when we have at least 2 cells
		if ( count( $cells ) >= 2 ) {
			$items[] = $cells[0] . ' (' . $cells[1] . ')';
		} else {
			$items[] = $cells[0];
		}
	}

	$items = array_values( array_filter( array_map( 'trim', $items ) ) );
	if ( empty( $items ) ) {
		return;
	}

	// Oxford comma join
	$n = count( $items );
	if ( $n === 1 ) {
		$list_str = $items[0];
	} elseif ( $n === 2 ) {
		$list_str = $items[0] . ' and ' . $items[1];
	} else {
		$tmp  = $items;
		$last = array_pop( $tmp );
		$list_str = implode( ', ', $tmp ) . ', and ' . $last;
	}

	$sentence = trim( (string) $lead_in );
	if ( $sentence !== '' && substr( $sentence, -1 ) !== ':' ) {
		$sentence .= ':';
	}
	if ( $sentence !== '' ) {
		$sentence .= ' ';
	}

	$sentence .= $list_str . '.';

	$para( $sentence );
};

/* -------------------------------------------------------------------------
 * Sexual Behavior narrative helper
 * ------------------------------------------------------------------------ */
$para_yn_with_count_and_date = function (
    $yn_id,
    $yes_text,
    $count_field_id = null,
    $date_field_id = null
) use ($yn, $val, $para, $as_applicant, &$A) {
    if ($yn($yn_id) !== "Yes") {
        return;
    }

    $text = (string) $yes_text;
    $text = $as_applicant($text, $A);

    if ($count_field_id) {
        $count_raw = trim((string) $val((string) $count_field_id, ""));

        if ($count_raw !== "") {
            $lc = strtolower($count_raw);

            $is_zeroish =
                $lc === "0" ||
                $lc === "0 times" ||
                $lc === "none" ||
                $lc === "n/a";

            if (!$is_zeroish) {
                if (preg_match("/\btime(s)?\b/i", $count_raw)) {
                    $text .=
                        " " . $A . " reports this occurred " . $count_raw . ".";
                } else {
                    $is_one = preg_match('/^\s*1\s*$/', $count_raw) === 1;
                    $text .=
                        " " .
                        $A .
                        " reports this occurred " .
                        $count_raw .
                        " " .
                        ($is_one ? "time" : "times") .
                        ".";
                }
            }
        }
    }

    if ($date_field_id) {
        $date = trim((string) $val((string) $date_field_id, ""));
        if ($date !== "") {
            $text .=
                " The most recent occurrence was approximately " . $date . ".";
        }
    }

    $para($text);
};

/* -------------------------------------------------------------------------
 * Associations narrative helper
 * ------------------------------------------------------------------------ */
$para_yn_with_from_to = function (
    $yn_id,
    $yes_text,
    $from_id = null,
    $to_id = null
) use ($yn, $val, $para, $as_applicant, &$A) {
    if ($yn($yn_id) !== "Yes") {
        return;
    }

    $text = $as_applicant((string) $yes_text, $A);

    $from = $from_id ? trim((string) $val((string) $from_id, "")) : "";
    $to = $to_id ? trim((string) $val((string) $to_id, "")) : "";

    if ($from !== "" || $to !== "") {
        $text .= " " . $A . " reports this association occurred";
        if ($from !== "") {
            $text .= " from " . $from;
        }
        if ($to !== "") {
            $text .= " to " . $to;
        }
        $text .= ".";
    }

    $para($text);
};

/* -------------------------------------------------------------------------
 * NESTED FORMS – GENERIC RENDERER + NAME FIELD SUPPORT
 * ------------------------------------------------------------------------ */

/**
 * Build a readable full name from a child entry Name field base ID.
 * Works for GF Name fields stored as sub-inputs: 1.2, 1.3, 1.4, 1.6, 1.8
 */
$child_name = function (array $child, $base_id = "1") {
    $base = (string) $base_id;

    $preferred = [
        "{$base}.2",
        "{$base}.3",
        "{$base}.4",
        "{$base}.6",
        "{$base}.8",
    ]; // prefix, first, middle, last, suffix
    $parts = [];

    foreach ($preferred as $k) {
        $v = trim((string) rgar($child, $k));
        if ($v !== "") {
            $parts[] = $v;
        }
    }

    // Fallback: sometimes name may be stored at base key (rare)
    if (empty($parts)) {
        $v = trim((string) rgar($child, $base));
        if ($v !== "") {
            return $v;
        }
    }

    return trim(preg_replace("/\s+/", " ", implode(" ", $parts)));
};

/**
 * Safe child value getter.
 * - Reads raw child entry key (string)
 * - Optionally formats dates
 */
$child_val = function (array $child, $key, $format_date = false) {
    $v = trim((string) rgar($child, (string) $key));
    if ($v === "") {
        return "";
    }

    if ($format_date) {
        $ts = strtotime($v);
        return $ts ? wp_date("F j, Y", $ts) : $v;
    }

    return $v;
};

/**
 * Generic nested renderer:
 * - Parent nested field stores child entry IDs (CSV or JSON-ish)
 * - Loads each child entry
 * - Calls $builder( $child ) to get a narrative sentence
 * - Outputs via $para()
 */
$para_nested = function (
    array $parent_entry,
    $parent_field_id,
    callable $builder,
    $expected_child_form_id = 0
) use ($para) {
    $raw = rgar($parent_entry, (string) $parent_field_id);
    $raw = is_string($raw) ? trim($raw) : "";

    if ($raw === "") {
        return;
    }

    // Support JSON arrays as well as CSV
    $ids = [];
    if ($raw !== "" && ($raw[0] === "[" || $raw[0] === "{")) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $ids = $decoded;
        }
    }

    if (empty($ids)) {
        $raw = trim($raw, "[] \t\n\r\0\x0B");
        $ids = preg_split("/\s*,\s*/", $raw, -1, PREG_SPLIT_NO_EMPTY);
    }

    $ids = array_values(array_filter(array_map("trim", (array) $ids)));

    foreach ($ids as $child_id) {
        // HARD guard: must be an integer string > 0
        if (!preg_match('/^\d+$/', (string) $child_id)) {
            continue;
        }

        $child_id = (int) $child_id;
        if ($child_id <= 0) {
            continue;
        }

        $child = GFAPI::get_entry($child_id);
        if (is_wp_error($child) || empty($child)) {
            continue;
        }

        // Optional: ensure we only render the expected child form entries
        if (
            $expected_child_form_id &&
            (int) rgar($child, "form_id") !== (int) $expected_child_form_id
        ) {
            continue;
        }

        $line = trim((string) call_user_func($builder, $child));
        if ($line !== "") {
            $para($line);
        }
    }
};

/**
 * Render "Other drugs" nested form (parent field stores child entry IDs).
 * Example output in a single sentence list:
 * "Applicant ... reports other drugs / last use: Ecstasy (01/08/2015), Steroids (04/2006)."
 *
 * @param array  $parent_entry
 * @param int    $parent_field_id   Parent Nested Form field ID (e.g. 315)
 * @param int    $child_form_id     Child form ID (e.g. 14) (optional but recommended)
 * @param string $drug_key          Child field ID for Drug (default "1")
 * @param string $date_key          Child field ID for Date (default "2")
 */
$para_other_drugs_list = function(
    array $parent_entry,
    $parent_field_id,
    $child_form_id = 0,
    $drug_key = '1',
    $date_key = '2'
) use ( $para_nested, $child_val, $fmt_date, &$A ) {

    $items = [];

    $collector = function( array $child ) use ( &$items, $child_val, $fmt_date, $drug_key, $date_key ) {
        $drug = trim( (string) $child_val( $child, $drug_key ) );
        $date = trim( (string) $child_val( $child, $date_key ) );

        if ( $drug === '' && $date === '' ) {
            return '';
        }

        // Format date nicely if present
        if ( $date !== '' ) {
            $date = $fmt_date( $date, 'm/d/Y' ); // or 'F j, Y' if you prefer
        }

        $items[] = $date !== '' ? "{$drug} ({$date})" : $drug;

        // Return empty because we're collecting; we don't want per-row paragraphs.
        return '';
    };

    // This iterates the child entries referenced by the parent field and fills $items.
    $para_nested( $parent_entry, $parent_field_id, $collector, $child_form_id );

    $items = array_values( array_filter( array_map( 'trim', $items ) ) );
    if ( empty( $items ) ) {
        return;
    }

    // Oxford comma style join
    $list = '';
    $count = count( $items );
    if ( $count === 1 ) {
        $list = $items[0];
    } elseif ( $count === 2 ) {
        $list = $items[0] . ' and ' . $items[1];
    } else {
        $list = implode( ', ', array_slice( $items, 0, -1 ) ) . ', and ' . $items[ $count - 1 ];
    }

    echo '<p>' . esc_html( $A . ' further reports other drugs / last use: ' . $list . '.' ) . '</p>';
};

/**
 * Count-ish helper for CVSA/Poly counts (handles: 0/none/never, numeric, or descriptive)
 */
$count_phrase = function ($raw, $singular, $plural = "") {
    $raw = trim((string) $raw);
    if ($raw === "") {
        return "";
    }

    $lc = strtolower($raw);

    // Treat dropdown placeholders as empty
    if (strpos($lc, "select") !== false) {
        return "";
    }

    // Common "empty" meanings
    if (in_array($lc, ["0", "none", "never", "no", "n/a", "na", "-"], true)) {
        return "";
    }

    // Numeric counts → proper singular/plural noun
    if (is_numeric($raw)) {
        $n = (int) $raw;
        if ($n <= 0) {
            return "";
        }

        $word =
            $n === 1 ? $singular : ($plural !== "" ? $plural : $singular . "s");

        return $n . " " . $word;
    }

    // Fallback: descriptive text (rare but safe)
    return $raw;
};

/* ------------------------------------------------------------------------
 * CORE VALUES
 * ------------------------------------------------------------------------ */
//$applicant_id  = trim( (string) $val( '556', '' ) );
$agency = trim((string) $val("414", ""));
//$agency_email  = trim( (string) $val( '594', '' ) );
$position = trim((string) $val("596", ""));
$result = "No Significant Reaction (NSR)";
$file_record_no = "24-1164";

$full_name = trim((string) $val("40", "")); // GF Name field (base)
$dob = trim((string) $val("9", ""));
$email_current = trim((string) $val("15", ""));

/**
 * Applicant display phrase used throughout.
 * Matches your example style: "Applicant John A. Doe"
 */
$A = $full_name !== "" ? "Applicant " . $full_name : "Applicant";

/**
 * DOB change format to Month, day, Year
 */
$dob_formatted = $fmt_date($dob);

/* Address */
$addr1 		= trim((string) $val("10.1", ""));
$addr2 		= trim((string) $val("10.2", ""));
$city 		= trim((string) $val("10.3", ""));
$state 		= trim((string) $val("10.4", ""));
$zip 		= trim((string) $val("10.5", ""));
$country 	= trim((string) $val("10.6", ""));

/* Section comments */
$general_comments 	= trim((string) $val("23", ""));
$law_enforcement_comments = trim((string) $val("30", ""));
$social_comments 	= trim((string) $val("221", ""));
$edu_comments 		= trim((string) $val("243", ""));
$driving_comments 	= trim((string) $val("260", ""));
$residential_explain 	= trim((string) $val("267", ""));
$financial_comments 	= trim((string) $val("295", ""));
$alcohol_explain 	= trim((string) $val("308", ""));
$drug_comments 		= trim((string) $val("319", ""));
$gambling_comments 	= trim((string) $val("327", ""));
$legal_comments 	= trim((string) $val("401", ""));
$integrity_comments 	= trim((string) $val("524", ""));
$aggressive_comments 	= trim((string) $val("405", ""));
$weapons_comments 	= trim((string) $val("555", ""));
$sexual_comments 	= trim((string) $val("166", ""));
$associations_comments 	= trim((string) $val("335", ""));
$additional_comments 	= trim((string) $val("120", ""));

/* Examiner comments */
$exam_pre_test_interview 	= trim((string) $val("597", ""));
$exam_general_comments 		= trim((string) $val("558", ""));
$exam_law_enforcement_comments 	= trim((string) $val("559", ""));
$exam_military_comments 	= trim((string) $val("560", ""));
$exam_social_comments 		= trim((string) $val("561", ""));
$exam_edu_comments 		= trim((string) $val("564", ""));
$exam_driving_comments 		= trim((string) $val("565", ""));
$exam_residential_explain 	= trim((string) $val("566", ""));
$exam_employment_explain 	= trim((string) $val("567", ""));
$exam_financial_comments 	= trim((string) $val("568", ""));
$exam_alcohol_explain 		= trim((string) $val("570", ""));
$exam_drug_comments 		= trim((string) $val("571", ""));
$exam_gambling_comments 	= trim((string) $val("576", ""));
$exam_legal_comments 		= trim((string) $val("577", ""));
$exam_integrity_comments 	= trim((string) $val("578", ""));
$exam_aggressive_comments 	= trim((string) $val("579", ""));
$exam_weapons_comments 		= trim((string) $val("588", ""));
$exam_sexual_comments 		= trim((string) $val("590", ""));
$exam_associations_comments 	= trim((string) $val("591", ""));
$exam_additional_comments 	= trim((string) $val("592", ""));
$exam_post_test_interview 	= trim((string) $val("593", ""));

/* Military Service logic */
$honorable_like = $yn("202") === "Yes" || $yn("203") === "Yes";
$non_honorable =
    $yn("204") === "Yes" ||
    $yn("205") === "Yes" ||
    $yn("206") === "Yes" ||
    $yn("207") === "Yes";
$discharge_expl = trim((string) $val("208", ""));

$sel_service = $yn("209") === "Yes";
$sel_number = trim((string) $val("61", ""));
?>