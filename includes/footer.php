    </main>
    <!-- End Main Content -->

    <!-- Footer (Frontend) -->
    <?php
        $basePath = dirname($_SERVER['PHP_SELF']);
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }

        $pick = function (array $candidates, string $fallback) use ($basePath): string {
            $rootDir = dirname(__DIR__); // project root
            foreach ($candidates as $relative) {
                $rel = ltrim((string) $relative, '/');
                $fs = $rootDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
                if (is_file($fs)) {
                    $v = (int) @filemtime($fs);
                    $src = $basePath . '/' . $rel;
                    return $src . ($v > 0 ? ('?v=' . $v) : '');
                }
            }
            $relFb = ltrim((string) $fallback, '/');
            $fsFb = $rootDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relFb);
            $vFb = (int) @filemtime($fsFb);
            $srcFb = $basePath . '/' . $relFb;
            return $srcFb . ($vFb > 0 ? ('?v=' . $vFb) : '');
        };

        $logoSrc = $pick([
            'assets/img/logo-sekolah.png',
            'assets/img/logo-sekolah.jpg',
            'assets/img/logo-sekolah.jpeg',
            'assets/img/logo-sekolah.webp',
            'assets/img/logo-sekolah.svg',
            'assets/img/logo.png',
        ], 'assets/img/logo-sekolah.svg');
    ?>
    <footer class="kaih-footer">
        <div class="footer-container">
            <div class="footer-brand">
                <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Logo KAIH" class="footer-logo">
                <div class="footer-info">
                    <h3 class="footer-name">KAIH</h3>
                    <p class="footer-school">SMP Negeri 28 Balikpapan</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/assets/js/header.js"></script>
</body>
</html>
