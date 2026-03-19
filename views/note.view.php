<?php
require('partials/header.php');
require('partials/nav.php');
require('partials/banner.php');
?>

<main>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        <p>
            <?= $note['body'] ?>
        </p>
        <br>
        <a href='/notes' class="text-blue-500 hover:underline">
            go back
        </a>

    </div>
</main>

<?php
require('partials/footer.php');
?>