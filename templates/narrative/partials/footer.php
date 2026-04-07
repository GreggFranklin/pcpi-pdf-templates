<htmlpagefooter name="MyCustomFooter">
    <table width="100%" style="padding-top: 5pt; font-size: 8pt; color: #666;">
        <tr>
            <td width="33%"></td>
            <td width="34%" align="center"></td>
            <td width="33%" align="right">REV 
            <?php
            $ts = strtotime(rgar($entry, "date_created") ?: "");
            echo esc_html($ts ? wp_date("m/Y", $ts) : "");
            ?>
	    </td>
        </tr>
    </table>
</htmlpagefooter>