<?php
require base_path('views/partials/header.php');
require base_path('views/partials/nav.php');
require base_path('views/partials/banner.php');
?>

<main>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        <p>
            <?= htmlspecialchars($note['body']) ?>
        </p>
        <br>
        <a href='/notes' class="text-blue-500 hover:underline">
            go back
        </a>

        <a href="/note/edit?id=<?= $note['id'] ?>" class="inline ml-6" method="POST">
            <button class="text-sm text-indigo-700 hover:underline">Edit</button>
        </a>

        
        <form class="inline ml-6" method="POST">
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="id" value="<?= $note['id'] ?>">
            <button class="text-sm text-red-500 hover:underline">Delete</button>
        </form>

    </div>
</main>

<?php
require base_path('views/partials/footer.php');
?>