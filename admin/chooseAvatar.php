<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="cssStyles/chooseAvatar.css">
    <link href="https:
    <title>Choose Avatar</title>
</head>
<body>
    <?php include 'nav.php'; ?> <!-- Include the navigation bar -->

    <div class=" main-content">
    <div class="avatar-container">
        <h1>Choose Your Avatar</h1>
        <p>Select an avatar that best represents you.</p>
        <div class="avatar-grid">
            <?php
            $avatars = [
                'fa-user-circle',
                'fa-user-ninja',
                'fa-user-astronaut',
                'fa-user-tie',
                'fa-user-secret',
                'fa-user-graduate',
                'fa-user-md',
                'fa-user-alt',
                'fa-user-gear',
                'fa-user-clock',
                'fa-user-shield',
                'fa-user-injured'
            ];
            foreach ($avatars as $avatar) {
                echo "<div class='avatar-item' data-avatar='$avatar'>
                            <i class='fas $avatar'></i>
                          </div>";
            }
            ?>
        </div>
        <button id="saveAvatar" class="save-btn">Save Avatar</button>
    </div>
    </div>

    <script>
        let selectedAvatar = null;


        document.querySelectorAll('.avatar-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.avatar-item').forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
                selectedAvatar = item.getAttribute('data-avatar');
            });
        });


        document.getElementById('saveAvatar').addEventListener('click', () => {
            if (selectedAvatar) {
                localStorage.setItem('selectedAvatar', selectedAvatar);
                alert('Avatar saved successfully!');
                window.location.href = 'systemSettings.php';
            } else {
                alert('Please select an avatar.');
            }
        });
    </script>
    <script>
        const darkModeButton = document.getElementById('darkModeButton');


        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            darkModeButton.textContent = 'Disable';
        }


        darkModeButton.addEventListener('click', () => {
            if (document.body.classList.contains('dark-mode')) {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
                darkModeButton.textContent = 'Enable';
            } else {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
                darkModeButton.textContent = 'Disable';
            }
        });
    </script>
    </body>

</html>