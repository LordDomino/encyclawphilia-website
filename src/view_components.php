<?php
// helpers/view_components.php

/**
 * Renders a standardized anchor redirect link for a single legislation entity.
 *
 * @param int $ordinanceId Explicit primary key identifier.
 * @return void Emits HTML output directly to the buffer.
 */
function renderOrdinanceRedirectLink(int $ordinanceId): void
{
    ?>
    <a href="/ordinance?id=<?= $ordinanceId ?>">
        <p class="link">Read More</p>
    </a>
    <?php
}