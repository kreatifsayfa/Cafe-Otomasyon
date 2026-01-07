<script>
    // Base URL PHP'den JavaScript'e aktar
    const BASE_URL = '<?php echo BASE_URL; ?>';
    window.BASE_URL = BASE_URL;
</script>
<script src="<?php echo BASE_URL; ?>assets/js/base-url.js"></script>
<script>
    // BASE_URL zaten tanımlı, sadece window'a da ekle
    if (typeof BASE_URL !== 'undefined') {
        window.BASE_URL = BASE_URL;
    }
</script>

