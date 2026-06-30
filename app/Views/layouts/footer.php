<?php

declare(strict_types=1);
?>
</main>
<footer class="site-footer">
    <div class="container">
        <?php if (current_path() === '/login' || str_starts_with(current_path(), '/staff')): ?>
            <p>IPUT EC 管理画面 | 学習目的の試作システムです。</p>
        <?php else: ?>
            <p>IPUT EC | このサイトは学習目的の試作システムです。</p>
        <?php endif; ?>
    </div>
</footer>
<script src="/assets/js/app.js"></script>
<script src="/assets/js/address-autofill.js"></script>
</body>
</html>
