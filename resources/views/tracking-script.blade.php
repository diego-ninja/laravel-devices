<script>
    window.DeviceTracker = {
        config: @json([
            'tracking_id' => $tracking->id,
            'reading' => $tracking->reading(),
            'routes' => $routes
        ])
    };
</script>
<script>
    (() => {
        const { config } = window.DeviceTracker;
        const delay = ms => new Promise(resolve => setTimeout(resolve, ms));

        const processFavicon = async (route) => {
            if (!route.should_track) return;

            const link = document.createElement('link');
            link.rel = 'icon';
            link.type = 'image/png';
            link.href = `${route.route}?t=${Date.now()}`;

            document.head.appendChild(link);
            await delay(100);
            document.head.removeChild(link);
        };

        const processRoutes = async () => {
            for (const route of config.routes) {
                try {
                    await processFavicon(route);
                    await delay(50);
                } catch (e) {
                    console.error('Error processing favicon:', e);
                }
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', processRoutes);
        } else {
            processRoutes();
        }
    })();
</script>