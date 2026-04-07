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

/* ------------------------------------------------------------------------
 * LIST JOIN UTILITIES
 * ------------------------------------------------------------------------ */
$oxford_join = function ( array $items, $conjunction = 'and' ) {

    // Normalize + clean
    $items = array_values(
        array_filter(
            array_map(
                static function ( $v ) {
                    return trim( (string) $v );
                },
                $items
            )
        )
    );

    $count = count( $items );

    if ( $count === 0 ) {
        return '';
    }

    if ( $count === 1 ) {
        return $items[0];
    }

    if ( $count === 2 ) {
        return $items[0] . ' ' . $conjunction . ' ' . $items[1];
    }

    return implode( ', ', array_slice( $items, 0, -1 ) )
        . ', ' . $conjunction . ' '
        . $items[ $count - 1 ];
};

/* -------------------------------------------------------------------------
 * TEXT JOIN HELPERS (non-breaking additions)
 * ------------------------------------------------------------------------ */
$join_oxford = function (array $items) use ($oxford_join) { // CHANGED
    return $oxford_join($items, 'and'); // CHANGED (single source of truth)
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
 * PCPI: Resolve related entries for "combined" PDFs
 *
 * Goal: Keep existing template intact (300+ $val() calls), but when this PDF is
 * generated from a Review entry (comments-only form), pull Questionnaire field
 * values from the linked Questionnaire entry.
 *
 * - $pcpi_review_entry:        the current entry passed to the template (often Review)
 * - $pcpi_source_entry:        the Questionnaire entry (if resolvable)
 * - $pcpi_source_form:         the Questionnaire form array (if resolvable)
 * - $pcpi_workflow_key/_wf:    registry data (Workflow Engine)
 * ------------------------------------------------------------------------ */

$pcpi_review_entry  = $entry;
$pcpi_source_entry  = null;
$pcpi_source_form   = null;
$pcpi_workflow_key  = '';
$pcpi_workflow      = [];

if ( class_exists( 'PCPI_Workflow_Engine' ) && class_exists( 'GFAPI' ) && is_array( $form ) ) {

	$current_form_id = absint( rgar( $form, 'id' ) );
	$workflows       = (array) PCPI_Workflow_Engine::get_workflows();

	foreach ( $workflows as $k => $wf ) {
		$wf = is_array( $wf ) ? $wf : [];
		if ( $current_form_id && $current_form_id === absint( $wf['review_form_id'] ?? 0 ) ) {
			$pcpi_workflow_key = sanitize_key( (string) $k );
			$pcpi_workflow     = $wf;
			break;
		}
	}

	// If this PDF is on the Review entry, try to load the linked Questionnaire entry.
	if ( ! empty( $pcpi_workflow ) ) {

		$parent_q_fid = absint( $pcpi_workflow['review_parent_questionnaire_field_id'] ?? 0 );
		$qid          = $parent_q_fid ? absint( rgar( $pcpi_review_entry, (string) $parent_q_fid ) ) : 0;

		if ( $qid ) {
			$tmp = GFAPI::get_entry( $qid );
			if ( ! is_wp_error( $tmp ) && ! empty( $tmp ) ) {
				$pcpi_source_entry = $tmp;

				$source_form_id = absint( $pcpi_workflow['source_form_id'] ?? 0 );
				if ( $source_form_id ) {
					$f = GFAPI::get_form( $source_form_id );
					if ( ! empty( $f ) ) {
						$pcpi_source_form = $f;
					}
				}
			}
		}
	}
}


/* ------------------------------------------------------------------------
 * CAPTURED-SCOPE FIELD GETTER (Gravity PDF safe)
 * ------------------------------------------------------------------------ */
$val = function ( $id, $default = "" ) use ( &$form_data, &$entry, &$form, &$pcpi_source_entry, &$pcpi_source_form ) {
	static $field_cache = [];

	$key = (string) $id;

	// If we have a Questionnaire "source" entry (PDF generated from Review form),
	// read values from the source entry/form instead of the current entry/form_data.
	$src_entry = ( ! empty( $pcpi_source_entry ) && is_array( $pcpi_source_entry ) ) ? $pcpi_source_entry : $entry;
	$src_form  = ( ! empty( $pcpi_source_form ) && is_array( $pcpi_source_form ) ) ? $pcpi_source_form : $form;

	$is_sourced = ( $src_entry !== $entry ); // true when we resolved Questionnaire from Review

	// Method 1: processed form_data values (only valid when using the current entry)
	if ( ! $is_sourced ) {
		if (
			isset( $form_data['field'][ $key ] ) &&
			$form_data['field'][ $key ] !== '' &&
			$form_data['field'][ $key ] !== null
		) {
			$v = $form_data['field'][ $key ];

			// Name/Address/etc. can come through as arrays in form_data.
			// Convert arrays to a readable string instead of returning "Array".
			if ( is_array( $v ) ) {
				$preferred = [
					"{$key}.2",
					"{$key}.3",
					"{$key}.4",
					"{$key}.6",
					"{$key}.8",
				]; // prefix, first, middle, last, suffix
				$parts = [];

				$has_sub_keys = false;
				foreach ( $preferred as $sub_key ) {
					if ( array_key_exists( $sub_key, $v ) ) {
						$has_sub_keys = true;
						$piece        = trim( (string) $v[ $sub_key ] );
						if ( $piece !== '' ) {
							$parts[] = $piece;
						}
					}
				}

				// Fallback: flatten whatever values exist
				if ( ! $has_sub_keys ) {
					foreach ( $v as $piece ) {
						$piece = trim( (string) $piece );
						if ( $piece !== '' ) {
							$parts[] = $piece;
						}
					}
				}

				$parts = array_values( array_unique( $parts ) );

				return trim( preg_replace( "/\s+/", " ", implode( " ", $parts ) ) );
			}

			return $v;
		}
	}

	// Method 2: raw entry (fast path)
	$raw = rgar( $src_entry, $key );
	if ( $raw !== null && $raw !== '' ) {
		return $raw;
	}

	// Method 3: Gravity Forms display formatting (name/address/etc.)
	if ( ! empty( $src_form['fields'] ) ) {

		// If sub-input passed (e.g. "40.3") GFAPI::get_field() wants the base ID.
		$lookup_id = $key;
		if ( strpos( $lookup_id, '.' ) !== false ) {
			$lookup_id = strtok( $lookup_id, '.' );
		}

		$cache_key = (string) absint( $lookup_id ) . ':' . (string) absint( rgar( $src_form, 'id' ) );

		if ( ! isset( $field_cache[ $cache_key ] ) ) {
			$field_cache[ $cache_key ] = GFAPI::get_field( $src_form, $lookup_id );
		}

		$field = $field_cache[ $cache_key ];

		if ( $field ) {
			$value = GFFormsModel::get_lead_field_value( $src_entry, $field );
			if ( $value !== null && $value !== '' ) {
				$currency = rgar( $src_entry, 'currency' );
				$currency = $currency ? $currency : 'USD';
				return GFCommon::get_lead_field_display( $field, $value, $currency );
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
    $s = preg_replace("/^The applicant\b/i", $A, $s); // CHANGED (removed redundant second replace)

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
 * LIST FIELD PARSING (canonical)
 * ------------------------------------------------------------------------ */
/**
 * Return a flat array of non-empty values from a GF List field.
 * Robust for Gravity PDF contexts (raw entry OR $val()) and supports JSON/serialized.
 *
 * IMPORTANT: This is the ONLY definition of $list_field_items in this file.
 */
$list_field_items = function ($field_id) use ($entry, $val) {
    // Prefer RAW entry value first, then fall back to $val()
    $raw = rgar($entry, (string) $field_id);
    if ($raw === null || $raw === "") {
        $raw = $val((string) $field_id, "");
    }

    if ($raw === "" || $raw === null) {
        return [];
    }

    // If already array, use it directly
    if (is_array($raw)) {
        $list = $raw;
    } else {
        $s = trim((string) $raw);

        // JSON list
        if ($s !== "" && ($s[0] === "[" || $s[0] === "{")) {
            $decoded = json_decode($s, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $list = $decoded;
            } else {
                $list = maybe_unserialize($s);
            }
        } else {
            $list = maybe_unserialize($s);
        }
    }

    $items = [];

    if (is_array($list)) {
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
    } else {
        // Fallback for unexpected formats
        $s = trim(wp_strip_all_tags((string) $raw));
        if ($s !== "") {
            $parts = preg_split("/\r\n|\n|\r|,/", $s);
            foreach ((array) $parts as $p) {
                $p = trim((string) $p);
                if ($p !== "") {
                    $items[] = $p;
                }
            }
        }
    }

    $items = array_values(array_unique(array_filter(array_map("trim", (array) $items))));
    return $items;
};

/**
 * Convenience: return list items as a string.
 */
$list_field_text = function ($field_id, $sep = ", ") use ($list_field_items) {
    $items = $list_field_items($field_id);
    return empty($items) ? "" : implode($sep, $items);
};

/**
 * Return a GF List field (single column or flattened) as pretty dates.
 *
 * IMPORTANT: This is the ONLY definition of $list_field_dates_pretty in this file.
 */
$list_field_dates_pretty = function ($field_id, $format = "F j, Y", $sep = ", ") use ($list_field_items, $fmt_date) {
    $items = $list_field_items($field_id);
    if (empty($items)) {
        return "";
    }

    $pretty = [];
    foreach ($items as $raw) {
        $raw = trim((string) $raw);
        if ($raw === "") {
            continue;
        }
        $pretty[] = $fmt_date($raw, $format);
    }

    $pretty = array_values(array_filter(array_map("trim", $pretty)));
    return empty($pretty) ? "" : implode($sep, $pretty);
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
) use ($yn, $para, $as_applicant, &$A, $list_field_items) { // CHANGED (reuse centralized parsing)
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

    $items = $list_field_items($date_field_id); // CHANGED
    $date_str = empty($items) ? "" : implode(", ", $items); // CHANGED

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
 * LIST ROW GETTER (shared by list renderers)
 * ------------------------------------------------------------------------ */
$get_list_rows = function ($field_id) use ($entry, $val) { // CHANGED: allow $val fallback
    $raw = rgar($entry, (string) $field_id);
    if ($raw === null || $raw === "") {
        $raw = $val((string) $field_id, "");
    }
    if (empty($raw)) {
        return [];
    }
    $list = maybe_unserialize($raw);
    return (is_array($list) && !empty($list)) ? $list : [];
};

/* -------------------------------------------------------------------------
 * Render a Gravity Forms List field as narrative text.
 * ------------------------------------------------------------------------ */
$para_list_narrative = function ($field_id, $intro = "") use ($para, $get_list_rows) { // CHANGED
    $list = $get_list_rows($field_id); // CHANGED
    if (empty($list)) {
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
 */
$para_list_sentence = function ($field_id, $lead_in = "") use ($para, $oxford_join, $get_list_rows) { // CHANGED
    $list = $get_list_rows($field_id); // CHANGED
    if (empty($list)) {
        return;
    }

    $items = [];

    foreach ($list as $row) {
        if (!is_array($row)) {
            continue;
        }

        // Pull first two non-empty cells (handles “any column names”)
        $cells = [];
        foreach ($row as $cell) {
            $cell = trim(wp_strip_all_tags((string) $cell));
            if ($cell !== "") {
                $cells[] = $cell;
            }
        }

        if (empty($cells)) {
            continue;
        }

        // Format as "Thing (Year)" when we have at least 2 cells
        if (count($cells) >= 2) {
            $items[] = $cells[0] . " (" . $cells[1] . ")";
        } else {
            $items[] = $cells[0];
        }
    }

    $items = array_values(array_filter(array_map("trim", $items)));
    if (empty($items)) {
        return;
    }

    $list_str = $oxford_join($items, 'and'); // CHANGED (remove duplicate joining logic)

    $sentence = trim((string) $lead_in);
    if ($sentence !== "" && substr($sentence, -1) !== ":") {
        $sentence .= ":";
    }
    if ($sentence !== "") {
        $sentence .= " ";
    }

    $sentence .= $list_str . ".";

    $para($sentence);
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
 */
$para_other_drugs_list = function (
    array $parent_entry,
    $parent_field_id,
    $child_form_id = 0,
    $drug_key = "1",
    $date_key = "2"
) use ($para_nested, $child_val, $fmt_date, $para, $oxford_join, &$A) { // CHANGED
    $items = [];

    $collector = function (array $child) use (
        &$items,
        $child_val,
        $fmt_date,
        $drug_key,
        $date_key
    ) {
        $drug = trim((string) $child_val($child, $drug_key));
        $date = trim((string) $child_val($child, $date_key));

        if ($drug === "" && $date === "") {
            return "";
        }

        // Format date nicely if present
        if ($date !== "") {
            $date = $fmt_date($date, "m/d/Y"); // unchanged behavior
        }

        $items[] = $date !== "" ? "{$drug} ({$date})" : $drug;

        // Return empty because we're collecting; we don't want per-row paragraphs.
        return "";
    };

    $para_nested($parent_entry, $parent_field_id, $collector, $child_form_id);

    $items = array_values(array_filter(array_map("trim", $items)));
    if (empty($items)) {
        return;
    }

    $list = $oxford_join($items, 'and'); // CHANGED

    // Use shared paragraph renderer for consistent escaping/wrapping
    $para($A . " further reports other drugs / last use: " . $list . "."); // CHANGED
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

        $word = $n === 1 ? $singular : ($plural !== "" ? $plural : $singular . "s");

        return $n . " " . $word;
    }

    // Fallback: descriptive text (rare but safe)
    return $raw;
};

/**
 * Get a field value from the current $form/$entry by Field Label or Admin Label.
 * Falls back safely to ''.
 */
$by_label = function( string $needle, bool $use_admin_label = false, string $default = '' ) use ( $form, $entry ) : string {

	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return $default;
	}

	$needle_norm = strtolower( trim( $needle ) );

	foreach ( $form['fields'] as $field ) {

		if ( ! is_object( $field ) ) {
			continue;
		}

		$hay = $use_admin_label
			? (string) ( $field->adminLabel ?? '' )
			: (string) ( $field->label ?? '' );

		$hay_norm = strtolower( trim( $hay ) );

		if ( $hay_norm === '' ) {
			continue;
		}

		if ( $hay_norm === $needle_norm ) {

			// Multi-input fields: return the base field value
			$id = (string) $field->id;
			$val = rgar( $entry, $id );

			// If it's an array (rare), stringify safely
			if ( is_array( $val ) ) {
				return $default;
			}

			return trim( (string) $val );
		}
	}

	return $default;
};

/**
 * Month number -> month name helper (kept as-is).
 */
$pretty_month = function( $m ) {
	$map = [
		'1'=>'January','2'=>'February','3'=>'March','4'=>'April',
		'5'=>'May','6'=>'June','7'=>'July','8'=>'August',
		'9'=>'September','10'=>'October','11'=>'November','12'=>'December',
	];
	return $map[(string)(int)$m] ?? '';
};

/**
 * Join sentences safely (kept as-is).
 */
$join_sentences = function( array $sentences ) {
	$sentences = array_values( array_filter( array_map( 'trim', $sentences ) ) );
	return implode( ' ', array_map(
		fn( $s ) => rtrim( $s, '.' ) . '.',
		$sentences
	));
};

/**
 * Set audit-friendly PDF metadata (not visible in content).
 *
 * Workflow-aware, no hard-coded field IDs.
 *
 * @param \Mpdf\Mpdf          $pdf
 * @param array<string,mixed> $entry
 */
function pcpi_set_pdf_metadata( $pdf, $entry ) {

	if ( ! is_object( $pdf ) || ! is_array( $entry ) || empty( $entry['id'] ) ) {
		return;
	}

	$pdf_entry_id = absint( rgar( $entry, 'id' ) );
	$pdf_form_id  = absint( rgar( $entry, 'form_id' ) );

	$examiner_user_id = absint( rgar( $entry, 'created_by' ) );
	if ( ! $examiner_user_id ) {
		$examiner_user_id = get_current_user_id();
	}

	$workflow_key = '';
	$workflow     = [];

	$questionnaire_entry_id = 0;
	$applicant_entry_id     = 0;
	$applicant_unique_id    = '';

	// Resolve workflow + relationships
	if ( class_exists( 'PCPI_Workflow_Engine' ) && class_exists( 'GFAPI' ) ) {

		$workflows = (array) PCPI_Workflow_Engine::get_workflows();

		foreach ( $workflows as $k => $wf ) {
			$wf = is_array( $wf ) ? $wf : [];

			if ( absint( $wf['review_form_id'] ?? 0 ) === $pdf_form_id ) {
				$workflow_key = sanitize_key( (string) $k );
				$workflow     = $wf;
				break;
			}
		}

		// Review → Questionnaire / Applicant
		if ( $workflow ) {

			$parent_q_fid = absint( $workflow['review_parent_questionnaire_field_id'] ?? 0 );
			if ( $parent_q_fid ) {
				$questionnaire_entry_id = absint( rgar( $entry, (string) $parent_q_fid ) );
			}

			$parent_a_fid = absint( $workflow['review_parent_applicant_field_id'] ?? 0 );
			if ( $parent_a_fid ) {
				$applicant_entry_id = absint( rgar( $entry, (string) $parent_a_fid ) );
			}

			// Questionnaire → Applicant
			if ( $questionnaire_entry_id ) {
				$q_entry = GFAPI::get_entry( $questionnaire_entry_id );
				if ( ! is_wp_error( $q_entry ) && ! empty( $q_entry ) ) {

					$q_parent_a_fid = absint( $workflow['questionnaire_parent_applicant_field_id'] ?? 0 );
					if ( $q_parent_a_fid ) {
						$applicant_entry_id = $applicant_entry_id
							?: absint( rgar( $q_entry, (string) $q_parent_a_fid ) );
					}
				}
			}
		}
	}

	// Applicant UID (preferred stable identifier)
	if ( $applicant_entry_id && class_exists( 'GFAPI' ) ) {
		$a_entry = GFAPI::get_entry( $applicant_entry_id );
		if ( ! is_wp_error( $a_entry ) && ! empty( $a_entry ) ) {
			$uid = trim( (string) rgar( $a_entry, '1000' ) ); // still valid if present
			if ( $uid !== '' ) {
				$applicant_unique_id = $uid;
			}
		}
	}

	// LAST-RESORT legacy fallbacks (old PDFs only)
	if ( ! $applicant_entry_id ) {
		$applicant_entry_id = absint( rgar( $entry, '579' ) );
	}
	if ( ! $questionnaire_entry_id ) {
		$questionnaire_entry_id = absint( rgar( $entry, '643' ) );
	}

	$applicant_id = $applicant_unique_id
		?: ( $applicant_entry_id ?: $pdf_entry_id );

	$generated_ts = gmdate( 'Y-m-d\TH:i:s\Z' );

	$audit_parts = array_filter( [
		'ApplicantID=' . $applicant_id,
		'PDFEntryID=' . $pdf_entry_id,
		'PDFFormID=' . $pdf_form_id,
		$workflow_key ? 'Workflow=' . $workflow_key : null,
		$applicant_entry_id ? 'ApplicantEntryID=' . $applicant_entry_id : null,
		$questionnaire_entry_id ? 'QuestionnaireEntryID=' . $questionnaire_entry_id : null,
		'ExaminerUserID=' . $examiner_user_id,
		'GeneratedUTC=' . $generated_ts,
	] );

	$audit_blob = implode( ' | ', $audit_parts );

	$pdf->SetTitle( 'PCPI Polygraph Report' );
	$pdf->SetAuthor( 'PCPI System (Examiner UID ' . $examiner_user_id . ')' );
	$pdf->SetCreator( 'WordPress + Gravity PDF' );
	$pdf->SetSubject( $audit_blob );
	$pdf->SetKeywords( 'PCPI,Polygraph,ApplicantID:' . $applicant_id . ',EntryID:' . $pdf_entry_id );
}

