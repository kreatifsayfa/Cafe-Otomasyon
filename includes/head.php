<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#faf7f4',
                            100: '#f4ede4',
                            200: '#e8d9c8',
                            300: '#d9c0a6',
                            400: '#c8a280',
                            500: '#b8865a',
                            600: '#a9734d',
                            700: '#8b5a3c',
                            800: '#6b4423',
                            900: '#4a2e18',
                        },
                        coffee: {
                            50: '#fef9f3',
                            100: '#fdf2e7',
                            200: '#fae3c9',
                            300: '#f6cea0',
                            400: '#f0b070',
                            500: '#d4a574',
                            600: '#b8865a',
                            700: '#8b5a3c',
                            800: '#6b4423',
                            900: '#4a2e18',
                        }
                    }
                }
            }
        }
    </script>
    <?php include 'includes/base_url_script.php'; ?>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        
        /* Responsive Table */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table-responsive table {
            min-width: 640px;
        }
        
        /* Mobile optimizations */
        @media (max-width: 640px) {
            .table-responsive table {
                font-size: 0.875rem;
            }
            
            .table-responsive th,
            .table-responsive td {
                padding: 0.5rem;
            }
        }
    </style>
</head>


