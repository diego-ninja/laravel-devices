<script>
    window.DeviceTracker = {
        config: @json([
            'current' => $current,
            'transport' => $transport,
            'library' => $library,
        ])
    };
</script>
<script>
    if (window.DeviceTracker.config.current === null) {
        const scriptPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = window.DeviceTracker.config.library.url;
            script.onload = () => resolve(window.ThumbmarkJS);
            script.onerror = reject;
            document.head.appendChild(script);
        });

        scriptPromise
            .then(ThumbmarkJS => {
                const tm = new ThumbmarkJS.Thumbmark();
                return tm.get();
            })
            .then(result => {
                const transport = window.DeviceTracker.config.transport;

                window.DeviceTracker.config.current = result.thumbmark;
                document.cookie = `${transport.key}=${result.thumbmark}; expires=Fri, 31 Dec 9999 23:59:59 GMT; domain=${location.hostname}; SameSite=Lax;`
            })
            .catch(error => console.error(error));
    } else {
        const transport = window.DeviceTracker.config.transport;
        document.cookie = `${transport.key}=${window.DeviceTracker.config.current}; expires=Fri, 31 Dec 9999 23:59:59 GMT; domain=${location.hostname}; SameSite=Lax;`
    }
</script>