    <?php
    $inPages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
    $jsPath = $inPages ? '../assets/js/main.js' : 'assets/js/main.js';
    ?>
    <script src="<?php echo $jsPath; ?>"></script>
</body>
</html>
