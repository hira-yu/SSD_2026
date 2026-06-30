<?php

declare(strict_types=1);
?>
</main>
<footer class="site-footer">
    <div class="container">
        <?php if (current_path() === '/login' || str_starts_with(current_path(), '/staff')): ?>
            <p>IPUT EC 管理画面 | 業務デモ用のため、本番決済や実在個人情報の利用は想定していません。</p>
        <?php else: ?>
            <p>IPUT EC | 学内デモ向けECサイトです。実在する個人情報や本物のカード情報は入力しないでください。</p>
        <?php endif; ?>
    </div>
</footer>
<script src="/assets/js/app.js"></script>
</body>
</html>
