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
    const transport = window.DeviceTracker.config.transport

    if (window.DeviceTracker.config.current === null) {
        const fpPromise = import(window.DeviceTracker.config.library.url)
            .then(FingerprintJS => FingerprintJS.load())

        fpPromise
            .then(fp => fp.get())
            .then(result => {
                window.DeviceTracker.config.current = result.visitorId
                document.cookie = `${transport.key}=${result.visitorId}; expires=Fri, 31 Dec 9999 23:59:59 GMT; domain=${location.hostname}; SameSite=Lax;`
            })
            .catch(error => console.error(error))
    } else {
        document.cookie = `${transport.key}=${window.DeviceTracker.config.current}; expires=Fri, 31 Dec 9999 23:59:59 GMT; domain=${location.hostname}; SameSite=Lax;`
    }
</script>
