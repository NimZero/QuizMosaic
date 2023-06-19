<?php

/**
 * QuizMosaic
 *
 * @author            NimZero <contact@nimzero.fr>
 * @copyright         2023 NimZero
 * @license           GPL v3 or later
 */

/** @var wpdb $wpdb */
global $wpdb;
$surveys = $wpdb->get_results(sprintf('SELECT * FROM %snz_quizmosaic_survey', $wpdb->prefix));
$sub_menu_url = menu_page_url('nz_quizmosaic_modify', false);
$menu_url = menu_page_url('nz_quizmosaic', false);
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <div>
        <form method="post">
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td>
                        <th scope="col" class="manage-column">Id</th>
                        <th scope="col" class="manage-column column-primary">Nom</th>
                        <th scope="col" class="manage-column">ShortCode</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($surveys as $survey) : echo
                        <<<ITEM
                            <tr>
                                <th scope="row" class="check-column">
                                    <input id="cb-select-15" type="checkbox" name="survey[]" value="15">
                                    <div class="locked-indicator">
                                        <span class="locked-indicator-icon" aria-hidden="true"></span>
                                        <span class="screen-reader-text">“TEST” is locked</span>
                                    </div>
                                </th>
                                <td>$survey->id</td>
                                <td>$survey->name</td>
                                <td>[quizmosaic questionnaire="$survey->id"]</td>
                                <td>
                                    <a href="$sub_menu_url&survey=$survey->id">modifier</a>
                                    <a href="$menu_url&delete=$survey->id">supprimer</a>
                                </td>
                            </tr>
                        ITEM;
                    endforeach; ?>
                </tbody>
                <tfoot>
                    <td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2">Select All</label><input id="cb-select-all-2" type="checkbox"></td>
                    <th scope="col" class="manage-column">Id</th>
                    <th scope="col" class="manage-column column-primary">Nom</th>
                    <th scope="col" class="manage-column">ShortCode</th>
                    <th scope="col"></th>
                </tfoot>
            </table>
        </form>
    </div>
</div>