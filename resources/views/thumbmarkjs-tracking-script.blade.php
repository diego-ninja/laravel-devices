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
                if (!result || !result.thumbmark) {
                    throw new Error('Invalid thumbmark result');
                }

                const transport = window.DeviceTracker.config.transport;

                window.DeviceTracker.config.current = result.thumbmark;
                document.cookie = `${transport.key}=${result.thumbmark}; path=/; expires=Fri, 31 Dec 9999 23:59:59 GMT; SameSite=Lax; Secure;`
            })
            .catch(error => console.error('ThumbmarkJS fingerprinting failed:', error));
    } else {
        const transport = window.DeviceTracker.config.transport;
        document.cookie = `${transport.key}=${window.DeviceTracker.config.current}; path=/; expires=Fri, 31 Dec 9999 23:59:59 GMT; SameSite=Lax; Secure;`
    }
</script>